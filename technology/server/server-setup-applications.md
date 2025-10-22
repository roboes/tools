# Debian and Virtualmin Server Setup - Applications

> [!NOTE]
> Last update: 2025-10-22

```.sh
# Settings
domain="website.com"
domain_root_path="/home/$domain"
subdomain="subdomain"
system_user="system_user"
# system_user="www-data:www-data"
```

## [Home Assistant](https://home-assistant.io)

```.sh
# Create Home Assistant directories
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
# https://www.home-assistant.io/installation/alternative/#docker-compose
cat <<EOF > "$domain_root_path/domains/$subdomain.$domain/homeassistant/docker-compose.yml"
services:
  homeassistant:
    container_name: homeassistant_${system_user}
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
# Find your Docker network details
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
# Confirm Home Assistant is running
docker ps
```

```.sh
# Start docker
# sudo docker start homeassistant_${system_user}

# Stop docker
# sudo docker stop homeassistant_${system_user}

# Logs
# sudo docker logs homeassistant_${system_user}
```

##### /etc/nginx/sites-available/subdomain.domain.com.conf

```.nginx
server {
    # Settings
    set $domain website.com;
    set $domain_root_path /home/${domain}/domains/subdomain.${domain}/public_html;
    set $php_socket_id 100000000000000;
    set $php_socket_path unix:/run/php/${php_socket_id}.sock;
    server_name subdomain.website.com www.subdomain.website.com mail.subdomain.website.com webmail.subdomain.website.com admin.subdomain.website.com;
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
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }

}
```

```.sh
# Restart Nginx
sudo systemctl reload nginx
```

```.sh
# HACS
docker exec -it homeassistant_${system_user} bash
```

```.sh
wget -O - https://get.hacs.xyz | bash -
```
