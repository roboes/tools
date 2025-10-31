# Debian and Virtualmin Server Setup - Applications

> [!NOTE]
> Last update: 2025-10-24

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
http:
  use_x_forwarded_for: true
  trusted_proxies:
    - 127.0.0.1
    - ::1
    - 172.19.0.0/16
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
