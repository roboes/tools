# Immich

> [!NOTE]  
> Last update: 2026-01-03

## Installation

```sh
# Settings
domain="website.com"
domain_root_path="/home/${domain}"
subdomain="photos"
system_user="website"
# system_user="www-data:www-data"
postgres_password=$(openssl rand -base64 32 | tr -dc 'A-Za-z0-9')
```

```sh
# Create directories
sudo mkdir -p ${domain_root_path}/domains/${subdomain}.${domain}/immich/library
sudo mkdir -p ${domain_root_path}/domains/${subdomain}.${domain}/immich/postgres
sudo chown -R ${system_user}:${system_user} ${domain_root_path}/domains/${subdomain}.${domain}/immich
```

```sh
# Add the system user to the docker group
sudo usermod -aG docker ${system_user}

# Verify the user is in the docker group
groups ${system_user}
```

```sh
# Create docker-compose.yml - https://github.com/immich-app/immich/releases/latest/download/docker-compose.yml
# Immich Upload Optimizer - https://github.com/miguelangel-nubla/immich-upload-optimizer
cat <<EOF > "${domain_root_path}/domains/${subdomain}.${domain}/immich/docker-compose.yml"
name: immich

services:
  immich-upload-optimizer:
    container_name: "immich_upload_optimizer_${system_user}"
    image: ghcr.io/miguelangel-nubla/immich-upload-optimizer:latest
    ports:
      - "2283:2283"
    environment:
      - IUO_UPSTREAM=http://immich-server:2283
      - IUO_TASKS_FILE=/app/config/tasks.yaml
      - IUO_TIMEOUT=3600
    volumes:
      - ./tasks.yaml:/app/config/tasks.yaml:ro
    depends_on:
      - immich-server
    restart: always

  immich-server:
    container_name: "immich_server_${system_user}"
    image: ghcr.io/immich-app/immich-server:\${IMMICH_VERSION:-release}
    volumes:
      - \${UPLOAD_LOCATION}:/data
      - /etc/localtime:/etc/localtime:ro
    env_file:
      - .env
    environment:
      - IMMICH_HOST=0.0.0.0
      - IMMICH_PORT=2283
    depends_on:
      - redis
      - database
    restart: always
    healthcheck:
      disable: false

  immich-machine-learning:
    container_name: "immich_machine_learning_${system_user}"
    image: ghcr.io/immich-app/immich-machine-learning:\${IMMICH_VERSION:-release}
    volumes:
      - model-cache:/cache
    env_file:
      - .env
    restart: always
    healthcheck:
      disable: false

  redis:
    container_name: "immich_redis_${system_user}"
    image: docker.io/valkey/valkey:8-bookworm
    healthcheck:
      test: redis-cli ping || exit 1
    restart: always

  database:
    container_name: "immich_postgres_${system_user}"
    image: ghcr.io/immich-app/postgres:14-vectorchord0.4.3-pgvectors0.2.0
    environment:
      POSTGRES_PASSWORD: \${DB_PASSWORD}
      POSTGRES_USER: \${DB_USERNAME}
      POSTGRES_DB: \${DB_DATABASE_NAME}
      POSTGRES_INITDB_ARGS: '--data-checksums'
      DB_STORAGE_TYPE: 'SSD'
    volumes:
      - \${DB_DATA_LOCATION}:/var/lib/postgresql/data
    shm_size: 128mb
    restart: always

volumes:
  model-cache:

EOF
```

```sh
# Create .env file - https://docs.immich.app/install/docker-compose/
cat <<EOF > "${domain_root_path}/domains/${subdomain}.${domain}/immich/.env"
# The location where your uploaded files are stored
UPLOAD_LOCATION=${domain_root_path}/domains/${subdomain}.${domain}/immich/library

# The location where your database files are stored. Network shares are not supported for the database
DB_DATA_LOCATION=${domain_root_path}/domains/${subdomain}.${domain}/immich/postgres

# To set a timezone, uncomment the next line and change Etc/UTC to a TZ identifier from this list: https://en.wikipedia.org/wiki/List_of_tz_database_time_zones#List
# TZ=Etc/UTC

# Immich version
IMMICH_VERSION=release

# Connection secret for postgres. You should change it to a random password
# Please use only the characters A-Za-z0-9, without special characters or spaces
DB_PASSWORD=${postgres_password}

# The values below this line do not need to be changed
###################################################################################
DB_USERNAME=postgres
DB_DATABASE_NAME=immich

EOF
```

Original (Lossless/Preservation):

```sh
cat <<EOF > "${domain_root_path}/domains/${subdomain}.${domain}/immich/tasks.yaml"
tasks:
  # JPEG → JXL (lossless JPEG preservation)
  - name: images-jpeg-to-jxl
    command: cjxl --lossless_jpeg=1 {{.folder}}/{{.name}}.{{.extension}} {{.folder}}/{{.name}}-new.jxl && rm {{.folder}}/{{.name}}.{{.extension}}
    extensions:
      - jpg
      - jpeg

  # PNG → JXL (lossless with distance 0)
  - name: images-png-to-jxl
    command: convert {{.folder}}/{{.name}}.{{.extension}} {{.folder}}/{{.name}}-clean.png && cjxl -d 0 {{.folder}}/{{.name}}-clean.png {{.folder}}/{{.name}}-new.jxl && rm {{.folder}}/{{.name}}.{{.extension}} {{.folder}}/{{.name}}-clean.png
    extensions:
      - png

  # Other image formats → JXL (lossless)
  - name: images-misc-to-jxl
    command: cjxl {{.folder}}/{{.name}}.{{.extension}} {{.folder}}/{{.name}}-new.jxl && rm {{.folder}}/{{.name}}.{{.extension}}
    extensions:
      - pgx
      - pam
      - pnm
      - pgm
      - ppm
      - pfm
      - gif
      - exr

  # Lossless optimization for other formats
  - name: images-misc-to-caesium
    command: caesiumclt --keep-dates --exif --quality=0 --output={{.folder}} {{.folder}}/{{.name}}.{{.extension}}
    extensions:
      - tiff
      - tif
      - webp

  # Video compression with HandBrake
  - name: video-compress-h264-1080p
    command: HandBrakeCLI --preset "Fast 1080p30" --encoder x264 --keep-display-aspect -i {{.folder}}/{{.name}}.{{.extension}} -o {{.folder}}/{{.name}}-new.mkv && rm {{.folder}}/{{.name}}.{{.extension}}
    extensions:
      - 3gp
      - 3gpp
      - avi
      - flv
      - m4v
      - mkv
      - mts
      - m2ts
      - m2t
      - mp4
      - insv
      - mpg
      - mpe
      - mpeg
      - mov
      - webm
      - wmv

  # Passthrough formats
  - name: passthrough-formats
    command: ""
    extensions:
      - avif
      - bmp
      - heic
      - heif
      - insp
      - jxl
      - psd
      - raw
      - rw2
      - svg

EOF
```

Alternative (Aggressive Storage Saving):

```sh
cat <<EOF > "${domain_root_path}/domains/${subdomain}.${domain}/immich/tasks.yaml"
tasks:
  # Aggressive Image Reduction: Resize to 1000px + Lossy JXL (-d 2.5)
  - name: images-resize-to-jxl
    command: convert {{.folder}}/{{.name}}.{{.extension}} -resize 1000x1000\> /tmp/{{.name}}-tmp.png && cjxl /tmp/{{.name}}-tmp.png {{.folder}}/{{.name}}-new.jxl -d 2.5 --effort 7 && rm {{.folder}}/{{.name}}.{{.extension}} /tmp/{{.name}}-tmp.png
    extensions:
      - jpg
      - jpeg
      - png
      - tiff
      - tif
      - webp
      - bmp
      - gif
      - heic
      - heif
      - pgx
      - pam
      - pnm
      - pgm
      - ppm
      - pfm
      - exr

  # Aggressive Video Reduction: 720p + x265 + Original Audio (with fallback)
  - name: video-compress-hevc-720p
    command: HandBrakeCLI --preset "H.265 MKV 720p30" --encoder-preset faster --quality 26 --aencoder copy --audio-fallback av_aac --all-subtitles -i {{.folder}}/{{.name}}.{{.extension}} -o {{.folder}}/{{.name}}-new.mkv && rm {{.folder}}/{{.name}}.{{.extension}}
    extensions:
      - 3gp
      - 3gpp
      - avi
      - flv
      - insv
      - m2t
      - m2ts
      - m4v
      - mkv
      - mov
      - mp4
      - mpe
      - mpeg
      - mpg
      - mts
      - webm
      - wmv

  # Passthrough formats
  - name: passthrough-formats
    command: ""
    extensions:
      - avif
      - insp
      - jxl
      - psd
      - raw
      - rw2
      - svg

EOF
```

```sh
cd ${domain_root_path}/domains/${subdomain}.${domain}/immich
sudo -u ${system_user} docker compose up -d

# Restart docker
# sudo docker compose restart
```

```sh
# Confirm docker is running
docker ps

# Logs
# docker compose logs -f --tail 100
```

Storage Template:

```txt
{{y}}/{{y}}-{{MM}}-{{dd}}, {{HH}}.{{mm}}.{{ss}}.{{SSS}}_{{assetIdShort}}
```

## Cloudflare Zero Trust

Cloudflare → `Zero Trust`.

### Service Token

`Access controls` → `Service credentials` → `Create Service Token`:

- `Service token name`: `Immich Mobile`.
- `Service Token Duration`: `Non-expiring`.

### Policies

`Access controls` → `Policies` → `Add a policy`:

- Immich: `Policy name`: `Immich`. `Action`: `Allow`. `Session duration`: `Same as application session duration`. `Policy rules` → `Include`: `Selector is...`: `Emails`.
- Immich Mobile: `Policy name`: `Immich Mobile`. `Action`: `Bypass`. `Session duration`: `Same as application session duration`. `Policy rules` → `Include`: `Selector is...`: `Service Token`, `Value`: `Immich Mobile`.
- ACME Challenge Passthrough: `Policy name`: `ACME Challenge Passthrough`. `Action`: `Bypass`. `Session duration`: `Same as application session duration`. `Policy rules` → `Include`: `Everyone`.

### Applications

`Access controls` → `Applications` → `Create new application` → `Self-hosted and private` → `Public DNS` → `Continue with Self-hosted and private`:

- Immich: `Application name`: `Immich`. `Session Duration`: `1 month`. `Public hostname`: `photos.website.com`. `Access policies`: `Select existing policies`: `Immich`, `Immich Mobile`.
- Immich ACME Challenge Passthrough: `Application name`: `Immich ACME Challenge Passthrough`. `Session Duration`: `24 hours`. `Public hostname`: `photos.website.com/.well-known/acme-challenge/*`. `Access policies`: `Select existing policies`: `ACME Challenge Passthrough`.

### Security

Cloudflare → Website → `Security` → `Security rules`.

1. Immich Passthrough

- `Rule name`: `Immich Passthrough`.
- `Expression`: `(http.request.headers["cf-access-client-id"][0] eq "CF-ACCESS-CLIENT-ID")`.
- `Choose action`: `Skip` (select all `WAF components to skip`).

### Cloudflare Caching

Cloudflare → Website → `Caching` → `Cache Rules`.

1. Cache Bypass

- `Rule name`: `Cache Bypass - Immich`.
- `If incoming requests match...`: `(starts_with(http.host, "photos."))`
- `Then...`: `Bypass cache`.
- `Browser TTL`: `Respect origin TTL`.
- `Place at`: `Last`.

### Immich Mobile App

Add both `CF-Access-Client-Id` and `CF-Access-Client-Secret` to the Immich mobile app.

---

## Nginx Directives

### /etc/nginx/sites-available/photos.website.com.conf

```nginx
server {
    # ...

    client_max_body_size 50000M;

    ## Main application entry point
    location / {
        proxy_pass http://127.0.0.1:2283;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        # WebSocket support
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";

        # Timeouts
        client_body_timeout 1200s;
        proxy_connect_timeout 1200s;
        proxy_read_timeout 1200s;
        proxy_send_timeout 1200s;

        # Disable buffering so the client stays "connected"
        proxy_request_buffering off;
        proxy_buffering off;
    }

}
```

```sh
# Restart Nginx
nginx -t && systemctl reload nginx
```

### Bulk Upload

```sh
# (Server) Install Immich CLI
sudo npm i -g @immich/cli

# (Local machine, Terminal 1) Open SSH tunnel
sshpass -P "passphrase" -p "passphrase" -v ssh -i "~/.ssh/id_ed25519" -o ProxyCommand="cloudflared access ssh --hostname ssh.website.com" "sysadmin@ssh.website.com" -L 2283:localhost:2283 -N

# (Local machine, Terminal 2) Login
immich login http://localhost:2283/api API_KEY

# (Local machine, Terminal 2) Bulk Upload
npx @immich/cli@latest upload --recursive "./"
```

## Update

```sh
cd ${domain_root_path}/domains/${subdomain}.${domain}/immich
```

```sh
# Stop Immich
docker compose down

# Pull the latest Immich images
docker compose pull

# Restart with the new version
docker compose up -d
```

```sh
# Clean up old images
docker image prune -f
```
