# Home Assistant Installation

> [!NOTE]  
> Last update: 2026-06-01

```.sh
# Settings
domain="website.com"
domain_root_path="/home/$domain"
subdomain="subdomain"
system_user="system_user"
installation_target="vps"
```

```
if [ "${installation_target}" = "pi" ]; then
  installation_path="/home/${system_user}"
else
  installation_path="${domain_root_path}/domains/${subdomain}.${domain}"
fi
```

## [Home Assistant](https://home-assistant.io)

```.sh
if [ "${installation_target}" = "vps" ]; then
    # Create subdomain
    virtualmin create-domain --domain $subdomain.$domain --parent $domain --dir --logrotate --virtualmin-nginx --virtualmin-awstats

    # List domains
    virtualmin list-domains --name-only
fi
```

```.sh
# Create directories
if [ "${installation_target}" = "pi" ]; then
    sudo mkdir -p ${installation_path}/homeassistant
    sudo mkdir -p ${installation_path}/homeassistant/matter-server
    sudo mkdir -p ${installation_path}/homeassistant/mosquitto
    sudo mkdir -p ${installation_path}/homeassistant/mosquitto/config
    sudo mkdir -p ${installation_path}/homeassistant/mosquitto/data
    sudo mkdir -p ${installation_path}/homeassistant/mosquitto/log
    sudo mkdir -p ${installation_path}/homeassistant/frigate
    sudo mkdir -p /mnt/usb_1/frigate
fi

sudo mkdir -p ${installation_path}/homeassistant/config
sudo chown -R $system_user:$system_user ${installation_path}/homeassistant
```

```.sh
# Add the system user to the docker group
sudo usermod -aG docker ${system_user}

# Verify the user is in the docker group
groups ${system_user}
```

```.sh
# Create docker-compose.yml - https://www.home-assistant.io/installation/alternative/#docker-compose
if [ "${installation_target}" = "vps" ]; then
cat <<EOF > "${installation_path}/homeassistant/docker-compose.yml"
services:
  homeassistant:
    container_name: "homeassistant_${system_user}"
    image: "ghcr.io/home-assistant/home-assistant:stable"
    volumes:
      - "${installation_path}/homeassistant/config:/config"
      - /etc/localtime:/etc/localtime:ro
      - /run/dbus:/run/dbus:ro
    restart: unless-stopped
    ports:
      - "127.0.0.1:8123:8123"
EOF

else
cat <<EOF > "${installation_path}/homeassistant/docker-compose.yml"
services:
  homeassistant:
    container_name: "homeassistant_${system_user}"
    image: "ghcr.io/home-assistant/home-assistant:stable"
    privileged: true
    security_opt:
      - apparmor:unconfined
    volumes:
      - "${installation_path}/homeassistant/config:/config"
      - /etc/localtime:/etc/localtime:ro
      - /run/dbus:/run/dbus:ro
    restart: unless-stopped
    network_mode: host
    depends_on:
      - matter-server
    cap_add:
      - NET_ADMIN
      - NET_RAW

  matter-server:
    container_name: "matter_server_${system_user}"
    image: ghcr.io/home-assistant-libs/python-matter-server:stable
    volumes:
      - "${installation_path}/homeassistant/matter-server:/data"
      - /run/dbus:/run/dbus:ro
    restart: unless-stopped
    network_mode: host

  mosquitto:
    container_name: "mosquitto_${system_user}"
    image: eclipse-mosquitto:latest
    restart: unless-stopped
    network_mode: host
    volumes:
      - "${installation_path}/homeassistant/mosquitto/config:/mosquitto/config"
      - "${installation_path}/homeassistant/mosquitto/data:/mosquitto/data"
      - "${installation_path}/homeassistant/mosquitto/log:/mosquitto/log"

  frigate:
    container_name: "frigate_${system_user}"
    image: ghcr.io/blakeblackshear/frigate:stable
    privileged: true
    restart: unless-stopped
    shm_size: "128mb"
    volumes:
      - "${installation_path}/homeassistant/frigate:/config"
      - /etc/localtime:/etc/localtime:ro
      - /mnt/usb_1/frigate:/media/frigate
      - type: tmpfs
        target: /tmp/cache
        tmpfs:
          size: 500000000
    devices:
      - /dev/video10
      - /dev/video11
      - /dev/video12
    network_mode: host
    environment:
      - FRIGATE_CAMERA_1_NAME=camera_name
      - FRIGATE_CAMERA_1_IP=192.168.178.02
      - FRIGATE_CAMERA_1_USERNAME=camera_username
      - FRIGATE_CAMERA_1_PASSWORD=camera_password

EOF
fi
```

```.sh
if [ "${installation_target}" = "vps" ]; then
    # Find Docker network details
    docker network inspect bridge | grep Gateway
    docker network inspect bridge | grep Subnet

    nano "${installation_path}/homeassistant/config/configuration.yaml"
fi
```

```.txt
default_config:

http:
  use_x_forwarded_for: true
  trusted_proxies:
    - 127.0.0.1
    - ::1
    - 172.16.0.0/12
```

```.sh
cd ${installation_path}/homeassistant
sudo -u ${system_user} docker compose up -d
```

```.sh
# Install HACS (Home Assistant Community Store) directly into the running container
docker exec -it "homeassistant_${system_user}" bash -c "wget -O - https://get.hacs.xyz | bash -"

# Restart the compose stack to load HACS components into memory
docker compose down && docker compose up -d --remove-orphans
```

```.sh
# Confirm docker is running
docker ps
```

```.sh
# Start docker
# sudo docker start "homeassistant_${system_user}"

# Stop docker
# sudo docker stop "homeassistant_${system_user}"

# Restart docker
# docker compose down && docker compose up -d --remove-orphans

# Logs
# sudo docker logs "homeassistant_${system_user}"
# sudo docker compose logs --tail 50
```

### Recordings

```.sh
if [ "${installation_target}" = "pi" ]; then
cat <<EOF > "${installation_path}/homeassistant/mosquitto/config/mosquitto.conf"
listener 1883
allow_anonymous true
persistence true
persistence_location /mosquitto/data/
EOF
fi
```

```.sh
if [ "${installation_target}" = "pi" ]; then
cat <<EOF > "${installation_path}/homeassistant/frigate/config.yml"
mqtt:
  enabled: true
  host: localhost
  port: 1883

detectors:
  cpu:
    type: cpu
    num_threads: 3

ffmpeg:
  hwaccel_args: preset-rpi-64-h264

detect:
  width: 640
  height: 360
  fps: 5

objects:
  track:
    - person
    - bird
    - cat
    - dog
    - bicycle
    - car
    - motorcycle
  filters:
    person:
      min_score: 0.65
      threshold: 0.75
    dog:
      min_score: 0.5
    bird:
      min_score: 0.5

record:
  enabled: true
  continuous:
    days: 7
  motion:
    days: 7
  alerts:
    retain:
      days: 14
      mode: all
  detections:
    retain:
      days: 10
      mode: all

snapshots:
  enabled: true
  bounding_box: true
  retain:
    default: 14

cameras:
  camera_1:
    enabled: True
    record:
      enabled: true
    ffmpeg:
      inputs:
        - path: rtsp://{FRIGATE_CAMERA_1_USERNAME}:{FRIGATE_CAMERA_1_PASSWORD}@{FRIGATE_CAMERA_1_IP}/stream1
          roles:
            - record
        - path: rtsp://{FRIGATE_CAMERA_1_USERNAME}:{FRIGATE_CAMERA_1_PASSWORD}@{FRIGATE_CAMERA_1_IP}/stream2
          roles:
            - detect
EOF
fi
```

### Home Assistant Community Store (HACS)

- [Tapo: Cameras Control](https://github.com/jurajnyiri/homeassistant-tapo-control).
- [Frigate](https://github.com/blakeblackshear/frigate-hass-integration).

### Nginx

/etc/nginx/sites-available/subdomain.domain.com.conf

```.nginx
server {
    # ...

    location / {
        proxy_pass http://127.0.0.1:8123;
        proxy_set_header Host $host;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        client_max_body_size 0;

        add_header Content-Security-Policy "";
    }

}
```

```.sh
# Restart Nginx
nginx -t && systemctl reload nginx
```
