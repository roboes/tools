# Odoo Installation (Docker)

> [!NOTE]  
> Last update: 2025-12-30

```.sh
# Settings
domain="website.com"
domain_root_path="/home/$domain"
subdomain="erp"
system_user="website"
odoo_version="18.0"
database_name="website_odoo"
database_host="db"
database_port=5432
database_username="website_odoo_user"
database_password=$(openssl rand -base64 32 | tr -dc 'A-Za-z0-9')
odoo_master_password=$(openssl rand -base64 32 | tr -dc 'A-Za-z0-9')
odoo_http_port=8069
odoo_longpolling_port=8072
odoo_workers=2
```

## [Odoo](https://www.odoo.com)

```.sh
# Create directories
sudo mkdir -p $domain_root_path/domains/$subdomain.$domain/odoo
sudo chown -R $system_user:$system_user $domain_root_path/domains/$subdomain.$domain/odoo
```

```.sh
# Add the system user to the docker group
sudo usermod -aG docker $system_user

# Verify the user is in the docker group
groups $system_user
```

### OCA Submodules

```.sh
# Initialize git repo in the root odoo folder
cd $domain_root_path/domains/$subdomain.$domain/odoo
git config --global --add safe.directory "$domain_root_path/domains/$subdomain.$domain/odoo"
git init

# Create the addons directory
mkdir -p "addons"
cd "addons"

# Add OCA repositories as submodules
git submodule add --branch $odoo_version https://github.com/OCA/brand.git oca/brand
git submodule add --branch $odoo_version https://github.com/OCA/product-attribute.git oca/product-attribute
git submodule add --branch $odoo_version https://github.com/OCA/queue.git oca/queue
git submodule add --branch $odoo_version https://github.com/OCA/server-tools.git oca/server-tools
# git submodule add --branch $odoo_version https://github.com/roboes/odoo-woocommerce-sync.git custom/odoo-woocommerce-sync

git commit -m "Add OCA submodules for Odoo $odoo_version"
```

### Dockerfile & Docker Compose

```.sh
# Create Dockerfile for custom Odoo image with Python dependencies
cat <<'EOF' > "$domain_root_path/domains/$subdomain.$domain/odoo/Dockerfile"
ARG ODOO_VERSION
FROM odoo:${ODOO_VERSION}

USER root

# Mount addons folder during build to install requirements (requires BuildKit)
RUN --mount=type=bind,target=/tmp/addons,source=addons \
    find /tmp/addons -name "requirements.txt" -exec pip3 install --no-cache-dir -r {} \; || true

USER odoo
EOF
```

```.sh
# Create docker-compose.yml
cat <<EOF > "$domain_root_path/domains/$subdomain.$domain/odoo/docker-compose.yml"
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
      - /etc/localtime:/etc/localtime:ro
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

```.sh
# Create .env file
cat <<EOF > "$domain_root_path/domains/$subdomain.$domain/odoo/.env"
# Odoo version
ODOO_VERSION=$odoo_version

# Odoo ports
ODOO_HTTP_PORT=$odoo_http_port
ODOO_LONGPOLLING_PORT=$odoo_longpolling_port

# Odoo data locations
ODOO_DATA_LOCATION=$domain_root_path/domains/$subdomain.$domain/odoo/data
ODOO_CONFIG_LOCATION=$domain_root_path/domains/$subdomain.$domain/odoo/config
ODOO_ADDONS_LOCATION=$domain_root_path/domains/$subdomain.$domain/odoo/addons

# PostgreSQL data location
DB_DATA_LOCATION=$domain_root_path/domains/$subdomain.$domain/odoo/postgres

# Database credentials
DB_HOST=$database_host
DB_PORT=$database_port
DB_USERNAME=$database_username
DB_PASSWORD=$database_password

EOF

# Secure .env file
chmod 600 "$domain_root_path/domains/$subdomain.$domain/odoo/.env"
```

```.sh
# Create config directory
sudo mkdir -p $domain_root_path/domains/$subdomain.$domain/odoo/config

# Generate addons_path from submodules (converts ./oca/repo to /mnt/extra-addons/oca/repo)
cd $domain_root_path/domains/$subdomain.$domain/odoo/addons
ADDONS_PATH=$(find . -mindepth 2 -maxdepth 2 -type d | grep -E "^\./oca/|^\./custom/" | sed 's|^\./|/mnt/extra-addons/|' | tr '\n' ',' | sed 's/,$//')
cd $domain_root_path/domains/$subdomain.$domain/odoo

# Create odoo.conf (addons_path includes OCA submodule paths)
cat <<EOF > "$domain_root_path/domains/$subdomain.$domain/odoo/config/odoo.conf"
[options]
addons_path = /usr/lib/python3/dist-packages/odoo/addons,$ADDONS_PATH
data_dir = /var/lib/odoo
admin_passwd = $odoo_master_password
db_host = $database_host
db_port = $database_port
db_name = $database_name
db_user = $database_username
db_password = $database_password
proxy_mode = True
workers = $odoo_workers
gevent_port = 8072
limit_memory_hard = 2684354560
limit_memory_soft = 2147483648
limit_request = 8192
limit_time_cpu = 600
limit_time_real = 1200
max_cron_threads = 1
without_demo = all

EOF
```

```.sh
# Create data directories
sudo mkdir -p "$domain_root_path/domains/$subdomain.$domain/odoo/data"
sudo mkdir -p "$domain_root_path/domains/$subdomain.$domain/odoo/postgres"

# Get Odoo container UID/GID
odoo_uid=$(docker run --rm odoo:$odoo_version id -u)
odoo_gid=$(docker run --rm odoo:$odoo_version id -g)
echo "Odoo UID: $odoo_uid, GID: $odoo_gid"

# Change ownership (after git operations are complete)
sudo chown -R $odoo_uid:$odoo_gid "$domain_root_path/domains/$subdomain.$domain/odoo/data"
sudo chown -R $odoo_uid:$odoo_gid "$domain_root_path/domains/$subdomain.$domain/odoo/addons"
sudo chown -R $odoo_uid:$odoo_gid "$domain_root_path/domains/$subdomain.$domain/odoo/config"

# Get PostgreSQL container UID
postgres_uid=$(docker run --rm postgres:16 id -u)
postgres_gid=$(docker run --rm postgres:16 id -g)
echo "Postgres UID: $postgres_uid, GID: $postgres_gid"

# Change ownership
sudo chown -R $postgres_uid:$postgres_gid "$domain_root_path/domains/$subdomain.$domain/odoo/postgres"
```

```.sh
# Build custom Odoo image
cd $domain_root_path/domains/$subdomain.$domain/odoo
docker compose build
```

```.sh
# Initialize Odoo database with core modules
cd $domain_root_path/domains/$subdomain.$domain/odoo

docker compose run --rm odoo odoo \
  --config /etc/odoo/odoo.conf \
  --init base,web,account,contacts,delivery,product,sale_management,stock,stock_account \
  --stop-after-init
```

```.sh
# Start containers
cd $domain_root_path/domains/$subdomain.$domain/odoo
docker compose up -d
```

```.sh
# Confirm docker is running
docker ps
```

```.sh
# View logs
# docker logs odoo_server_${system_user}
# docker logs odoo_postgres_${system_user}
```

### Nginx directives

```.txt
server {
    client_max_body_size 512M;
    proxy_buffering off;

    location / {
        proxy_pass http://127.0.0.1:8069;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
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
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    location /websocket {
        proxy_pass http://127.0.0.1:8072;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
    }
}
```

```.sh
# Restart Nginx
sudo systemctl reload nginx
```

### Useful Commands

```.sh
# Stop containers
# cd $domain_root_path/domains/$subdomain.$domain/odoo
# docker compose down

# Update Odoo to latest patch
# docker compose pull
# docker compose build
# docker compose up -d

# Access Odoo shell
# docker exec -it odoo_server_${system_user} odoo shell -d $database_name

# Backup database
# docker exec odoo_postgres_${system_user} pg_dump -U $database_username $database_name > backup.sql
```

### Update OCA Submodules

```.sh
# Update all OCA submodules to latest
cd $domain_root_path/domains/$subdomain.$domain/odoo/addons
git submodule update --remote --merge
git add .
git commit -m "Update OCA submodules"

# Rebuild Docker image (in case requirements.txt changed)
cd $domain_root_path/domains/$subdomain.$domain/odoo
docker compose build
docker compose up -d
```

### Add New OCA Submodule

```.sh
# Example: add a new OCA repo
cd $domain_root_path/domains/$subdomain.$domain/odoo/addons
git submodule add --branch $odoo_version https://github.com/OCA/account-financial-tools.git oca/account-financial-tools
git commit -m "Add OCA account-financial-tools"

# Update odoo.conf to include new path
# Add: /mnt/extra-addons/oca/account-financial-tools

# Rebuild and restart
cd $domain_root_path/domains/$subdomain.$domain/odoo
docker compose build
docker compose up -d
```

## Uninstall

```.sh
# cd $domain_root_path/domains/$subdomain.$domain/odoo

# Stop and remove containers + volumes
# docker compose down -v

# Remove all data
# sudo rm -rf $domain_root_path/domains/$subdomain.$domain/odoo

# Confirm docker is not running
# docker ps
```
