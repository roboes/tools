"""Synchronize employee active status from Urlaubsverwaltung to Zeiterfassung.

Reads active and inactive people from Urlaubsverwaltung's API and updates the matching Zeiterfassung tenant_user rows. Existing time-tracking data is kept.

Requires: pip install requests psycopg2-binary
The sync bot must have the BOSS or OFFICE permission in Urlaubsverwaltung.
"""

import logging
import os
import sys

import psycopg2
import requests

logging.basicConfig(level=logging.INFO, format='%(asctime)s %(levelname)s %(message)s')
log = logging.getLogger('uv-zf-user-status-sync')

UV_BASE_URL = os.environ['UV_BASE_URL'].rstrip('/')
KEYCLOAK_TOKEN_URL = os.environ['KEYCLOAK_TOKEN_URL']
UV_OIDC_CLIENT_ID = os.environ['UV_OIDC_CLIENT_ID']
UV_OIDC_CLIENT_SECRET = os.environ['UV_OIDC_CLIENT_SECRET']
SYNC_BOT_USERNAME = os.environ['SYNC_BOT_USERNAME']
SYNC_BOT_PASSWORD = os.environ['SYNC_BOT_PASSWORD']

ZF_DB_HOST = os.environ.get('ZF_DB_HOST', 'localhost')
ZF_DB_PORT = int(os.environ.get('ZF_DB_PORT', '5433'))
ZF_DB_NAME = os.environ['ZF_DB_NAME']
ZF_DB_USER = os.environ['ZF_DB_USER']
ZF_DB_PASSWORD = os.environ['ZF_DB_PASSWORD']
ZF_TENANT_ID = os.environ.get('ZF_TENANT_ID', 'default')


def get_uv_token() -> str:
    response = requests.post(
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
    response.raise_for_status()
    return response.json()['access_token']


def get_uv_people(token: str, active: bool) -> list[dict]:
    response = requests.get(
        f'{UV_BASE_URL}/api/persons',
        params={'active': str(active).lower()},
        headers={'Authorization': f'Bearer {token}'},
        timeout=30,
    )
    response.raise_for_status()
    return response.json()['persons']


def load_uv_people_by_email(token: str) -> tuple[dict[str, bool], set[str]]:
    people_by_email: dict[str, bool] = {}
    ambiguous_emails: set[str] = set()

    for active in (True, False):
        people = get_uv_people(token, active)
        log.info('Found %d %s person(s) in Urlaubsverwaltung', len(people), 'active' if active else 'inactive')

        for person in people:
            email = (person.get('email') or '').strip().lower()
            if not email:
                log.warning('Skipping Urlaubsverwaltung person id=%s with no email', person.get('id'))
                continue

            if email in people_by_email:
                ambiguous_emails.add(email)
                continue

            people_by_email[email] = active

    for email in ambiguous_emails:
        people_by_email.pop(email, None)
        log.warning('Skipping ambiguous Urlaubsverwaltung email %s', email)

    return people_by_email, ambiguous_emails


def zf_connect():
    connection = psycopg2.connect(
        host=ZF_DB_HOST,
        port=ZF_DB_PORT,
        dbname=ZF_DB_NAME,
        user=ZF_DB_USER,
        password=ZF_DB_PASSWORD,
    )
    with connection.cursor() as cursor:
        cursor.execute('SET app.tenant_id TO %s', (ZF_TENANT_ID,))
    return connection


def load_zf_users_by_email(connection) -> tuple[dict[str, tuple[int, str]], set[str]]:
    with connection.cursor() as cursor:
        cursor.execute(
            """
            SELECT id, lower(trim(email)), status
            FROM tenant_user
            WHERE tenant_id = %s
              AND deleted_at IS NULL
              AND status <> 'DELETED'
            """,
            (ZF_TENANT_ID,),
        )
        rows = cursor.fetchall()

    users_by_email: dict[str, tuple[int, str]] = {}
    ambiguous_emails: set[str] = set()
    for user_id, email, status in rows:
        if not email:
            continue
        if email in users_by_email:
            ambiguous_emails.add(email)
            continue
        users_by_email[email] = (user_id, status)

    for email in ambiguous_emails:
        users_by_email.pop(email, None)
        log.warning('Skipping ambiguous Zeiterfassung email %s', email)

    return users_by_email, ambiguous_emails


def synchronize_statuses(connection, uv_people: dict[str, bool], zf_users: dict[str, tuple[int, str]]) -> tuple[int, int]:
    updated = unchanged = unmatched = unmatched_inactive = 0

    with connection.cursor() as cursor:
        for email, active in uv_people.items():
            zf_user = zf_users.get(email)
            if zf_user is None:
                unmatched += 1
                if active:
                    log.warning('No Zeiterfassung user matches active Urlaubsverwaltung email %s', email)
                else:
                    unmatched_inactive += 1
                    log.error('Inactive Urlaubsverwaltung user %s has no Zeiterfassung row; this sync cannot block their first login', email)
                continue

            user_id, current_status = zf_user
            if active:
                if current_status in {'ACTIVE', 'UNKNOWN'}:
                    unchanged += 1
                    continue
                next_status = 'ACTIVE'
                sql = 'UPDATE tenant_user SET status = %s, updated_at = NOW() WHERE id = %s AND tenant_id = %s'
            else:
                next_status = 'DEACTIVATED'
                if current_status == next_status:
                    unchanged += 1
                    continue
                sql = """
                    UPDATE tenant_user
                    SET status = %s, deactivated_at = COALESCE(deactivated_at, NOW()), updated_at = NOW()
                    WHERE id = %s AND tenant_id = %s
                """

            cursor.execute(sql, (next_status, user_id, ZF_TENANT_ID))
            log.info('Set %s (%s) from %s to %s', email, user_id, current_status, next_status)
            updated += 1

    return updated, unmatched, unmatched_inactive


def main() -> int:
    log.info('Starting user-status sync')
    token = get_uv_token()
    uv_people, _ = load_uv_people_by_email(token)

    connection = zf_connect()
    try:
        zf_users, _ = load_zf_users_by_email(connection)
        log.info('Loaded %d non-deleted Zeiterfassung user(s)', len(zf_users))
        updated, unmatched, unmatched_inactive = synchronize_statuses(connection, uv_people, zf_users)

        connection.commit()

        log.info('User-status sync complete: %d changed, %d unchanged, %d unmatched', updated, len(uv_people) - updated - unmatched, unmatched)
    except Exception:
        connection.rollback()
        raise
    finally:
        connection.close()

    return 1 if unmatched_inactive else 0


if __name__ == '__main__':
    sys.exit(main())
