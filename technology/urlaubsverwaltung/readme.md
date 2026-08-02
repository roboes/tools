# Urlaubsverwaltung + Zeiterfassung + Keycloak

> [!NOTE]  
> Last update: 2026-07-31

---

## Architecture overview

```
Internet
    │  HTTPS
    ▼
Cloudflare
    ├── hr.website.com/urlaubsverwaltung → Cloudflare Access policy
    │   ├── Office IP(s) → pass through
    │   └── Other IPs    → Zero Trust login screen
    │
    │  HTTPS
    ▼
NGINX on Debian 13 (Virtualmin)  - hr.website.com
    ├── /realms/  /resources/  /js/    ──► 127.0.0.1:8090  Keycloak
    ├── /urlaubsverwaltung/*           ──► 127.0.0.1:8010  Urlaubsverwaltung (leave management)
    ├── /zeiterfassung/*               ──► 127.0.0.1:8011  Zeiterfassung (clock-in/out)
    └── /                              ──► redirect → /zeiterfassung/

Docker network: keycloak_default
    ├── keycloak:8080      IdP - shared by both apps
    ├── urlaubsverwaltung  Spring Boot - back-channel → keycloak:8080
    └── zeiterfassung      Spring Boot - back-channel → keycloak:8080

Keycloak Admin UI:
    SSH tunnel → localhost:8090 → /admin/  (not exposed publicly - Cloudflare blocks origin IP access)
```

---

## Variables

Run this block first in a fresh shell. All subsequent steps reference these variables - they must stay in scope.

```.sh
# Settings
domain="website.com"
domain_root_path="/home/$domain"
subdomain="hr"
system_user="website"

urlaubsverwaltung_version="6.5.0"
zeiterfassung_version="3.2.0"
keycloak_version="26.7.0"

keycloak_http_port=8090
keycloak_db_name="${system_user}_keycloak"
keycloak_db_user="${system_user}_keycloak_user"
keycloak_db_password=$(openssl rand -base64 32 | tr -dc 'A-Za-z0-9')

zeiterfassung_http_port=8011
zeiterfassung_db_name="${system_user}_zeiterfassung"
zeiterfassung_db_user="${system_user}_zeiterfassung_user"
zeiterfassung_db_password=$(openssl rand -base64 32 | tr -dc 'A-Za-z0-9')

urlaubsverwaltung_http_port=8010
urlaubsverwaltung_db_name="${system_user}_urlaubsverwaltung"
urlaubsverwaltung_db_user="${system_user}_urlaubsverwaltung_user"
urlaubsverwaltung_db_password=$(openssl rand -base64 32 | tr -dc 'A-Za-z0-9')

oidc_secret=$(openssl rand -base64 48 | tr -dc 'A-Za-z0-9')

# Keycloak bootstrap admin (master realm only - not used to log into the apps)
keycloak_admin_user="admin"
keycloak_admin_password=$(openssl rand -base64 16 | tr -dc 'A-Za-z0-9')

# First app user - will automatically get the Office (admin) role in Urlaubsverwaltung because the Urlaubsverwaltung database is empty on first login
first_user_email="email@website.com"
first_user_firstname="Firstname"
first_user_lastname="Lastname"
first_user_password=$(openssl rand -base64 12 | tr -dc 'A-Za-z0-9')

# Mail server
mail_host="smtp.gmail.com"
mail_port=587
mail_username="email@website.com"
mail_password="yoursmtppassword"
mail_from="email@website.com"
mail_from_name="Website HR Portal"
```

```.sh
# Echo all settings for verification
echo "---"
echo "domain:                        $domain"
echo "domain_root_path:              $domain_root_path"
echo "subdomain:                     $subdomain"
echo "system_user:                   $system_user"
echo "---"
echo "urlaubsverwaltung_version:     $urlaubsverwaltung_version"
echo "zeiterfassung_version:         $zeiterfassung_version"
echo "keycloak_version:              $keycloak_version"
echo "---"
echo "keycloak_http_port:            $keycloak_http_port"
echo "keycloak_db_name:              $keycloak_db_name"
echo "keycloak_db_user:              $keycloak_db_user"
echo "keycloak_db_password:          $keycloak_db_password"
echo "keycloak_admin_user:           $keycloak_admin_user"
echo "keycloak_admin_password:       $keycloak_admin_password"
echo "---"
echo "zeiterfassung_http_port:       $zeiterfassung_http_port"
echo "zeiterfassung_db_name:         $zeiterfassung_db_name"
echo "zeiterfassung_db_user:         $zeiterfassung_db_user"
echo "zeiterfassung_db_password:     $zeiterfassung_db_password"
echo "---"
echo "urlaubsverwaltung_http_port:   $urlaubsverwaltung_http_port"
echo "urlaubsverwaltung_db_name:     $urlaubsverwaltung_db_name"
echo "urlaubsverwaltung_db_user:     $urlaubsverwaltung_db_user"
echo "urlaubsverwaltung_db_password: $urlaubsverwaltung_db_password"
echo "---"
echo "oidc_secret:                   $oidc_secret"
echo "---"
echo "first_user_email:              $first_user_email"
echo "first_user_firstname:          $first_user_firstname"
echo "first_user_lastname:           $first_user_lastname"
echo "first_user_password:           $first_user_password"
echo "---"
echo "mail_host:                     $mail_host"
echo "mail_port:                     $mail_port"
echo "mail_username:                 $mail_username"
echo "mail_password:                 $mail_password"
echo "mail_from:                     $mail_from"
echo "mail_from_name:                $mail_from_name"
echo "---"
```

---

## Subdomain & Directory

```.sh
# Create the hr.website.com subdomain in Virtualmin
virtualmin create-domain \
  --domain $subdomain.$domain \
  --parent $domain \
  --dir \
  --logrotate \
  --virtualmin-nginx \
  --virtualmin-awstats

# Create the shared app directory under the subdomain
mkdir -p $domain_root_path/domains/$subdomain.$domain/hr
chown -R $system_user:$system_user $domain_root_path/domains/$subdomain.$domain/hr

# Allow the system user to run Docker commands
usermod -aG docker $system_user
```

---

## Nginx Directives

Replace the Virtualmin-generated config at `/etc/nginx/sites-available/hr.website.com.conf`.

```nginx
server {
    # ...

    # Keycloak - public OIDC endpoints only (/realms/, /resources/, /js/)
    location ~* ^/(realms|resources|js)/ {
        proxy_pass http://127.0.0.1:8090;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $http_cf_connecting_ip;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 300s;
    }

    # Keycloak admin console - blocked from public internet
    location /admin/ {
        proxy_pass http://127.0.0.1:8090/admin/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $http_cf_connecting_ip;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 300s;
    }

    # Zeiterfassung - time tracking
    location /zeiterfassung/ {
        proxy_pass http://127.0.0.1:8011;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $http_cf_connecting_ip;
        proxy_set_header X-Forwarded-Prefix /zeiterfassung;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 300s;
        client_max_body_size 16M;
    }

    # Urlaubsverwaltung - leave management
    location /urlaubsverwaltung/ {
        proxy_pass http://127.0.0.1:8010;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $http_cf_connecting_ip;
        proxy_set_header X-Forwarded-Prefix /urlaubsverwaltung;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 300s;
        client_max_body_size 16M;
    }

    # Root redirect → Zeiterfassung
    location = / {
        return 301 /zeiterfassung/;
    }

}
```

```.sh
nginx -t && systemctl reload nginx
```

---

## Keycloak

### Realm import JSON

Keycloak imports this file on first start, creating the realm, both OIDC clients, the HR group, and all Zeiterfassung roles in one shot.

```.sh
# Create the import directory
mkdir -p "$domain_root_path/domains/$subdomain.$domain/hr/keycloak/import"

# Write the realm JSON - shell expands $variables from the Variables block above
cat <<EOF > "$domain_root_path/domains/$subdomain.$domain/hr/keycloak/import/urlaubsverwaltung-realm.json"
{
  "realm": "urlaubsverwaltung",
  "enabled": true,
  "displayName": "$mail_from_name",
  "loginTheme": "${system_user}",
  "sslRequired": "external",
  "registrationAllowed": false,
  "loginWithEmailAllowed": true,
  "duplicateEmailsAllowed": false,
  "resetPasswordAllowed": true,
  "editUsernameAllowed": false,
  "bruteForceProtected": true,
  "accessTokenLifespan": 300,
  "ssoSessionIdleTimeout": 2592000,
  "ssoSessionMaxLifespan": 2592000,
  "clients": [
    {
      "clientId": "urlaubsverwaltung",
      "name": "Urlaubsverwaltung",
      "enabled": true,
      "clientAuthenticatorType": "client-secret",
      "secret": "${oidc_secret}",
      "standardFlowEnabled": true,
      "directAccessGrantsEnabled": false,
      "redirectUris": ["https://${subdomain}.${domain}/urlaubsverwaltung/login/oauth2/code/default"],
      "webOrigins": ["https://${subdomain}.${domain}"],
      "protocol": "openid-connect",
      "publicClient": false,
      "fullScopeAllowed": true,
      "defaultClientScopes": ["web-origins","acr","profile","roles","email"],
      "optionalClientScopes": ["address","phone","offline_access","microprofile-jwt"]
    },
    {
      "clientId": "zeiterfassung",
      "name": "Zeiterfassung",
      "enabled": true,
      "clientAuthenticatorType": "client-secret",
      "secret": "${oidc_secret}",
      "standardFlowEnabled": true,
      "directAccessGrantsEnabled": false,
      "redirectUris": ["https://${subdomain}.${domain}/zeiterfassung/login/oauth2/code/default"],
      "webOrigins": ["https://${subdomain}.${domain}"],
      "protocol": "openid-connect",
      "publicClient": false,
      "fullScopeAllowed": true,
      "defaultClientScopes": ["web-origins","acr","profile","roles","email"],
      "optionalClientScopes": ["address","phone","offline_access","microprofile-jwt"]
    }
  ],
  "roles": {
    "realm": [
      {"name": "USER",                                    "description": "Urlaubsverwaltung: basic access, request own leave"},
      {"name": "DEPARTMENT_HEAD",                         "description": "Urlaubsverwaltung: approve leave for own department"},
      {"name": "SECOND_STAGE_AUTHORITY",                  "description": "Urlaubsverwaltung: second-stage approver in two-level workflows"},
      {"name": "BOSS",                                    "description": "Urlaubsverwaltung: approve leave for all employees across departments"},
      {"name": "OFFICE",                                  "description": "Urlaubsverwaltung: HR admin - manage settings, receive all notifications"},
      {"name": "INACTIVE",                                "description": "Urlaubsverwaltung: deactivated user, no access"},
      {"name": "APPLICATION_ADD",                         "description": "Urlaubsverwaltung: submit leave applications"},
      {"name": "APPLICATION_EDIT",                        "description": "Urlaubsverwaltung: edit leave applications"},
      {"name": "APPLICATION_CANCEL",                      "description": "Urlaubsverwaltung: cancel leave applications"},
      {"name": "APPLICATION_CANCELLATION_REQUESTED",      "description": "Urlaubsverwaltung: handle cancellation requests"},
      {"name": "PERSON_ADD",                              "description": "Urlaubsverwaltung: add new persons/employees"},
      {"name": "SICK_NOTE_VIEW",                          "description": "Urlaubsverwaltung: view sick notes"},
      {"name": "SICK_NOTE_ADD",                           "description": "Urlaubsverwaltung: add sick notes"},
      {"name": "SICK_NOTE_EDIT",                          "description": "Urlaubsverwaltung: edit sick notes"},
      {"name": "SICK_NOTE_CANCEL",                        "description": "Urlaubsverwaltung: cancel sick notes"},
      {"name": "SICK_NOTE_COMMENT",                       "description": "Urlaubsverwaltung: comment on sick notes"},
      {"name": "ZEITERFASSUNG_USER",                      "description": "Zeiterfassung: basic access to record own working hours"},
      {"name": "ZEITERFASSUNG_VIEW_REPORT_ALL",           "description": "Zeiterfassung: view reports for all team members"},
      {"name": "ZEITERFASSUNG_WORKING_TIME_EDIT_ALL",     "description": "Zeiterfassung: edit working time settings for all users"},
      {"name": "ZEITERFASSUNG_TIME_ENTRY_EDIT_ALL",       "description": "Zeiterfassung: create/update/delete time entries for others"},
      {"name": "ZEITERFASSUNG_OVERTIME_ACCOUNT_EDIT_ALL", "description": "Zeiterfassung: edit overtime accounts for all users"},
      {"name": "ZEITERFASSUNG_PERMISSIONS_EDIT_ALL",      "description": "Zeiterfassung: manage application permissions for all users"},
      {"name": "ZEITERFASSUNG_SETTINGS_GLOBAL",           "description": "Zeiterfassung: configure global application settings"}
    ]
  },
  "smtpServer": {
    "host": "$mail_host",
    "port": "$mail_port",
    "from": "$mail_from",
    "fromDisplayName": "$mail_from_name",
    "starttls": "true",
    "auth": "true",
    "user": "$mail_username",
    "password": "$mail_password"
  },
  "groups": [
    {
      "name": "${system_user}_hr",
      "realmRoles": [
        "USER",
        "DEPARTMENT_HEAD",
        "SECOND_STAGE_AUTHORITY",
        "BOSS",
        "OFFICE",
        "APPLICATION_ADD",
        "APPLICATION_EDIT",
        "APPLICATION_CANCEL",
        "APPLICATION_CANCELLATION_REQUESTED",
        "PERSON_ADD",
        "SICK_NOTE_VIEW",
        "SICK_NOTE_ADD",
        "SICK_NOTE_EDIT",
        "SICK_NOTE_CANCEL",
        "SICK_NOTE_COMMENT",
        "ZEITERFASSUNG_USER",
        "ZEITERFASSUNG_VIEW_REPORT_ALL",
        "ZEITERFASSUNG_WORKING_TIME_EDIT_ALL",
        "ZEITERFASSUNG_TIME_ENTRY_EDIT_ALL",
        "ZEITERFASSUNG_OVERTIME_ACCOUNT_EDIT_ALL",
        "ZEITERFASSUNG_PERMISSIONS_EDIT_ALL",
        "ZEITERFASSUNG_SETTINGS_GLOBAL"
      ]
    }
  ]
}

EOF
```

### Theme

```.sh
# Create theme directory structure
mkdir -p "$domain_root_path/domains/$subdomain.$domain/hr/keycloak/themes/${system_user}/login/resources/css"

# theme.properties - extend built-in Keycloak login theme
cat <<EOF > "$domain_root_path/domains/$subdomain.$domain/hr/keycloak/themes/${system_user}/login/theme.properties"
parent=keycloak
import=common/keycloak
styles=css/login.css
EOF

# login.css
cat <<EOF > "$domain_root_path/domains/$subdomain.$domain/hr/keycloak/themes/${system_user}/login/resources/css/login.css"
:root {
    --color-cararra: #ECEAE3;
    --color-dove-gray-light: rgba(101, 101, 101, 0.1);
    --color-mine-shaft: #262626;
    --color-mongoose: #BCA38A;
    --color-pampas: #F2F0EB;
    --color-sandal: #AB8C6C;
    --color-coffee-dark: #5c4033;
    --color-white: #FFFFFF;
}

/* Page background */
html, body, .login-pf, .login-pf body {
    background: var(--color-pampas) !important;
    background-image: none !important;
    min-height: 100vh;
}

/* Align contents toward top */
.login-pf-page {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start !important;
    min-height: 100vh;
    padding-top: 80px !important;
    padding-bottom: 40px;
}

/* Logo container & HR Portal subtitle */
#kc-header {
    margin-bottom: 24px;
    width: 100%;
    max-width: 320px;
    text-align: center;
}

#kc-header-wrapper {
    font-size: 0 !important;
    color: transparent !important;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

#kc-header-wrapper::before {
    content: '';
    display: block;
    width: 220px;
    height: 70px;
    background: url('https://${domain}/wp-content/uploads/${system_user}-logo.svg') no-repeat center / contain;
    filter: invert(28%) sepia(21%) saturate(982%) hue-rotate(346deg) brightness(92%) contrast(88%);
}

#kc-header-wrapper::after {
    content: 'HR Portal';
    display: block;
    font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    font-size: 18px;
    font-weight: 700;
    color: var(--color-coffee-dark);
    letter-spacing: 0.08em;
    margin-top: 10px;
}

/* Hide default page title */
#kc-page-title {
    display: none !important;
}

/* Unified Card Container */
#kc-container-wrapper,
#kc-form-login-wrapper,
#kc-container {
    width: 100% !important;
    max-width: 320px !important;
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    padding: 0 !important;
    margin: 0 auto !important;
}

.card-pf,
.login-pf-page .card-pf {
    background: var(--color-cararra) !important;
    border: 1px solid var(--color-dove-gray-light) !important;
    border-radius: 10px !important;
    box-shadow: none !important;
    padding: 24px !important;
    width: 100% !important;
    max-width: 320px !important;
    margin: 0 auto !important;
}

/* Labels */
label,
.pf-c-form__label,
.pf-v5-c-form__label,
#kc-form-wrapper label {
    color: var(--color-mine-shaft) !important;
    font-size: 14px !important;
    font-weight: 400 !important;
    margin-bottom: 6px !important;
    display: block;
}

/* Remove outer styling from patternfly input groups (prevents double background box) */
.pf-c-input-group,
.pf-c-input-group__item,
.pf-v5-c-input-group,
.pf-v5-c-input-group__item {
    background: transparent !important;
    background-color: transparent !important;
    border: none !important;
    box-shadow: none !important;
    padding: 0 !important;
    margin: 0 !important;
    width: 100% !important;
}

/* Apply background styling directly to actual input elements */
input.pf-c-form-control,
input.pf-v5-c-form-control,
input[type="text"],
input[type="password"],
input[type="email"] {
    background-color: var(--color-dove-gray-light) !important;
    background: var(--color-dove-gray-light) !important;
    color: var(--color-mine-shaft) !important;
    border: 1px solid var(--color-dove-gray-light) !important;
    border-radius: 3px !important;
    height: 40px !important;
    padding: 0 10px !important;
    font-size: 14px !important;
    box-shadow: none !important;
    width: 100% !important;
}

input:focus,
.pf-c-form-control:focus,
.pf-v5-c-form-control:focus {
    border-color: var(--color-sandal) !important;
    outline: none !important;
}

/* Hide Password Toggle Icon (Eye Button) */
.pf-c-button.pf-m-control,
.pf-v5-c-button.pf-m-control,
button[aria-label*="password"],
button[aria-label*="Password"],
.pf-c-form-control + button,
.pf-v5-c-form-control + button,
#kc-input-wrapper button,
.login-pf-settings + button,
i.fa-eye,
i.fa-eye-slash {
    display: none !important;
}

/* Remember Me Checkbox */
.login-pf-settings {
    margin-top: 16px !important;
    margin-bottom: 16px !important;
    display: flex;
    align-items: center;
}

.login-pf-settings label {
    font-size: 13px !important;
    font-weight: normal !important;
    margin-bottom: 0 !important;
}

/* Submit Button */
.pf-c-button[type="submit"],
.pf-v5-c-button[type="submit"],
#kc-login {
    background-color: var(--color-mine-shaft) !important;
    border: 2px solid var(--color-mine-shaft) !important;
    border-radius: 0 !important;
    color: var(--color-white) !important;
    font-size: 14px !important;
    font-weight: 500 !important;
    padding: 10px 20px !important;
    height: auto !important;
    width: auto !important;
    min-width: 90px;
    float: right;
    cursor: pointer;
    transition: all 0.3s !important;
    margin-top: 10px !important;
}

.pf-c-button[type="submit"]:hover,
.pf-v5-c-button[type="submit"]:hover,
#kc-login:hover {
    background-color: #000000 !important;
    border-color: #000000 !important;
}

/* Bottom Links */
#kc-form-options a,
#kc-registration a,
.login-pf-page a {
    color: var(--color-sandal) !important;
    font-size: 13px !important;
    text-decoration: none !important;
}

#kc-form-options a:hover,
#kc-registration a:hover,
.login-pf-page a:hover {
    color: var(--color-mongoose) !important;
}

EOF
```

### Docker Compose

```.sh
cat <<EOF > "$domain_root_path/domains/$subdomain.$domain/hr/docker-compose.keycloak.yml"
name: keycloak
services:

  keycloak_db:
    container_name: "keycloak_postgres_${system_user}"
    image: postgres:18
    command: postgres -c shared_buffers=16MB -c max_connections=10
    restart: unless-stopped
    environment:
      - POSTGRES_DB=\${KEYCLOAK_DB_NAME}
      - POSTGRES_USER=\${KEYCLOAK_DB_USER}
      - POSTGRES_PASSWORD=\${KEYCLOAK_DB_PASSWORD}
    volumes:
      - \${KEYCLOAK_DB_DATA_LOCATION}:/var/lib/postgresql
    healthcheck:
      test: ["CMD", "pg_isready", "-U", "\${KEYCLOAK_DB_USER}"]
      interval: 10s
      timeout: 5s
      retries: 5
      start_period: 20s

  keycloak:
    container_name: "keycloak_server_${system_user}"
    image: quay.io/keycloak/keycloak:\${KEYCLOAK_VERSION}
    # start-dev: TLS is terminated by NGINX, so Keycloak runs plain HTTP internally.
    # --import-realm: auto-imports the realm JSON on first boot.
    command: ["start-dev", "--import-realm"]
    restart: unless-stopped
    depends_on:
      keycloak_db:
        condition: service_healthy
    ports:
      - "127.0.0.1:\${KEYCLOAK_HTTP_PORT}:8080"
    networks:
      default:
        aliases:
          - keycloak    # other containers reach Keycloak at http://keycloak:8080
    environment:
      - JAVA_OPTS_APPEND=-Xms128m -Xmx384m
      # Bootstrap admin - only active on first start; used to access /admin/ via SSH tunnel
      - KC_BOOTSTRAP_ADMIN_USERNAME=\${KEYCLOAK_ADMIN_USER}
      - KC_BOOTSTRAP_ADMIN_PASSWORD=\${KEYCLOAK_ADMIN_PASSWORD}

      # PostgreSQL database for Keycloak's own data
      - KC_DB=postgres
      - KC_DB_URL=jdbc:postgresql://keycloak_db:5432/\${KEYCLOAK_DB_NAME}
      - KC_DB_USERNAME=\${KEYCLOAK_DB_USER}
      - KC_DB_PASSWORD=\${KEYCLOAK_DB_PASSWORD}

      # Tell Keycloak it's behind a reverse proxy that sends X-Forwarded-* headers
      - KC_PROXY_HEADERS=xforwarded
      - KC_HTTP_ENABLED=true

      # Public URL - Keycloak uses this to build redirect URIs and the issuer claim in JWTs
      - KC_HOSTNAME=https://\${KEYCLOAK_DOMAIN}

      # Single-node setup - no Infinispan cluster needed
      - KC_CACHE=local
    volumes:
      - \${KEYCLOAK_IMPORT_LOCATION}:/opt/keycloak/data/import
      - \${KEYCLOAK_THEMES_LOCATION}:/opt/keycloak/themes

networks:
  default:
    name: keycloak_default

EOF
```

### Environment File

```.sh
cat <<EOF > "$domain_root_path/domains/$subdomain.$domain/hr/.env.keycloak"
KEYCLOAK_VERSION=$keycloak_version
KEYCLOAK_HTTP_PORT=$keycloak_http_port
KEYCLOAK_DOMAIN=$subdomain.$domain

KEYCLOAK_ADMIN_USER=$keycloak_admin_user
KEYCLOAK_ADMIN_PASSWORD=$keycloak_admin_password

KEYCLOAK_DB_NAME=$keycloak_db_name
KEYCLOAK_DB_USER=$keycloak_db_user
KEYCLOAK_DB_PASSWORD=$keycloak_db_password

KEYCLOAK_DB_DATA_LOCATION=$domain_root_path/domains/$subdomain.$domain/hr/keycloak/postgres
KEYCLOAK_IMPORT_LOCATION=$domain_root_path/domains/$subdomain.$domain/hr/keycloak/import
KEYCLOAK_THEMES_LOCATION=$domain_root_path/domains/$subdomain.$domain/hr/keycloak/themes

EOF

chmod 600 "$domain_root_path/domains/$subdomain.$domain/hr/.env.keycloak"
```

### Start & Verify

```.sh
# Create data directories
mkdir -p "$domain_root_path/domains/$subdomain.$domain/hr/keycloak/"{postgres,import,themes}

cd $domain_root_path/domains/$subdomain.$domain/hr
docker compose -f docker-compose.keycloak.yml --env-file .env.keycloak up -d

# Watch for successful startup
docker logs keycloak_server_${system_user} -f | grep -i "started\|error"
```

### Create User

```.sh
# Get a Keycloak admin token (uses the master realm bootstrap admin)
KEYCLOAK_TOKEN=$(curl -s \
  -d "client_id=admin-cli&grant_type=password" \
  -d "username=${keycloak_admin_user}&password=${keycloak_admin_password}" \
  http://localhost:${keycloak_http_port}/realms/master/protocol/openid-connect/token \
  | python3 -c "import sys,json; print(json.load(sys.stdin)['access_token'])")

# Create the first HR admin user, assigned to the HR group in one call
curl -s -o /dev/null -w "HTTP %{http_code}\n" \
  -H "Authorization: Bearer $KEYCLOAK_TOKEN" \
  -H "Content-Type: application/json" \
  http://localhost:${keycloak_http_port}/admin/realms/urlaubsverwaltung/users \
  -d "{
    \"username\":      \"${first_user_email}\",
    \"email\":         \"${first_user_email}\",
    \"firstName\":     \"${first_user_firstname}\",
    \"lastName\":      \"${first_user_lastname}\",
    \"enabled\":       true,
    \"emailVerified\": true,
    \"groups\":        [\"${system_user}_hr\"],
    \"credentials\":   [{\"type\":\"password\",\"value\":\"${first_user_password}\",\"temporary\":true}]
  }"

echo "==> User created: ${first_user_email}"
echo "==> Temp password: ${first_user_password}"
echo "==> Group: ${system_user}_hr (all ZT admin roles assigned)"
```

---

## Zeiterfassung

### ARM64 Native Build

> [!NOTE]  
> Re-run the build whenever you upgrade `zeiterfassung_version`.

```.sh
# Clone source code
git clone --depth=1 --branch zeiterfassung-${zeiterfassung_version} https://github.com/urlaubsverwaltung/zeiterfassung.git /tmp/zeiterfassung-build
cd /tmp/zeiterfassung-build

# Create multi-stage Dockerfile
cat > Dockerfile << 'DOCKERFILE'
# Build JAR inside container (no host Java/Maven needed)
FROM maven:3-eclipse-temurin-25 AS builder
WORKDIR /app
COPY . .
RUN ./mvnw -q -DskipTests package

# Runtime
FROM eclipse-temurin:25-jre
WORKDIR /app
COPY --from=builder /app/target/zeiterfassung-*.jar app.jar
EXPOSE 8080
ENTRYPOINT ["java", "-jar", "app.jar"]
DOCKERFILE

# Build native ARM64 image
docker build --no-cache -t urlaubsverwaltung/zeiterfassung:${zeiterfassung_version}-arm64 .

# Clean up source directory
cd / && rm -rf /tmp/zeiterfassung-build
```

### Docker Compose

```.sh
cat <<EOF > "$domain_root_path/domains/$subdomain.$domain/hr/docker-compose.zeiterfassung.yml"
name: zeiterfassung
services:

  zeiterfassung_db:
    container_name: "zeiterfassung_postgres_${system_user}"
    image: postgres:18
    command: postgres -c shared_buffers=16MB -c max_connections=10
    restart: unless-stopped
    environment:
      - POSTGRES_DB=\${ZEITERFASSUNG_DB_NAME}
      - POSTGRES_USER=\${ZEITERFASSUNG_DB_USER}
      - POSTGRES_PASSWORD=\${ZEITERFASSUNG_DB_PASSWORD}
    volumes:
      - \${ZEITERFASSUNG_DB_DATA_LOCATION}:/var/lib/postgresql
    healthcheck:
      test: ["CMD", "pg_isready", "-U", "\${ZEITERFASSUNG_DB_USER}"]
      interval: 10s
      timeout: 5s
      retries: 5
      start_period: 20s

  zeiterfassung:
    container_name: "zeiterfassung_${system_user}"
    image: urlaubsverwaltung/zeiterfassung:\${ZEITERFASSUNG_VERSION}-arm64
    restart: unless-stopped
    depends_on:
      zeiterfassung_db:
        condition: service_healthy
    ports:
      - "127.0.0.1:\${ZEITERFASSUNG_HTTP_PORT}:8080"
    networks:
      - default
      - keycloak_default
    environment:
      - JAVA_TOOL_OPTIONS=-Xms64m -Xmx256m
      - SERVER_FORWARD_HEADERS_STRATEGY=native
      - SERVER_SERVLET_CONTEXT_PATH=/zeiterfassung # Zeiterfassung is served under /zeiterfassung/ so it must generate correct links

      # PostgreSQL
      - SPRING_DATASOURCE_URL=jdbc:postgresql://zeiterfassung_db:5432/\${ZEITERFASSUNG_DB_NAME}
      - SPRING_DATASOURCE_USERNAME=\${ZEITERFASSUNG_DB_USER}
      - SPRING_DATASOURCE_PASSWORD=\${ZEITERFASSUNG_DB_PASSWORD}

      # OIDC - same pattern as Urlaubsverwaltung: public issuer, internal back-channel
      - SPRING_SECURITY_OAUTH2_CLIENT_REGISTRATION_DEFAULT_CLIENT_ID=\${OIDC_CLIENT_ID}
      - SPRING_SECURITY_OAUTH2_CLIENT_REGISTRATION_DEFAULT_CLIENT_SECRET=\${OIDC_CLIENT_SECRET}
      - SPRING_SECURITY_OAUTH2_CLIENT_REGISTRATION_DEFAULT_CLIENT_NAME=Keycloak
      - SPRING_SECURITY_OAUTH2_CLIENT_REGISTRATION_DEFAULT_SCOPE=openid,profile,email
      - SPRING_SECURITY_OAUTH2_CLIENT_REGISTRATION_DEFAULT_AUTHORIZATION_GRANT_TYPE=authorization_code
      - SPRING_SECURITY_OAUTH2_CLIENT_REGISTRATION_DEFAULT_REDIRECT_URI=https://\${ZEITERFASSUNG_DOMAIN}/zeiterfassung/login/oauth2/code/default
      - SPRING_SECURITY_OAUTH2_CLIENT_REGISTRATION_DEFAULT_PROVIDER=default
      - SPRING_SECURITY_OAUTH2_CLIENT_REGISTRATION_DEFAULT_CLIENT_AUTHENTICATION_METHOD=client_secret_basic

      - SPRING_SECURITY_OAUTH2_CLIENT_PROVIDER_DEFAULT_AUTHORIZATION_URI=https://\${ZEITERFASSUNG_DOMAIN}/realms/urlaubsverwaltung/protocol/openid-connect/auth
      - SPRING_SECURITY_OAUTH2_CLIENT_PROVIDER_DEFAULT_TOKEN_URI=http://keycloak:8080/realms/urlaubsverwaltung/protocol/openid-connect/token
      - SPRING_SECURITY_OAUTH2_CLIENT_PROVIDER_DEFAULT_JWK_SET_URI=http://keycloak:8080/realms/urlaubsverwaltung/protocol/openid-connect/certs
      - SPRING_SECURITY_OAUTH2_CLIENT_PROVIDER_DEFAULT_USER_INFO_URI=http://keycloak:8080/realms/urlaubsverwaltung/protocol/openid-connect/userinfo
      - SPRING_SECURITY_OAUTH2_CLIENT_PROVIDER_DEFAULT_USER_NAME_ATTRIBUTE=preferred_username

      - SPRING_SECURITY_OAUTH2_RESOURCESERVER_JWT_ISSUER_URI=https://\${ZEITERFASSUNG_DOMAIN}/realms/urlaubsverwaltung
      - SPRING_SECURITY_OAUTH2_RESOURCESERVER_JWT_JWK_SET_URI=http://keycloak:8080/realms/urlaubsverwaltung/protocol/openid-connect/certs

      - ZEITERFASSUNG_SECURITY_OIDC_LOGIN_FORM_URL=/oauth2/authorization/default
      - ZEITERFASSUNG_SECURITY_OIDC_CLAIM_MAPPERS_GROUP_CLAIM_ENABLED=false
      - ZEITERFASSUNG_SECURITY_OIDC_CLAIM_MAPPERS_REALM_ROLE_CLAIM_ENABLED=true
      - ZEITERFASSUNG_SECURITY_OIDC_SERVER_URL=https://\${ZEITERFASSUNG_DOMAIN}/realms/urlaubsverwaltung

      # Launchpad
      - SPRING_APPLICATION_JSON={"launchpad":{"name-default-locale":"de","apps":[{"url":"https://${subdomain}.${domain}/urlaubsverwaltung/","name":{"de":"Urlaubsverwaltung","en":"Leave Management"},"icon":"data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCI+PHJlY3QgeD0iMyIgeT0iNCIgd2lkdGg9IjE4IiBoZWlnaHQ9IjE4IiByeD0iMiIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjNDI5MmY0IiBzdHJva2Utd2lkdGg9IjIiLz48bGluZSB4MT0iMyIgeTE9IjkiIHgyPSIyMSIgeTI9IjkiIHN0cm9rZT0iIzQyOTJmNCIgc3Ryb2tlLXdpZHRoPSIyIi8+PGxpbmUgeDE9IjgiIHkxPSI0IiB4Mj0iOCIgeTI9IjIiIHN0cm9rZT0iIzQyOTJmNCIgc3Ryb2tlLXdpZHRoPSIyIi8+PGxpbmUgeDE9IjE2IiB5MT0iNCIgeDI9IjE2IiB5Mj0iMiIgc3Ryb2tlPSIjNDI5MmY0IiBzdHJva2Utd2lkdGg9IjIiLz48L3N2Zz4="}]}}

      # Mail
      - SPRING_MAIL_HOST=\${MAIL_HOST}
      - SPRING_MAIL_PORT=\${MAIL_PORT}
      - SPRING_MAIL_USERNAME=\${MAIL_USERNAME}
      - SPRING_MAIL_PASSWORD=\${MAIL_PASSWORD}
      - SPRING_MAIL_PROPERTIES_MAIL_SMTP_AUTH=true
      - SPRING_MAIL_PROPERTIES_MAIL_SMTP_STARTTLS_ENABLE=true

      # SpringDoc
      - SPRINGDOC_API_DOCS_ENABLED=false
      - SPRINGDOC_SWAGGER_UI_ENABLED=false

      # Logging
      - LOGGING_LEVEL_ORG_SPRINGFRAMEWORK_SECURITY=WARN

networks:
  default:
  keycloak_default:
    external: true

EOF
```

### Environment File

```.sh
cat <<EOF > "$domain_root_path/domains/$subdomain.$domain/hr/.env.zeiterfassung"
ZEITERFASSUNG_VERSION=$zeiterfassung_version
ZEITERFASSUNG_HTTP_PORT=$zeiterfassung_http_port
ZEITERFASSUNG_DOMAIN=$subdomain.$domain

ZEITERFASSUNG_DB_NAME=$zeiterfassung_db_name
ZEITERFASSUNG_DB_USER=$zeiterfassung_db_user
ZEITERFASSUNG_DB_PASSWORD=$zeiterfassung_db_password

OIDC_CLIENT_ID=zeiterfassung
OIDC_CLIENT_SECRET=$oidc_secret

MAIL_HOST=$mail_host
MAIL_PORT=$mail_port
MAIL_USERNAME=$mail_username
MAIL_PASSWORD=$mail_password
MAIL_FROM=$mail_from
MAIL_FROM_NAME=$mail_from_name

ZEITERFASSUNG_DB_DATA_LOCATION=$domain_root_path/domains/$subdomain.$domain/hr/zeiterfassung/postgres

EOF

chmod 600 "$domain_root_path/domains/$subdomain.$domain/hr/.env.zeiterfassung"
```

### Start

```.sh
mkdir -p "$domain_root_path/domains/$subdomain.$domain/hr/zeiterfassung/postgres"

cd $domain_root_path/domains/$subdomain.$domain/hr
docker compose -f docker-compose.zeiterfassung.yml --env-file .env.zeiterfassung up -d --force-recreate

docker logs zeiterfassung_${system_user} -f | grep -i "started\|error\|oauth"
```

---

## Urlaubsverwaltung

### ARM64 Native Build

> [!NOTE]  
> Re-run the build whenever you upgrade `urlaubsverwaltung_version`.

```.sh
# Clone source code
git clone --depth=1 --branch urlaubsverwaltung-${urlaubsverwaltung_version} https://github.com/urlaubsverwaltung/urlaubsverwaltung.git /tmp/urlaubsverwaltung-build
cd /tmp/urlaubsverwaltung-build

# Create multi-stage Dockerfile
cat > Dockerfile << 'DOCKERFILE'
# Build JAR inside container (no host Java/Maven needed)
FROM maven:3-eclipse-temurin-25 AS builder
WORKDIR /app
COPY . .
RUN ./mvnw -q -DskipTests package

# Runtime
FROM eclipse-temurin:25-jre
WORKDIR /app
COPY --from=builder /app/target/urlaubsverwaltung-*.jar app.jar
EXPOSE 8080
ENTRYPOINT ["java", "-jar", "app.jar"]
DOCKERFILE

# Build native ARM64 image
docker build --no-cache -t urlaubsverwaltung/urlaubsverwaltung:${urlaubsverwaltung_version}-arm64 .

# Clean up source directory
cd / && rm -rf /tmp/urlaubsverwaltung-build
```

### Docker Compose

```.sh
cat <<EOF > "$domain_root_path/domains/$subdomain.$domain/hr/docker-compose.urlaubsverwaltung.yml"
name: urlaubsverwaltung
services:

  urlaubsverwaltung_db:
    container_name: "urlaubsverwaltung_postgres_${system_user}"
    image: postgres:18
    command: postgres -c shared_buffers=16MB -c max_connections=10
    restart: unless-stopped
    environment:
      - POSTGRES_DB=\${URLAUBSVERWALTUNG_DB_NAME}
      - POSTGRES_USER=\${URLAUBSVERWALTUNG_DB_USER}
      - POSTGRES_PASSWORD=\${URLAUBSVERWALTUNG_DB_PASSWORD}
    volumes:
      - \${URLAUBSVERWALTUNG_DB_DATA_LOCATION}:/var/lib/postgresql
    healthcheck:
      test: ["CMD", "pg_isready", "-U", "\${URLAUBSVERWALTUNG_DB_USER}"]
      interval: 10s
      timeout: 5s
      retries: 5
      start_period: 20s

  urlaubsverwaltung:
    container_name: "urlaubsverwaltung_${system_user}"
    image: urlaubsverwaltung/urlaubsverwaltung:\${URLAUBSVERWALTUNG_VERSION}-arm64
    restart: unless-stopped
    depends_on:
      urlaubsverwaltung_db:
        condition: service_healthy
    ports:
      - "127.0.0.1:\${URLAUBSVERWALTUNG_HTTP_PORT}:8080"
    networks:
      - default
      - keycloak_default
    environment:
      - JAVA_TOOL_OPTIONS=-Xms64m -Xmx256m
      - SERVER_FORWARD_HEADERS_STRATEGY=native
      - SERVER_SERVLET_CONTEXT_PATH=/urlaubsverwaltung # Urlaubsverwaltung is served under /urlaubsverwaltung/ so it must generate correct links

      # PostgreSQL
      - SPRING_DATASOURCE_URL=jdbc:postgresql://urlaubsverwaltung_db:5432/\${URLAUBSVERWALTUNG_DB_NAME}
      - SPRING_DATASOURCE_USERNAME=\${URLAUBSVERWALTUNG_DB_USER}
      - SPRING_DATASOURCE_PASSWORD=\${URLAUBSVERWALTUNG_DB_PASSWORD}

      # OIDC client registration
      - SPRING_SECURITY_OAUTH2_CLIENT_REGISTRATION_DEFAULT_CLIENT_ID=\${OIDC_CLIENT_ID}
      - SPRING_SECURITY_OAUTH2_CLIENT_REGISTRATION_DEFAULT_CLIENT_SECRET=\${OIDC_CLIENT_SECRET}
      - SPRING_SECURITY_OAUTH2_CLIENT_REGISTRATION_DEFAULT_CLIENT_NAME=Keycloak
      - SPRING_SECURITY_OAUTH2_CLIENT_REGISTRATION_DEFAULT_SCOPE=openid,profile,email
      - SPRING_SECURITY_OAUTH2_CLIENT_REGISTRATION_DEFAULT_AUTHORIZATION_GRANT_TYPE=authorization_code
      - SPRING_SECURITY_OAUTH2_CLIENT_REGISTRATION_DEFAULT_REDIRECT_URI=https://\${URLAUBSVERWALTUNG_DOMAIN}/urlaubsverwaltung/login/oauth2/code/default
      - SPRING_SECURITY_OAUTH2_CLIENT_REGISTRATION_DEFAULT_PROVIDER=default
      - SPRING_SECURITY_OAUTH2_CLIENT_REGISTRATION_DEFAULT_CLIENT_AUTHENTICATION_METHOD=client_secret_basic

      # Authorization URI override - the browser is redirected to the PUBLIC HTTPS URL,
      # not the internal Docker alias (which the browser can't reach)
      - SPRING_SECURITY_OAUTH2_CLIENT_PROVIDER_DEFAULT_AUTHORIZATION_URI=https://\${URLAUBSVERWALTUNG_DOMAIN}/realms/urlaubsverwaltung/protocol/openid-connect/auth

      # Back-channel calls go to the internal Docker alias (no TLS, faster)
      - SPRING_SECURITY_OAUTH2_CLIENT_PROVIDER_DEFAULT_TOKEN_URI=http://keycloak:8080/realms/urlaubsverwaltung/protocol/openid-connect/token
      - SPRING_SECURITY_OAUTH2_CLIENT_PROVIDER_DEFAULT_JWK_SET_URI=http://keycloak:8080/realms/urlaubsverwaltung/protocol/openid-connect/certs
      - SPRING_SECURITY_OAUTH2_CLIENT_PROVIDER_DEFAULT_USER_INFO_URI=http://keycloak:8080/realms/urlaubsverwaltung/protocol/openid-connect/userinfo
      - SPRING_SECURITY_OAUTH2_CLIENT_PROVIDER_DEFAULT_USER_NAME_ATTRIBUTE=preferred_username

      # Resource server - issuer must match the "iss" claim Keycloak puts in JWTs (public URL, no trailing slash)
      - SPRING_SECURITY_OAUTH2_RESOURCESERVER_JWT_ISSUER_URI=https://\${URLAUBSVERWALTUNG_DOMAIN}/realms/urlaubsverwaltung
      - SPRING_SECURITY_OAUTH2_RESOURCESERVER_JWT_JWK_SET_URI=http://keycloak:8080/realms/urlaubsverwaltung/protocol/openid-connect/certs

      # Mail
      - UV_MAIL_FROM=\${MAIL_FROM}
      - UV_MAIL_REPLY_TO=\${MAIL_FROM}
      - UV_MAIL_FROM_DISPLAY_NAME=\${MAIL_FROM_NAME}
      - UV_MAIL_APPLICATION_URL=https://\${URLAUBSVERWALTUNG_DOMAIN}/urlaubsverwaltung
      - UV_CALENDAR_ORGANIZER=\${MAIL_FROM}
      - SPRING_MAIL_HOST=\${MAIL_HOST}
      - SPRING_MAIL_PORT=\${MAIL_PORT}
      - SPRING_MAIL_USERNAME=\${MAIL_USERNAME}
      - SPRING_MAIL_PASSWORD=\${MAIL_PASSWORD}
      - SPRING_MAIL_PROPERTIES_MAIL_SMTP_AUTH=true
      - SPRING_MAIL_PROPERTIES_MAIL_SMTP_STARTTLS_ENABLE=true

      # Launchpad
      - SPRING_APPLICATION_JSON={"launchpad":{"name-default-locale":"de","apps":[{"url":"https://${subdomain}.${domain}/zeiterfassung/","name":{"de":"Zeiterfassung","en":"Time Tracking"},"icon":"data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCI+PGNpcmNsZSBjeD0iMTIiIGN5PSIxMiIgcj0iMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzQyOTJmNCIgc3Ryb2tlLXdpZHRoPSIyIi8+PGxpbmUgeDE9IjEyIiB5MT0iNyIgeDI9IjEyIiB5Mj0iMTIiIHN0cm9rZT0iIzQyOTJmNCIgc3Ryb2tlLXdpZHRoPSIyIiBzdHJva2UtbGluZWNhcD0icm91bmQiLz48bGluZSB4MT0iMTIiIHkxPSIxMiIgeDI9IjE2IiB5Mj0iMTQiIHN0cm9rZT0iIzQyOTJmNCIgc3Ryb2tlLXdpZHRoPSIyIiBzdHJva2UtbGluZWNhcD0icm91bmQiLz48L3N2Zz4="}]}}

      # SpringDoc
      - SPRINGDOC_API_DOCS_ENABLED=false
      - SPRINGDOC_SWAGGER_UI_ENABLED=false

      # Logging
      - LOGGING_LEVEL_ORG_SPRINGFRAMEWORK_SECURITY=WARN

networks:
  default:
  keycloak_default:
    external: true

EOF
```

### Environment File

```.sh
cat <<EOF > "$domain_root_path/domains/$subdomain.$domain/hr/.env.urlaubsverwaltung"
URLAUBSVERWALTUNG_VERSION=$urlaubsverwaltung_version
URLAUBSVERWALTUNG_HTTP_PORT=$urlaubsverwaltung_http_port
URLAUBSVERWALTUNG_DOMAIN=$subdomain.$domain

URLAUBSVERWALTUNG_DB_NAME=$urlaubsverwaltung_db_name
URLAUBSVERWALTUNG_DB_USER=$urlaubsverwaltung_db_user
URLAUBSVERWALTUNG_DB_PASSWORD=$urlaubsverwaltung_db_password

OIDC_CLIENT_ID=urlaubsverwaltung
OIDC_CLIENT_SECRET=$oidc_secret

MAIL_HOST=$mail_host
MAIL_PORT=$mail_port
MAIL_USERNAME=$mail_username
MAIL_PASSWORD=$mail_password
MAIL_FROM=$mail_from
MAIL_FROM_NAME=$mail_from_name

URLAUBSVERWALTUNG_DB_DATA_LOCATION=$domain_root_path/domains/$subdomain.$domain/hr/urlaubsverwaltung/postgres

EOF

chmod 600 "$domain_root_path/domains/$subdomain.$domain/hr/.env.urlaubsverwaltung"
```

### Start

```.sh
# Create the database data directory
mkdir -p "$domain_root_path/domains/$subdomain.$domain/hr/urlaubsverwaltung/postgres"

cd $domain_root_path/domains/$subdomain.$domain/hr
docker compose -f docker-compose.urlaubsverwaltung.yml --env-file .env.urlaubsverwaltung up -d --force-recreate

# Watch for successful startup ("Started UrlaubsverwaltungApplication")
docker logs urlaubsverwaltung_${system_user} -f | grep -i "started\|error\|oauth"
```

---

## Adding More Users

Two patterns: **employee** (both apps, standard access) and **HR admin** (full permissions including Urlaubsverwaltung admin and Zeiterfassung admin). Both create the user and assign roles in a single API call.

**Employee** - can request leave in Urlaubsverwaltung (`USER`) and clock in/out in Zeiterfassung (`ZEITERFASSUNG_USER`):

```.sh
new_email="email@website.com"
new_firstname="Name"
new_lastname="Lastname"
new_password=$(openssl rand -base64 10 | tr -dc 'A-Za-z0-9')

KEYCLOAK_TOKEN=$(curl -s \
  -d "client_id=admin-cli&grant_type=password" \
  -d "username=${keycloak_admin_user}&password=${keycloak_admin_password}" \
  http://localhost:${keycloak_http_port}/realms/master/protocol/openid-connect/token \
  | python3 -c "import sys,json; print(json.load(sys.stdin)['access_token'])")

curl -s -o /dev/null -w "HTTP %{http_code}\n" \
  -H "Authorization: Bearer $KEYCLOAK_TOKEN" \
  -H "Content-Type: application/json" \
  http://localhost:${keycloak_http_port}/admin/realms/urlaubsverwaltung/users \
  -d "{
    \"username\":      \"${new_email}\",
    \"email\":         \"${new_email}\",
    \"firstName\":     \"${new_firstname}\",
    \"lastName\":      \"${new_lastname}\",
    \"enabled\":       true,
    \"emailVerified\": true,
    \"realmRoles\":    [\"USER\", \"ZEITERFASSUNG_USER\"],
    \"credentials\":   [{\"type\":\"password\",\"value\":\"${new_password}\",\"temporary\":true}]
  }"

echo "==> Created employee: ${new_email} / temp password: ${new_password}"
```

---

### Cloudflare Zero Trust

Zeiterfassung is at `/zeiterfassung/` and is intended to be reachable from the office network without any Cloudflare Zero Trust challenge. Urlaubsverwaltung is reachable at `/urlaubsverwaltung/` and is restricted to office IP or explicitly whitelisted remote users. The root `/` redirects to `/zeiterfassung/`.

This restricts `hr.website.com/urlaubsverwaltung/*` so that:

- Requests from your **office IP** pass through without any challenge
- All other IPs see a **Cloudflare Zero Trust** login screen before reaching the app
- HR admins (who may work remotely) can be added to the Zero Trust policy

Cloudflare → `Zero Trust`.

##### Policies

`Access controls` → `Policies` → `Add a policy`.

`Policy name`: `HR Portal`. `Action`: `Allow`. `Session duration`: `Same as application session duration`.

`Policy rules` → `Include`:

- `Selector is...`: `Emails` or `IP ranges`: `1.2.3.4`.

##### Applications

`Access controls` → `Applications` → `Create new application` → `Self-hosted and private` → `Public DNS` → `Continue with Self-hosted and private`.

HR Portal Access `Application name`: `HR Portal`. `Session Duration`: `1 month`. `Public hostname`: `hr.website.com`. `Access policies`: `Select existing policies` (`HR Portal`).

---

## Settings

### Keycloak

`Manage realms` → `urlaubsverwaltung` → `Realm settings` → `Themes` → Set `Login theme`.

### Urlaubsverwaltung

Roles to HR manager: `Company` → `Staff` → Select HR manager → `Account` → `Permissions` → `Edit` → Tick: `User`, `Department Head`, `Boss`, `Office`, `Management of sick notes`, `Manage absences`.

Create a department and assign an approver: `Company` → `Departments` → `New department`. Set a name, then define `is department head` for specific user(s).

`Settings` → `Absences`:

- `Sick notes settings` → Enable `Users can enter sick notes themselves`.
- `Automatic reminder for waiting absences` → Enable `Automatic reminder function`.

Notifications: User → `Notifications` → `E-Mail Notifications` → `Departments`:

- HR manager: `Absences of my colleagues` → Enable `Submissions`, `Reminder for waiting absences to be approved`.
- Hr manager: `Sick notes from my colleagues` → Enable `Created sick notes`, `Edited Sick Notes`.
- User: `Sick notes from my colleagues` → Enable `Created sick notes`, `Submissions`, `Accepted Sick Notes`, `Edited Sick Notes`.

### Zeiterfassung

`Persons` → HR manager → `Permissions` → Enable all permissions.

`Settings` → `Festschreiben von Zeiteinträgen` → Enable `Enable time entry locking`.

---

## Uninstall

```.sh
# cd $domain_root_path/domains/$subdomain.$domain/hr

# Stop and remove containers + volumes (order matters)
# docker compose -f docker-compose.keycloak.yml       --env-file .env.keycloak       down -v
# docker compose -f docker-compose.zeiterfassung.yml  --env-file .env.zeiterfassung  down -v
# docker compose -f docker-compose.urlaubsverwaltung.yml --env-file .env.urlaubsverwaltung down -v

# Remove all data
# rm -rf $domain_root_path/domains/$subdomain.$domain/hr

# Confirm
# docker ps
```
