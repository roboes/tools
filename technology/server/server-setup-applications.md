# Debian and Virtualmin Server Setup - Applications

> [!NOTE]  
> Last update: 2025-11-30

```.sh
# Settings
domain="website.com"
domain_root_path="/home/$domain"
subdomain="subdomain"
system_user="system_user"
# system_user="www-data:www-data"
postgres_password=$(openssl rand -base64 32 | tr -dc 'A-Za-z0-9')
```

## [Home Assistant](https://home-assistant.io)

```.sh
# Create directories
sudo mkdir -p $domain_root_path/domains/$subdomain.$domain/homeassistant/config
sudo chown -R $system_user:$system_user $domain_root_path/domains/$subdomain.$domain/homeassistant
```

```.sh
# Add the system user to the docker group
sudo usermod -aG docker $system_user

# Verify the user is in the docker group
groups $system_user
```

```.sh
# Create docker-compose.yml - https://www.home-assistant.io/installation/alternative/#docker-compose
cat <<EOF > "$domain_root_path/domains/$subdomain.$domain/homeassistant/docker-compose.yml"
services:
  homeassistant:
    container_name: "homeassistant_${system_user}"
    image: "ghcr.io/home-assistant/home-assistant:stable"
    volumes:
      - "${domain_root_path}/domains/${subdomain}.${domain}/homeassistant/config:/config"
      - /etc/localtime:/etc/localtime:ro
    restart: unless-stopped
    ports:
      - "127.0.0.1:8123:8123"
EOF
```

```.sh
# Find Docker network details
docker network inspect bridge | grep Gateway
docker network inspect bridge | grep Subnet

nano "$domain_root_path/domains/$subdomain.$domain/homeassistant/config/configuration.yaml"
```

```.txt
default_config:

http:
  use_x_forwarded_for: true
  trusted_proxies:
    - 127.0.0.1
    - ::1
    - 172.16.0.0/12
    - 172.23.0.1
```

```.sh
cd $domain_root_path/domains/$subdomain.$domain/homeassistant
sudo -u $system_user docker compose up -d

# Restart docker
# sudo docker compose restart
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

### Nginx

/etc/nginx/sites-available/subdomain.domain.com.conf

```.nginx
server {
    location / {
        proxy_pass http://127.0.0.1:8123;
        proxy_set_header Host $host;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        # Add this for large backups/updates
        client_max_body_size 0;
    }
}
```

```.sh
# Restart Nginx
sudo systemctl reload nginx
```

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

# Check open ports
sudo firewall-cmd --list-ports
```

### Generate client keys

```.sh
# Generate client private key
CLIENT_PRIVATE_KEY=$(wg genkey | tee client1_private.key)

# Generate client public key
CLIENT_PUBLIC_KEY=$(echo $CLIENT_PRIVATE_KEY | wg pubkey | tee client1_public.key)
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
AllowedIPs = 10.6.0.2/32
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
DNS = 1.1.1.1
MTU = 1420

[Peer]
PublicKey = $SERVER_PUBLIC_KEY
Endpoint = vpn.website.com:51820
AllowedIPs = 0.0.0.0/0
PersistentKeepAlive = 25
```

### Test

```.sh
# Monitor ICMP traffic on the physical interface
tcpdump -i eth0 icmp
```
