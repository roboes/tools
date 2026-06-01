# Debian and Virtualmin Server Setup - Applications

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
    volumes:
      - "${installation_path}/homeassistant/config:/config"
      - /etc/localtime:/etc/localtime:ro
      - /run/dbus:/run/dbus:ro
    restart: unless-stopped
    network_mode: host
    depends_on:
      - matter-server

  matter-server:
    container_name: "matter_server_${system_user}"
    image: ghcr.io/home-assistant-libs/python-matter-server:stable
    volumes:
      - "${installation_path}/homeassistant/matter-server:/data"
      - /run/dbus:/run/dbus:ro
    restart: unless-stopped
    network_mode: host

  frigate:
    container_name: "frigate_${system_user}"
    image: ghcr.io/blakeblackshear/frigate:stable
    privileged: true
    restart: unless-stopped
    shm_size: "64mb"
    volumes:
      - "${installation_path}/homeassistant/config:/frigate"
      - /etc/localtime:/etc/localtime:ro
      - /mnt/usb_1/recordings:/media/frigate
    devices:
      - /dev/video10
      - /dev/video11
      - /dev/video12
    ports:
      - "8971:8971"
      - "8554:8554"
      - "8555:8555/tcp"
      - "8555:8555/udp"
    tmpfs:
      - /tmp/cache:size=500000000
    environment:
      - CAMERA_1_NAME=camera_name
      - CAMERA_1_IP=192.168.178.02
      - CAMERA_1_USERNAME=camera_username
      - CAMERA_1_PASSWORD=camera_password

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

# Restart docker
# docker compose down && docker compose up -d
```

```.sh
# Install HACS (Home Assistant Community Store) directly into the running container
docker exec -it "homeassistant_${system_user}" bash -c "wget -O - https://get.hacs.xyz | bash -"

# Restart the compose stack to load HACS components into memory
sudo -u ${system_user} docker compose restart
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

# Logs
# sudo docker logs "homeassistant_${system_user}"
# sudo docker compose logs --tail 50
```

#### Recordings

```.sh
if [ "${installation_target}" = "pi" ]; then
    nano "${installation_path}/homeassistant/config/frigate.yaml"
fi
```

```.yaml
mqtt:
  enabled: false

ffmpeg:
  hwaccel_args: preset-rpi-64-h264

detectors:
  cpu:
    type: cpu
    num_threads: 3

objects:
  track:
    - person

record:
  enabled: true
  retain:
    days: 7
    mode: all
  events:
    retain:
      default: 14
  detections:
    retain:
      days: 10

snapshots:
  enabled: true
  retain:
    default: 14

detect:
  width: 640
  height: 360
  fps: 5

cameras:
  camera1:
    enabled: True
    ffmpeg:
      inputs:
        - path: rtsp://{CAMERA_1_USERNAME}:{CAMERA_1_PASSWORD}@{CAMERA_1_IP}/stream1
          input_args: preset-rtsp-restream
          roles:
            - record
        # Low-res substream → detect (less CPU)
        - path: rtsp://user:pass@192.168.1.x:554/stream2  # substream if cam has one
          roles:
            - detect


```

```.sh
# Create folder for camera stream recordings
mkdir -p /mnt/usb_1/recordings
mkdir -p /mnt/usb_1/recordings/tapo_c200_m0123
```

```.sh
if [ "${installation_target}" = "pi" ]; then
    nano "${installation_path}/homeassistant/recordings_cleanup.sh"
fi
```

```.txt
#!/bin/bash
RECORDINGS_DIR="/mnt/usb_1/recordings"
THRESHOLD=90  # Delete oldest files when disk is 90% full

while [ $(df "$RECORDINGS_DIR" | awk 'NR==2 {print $5}' | tr -d '%') -ge $THRESHOLD ]; do
    oldest=$(find "$RECORDINGS_DIR" -name "*.mp4" -printf "%T+ %p\n" | sort | head -1 | awk '{print $2}')
    [ -z "$oldest" ] && break
    rm "$oldest"
    echo "Deleted: $oldest"
done
```

```.sh
if [ "${installation_target}" = "pi" ]; then
    chmod +x "${installation_path}/homeassistant/recordings_cleanup.sh"
fi
```

```.sh
crontab -e
```

Add:

```.txt
0 * * * * installation_path/homeassistant/recordings_cleanup.sh
```

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

### Settings

To add a Matter device: `ws://172.17.0.1:5580/ws`.

## WireGuard VPN

### Installation

> Notes: No need to create a sub-server (e.g. `vpn.website.com`) in Virtualmin. The VPN runs independently on a DNS-only Cloudflare record.

```.sh
sudo apt install -y wireguard
```

#### Generate server keys

```.sh
# Generate server private key and save it
SERVER_PRIVATE_KEY=$(wg genkey | tee /etc/wireguard/server_private.key)

# Generate server public key from the private key
SERVER_PUBLIC_KEY=$(echo $SERVER_PRIVATE_KEY | wg pubkey | tee /etc/wireguard/server_public.key)
```

> Keys are now stored in `/etc/wireguard/server_private.key` and `/etc/wireguard/server_public.key`

#### Create WireGuard server config

```.sh
# Create wg0.conf with server interface and listen port
cat <<EOF > "/etc/wireguard/wg0.conf"
[Interface]
Address = 10.6.0.1/24
ListenPort = 51820
PrivateKey = $SERVER_PRIVATE_KEY
MTU = 1420
EOF
```

#### Enable IP forwarding

```.sh
# Create a dedicated configuration file for WireGuard forwarding
echo "net.ipv4.ip_forward=1" | sudo tee /etc/sysctl.d/99-wireguard-forwarding.conf > /dev/null

# Load all settings from /etc/sysctl.conf and /etc/sysctl.d/
sudo sysctl --system

# Verify that IP forwarding is enabled
sysctl net.ipv4.ip_forward
# Output should be: net.ipv4.ip_forward = 1
```

#### Configure firewall

```.sh
# Open UDP port 51820 in the "public" zone permanently
sudo firewall-cmd --zone=public --add-port=51820/udp --permanent

# Add the WireGuard interface to the "trusted" zone
sudo firewall-cmd --zone=trusted --add-interface=wg0 --permanent

# Enable masquerading (NAT) for outgoing VPN traffic
sudo firewall-cmd --zone=public --add-masquerade --permanent

# Reload firewall to apply changes
sudo firewall-cmd --reload

# Show active rules
sudo firewall-cmd --list-all
```

### Generate client keys

```.sh
# Generate client private key
CLIENT_PRIVATE_KEY=$(wg genkey | tee /etc/wireguard/client1_private.key)

# Generate client public key
CLIENT_PUBLIC_KEY=$(echo $CLIENT_PRIVATE_KEY | wg pubkey | tee /etc/wireguard/client1_public.key)
```

> Keys are stored in `client1_private.key` and `client1_public.key`

#### Add Client Peer to server config

```.sh
# Append client peer block to server configuration
cat <<EOF >> /etc/wireguard/wg0.conf
# Client 1 configuration
[Peer]
# Public key of client device
PublicKey = $CLIENT_PUBLIC_KEY
# IP assigned to client inside VPN
AllowedIPs = 10.6.0.2/32, 192.168.178.0/24
PersistentKeepAline = 25
EOF
```

#### Start WireGuard

```.sh
# Enable WireGuard to start on boot and start now
sudo systemctl enable --now wg-quick@wg0

# Verify server status
sudo wg show
```

```.sh
echo "Client private key: $CLIENT_PRIVATE_KEY"
echo "Server public key: $SERVER_PUBLIC_KEY"
```

#### DNS

Create a DNS record in Cloudflare:

| Setting      | Value          |
| ------------ | -------------- |
| Type         | A              |
| Name         | vpn            |
| Proxy status | Off (DNS only) |

### Client configuration

```.conf
[Interface]
PrivateKey = $CLIENT_PRIVATE_KEY
Address = 10.6.0.2/32
# DNS = 1.1.1.1
MTU = 1420

[Peer]
PublicKey = $SERVER_PUBLIC_KEY
Endpoint = vpn.website.com:51820
# Routes all traffic through VPN
# AllowedIPs = 0.0.0.0/0
# Routes only home network traffic
AllowedIPs = 10.6.0.0/24
PersistentKeepalive = 25
```

### Test

```.sh
# Find interface name
ip link show

# Test handshake
sudo wg show

# Monitor ICMP traffic on the physical interface
tcpdump -i eth0 icmp
```
