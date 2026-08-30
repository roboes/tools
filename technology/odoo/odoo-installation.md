# Odoo Installation

> [!NOTE]  
> Last update: 2026-08-28

```sh
# Settings
domain="website.com"
domain_root_path="/home/${domain}"
subdomain="erp"
system_user="website"
odoo_version="18.0"
database_name="${system_user}_odoo"
database_host="db"
database_port=5432
database_username="${system_user}_odoo_user"
database_password=$(openssl rand -base64 32 | tr -dc 'A-Za-z0-9')
odoo_master_password=$(openssl rand -base64 32 | tr -dc 'A-Za-z0-9')
odoo_http_port=8069
odoo_longpolling_port=8072
odoo_workers=4
odoo_account_fiscal_localization_country='de'
```

## [Odoo](https://www.odoo.com)

```sh
# Create directories
sudo mkdir -p ${domain_root_path}/domains/${subdomain}.${domain}/odoo
sudo chown -R ${system_user}:${system_user} ${domain_root_path}/domains/${subdomain}.${domain}/odoo
```

```sh
# Add the system user to the docker group
sudo usermod -aG docker ${system_user}

# Verify the user is in the docker group
groups ${system_user}
```

### OCA Submodules

```sh
# Initialize git repo in the root odoo folder
cd ${domain_root_path}/domains/${subdomain}.${domain}/odoo
git config --global --add safe.directory "${domain_root_path}/domains/${subdomain}.${domain}/odoo"
git init

# Create the addons directory
mkdir -p "addons"

# Add OCA repositories as submodules
git submodule add --branch ${odoo_version} https://github.com/OCA/brand.git addons/oca/brand
git submodule add --branch ${odoo_version} https://github.com/OCA/product-attribute.git addons/oca/product-attribute
git submodule add --branch ${odoo_version} https://github.com/OCA/queue.git addons/oca/queue
git submodule add --branch ${odoo_version} https://github.com/OCA/server-tools.git addons/oca/server-tools
git submodule add --branch ${odoo_version} https://github.com/roboes/odoo-woocommerce-sync.git addons/custom/odoo-woocommerce-sync
# git submodule add --branch ${odoo_version} https://github.com/roboes/odoo-shorepos-sync.git addons/custom/odoo-shorepos-sync
# git submodule add --branch ${odoo_version} https://github.com/OCA/l10n-brazil.git addons/oca/l10n-brazil

git commit -m "Add OCA submodules for Odoo ${odoo_version}"
```

### Dockerfile & Docker Compose

```sh
cat <<'EOF' > "${domain_root_path}/domains/${subdomain}.${domain}/odoo/Dockerfile"
ARG ODOO_VERSION
FROM odoo:${ODOO_VERSION}

USER root

# Allow pip to install system-wide across all Python/Debian versions safely
ENV PIP_BREAK_SYSTEM_PACKAGES=1

RUN apt-get update && apt-get install -y --no-install-recommends \
    gcc \
    python3-dev \
	pkg-config \
    libavif-dev \
	libavif-bin \
    zlib1g-dev \
    libjpeg-dev \
    libwebp-dev \
    && rm -rf /var/lib/apt/lists/*

RUN pip3 install --no-cache-dir --no-binary Pillow "Pillow>=11.3.0"

RUN --mount=type=bind,source=addons,target=/tmp/addons \
    find /tmp/addons -name "requirements.txt" \
        ! -path "*/oca/server-tools/*" \
        -exec pip3 install --no-cache-dir -r {} \; ; true

USER odoo
EOF
```

```sh
# Create docker-compose.yml
cat <<EOF > "${domain_root_path}/domains/${subdomain}.${domain}/odoo/docker-compose.yml"
name: odoo

services:
  odoo:
    container_name: "odoo_server_${system_user}"
    build:
      context: .
      args:
        ODOO_VERSION: \${ODOO_VERSION}
    depends_on:
      db:
        condition: service_healthy
    ports:
      - "127.0.0.1:\${ODOO_HTTP_PORT}:8069"
      - "127.0.0.1:\${ODOO_LONGPOLLING_PORT}:8072"
    volumes:
      - \${ODOO_DATA_LOCATION}:/var/lib/odoo
      - \${ODOO_CONFIG_LOCATION}:/etc/odoo
      - \${ODOO_ADDONS_LOCATION}:/mnt/extra-addons
    environment:
      - HOST=\${DB_HOST}
      - PORT=\${DB_PORT}
      - USER=\${DB_USERNAME}
      - PASSWORD=\${DB_PASSWORD}
    env_file:
      - .env
    restart: always

  db:
    container_name: "odoo_postgres_${system_user}"
    image: postgres:16
    environment:
      POSTGRES_DB: postgres
      POSTGRES_USER: \${DB_USERNAME}
      POSTGRES_PASSWORD: \${DB_PASSWORD}
      PGDATA: /var/lib/postgresql/data/pgdata
    volumes:
      - \${DB_DATA_LOCATION}:/var/lib/postgresql/data/pgdata
    shm_size: 128mb
    restart: always
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U \${DB_USERNAME}"]
      interval: 10s
      timeout: 5s
      retries: 5

EOF
```

```sh
# Create .env file
cat <<EOF > "${domain_root_path}/domains/${subdomain}.${domain}/odoo/.env"
# Odoo version
ODOO_VERSION=${odoo_version}

# Odoo ports
ODOO_HTTP_PORT=${odoo_http_port}
ODOO_LONGPOLLING_PORT=${odoo_longpolling_port}

# Odoo data locations
ODOO_DATA_LOCATION=${domain_root_path}/domains/${subdomain}.${domain}/odoo/data
ODOO_CONFIG_LOCATION=${domain_root_path}/domains/${subdomain}.${domain}/odoo/config
ODOO_ADDONS_LOCATION=${domain_root_path}/domains/${subdomain}.${domain}/odoo/addons

# PostgreSQL data location
DB_DATA_LOCATION=${domain_root_path}/domains/${subdomain}.${domain}/odoo/postgres

# Database credentials
DB_HOST=${database_host}
DB_PORT=${database_port}
DB_USERNAME=${database_username}
DB_PASSWORD=${database_password}

EOF

# Secure .env file
chmod 600 "${domain_root_path}/domains/${subdomain}.${domain}/odoo/.env"
```

```sh
# Create config directory
sudo mkdir -p ${domain_root_path}/domains/${subdomain}.${domain}/odoo/config

# Generate addons_path from submodules (converts ./oca/repo to /mnt/extra-addons/oca/repo)
cd ${domain_root_path}/domains/${subdomain}.${domain}/odoo/addons
ADDONS_PATH=$(find . -mindepth 2 -maxdepth 2 -type d | grep -E "^\./oca/|^\./custom/" | sed 's|^\./|/mnt/extra-addons/|' | tr '\n' ',' | sed 's/,$//')
cd ${domain_root_path}/domains/${subdomain}.${domain}/odoo

# Create odoo.conf (addons_path includes OCA submodule paths)
cat <<EOF > "${domain_root_path}/domains/${subdomain}.${domain}/odoo/config/odoo.conf"
[options]
addons_path = /usr/lib/python3/dist-packages/odoo/addons,${ADDONS_PATH}
data_dir = /var/lib/odoo
admin_passwd = ${odoo_master_password}
db_host = ${database_host}
db_port = ${database_port}
db_name = ${database_name}
db_user = ${database_username}
db_password = ${database_password}
proxy_mode = True
http_interface = 0.0.0.0
workers = ${odoo_workers}
gevent_port = 8072
limit_memory_hard = 2684354560
limit_memory_soft = 2147483648
limit_request = 8192
limit_time_cpu = 600
limit_time_real = 1200
max_cron_threads = 1
without_demo = True
server_wide_modules = base,web,queue_job
# log_handler = :INFO,odoo.addons.queue_job:DEBUG

[queue_job]
channels = root:2

EOF
```

```sh
# Create data directories
sudo mkdir -p "${domain_root_path}/domains/${subdomain}.${domain}/odoo/data"
sudo mkdir -p "${domain_root_path}/domains/${subdomain}.${domain}/odoo/postgres"

# Get Odoo container UID/GID
odoo_uid=$(docker run --rm odoo:${odoo_version} id -u)
odoo_gid=$(docker run --rm odoo:${odoo_version} id -g)
echo "Odoo UID: ${odoo_uid}, GID: ${odoo_gid}"

# Change ownership (after git operations are complete)
sudo chown -R ${odoo_uid}:${odoo_gid} "${domain_root_path}/domains/${subdomain}.${domain}/odoo/data"
sudo chown -R ${odoo_uid}:${odoo_gid} "${domain_root_path}/domains/${subdomain}.${domain}/odoo/addons"
sudo chown -R ${odoo_uid}:${odoo_gid} "${domain_root_path}/domains/${subdomain}.${domain}/odoo/config"

# Get PostgreSQL container UID
postgres_uid=$(docker run --rm postgres:16 id -u)
postgres_gid=$(docker run --rm postgres:16 id -g)
echo "Postgres UID: ${postgres_uid}, GID: ${postgres_gid}"

# Change ownership
sudo chown -R ${postgres_uid}:${postgres_gid} "${domain_root_path}/domains/${subdomain}.${domain}/odoo/postgres"
```

```sh
# Build custom Odoo image
cd ${domain_root_path}/domains/${subdomain}.${domain}/odoo
docker compose build --no-cache # --progress=plain
```

```sh
# Initialize Odoo database with core modules
cd ${domain_root_path}/domains/${subdomain}.${domain}/odoo

docker compose run --rm odoo odoo \
  --config /etc/odoo/odoo.conf \
  --init base,web,account,contacts,delivery,product,sale_management,stock,stock_account,l10n_${odoo_account_fiscal_localization_country} \
  --stop-after-init
```

```sh
# Start containers
cd ${domain_root_path}/domains/${subdomain}.${domain}/odoo
docker compose up -d
```

```sh
# Define modules to install
modules=(
    base_multi_image
    l10n_br_base
    l10n_br_sale
    module_auto_update
    product_brand
    product_dimension
    product_multi_category
    queue_job
    queue_job_cron
)

# Check which are available
modules_available=()
modules_unavailable=()
for module in "${modules[@]}"; do
    if docker exec odoo_server_${system_user} find /mnt/extra-addons -type d -name "${module}" | grep -q .; then
        modules_available+=("${module}")
    else
        modules_unavailable+=("${module}")
    fi
done

echo "Modules available: ${modules_available[*]}"
echo "Modules unavailable: ${modules_unavailable[*]}"

# Install only available modules
if [ ${#modules_available[@]} -gt 0 ]; then
    modules_available_csv=$(IFS=,; echo "${modules_available[*]}")
    docker exec -it odoo_server_${system_user} odoo \
        --database ${database_name} \
        --init ${modules_available_csv} \
        --no-http \
        --stop-after-init
fi
```

## Odoo Settings Configuration

```sh
# Access Odoo shell
docker exec -it odoo_server_${system_user} odoo shell --no-http -d ${database_name}
```

```py
# Settings
odoo_username = 'admin'
odoo_account_fiscal_localization_country = 'de'
odoo_account_price_include = 'tax_included'

# Odoo version
from odoo.release import version_info

print(f'Odoo version: {version_info[0]}')

odoo_country = env['res.country'].search([('code', '=', odoo_account_fiscal_localization_country.upper())], limit=1)

# Chart template lookup: v16 uses a Many2one 'chart_template_id' on account.chart.template records;
# v17+ replaced this with a Selection field 'chart_template' guessed via _guess_chart_template()
if version_info[0] <= 16:
    odoo_chart_template_field = 'chart_template_id'
    odoo_account_fiscal_localization_chart_template = env['account.chart.template'].search([('country_id', '=', odoo_country.id)], limit=1).id
else:
    odoo_chart_template_field = 'chart_template'
    odoo_account_fiscal_localization_chart_template = env['account.chart.template']._guess_chart_template(odoo_country)

odoo_user = env['res.users'].search([('login', '=', odoo_username)], limit=1)
odoo_fiscal_module = env['ir.module.module'].search([('name', '=', f'l10n_{odoo_account_fiscal_localization_country}')], limit=1)

# List all available chart templates for the selected fiscal localization country
if version_info[0] <= 16:
    chart_template_options = [(t.id, t.name) for t in env['account.chart.template'].search([('country_id', '=', odoo_country.id)])]
else:
    chart_template_options = [option for option in env['res.config.settings']._fields['chart_template'].selection(env['res.config.settings']) if odoo_account_fiscal_localization_country in option[0]]

for value, label in chart_template_options:
    print(f'{value}: {label}')


def assign_group(xml_id: str) -> None:
    group = env.ref(xml_id)
    # 'group_ids' replaced 'groups_id' on 'res.users' in Odoo v19
    field_name = 'group_ids' if 'group_ids' in odoo_user._fields else 'groups_id'
    if group not in odoo_user[field_name]:
        odoo_user.write({field_name: [(4, group.id)]})
    print(f'Assigned group: {xml_id}')


if odoo_user and odoo_fiscal_module and odoo_fiscal_module.state == 'installed':
    # Group assignments
    assign_group('sales_team.group_sale_manager')  # Sales Administrator
    assign_group('account.group_account_manager')  # Billing Administrator
    assign_group('account.group_account_user')  # Full Accounting Features
    assign_group('stock.group_stock_multi_locations')  # Storage Locations
    # Settings (Product Variants, Units of Measure, Product Packagings)
    config_values = {'group_product_variant': True, 'group_uom': True}
    if version_info[0] == 18:
        config_values['group_stock_packaging'] = True
    env['res.config.settings'].create(config_values).execute()
    # Fiscal Localization
    env['res.config.settings'].create({odoo_chart_template_field: odoo_account_fiscal_localization_chart_template, 'account_fiscal_country_id': odoo_country.id}).execute()
    # Purchase Tax Prices setting
    if version_info[0] >= 17:
        print(f'Current Purchase Tax Prices setting: {env.company.account_price_include}')
        if env.company.account_price_include != odoo_account_price_include:
            env['res.config.settings'].create({'account_price_include': odoo_account_price_include}).execute()
            print(f'Updated Purchase Tax Prices setting: {env.company.account_price_include}')
    else:
        odoo_price_include_bool = odoo_account_price_include == 'tax_included'
        for tax_field in ('account_sale_tax_id', 'account_purchase_tax_id'):
            tax = env.company[tax_field]
            print(f'Current {tax_field} ({tax.name!r}) Included in Price: {tax.price_include}')
            if tax and tax.price_include != odoo_price_include_bool:
                tax.write({'price_include': odoo_price_include_bool})
                print(f'Updated {tax_field} ({tax.name!r}) Included in Price: {tax.price_include}')
    env.cr.commit()  # Commit changes to database
else:
    print(f'User ({odoo_username}) and/or fiscal module ({f"l10n_{odoo_account_fiscal_localization_country}"}) not found')
```

```py
exit()
```

```sh
# Confirm docker is running
docker ps
```

```sh
# View logs
# docker logs odoo_server_${system_user}
# docker logs odoo_postgres_${system_user}

# Clean logs
# truncate -s 0 $(docker inspect --format='{{.LogPath}}' odoo_server_${system_user})
```

### Nginx Directives

```txt
server {
    client_max_body_size 512M;
    proxy_buffering off;

    location / {
        proxy_pass http://127.0.0.1:8069;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-Host $host;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 720s;
        proxy_connect_timeout 720s;
        proxy_send_timeout 720s;
    }

    location /longpolling {
        proxy_pass http://127.0.0.1:8072;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-Host $host;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    location /websocket {
        proxy_pass http://127.0.0.1:8072;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-Host $host;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
    }

}
```

```sh
# Restart Nginx
nginx -t && systemctl reload nginx
```

### Useful Commands

```sh
cd ${domain_root_path}/domains/${subdomain}.${domain}/odoo

# Restart containers
docker compose down && docker compose up -d --remove-orphans

# Stop containers
# docker compose down -v

# Update Odoo to latest patch
# docker compose pull
# docker compose build
# docker compose up -d

# Access Odoo shell
# docker exec -it odoo_server_${system_user} odoo shell --no-http -d ${database_name}

# Backup database
# docker exec odoo_postgres_${system_user} pg_dump -U ${database_username} ${database_name} > backup.sql

# Install Python packages
# docker exec -u 0 -it odoo_server_${system_user} pip3 install --no-cache-dir --break-system-packages --ignore-installed packaging brazilcep email-validator erpbrasil.assinatura erpbrasil.base erpbrasil.edoc erpbrasil.transmissao nfelib num2words phonenumbers
# docker restart odoo_server_${system_user}
```

### Update OCA Submodules

```sh
# Update all OCA submodules to latest
cd ${domain_root_path}/domains/${subdomain}.${domain}/odoo
git submodule update --remote --merge
git add .
git commit -m "Update OCA submodules"

# Rebuild Docker image (in case requirements.txt changed)
cd ${domain_root_path}/domains/${subdomain}.${domain}/odoo
docker compose build
docker compose up -d
```

### Add New OCA Submodule

```sh
# Example: add a new OCA repo
cd ${domain_root_path}/domains/${subdomain}.${domain}/odoo/addons
git submodule add --branch ${odoo_version} https://github.com/OCA/account-financial-tools.git oca/account-financial-tools
git commit -m "Add OCA account-financial-tools"

# Update odoo.conf to include new path
# Add: /mnt/extra-addons/oca/account-financial-tools

# Get Odoo container UID/GID
odoo_uid=$(docker run --rm odoo:${odoo_version} id -u)
odoo_gid=$(docker run --rm odoo:${odoo_version} id -g)
echo "Odoo UID: ${odoo_uid}, GID: ${odoo_gid}"

# Change ownership (after git operations are complete)
sudo chown -R ${odoo_uid}:${odoo_gid} "${domain_root_path}/domains/${subdomain}.${domain}/odoo/addons"

# Rebuild and restart
cd ${domain_root_path}/domains/${subdomain}.${domain}/odoo
docker compose build
docker compose up -d
```

## Uninstall

```sh
# cd ${domain_root_path}/domains/${subdomain}.${domain}/odoo

# Stop and remove containers + volumes
# docker compose down -v

# cd ${domain_root_path}/domains/${subdomain}.${domain}

# Remove all data
# sudo rm -rf ${domain_root_path}/domains/${subdomain}.${domain}/odoo

# Confirm docker is not running
# docker ps
```
