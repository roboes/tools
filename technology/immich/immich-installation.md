# Immich Installation

> [!NOTE]  
> Last update: 2025-12-29

```.sh
# Settings
domain="website.com"
domain_root_path="/home/$domain"
subdomain="photos"
system_user="website"
# system_user="www-data:www-data"
postgres_password=$(openssl rand -base64 32 | tr -dc 'A-Za-z0-9')
```

## [Immich](https://immich.app)

```.sh
# Create directories
sudo mkdir -p $domain_root_path/domains/$subdomain.$domain/immich/library
sudo mkdir -p $domain_root_path/domains/$subdomain.$domain/immich/postgres
sudo chown -R $system_user:$system_user $domain_root_path/domains/$subdomain.$domain/immich
```

```.sh
# Add the system user to the docker group
sudo usermod -aG docker $system_user

# Verify the user is in the docker group
groups $system_user
```

```.sh
# Create docker-compose.yml - https://github.com/immich-app/immich/releases/latest/download/docker-compose.yml
# Immich Upload Optimizer - https://github.com/miguelangel-nubla/immich-upload-optimizer
cat <<EOF > "$domain_root_path/domains/$subdomain.$domain/immich/docker-compose.yml"
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

  immich-server:
    container_name: "immich_server_${system_user}"
    image: ghcr.io/immich-app/immich-server:${IMMICH_VERSION:-release}
    # extends:
    #   file: hwaccel.transcoding.yml
    #   service: cpu # set to one of [nvenc, quicksync, rkmpp, vaapi, vaapi-wsl] for accelerated transcoding
    volumes:
      # Do not edit the next line. If you want to change the media storage location on your system, edit the value of UPLOAD_LOCATION in the .env file
      - \${UPLOAD_LOCATION}:/data
      - /etc/localtime:/etc/localtime:ro
    env_file:
      - .env
    environment:
      - IMMICH_HOST=0.0.0.0
      - IMMICH_PORT=2283
    # ports:
      # - '127.0.0.1:2283:2283'
    depends_on:
      - redis
      - database
    restart: always
    healthcheck:
      disable: false

  immich-machine-learning:
    container_name: "immich_machine_learning_${system_user}"
    # For hardware acceleration, add one of -[armnn, cuda, rocm, openvino, rknn] to the image tag.
    # Example tag: \${IMMICH_VERSION:-release}-cuda
    image: ghcr.io/immich-app/immich-machine-learning:\${IMMICH_VERSION:-release}
    # extends: # uncomment this section for hardware acceleration - see https://docs.immich.app/features/ml-hardware-acceleration
    #   file: hwaccel.ml.yml
    #   service: cpu # set to one of [armnn, cuda, rocm, openvino, openvino-wsl, rknn] for accelerated inference - use the `-wsl` version for WSL2 where applicable
    volumes:
      - model-cache:/cache
    env_file:
      - .env
    restart: always
    healthcheck:
      disable: false

  redis:
    container_name: "immich_redis_${system_user}"
    image: docker.io/valkey/valkey:8-bookworm@sha256:fea8b3e67b15729d4bb70589eb03367bab9ad1ee89c876f54327fc7c6e618571
    healthcheck:
      test: redis-cli ping || exit 1
    restart: always

  database:
    container_name: "immich_postgres_${system_user}"
    image: ghcr.io/immich-app/postgres:14-vectorchord0.4.3-pgvectors0.2.0@sha256:bcf63357191b76a916ae5eb93464d65c07511da41e3bf7a8416db519b40b1c23
    environment:
      POSTGRES_PASSWORD: \${DB_PASSWORD}
      POSTGRES_USER: \${DB_USERNAME}
      POSTGRES_DB: \${DB_DATABASE_NAME}
      POSTGRES_INITDB_ARGS: '--data-checksums'
      # Uncomment the DB_STORAGE_TYPE: 'HDD' var if your database isn't stored on SSDs
      # DB_STORAGE_TYPE: 'HDD'
    volumes:
      # Do not edit the next line. If you want to change the database storage location on your system, edit the value of DB_DATA_LOCATION in the .env file
      - \${DB_DATA_LOCATION}:/var/lib/postgresql/data
    shm_size: 128mb
    restart: always

volumes:
  model-cache:

EOF
```

```.sh
# Create .env file - https://docs.immich.app/install/docker-compose/
cat <<EOF > "$domain_root_path/domains/$subdomain.$domain/immich/.env"
# The location where your uploaded files are stored
UPLOAD_LOCATION=$domain_root_path/domains/$subdomain.$domain/immich/library

# The location where your database files are stored. Network shares are not supported for the database
DB_DATA_LOCATION=$domain_root_path/domains/$subdomain.$domain/immich/postgres

# To set a timezone, uncomment the next line and change Etc/UTC to a TZ identifier from this list: https://en.wikipedia.org/wiki/List_of_tz_database_time_zones#List
# TZ=Etc/UTC

# The Immich version to use. You can pin this to a specific version like "v1.71.0"
IMMICH_VERSION=release

# Connection secret for postgres. You should change it to a random password
# Please use only the characters `A-Za-z0-9`, without special characters or spaces
DB_PASSWORD=$postgres_password

# The values below this line do not need to be changed
###################################################################################
DB_USERNAME=postgres
DB_DATABASE_NAME=immich

EOF
```

```.sh
cat <<EOF > "$domain_root_path/domains/$subdomain.$domain/immich/tasks.yaml"
tasks:
  # JPEG → JXL (lossless JPEG preservation)
  - name: jpeg2jxl-lossless
    command: cjxl --lossless_jpeg=1 {{.folder}}/{{.name}}.{{.extension}} {{.folder}}/{{.name}}-new.jxl && rm {{.folder}}/{{.name}}.{{.extension}}
    extensions:
      - jpg
      - jpeg

  # PNG → JXL (lossless with distance 0)
  - name: png2jxl-lossless
    command: convert {{.folder}}/{{.name}}.{{.extension}} {{.folder}}/{{.name}}-clean.png && cjxl -d 0 {{.folder}}/{{.name}}-clean.png {{.folder}}/{{.name}}-new.jxl && rm {{.folder}}/{{.name}}.{{.extension}} {{.folder}}/{{.name}}-clean.png
    extensions:
      - png

  # Other image formats → JXL (lossless)
  - name: image2jxl-lossless
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
  - name: caesium-lossless
    command: caesiumclt --keep-dates --exif --quality=0 --output={{.folder}} {{.folder}}/{{.name}}.{{.extension}}
    extensions:
      - tiff
      - tif
      - webp

  # Video compression with HandBrake
  - name: handbrake-video
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

```.sh
cd $domain_root_path/domains/$subdomain.$domain/immich
sudo -u $system_user docker compose up -d

# Restart docker
# sudo docker compose restart
```

```.sh
# Confirm docker is running
docker ps
```

Storage Template:

```.txt
{{y}}/{{y}}-{{MM}}-{{dd}}, {{HH}}.{{mm}}.{{ss}}.{{SSS}}_{{assetIdShort}}
```

### Cloudflare Zero Trust

Cloudflare → `Zero Trust`.

#### Service Token

`Access` → `Service auth` → `Create Service Token`. `Service token name`: `Immich Mobile Access`. `Service Token Duration`: `Non-expiring`.

#### Policy

`Access` → `Policies` → `Add a policy`.

`Policy name`: `Immich Mobile App`. `Action`: `Bypass`. `Session duration`: `Same as application session timeout`.

`Add rules` → `Include`. `Selector`: `Service Token`. `Value`: `Immich Mobile Access`.

Then, add the newly created policy to your Immich Cloudflare Zero Trust application.

#### Immich Mobile App

Add both `CF-Access-Client-Id` and `CF-Access-Client-Secret` to the Immich mobile app.

## Nginx

/etc/nginx/sites-available/subdomain.domain.com.conf

```.nginx
server {
    # Settings
    set $domain website.com;
    set $subdomain subdomain;
    set $domain_root_path /home/${domain}/domains/${subdomain}.${domain}/public_html;
    set $php_socket_id 100000000000000;
    set $php_socket_path unix:/run/php/${php_socket_id}.sock;
    server_name subdomain.website.com;
    listen 100.00.000.01;
    listen 100.00.000.01:443 ssl;
    listen [1000:0000:0000:0000:0000:0000:0000:0000];
    listen [1000:0000:0000:0000:0000:0000:0000:0000]:443 ssl;
    ssl_certificate /etc/ssl/virtualmin/100000000000000/ssl.cert;
    ssl_certificate_key /etc/ssl/virtualmin/100000000000000/ssl.key;


    # Main Web Root Setup
    root ${domain_root_path};
    index index.php index.htm index.html;

    # Logging
    access_log /var/log/virtualmin/${domain}_access_log;
    error_log /var/log/virtualmin/${domain}_error_log warn;


    # Rewrites and Redirects

    ## Admin & Webmail redirects
    if ($host = webmail.${domain}) {
        rewrite "^/(.*)$" "https://${domain}:20000/$1" redirect;
    }
    if ($host = admin.${domain}) {
        rewrite "^/(.*)$" "https://${domain}:10000/$1" redirect;
    }

    ## AWStats CGI rewrite
    rewrite /awstats/awstats.pl /cgi-bin/awstats.pl;

    ## Well-known and ACME challenges for SSL certificate renewal
    location ^~ /.well-known/acme-challenge/ {
        allow all;
        default_type "text/plain";
        add_header Content-Type text/plain;
        try_files $uri =404;
    }

    location ^~ /.well-known/ {
        try_files $uri /;
    }

    # Location Blocks - General & Security

    ## Block access to sensitive files
    location ~ ^/\.user\.ini {
        deny all;
    }

    location / {
        proxy_pass http://127.0.0.1:8123;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        # WebSocket support
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";

        # Timeouts
        proxy_connect_timeout 300s;
        proxy_send_timeout 300s;
        proxy_read_timeout 300s;
    }

}
```

```.sh
# Restart Nginx
sudo systemctl reload nginx
```
