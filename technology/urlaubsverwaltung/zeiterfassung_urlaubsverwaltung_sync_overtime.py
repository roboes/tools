## Zeiterfassung → Urlaubsverwaltung Sync Overtime
# Last update: 2026-08-16

"""
Reads worked hours per day from Zeiterfassung's Postgres `time_entry` table and upserts them as "external" overtime records into Urlaubsverwaltung's Postgres `overtime` table - the same shape UV's own (SaaS-only) overtime-sync feature would produce, using the idempotency mechanism UV's OSS code already ships for exactly this purpose (OvertimeRepository.findByPersonIdAndStartDateAndEndDateAndExternalIsTrue).

IMPORTANT - what this does NOT do: Zeiterfassung's own "overtime" figure is (worked hours - should- work hours), where should-work hours depends on each person's WorkingTime schedule, public holidays, and absences. That full calculation lives entirely in Java (WorkingTimeCalendarService + Jollyday) and has no SQL view or API to read it from. Re-deriving all of that here would drift from the app's real logic over time. This script takes a middle ground: it reads each person's actual per-weekday contracted hours straight from Zeiterfassung's own `working_time` table (see build_working_time_schedules / standard_seconds_for below), so mixed-contract teams (e.g. 38h vs 40h/week, part-time) get a correct per-person comparison instead of one flat number for everyone - but it still does NOT account for public holidays on a given day (approved full-day absences are handled naturally, see get_worked_seconds_per_user_per_day). A day that's a public holiday will still be compared against that person's normal scheduled hours, typically showing as negative overtime for the day unless such dates are excluded externally.

Requires: pip install psycopg2-binary
"""

import logging
import os
import re
import sys
from datetime import date, datetime, timedelta
from zoneinfo import ZoneInfo

import psycopg2

logging.basicConfig(level=logging.INFO, format='%(asctime)s %(levelname)s %(message)s')
log = logging.getLogger('zf-uv-overtime-sync')

# Settings

# Zeiterfassung database (source)
ZF_DB_HOST = os.environ.get('ZF_DB_HOST', 'localhost')
ZF_DB_PORT = int(os.environ.get('ZF_DB_PORT', '5433'))
ZF_DB_NAME = os.environ['ZF_DB_NAME']
ZF_DB_USER = os.environ['ZF_DB_USER']
ZF_DB_PASSWORD = os.environ['ZF_DB_PASSWORD']
ZF_TENANT_ID = os.environ.get('ZF_TENANT_ID', 'default')
# Fixed to Europe/Berlin, matching UserSettingsProviderImpl.zoneId() in the Java app - not user-configurable there either, see that class's comment for why
ZF_TIMEZONE = ZoneInfo(os.environ.get('ZF_TIMEZONE', 'Europe/Berlin'))

# Urlaubsverwaltung database (destination)
UV_DB_HOST = os.environ.get('UV_DB_HOST', 'localhost')
UV_DB_PORT = int(os.environ.get('UV_DB_PORT', '5434'))
UV_DB_NAME = os.environ['UV_DB_NAME']
UV_DB_USER = os.environ['UV_DB_USER']
UV_DB_PASSWORD = os.environ['UV_DB_PASSWORD']
UV_TENANT_ID = os.environ.get('UV_TENANT_ID', 'default')

# Sync window: never sync "today" or later - only fully-finished past days - and stay clear of Zeiterfassung's own time-entry lock window (default 2 days, configurable in its admin settings) so a day a user might still be actively editing is never read
SYNC_WINDOW_PAST_DAYS = int(os.environ.get('SYNC_WINDOW_PAST_DAYS', '45'))
SYNC_MIN_DAYS_AGO = int(os.environ.get('SYNC_MIN_DAYS_AGO', '2'))


_ISO_DURATION_RE = re.compile(r'^PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?$')


def parse_iso_duration_seconds(value: str) -> int:
    """Parses simple ISO-8601 durations as Zeiterfassung stores them (PT7H, PT0S, etc.)."""
    m = _ISO_DURATION_RE.match(value)
    if not m:
        raise ValueError(f'Unexpected working_time duration format: {value!r}')
    h, mi, s = (int(g) if g else 0 for g in m.groups())
    return h * 3600 + mi * 60 + s


WEEKDAY_COLUMNS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']


def build_working_time_schedules(zf_conn) -> dict[int, list[tuple]]:
    """Returns tenant_user.id → list of (valid_from_or_None, [seconds_mon..seconds_sun]), sorted with NULL valid_from first (earliest), then ascending by date."""
    with zf_conn.cursor() as cur:
        cur.execute(
            f"""
            SELECT user_id, valid_from, {', '.join(WEEKDAY_COLUMNS)}
            FROM working_time
            WHERE tenant_id = %s
            ORDER BY user_id, valid_from ASC NULLS FIRST
            """,
            (ZF_TENANT_ID,),
        )
        schedules: dict[int, list[tuple]] = {}
        for row in cur.fetchall():
            user_id, valid_from = row[0], row[1]
            day_seconds = [parse_iso_duration_seconds(v) for v in row[2:]]
            schedules.setdefault(user_id, []).append((valid_from, day_seconds))
    return schedules


def standard_seconds_for(schedules: dict[int, list[tuple]], user_id: int, day: date) -> int | None:
    """Finds the working_time version in effect on `day` (latest valid_from <= day, or the NULL/earliest version if no dated version applies yet), returns that weekday's seconds. Returns None if the user has no working_time row at all."""
    versions = schedules.get(user_id)
    if not versions:
        return None
    applicable = versions[0]  # NULL/earliest as fallback
    for valid_from, day_seconds in versions:
        if valid_from is not None and valid_from <= day:
            applicable = (valid_from, day_seconds)
    return applicable[1][day.weekday()]


def zf_connect():
    conn = psycopg2.connect(
        host=ZF_DB_HOST,
        port=ZF_DB_PORT,
        dbname=ZF_DB_NAME,
        user=ZF_DB_USER,
        password=ZF_DB_PASSWORD,
    )
    with conn.cursor() as cur:
        cur.execute('SET app.tenant_id TO %s', (ZF_TENANT_ID,))
    return conn


def uv_connect():
    return psycopg2.connect(
        host=UV_DB_HOST,
        port=UV_DB_PORT,
        dbname=UV_DB_NAME,
        user=UV_DB_USER,
        password=UV_DB_PASSWORD,
    )


def build_email_maps(zf_conn, uv_conn):
    """Returns (email → UV person.id, email → ZF uuid, uuid → ZF tenant_user.id), restricted to users that exist in both systems and are allowed to accrue overtime per Zeiterfassung's own overtime_account.allowed flag (defaults to true when no row exists for a user, matching the column's DB default).

    tenant_user.id (bigint) is separate from tenant_user.uuid (used as time_entry.owner) - working_time.user_id references the bigint id, so both are needed.
    """
    with zf_conn.cursor() as cur:
        cur.execute(
            """
            SELECT lower(tu.email), tu.uuid, tu.id
            FROM tenant_user tu
            LEFT JOIN overtime_account oa
                ON oa.user_id = tu.id AND oa.tenant_id = %s
            WHERE tu.tenant_id = %s
              AND tu.deactivated_at IS NULL
              AND tu.deleted_at IS NULL
              AND COALESCE(oa.allowed, true) = true
            """,
            (ZF_TENANT_ID, ZF_TENANT_ID),
        )
        rows = cur.fetchall()
        zf_email_to_uuid = {row[0]: row[1] for row in rows}
        uuid_to_zf_user_id = {row[1]: row[2] for row in rows}

    with uv_conn.cursor() as cur:
        cur.execute('SELECT lower(email), id FROM person WHERE email IS NOT NULL')
        uv_email_to_person_id = {row[0]: row[1] for row in cur.fetchall()}

    email_to_person_id = {email: uv_email_to_person_id[email] for email in zf_email_to_uuid if email in uv_email_to_person_id}
    return email_to_person_id, zf_email_to_uuid, uuid_to_zf_user_id


def build_absence_adjustments(zf_conn, window_start: date, window_end_exclusive: date) -> dict[tuple[str, date], str]:
    """Returns (owner_uuid, day) → day_length ('MORNING'/'NOON') for half-day absences in the window, so the comparison in main() can prorate the standard hours instead of comparing worked hours against a full day's schedule.

    Full-day absences need no entry here: a day with no time worked has no row in get_worked_seconds_per_user_per_day's result and is never compared against a schedule at all.
    """
    with zf_conn.cursor() as cur:
        cur.execute(
            """
            SELECT user_id, start_date, day_length
            FROM absence
            WHERE tenant_id = %s AND start_date = end_date
              AND start_date >= %s AND start_date < %s
              AND day_length IN ('MORNING', 'NOON')
            """,
            (ZF_TENANT_ID, window_start, window_end_exclusive),
        )
        return {(row[0], row[1]): row[2] for row in cur.fetchall()}


def get_worked_seconds_per_user_per_day(zf_conn, window_start: date, window_end_exclusive: date) -> dict[tuple[str, date], int]:
    """Sums (end - start) per owner (tenant_user.uuid) per calendar day, with break rows (is_break = true) subtracted rather than excluded.

    time_entry has no separate break-duration field - breaks are their own rows with is_break = true, so net worked time = sum(work row durations) - sum(break row durations) for the day.
    Groups by the local calendar date of `start` in Europe/Berlin, matching UserSettingsProviderImpl.zoneId() (a fixed value in the Java app, not user-configurable there).
    """
    sql = """
        SELECT
            owner,
            (start AT TIME ZONE 'Europe/Berlin')::date AS local_day,
            SUM(CASE WHEN is_break THEN -1 ELSE 1 END * EXTRACT(EPOCH FROM ("end" - start))) AS worked_seconds
        FROM time_entry
        WHERE tenant_id = %s
          AND start >= %s
          AND start < %s
        GROUP BY owner, local_day
    """
    # window_start/window_end_exclusive are Berlin-local calendar dates (see main()); combining them with ZF_TIMEZONE gets the correct UTC instant instead of assuming UTC midnight == Berlin midnight
    window_start_ts = datetime.combine(window_start, datetime.min.time(), tzinfo=ZF_TIMEZONE)
    window_end_ts = datetime.combine(window_end_exclusive, datetime.min.time(), tzinfo=ZF_TIMEZONE)

    with zf_conn.cursor() as cur:
        cur.execute(sql, (ZF_TENANT_ID, window_start_ts, window_end_ts))
        return {(row[0], row[1]): int(row[2]) for row in cur.fetchall() if row[2] is not None}


def delete_orphaned_overtime(uv_conn, person_id: int, window_start: date, window_end_exclusive: date, present_days: set[date]) -> int:
    """Deletes external overtime rows this script previously wrote for days that no longer have any time_entry backing them (the entries were deleted, not just corrected).

    Restricted to external=true so it can never touch manually-entered overtime, and to the sync window so a day outside the lookback isn't affected by data that was never examined.
    """
    with uv_conn.cursor() as cur:
        cur.execute(
            """
            DELETE FROM overtime
            WHERE person_id = %s AND external = true
              AND start_date >= %s AND start_date < %s
              AND start_date != ALL(%s)
            """,
            (person_id, window_start, window_end_exclusive, list(present_days) or [date.min]),
        )
        deleted = cur.rowcount
    uv_conn.commit()
    return deleted


def upsert_uv_overtime(uv_conn, person_id: int, day: date, hours_delta: float, today: date) -> str:
    """Upserts one external overtime row for (person_id, day, day), using the same idempotency query UV's own OvertimeRepository ships (findByPersonIdAndStartDateAndEndDateAndExternalIsTrue), reimplemented here as raw SQL since the Java repository can't be called directly.

    There's no unique DB constraint on (person_id, start_date, end_date, external) to upsert against, so this has to select first and branch, same as the repository method it mirrors.

    duration is stored as fractional HOURS (a Postgres double), per DurationConverter.java - not seconds and not an ISO-8601 string.
    """
    with uv_conn.cursor() as cur:
        cur.execute(
            """
            SELECT id FROM overtime
            WHERE person_id = %s AND start_date = %s AND end_date = %s AND external = true
            """,
            (person_id, day, day),
        )
        existing = cur.fetchone()

        if existing:
            cur.execute(
                'UPDATE overtime SET duration = %s, last_modification_date = %s WHERE id = %s',
                (hours_delta, today, existing[0]),
            )
            action = 'updated'
        else:
            cur.execute(
                """
                INSERT INTO overtime (id, tenant_id, person_id, start_date, end_date, duration, external, last_modification_date)
                VALUES (nextval('overtime_id_seq'), %s, %s, %s, %s, %s, true, %s)
                """,
                (UV_TENANT_ID, person_id, day, day, hours_delta, today),
            )
            action = 'inserted'

    uv_conn.commit()
    return action


def main() -> int:
    today = datetime.now(ZF_TIMEZONE).date()
    window_end_exclusive = today - timedelta(days=SYNC_MIN_DAYS_AGO)
    window_start = today - timedelta(days=SYNC_WINDOW_PAST_DAYS)
    if window_start >= window_end_exclusive:
        log.info('Nothing to sync (window is empty).')
        return 0

    zf_conn = zf_connect()
    uv_conn = uv_connect()
    try:
        email_to_person_id, zf_email_to_uuid, uuid_to_zf_user_id = build_email_maps(zf_conn, uv_conn)
        log.info('Matched %d user(s) present in both systems by email', len(email_to_person_id))
        uuid_to_email = {uuid: email for email, uuid in zf_email_to_uuid.items()}

        schedules = build_working_time_schedules(zf_conn)
        half_day_absences = build_absence_adjustments(zf_conn, window_start, window_end_exclusive)
        worked_seconds = get_worked_seconds_per_user_per_day(zf_conn, window_start, window_end_exclusive)
        log.info('Found worked time for %d (user, day) combination(s) in the sync window', len(worked_seconds))

        inserted = updated = skipped_no_match = skipped_no_schedule = skipped_zero_delta = 0
        present_days_by_person: dict[int, set[date]] = {}

        for (owner_uuid, day), seconds in worked_seconds.items():
            email = uuid_to_email.get(owner_uuid)
            person_id = email_to_person_id.get(email) if email else None
            if not person_id:
                skipped_no_match += 1
                continue

            present_days_by_person.setdefault(person_id, set()).add(day)

            zf_user_id = uuid_to_zf_user_id.get(owner_uuid)
            standard_seconds = standard_seconds_for(schedules, zf_user_id, day)
            if standard_seconds is None:
                log.warning('No working_time schedule for %s, skipping day %s', email, day)
                skipped_no_schedule += 1
                continue

            if (zf_user_id, day) in half_day_absences:
                standard_seconds //= 2

            hours_delta = (seconds - standard_seconds) / 3600

            if hours_delta == 0:
                skipped_zero_delta += 1
                continue

            action = upsert_uv_overtime(uv_conn, person_id, day, hours_delta, today)
            if action == 'inserted':
                inserted += 1
            else:
                updated += 1

        total_deleted = 0
        for person_id, present_days in present_days_by_person.items():
            total_deleted += delete_orphaned_overtime(uv_conn, person_id, window_start, window_end_exclusive, present_days)
        if total_deleted:
            log.info('Deleted %d orphaned overtime row(s) no longer backed by time entries', total_deleted)

        if skipped_no_match:
            log.warning('Skipped %d (user, day) combination(s) with no matching Urlaubsverwaltung person', skipped_no_match)
        if skipped_no_schedule:
            log.warning('Skipped %d (user, day) combination(s) with no working_time schedule', skipped_no_schedule)
        if skipped_zero_delta:
            log.info('Skipped %d (user, day) combination(s) with zero overtime delta', skipped_zero_delta)

        log.info('Overtime sync done: %d inserted, %d updated', inserted, updated)
    finally:
        zf_conn.close()
        uv_conn.close()

    return 0


if __name__ == '__main__':
    sys.exit(main())
