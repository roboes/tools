## Urlaubsverwaltung → Zeiterfassung Sync Absences
# Last update: 2026-08-16

"""
Polls Urlaubsverwaltung's REST API for absences and upserts them directly into Zeiterfassung's Postgres `absence` table - same idempotency key (source_id + type_category), same table, same row shape that Zeiterfassung's own RabbitMQ-based sync consumer (AbsenceWriteServiceImpl) would produce.

Requires: pip install requests psycopg2-binary
"""

import json
import logging
import os
import re
import sys
from datetime import date, datetime, timedelta

import psycopg2
import requests
from psycopg2.extras import execute_values

logging.basicConfig(level=logging.INFO, format='%(asctime)s %(levelname)s %(message)s')
log = logging.getLogger('uv-zf-sync')

# Settings

# Urlaubsverwaltung REST API (source)
UV_BASE_URL = os.environ['UV_BASE_URL'].rstrip('/')
KEYCLOAK_TOKEN_URL = os.environ['KEYCLOAK_TOKEN_URL']
UV_OIDC_CLIENT_ID = os.environ['UV_OIDC_CLIENT_ID']
UV_OIDC_CLIENT_SECRET = os.environ['UV_OIDC_CLIENT_SECRET']
SYNC_BOT_USERNAME = os.environ['SYNC_BOT_USERNAME']
SYNC_BOT_PASSWORD = os.environ['SYNC_BOT_PASSWORD']

# Zeiterfassung database (destination)
ZF_DB_HOST = os.environ.get('ZF_DB_HOST', 'localhost')
ZF_DB_PORT = int(os.environ.get('ZF_DB_PORT', '5432'))
ZF_DB_NAME = os.environ['ZF_DB_NAME']
ZF_DB_USER = os.environ['ZF_DB_USER']
ZF_DB_PASSWORD = os.environ['ZF_DB_PASSWORD']
ZF_TENANT_ID = os.environ.get('ZF_TENANT_ID', 'default')

# Sync window
SYNC_WINDOW_PAST_DAYS = int(os.environ.get('SYNC_WINDOW_PAST_DAYS', '45'))
SYNC_WINDOW_FUTURE_DAYS = int(os.environ.get('SYNC_WINDOW_FUTURE_DAYS', '120'))

# Urlaubsverwaltung's absences endpoint returns pending requests too (status=WAITING for vacation, also WAITING for sick notes in practice - despite what UV's SickNoteStatus enum name might suggest, WAITING is the only pending status actually observed here), so this filter is what restricts the sync to approved/final records, not a redundant check. Rejected/cancelled requests don't get a terminal status at all - confirmed by testing -they simply stop appearing in the response once declined
# TEMPORARY_ALLOWED (one of two required approvals given, two-stage approval) intentionally excluded - same as WAITING, it isn't a final decision yet
UV_SYNCABLE_VACATION_STATUSES = {'ALLOWED', 'ALLOWED_CANCELLATION_REQUESTED'}

UV_SYNCABLE_SICK_STATUSES = {'ACTIVE'}

# Urlaubsverwaltung DayLength name → Zeiterfassung DayLength name (see absence/DayLength.java). Identical today; kept as an explicit map so a future rename on either side fails loudly here instead of silently mismatching day lengths
UV_DAYLENGTH_TO_ZF = {
    'FULL': 'FULL',
    'MORNING': 'MORNING',
    'NOON': 'NOON',
}

# Urlaubsverwaltung absence category → Zeiterfassung absence_type.category
UV_CATEGORY_TO_ZF_CATEGORY = {
    'HOLIDAY': 'HOLIDAY',
    'SPECIALLEAVE': 'SPECIALLEAVE',
    'UNPAIDLEAVE': 'UNPAIDLEAVE',
    'OVERTIME': 'OVERTIME',
    'SICK_NOTE': 'SICK',
    'OTHER': 'OTHER',
}

# Real Urlaubsverwaltung type ids (vacation_type.id / sick_note_type.id), used directly as absence.type_source_id. Zeiterfassung's AbsenceServiceImpl.toAbsence() silently drops any absence row whose type_source_id has no matching absence_type row, so every id referenced above needs an entry here. Color/label transcribed from Urlaubsverwaltung's own vacation_type / sick_note_type tables; add a new row here whenever a new type is activated in Urlaubsverwaltung (an unmapped one only produces the "Unmapped absence" warning below and is skipped, not synced)
# absence.overtime_hours is a misleading column name - it stores seconds (Duration.ofSeconds in Zeiterfassung's AbsenceWriteEntity), confirmed against source
ZF_ABSENCE_TYPE_UPSERTS = [
    # (type_source_id, category, color, {locale: label})
    (10000, 'HOLIDAY', 'YELLOW', {'de': 'Urlaub', 'en': 'Vacation'}),
    (10001, 'SPECIALLEAVE', 'YELLOW', {'de': 'Sonderurlaub', 'en': 'Special leave'}),
    (10002, 'UNPAIDLEAVE', 'YELLOW', {'de': 'Unbezahlter Urlaub', 'en': 'Unpaid leave'}),
    (10003, 'OVERTIME', 'YELLOW', {'de': 'Überstundenabbau', 'en': 'Overtime reduction'}),
    (2002, 'SICK', 'RED', {'de': 'Krank', 'en': 'Sick'}),
    (2003, 'SICK', 'RED', {'de': 'Kind krank', 'en': 'Child sick'}),
]

_ISO_DURATION_RE = re.compile(r'^PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?$')


def parse_iso_duration_hours(value: str) -> float:
    m = _ISO_DURATION_RE.match(value)
    if not m:
        raise ValueError(f'Unexpected duration format: {value!r}')
    h, mi, s = (int(g) if g else 0 for g in m.groups())
    return h + mi / 60 + s / 3600


def get_uv_token() -> str:
    resp = requests.post(
        KEYCLOAK_TOKEN_URL,
        data={
            'grant_type': 'password',
            'client_id': UV_OIDC_CLIENT_ID,
            'client_secret': UV_OIDC_CLIENT_SECRET,
            'username': SYNC_BOT_USERNAME,
            'password': SYNC_BOT_PASSWORD,
            'scope': 'openid profile email',
        },
        timeout=15,
    )
    resp.raise_for_status()
    return resp.json()['access_token']


def get_uv_persons(token: str) -> list[dict]:
    resp = requests.get(
        f'{UV_BASE_URL}/api/persons',
        params={'active': 'true'},
        headers={'Authorization': f'Bearer {token}'},
        timeout=30,
    )
    resp.raise_for_status()
    return resp.json()['persons']


def get_uv_absences(token: str, person_id: int, start: date, end: date) -> list[dict]:
    resp = requests.get(
        f'{UV_BASE_URL}/api/persons/{person_id}/absences',
        params={
            'from': start.isoformat(),
            'to': end.isoformat(),
            'absence-types': 'vacation, sick_note',
        },
        headers={'Authorization': f'Bearer {token}'},
        timeout=30,
    )
    resp.raise_for_status()
    absences = resp.json()['absences']

    def is_syncable(a: dict) -> bool:
        if a.get('absenceType') == 'SICK_NOTE':
            return a.get('status') in UV_SYNCABLE_SICK_STATUSES
        return a.get('status') in UV_SYNCABLE_VACATION_STATUSES

    return [a for a in absences if is_syncable(a)]


def get_uv_overtime_reduction_hours(token: str, person_id: int, absence_id: int) -> float:
    """Fetches the reduction amount for an OVERTIME-category absence from Urlaubsverwaltung's per-absence sub-resource - the plain /absences list doesn't include this, only a link to it."""
    resp = requests.get(
        f'{UV_BASE_URL}/api/persons/{person_id}/absences/{absence_id}/overtime',
        headers={'Authorization': f'Bearer {token}'},
        timeout=30,
    )
    resp.raise_for_status()
    return parse_iso_duration_hours(resp.json()['duration'])  # e.g. "PT4H" -> 4.0


def zf_connect():
    """Opens a Zeiterfassung Postgres connection with the tenant session variable its own RLS policies require."""
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


def build_email_to_zf_user_id_map(conn) -> dict[str, str]:
    """Maps email → Zeiterfassung tenant_user.uuid (the OIDC subject, used as absence.user_id).

    Matching by email only works because both apps authenticate against the same Keycloak realm.
    If a person's email changes in one app but not the other, they stop matching until realigned.
    """
    with conn.cursor() as cur:
        cur.execute(
            'SELECT lower(email), uuid FROM tenant_user WHERE tenant_id = %s AND deactivated_at IS NULL AND deleted_at IS NULL',
            (ZF_TENANT_ID,),
        )
        return {row[0]: row[1] for row in cur.fetchall()}


def possible_source_ids_for_window(person_id: int, window_start: date, window_end: date) -> set[int]:
    """Every source_id this script could ever have produced for `person_id` in the sync window, across all categories - used to find orphans (see delete_orphans below)."""
    ids = set()
    day = window_start
    while day <= window_end:
        ids.add(int(f'{person_id}{day.strftime("%Y%m%d")}'))
        day += timedelta(days=1)
    return ids


def delete_orphans(conn, zf_user_id: str, category: str, possible_ids: set[int], present_ids: set[int]) -> int:
    """Deletes absence rows this script previously wrote for (user, category) that no longer appear in Urlaubsverwaltung's response (the leave request was cancelled or shortened since the last run).

    Restricted to `possible_ids` so it can never touch rows from a different source - the RabbitMQ consumer's own sourceId is Urlaubsverwaltung's real application id, not person_id+date, so the two id schemes can't collide.
    """
    orphan_ids = possible_ids - present_ids
    if not orphan_ids:
        return 0

    with conn.cursor() as cur:
        cur.execute(
            """
            DELETE FROM absence
            WHERE tenant_id = %s AND user_id = %s AND type_category = %s
              AND source_id = ANY(%s)
            """,
            (ZF_TENANT_ID, zf_user_id, category, list(orphan_ids)),
        )
        deleted = cur.rowcount
    conn.commit()
    return deleted


def ensure_absence_types(conn) -> None:
    """Upserts the absence_type rows the synced categories depend on - Zeiterfassung drops any absence whose type_source_id isn't registered here, so this must run before upsert_absences."""
    with conn.cursor() as cur:
        for source_id, category, color, labels in ZF_ABSENCE_TYPE_UPSERTS:
            cur.execute(
                """
                INSERT INTO absence_type (tenant_id, id, category, source_id, color, label_by_locale)
                VALUES (%s, nextval('absence_type_seq'), %s, %s, %s, %s)
                ON CONFLICT (tenant_id, source_id, category)
                DO UPDATE SET color = EXCLUDED.color, label_by_locale = EXCLUDED.label_by_locale
                """,
                (ZF_TENANT_ID, category, source_id, color, json.dumps(labels)),
            )
    conn.commit()


def upsert_absences(conn, rows: list[tuple]) -> int:
    """rows: (source_id, user_id, start_date, end_date, day_length, type_category, type_source_id, overtime_seconds)

    Relies on the UC_ABSENCE unique constraint (tenant_id, source_id, type_category) that Zeiterfassung's own migrations create - see changelog-1.6.2-add-absence-source-id.xml and changelog-1.6.5-add-absence-type-source-id.xml.
    """
    if not rows:
        return 0

    sql = """
        INSERT INTO absence (
            id, tenant_id, source_id, user_id, start_date, end_date,
            day_length, type_category, type_source_id, overtime_hours
        )
        VALUES %s
        ON CONFLICT (tenant_id, source_id, type_category)
        DO UPDATE SET
            user_id = EXCLUDED.user_id,
            start_date = EXCLUDED.start_date,
            end_date = EXCLUDED.end_date,
            day_length = EXCLUDED.day_length,
            type_source_id = EXCLUDED.type_source_id,
            overtime_hours = EXCLUDED.overtime_hours
    """
    template = "(nextval('absence_seq'), %s, %s, %s, %s, %s, %s, %s, %s, %s)"
    values = [(ZF_TENANT_ID,) + row for row in rows]

    with conn.cursor() as cur:
        execute_values(cur, sql, values, template=template)
        affected = cur.rowcount
    conn.commit()
    return affected


def main() -> int:
    today = datetime.now().astimezone().date()
    window_start = today - timedelta(days=SYNC_WINDOW_PAST_DAYS)
    window_end = today + timedelta(days=SYNC_WINDOW_FUTURE_DAYS)

    log.info('Fetching Urlaubsverwaltung token for sync-bot...')
    token = get_uv_token()

    log.info('Fetching active persons from Urlaubsverwaltung...')
    persons = get_uv_persons(token)
    log.info('Found %d active person(s) in Urlaubsverwaltung', len(persons))

    conn = zf_connect()
    ensure_absence_types(conn)

    try:
        email_to_user_id = build_email_to_zf_user_id_map(conn)
        log.info('Loaded %d Zeiterfassung user(s) for email matching', len(email_to_user_id))

        skipped_no_match = set()
        matched_persons = []
        for person in persons:
            email = (person.get('email') or '').strip().lower()
            zf_user_id = email_to_user_id.get(email)
            if zf_user_id:
                matched_persons.append((person, zf_user_id))
            elif email:
                skipped_no_match.add(email)
        if skipped_no_match:
            log.warning(
                'Skipped %d Urlaubsverwaltung person(s) with no matching Zeiterfassung user by email: %s',
                len(skipped_no_match),
                ', '.join(sorted(skipped_no_match)),
            )

        rows = []
        # present_ids_by_person_category: (person_id, category) → source_ids seen in THIS run's Urlaubsverwaltung response, needed below to detect orphans (cancelled/shortened leave)
        present_ids_by_person_category: dict[tuple[int, str], set[int]] = {}

        for person, zf_user_id in matched_persons:
            absences = get_uv_absences(token, person['id'], window_start, window_end)
            for absence in absences:
                category = UV_CATEGORY_TO_ZF_CATEGORY.get(absence['category'])
                day_length = UV_DAYLENGTH_TO_ZF.get(absence['absent'])
                if not category or not day_length:
                    if absence['absenceType'] not in ('PUBLIC_HOLIDAY', 'NO_WORKDAY'):
                        log.warning(
                            'Unmapped absence for person %s on %s: absenceType=%s absent=%s',
                            person['id'],
                            absence['date'],
                            absence['absenceType'],
                            absence['absent'],
                        )
                    continue

                absence_day = date.fromisoformat(absence['date'])
                # No shared numeric id exists between the two systems for a single absent day, so person id + calendar day is used as a stable, deterministic source id instead
                source_id = int(f'{person["id"]}{absence_day.strftime("%Y%m%d")}')

                overtime_seconds = None
                if category == 'OVERTIME':
                    overtime_hours = get_uv_overtime_reduction_hours(token, person['id'], absence['id'])
                    overtime_seconds = round(overtime_hours * 3600)

                present_ids_by_person_category.setdefault((person['id'], category), set()).add(source_id)

                rows.append(
                    (
                        source_id,
                        zf_user_id,
                        absence_day,
                        absence_day,
                        day_length,
                        category,
                        absence['typeId'],
                        overtime_seconds,
                    )
                )

        affected = upsert_absences(conn, rows)
        log.info('Upserted %d absence day(s) into Zeiterfassung', affected)

        # Clean up rows previously written for days that no longer show up in Urlaubsverwaltung's response (cancelled or shortened leave). Restricted to matched_persons/synced categories so it never touches absence rows this script didn't itself write
        total_deleted = 0
        for person, zf_user_id in matched_persons:
            possible_ids = possible_source_ids_for_window(person['id'], window_start, window_end)
            for category in UV_CATEGORY_TO_ZF_CATEGORY.values():
                present_ids = present_ids_by_person_category.get((person['id'], category), set())
                total_deleted += delete_orphans(conn, zf_user_id, category, possible_ids, present_ids)
        if total_deleted:
            log.info('Deleted %d orphaned absence day(s) no longer present in Urlaubsverwaltung', total_deleted)

    finally:
        conn.close()

    return 0


if __name__ == '__main__':
    sys.exit(main())
