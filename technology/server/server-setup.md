# Debian and Virtualmin Server Setup

> [!NOTE]
> Last update: 2025-03-28

```.sh
# Settings
server_ip="100.00.000.01"
website="website.com"
website_root_path="/home/$website/public_html"
system_user="system_user"
# system_user="www-data:www-data"
database_name="database_name"
```

## Initial setup

```.sh
# Check Debian version
cat /etc/os-release
```

```.sh
# Update packages
sudo apt update && sudo apt upgrade -y && sudo apt dist-upgrade -y && sudo apt autoremove -y && sudo apt clean
```

```.sh
# Change locale

## Check Current Locale Settings
locale

## Reconfigure Locales
sudo dpkg-reconfigure locales

## Update the Environment Variables
nano ~/.bashrc

# Add or update the following line
# export LANG=en_US.UTF-8
```

```.sh
# Install packages
sudo apt-get install curl \
  dnsutils \
  wget
```

## Virtualmin

```.sh
# Installation
wget http://software.virtualmin.com/gpl/scripts/install.sh
chmod a+x install.sh
./install.sh

sudo apt install webmin --install-recommends -y
```

After installation, login to Virtualmin.

### Nginx webserver

- [Configure nginx as default webserver](https://www.virtualmin.com/docs/server-components/configuring-nginx-as-default-webserver/).
- Check if it worked: `Virtualmin` > Choose Virtual Server > `System Settings` > `Features and Plugins` > Ensure that `Nginx Website` and `Nginx SSL Website are enabled`.
- `Virtualmin` > Choose Virtual Server > `System Settings` > `Virtualmin Configuration` > `Configuration category`: `Defaults for new domains` > Set the "Home subdirectory" to `${DOM}`.

### Virtualmin settings

- Disable POP3: `Webmin` > `Servers` > `Dovecot IMAP/POP3 Server` > `Networking and Protocols` > Uncheck `POP3`.

- Fail2Ban: `Webmin` > `Networking` > `Fail2Ban Intrusion Detector` > `Edit Config Files` > `/etc/fail2ban/jail.conf`

```.txt
[DEFAULT]
bantime = 1440m
findtime = 60m
maxretry = 3
```

```.sh
sudo systemctl restart fail2ban
```

- Fail2Ban: > `Webmin` > `Networking` > `Fail2Ban Intrusion Detector`> `Filter Action Jails` > Enable `nginx-http-auth`.

- IP Access Control: `Webmin` > `Webmin` > `Webmin Configuration` > `IP Access Control` > `Allowed IP addresses` > `Only allow from listed addresses` > [IP Access Control](https://www.ipdeny.com/ipblocks/) (download aggregated IP blocks).
- Two-Factor Authentication (2FA):
  - Enable: `Webmin` > `Webmin` > `Usermin Configuration` > `Available Modules` > Enable `Two-Factor Authentication`.
  - Authentication provider: `Webmin` > `Webmin` > `Webmin Configuration` > `Two-Factor Authentication` > `Authentication provider`: `TOTOP Authenticator`.
  - Setup: `Webmin` > `Webmin` > `Webmin Users` > `Two-Factor Authentication`.
- Apps: `Virtualmin` > Choose Virtual Server > `Manage Web Apps` > Install `phpMyAdmin` and `RoundCube`.

### PHP

[Configuring Multiple PHP Versions](https://www.virtualmin.com/docs/server-components/configuring-multiple-php-versions/)

```.sh
# Remove older PHP Versions
php_version_old="8.3"
sudo apt-get purge php${php_version_old} php${php_version_old}-cli php${php_version_old}-fpm php${php_version_old}-common php${php_version_old}-mysql php${php_version_old}-xml php${php_version_old}-opcache php${php_version_old}-curl php${php_version_old}-mbstring
sudo apt-get autoremove
sudo apt-get clean
```

```.sh
php_version_current="8.4"
sudo apt-get install php${php_version_current}-sqlite3
```

### Packages

```.sh
sudo apt-get install libnginx-mod-http-brotli-filter \
  redis
```

### SSH key

```.sh
# Generate the SSH Key Pair
ssh-keygen -t rsa -b 4096 -C "root@$server_ip"

# Add the Public Key to the Authorized Keys
cat /root/.ssh/id_rsa.pub >> /root/.ssh/authorized_keys
chmod 700 /root/.ssh
chmod 600 /root/.ssh/authorized_keys
```

Save the private key (id_rsa) on your local machine

```.sh
# Copy SSH key
# cp "/mnt/c/Users/${USER}/Downloads/id_rsa" ~/.ssh/
# chmod 600 ~/.ssh/id_rsa
```

#### Configure SSH to Use Key-Based Authentication

```.sh
nano /etc/ssh/sshd_config
```

```.txt
PasswordAuthentication no
PubkeyAuthentication yes
PermitRootLogin prohibit-password
AllowUsers root
LoginGraceTime 60
MaxAuthTries 3
ClientAliveInterval 300
```

Restart server.

### Nginx directives

#### Webmin > Servers > Nginx Webserver > Edit Configuration Files

##### /etc/nginx/nginx.conf

```.txt
user www-data;
worker_processes auto;
pid /run/nginx.pid;
error_log /var/log/nginx/error.log;
include /etc/nginx/modules-enabled/*.conf;

events {
    worker_connections 4096;
    multi_accept on;
    use epoll;
}

http {

    ##
    # Basic Settings
    ##

    sendfile on;
    tcp_nopush on;
    types_hash_max_size 2048;

    # server_names_hash_bucket_size 64;
    # server_name_in_redirect off;

    include /etc/nginx/mime.types;
    default_type application/octet-stream;

    ##
    # Logging Settings
    ##

    access_log /var/log/nginx/access.log;

    ##
    # SSL Settings
    ##

    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers on;

    ##
    # Security
    ##

    server_tokens off;

    ##
    # Compression
    ##

    brotli on;
    brotli_comp_level 4;
    brotli_types text/plain text/css text/javascript application/javascript application/json application/xml application/rss+xml;

    gzip on;
    gzip_min_length 1024;
    gzip_comp_level 2;
    gzip_types text/plain text/css text/javascript application/javascript application/json application/xml application/rss+xml;

    ##
    # Proxy Settings (For Backend, Cloudflare, etc.)
    ##

    proxy_http_version 1.1;
    proxy_set_header Connection "keep-alive";
    proxy_cache_revalidate on;
    proxy_buffer_size 64k;
    proxy_buffers 4 128k;
    proxy_busy_buffers_size 256k;
    proxy_read_timeout 30;
    proxy_send_timeout 30;

    fastcgi_buffer_size 256k;
    fastcgi_buffers 8 512k;
    fastcgi_busy_buffers_size 1024k;
    fastcgi_read_timeout 30;

    proxy_buffering on;
    large_client_header_buffers 8 32k;

    keepalive_timeout 30;
    reset_timedout_connection on;
    keepalive_requests 500;

    add_header Vary "Accept-Encoding";
    add_header X-Cache-Status $upstream_cache_status;

    ##
    # Cache
    ##

    fastcgi_cache_lock on;
    fastcgi_cache_lock_timeout 5s;
    fastcgi_cache_background_update on;

    fastcgi_cache_path /var/cache/nginx levels=1:2 keys_zone=MYCACHE:100m inactive=4h max_size=2g use_temp_path=off loader_files=500 loader_sleep=50ms loader_threshold=300ms;
    fastcgi_cache_key "$scheme$request_method$host$request_uri";

    ##
    # Virtual Host Configs
    ##

    include /etc/nginx/conf.d/*.conf;
    include /etc/nginx/sites-enabled/*;
    server_names_hash_bucket_size 128;
}


#mail {
# # See sample authentication script at:
# # http://wiki.nginx.org/ImapAuthenticateWithApachePhpScript
#
# # auth_http localhost/auth.php;
# # pop3_capabilities "TOP" "USER";
# # imap_capabilities "IMAP4rev1" "UIDPLUS";
#
# server {
#  listen     localhost:110;
#  protocol   pop3;
#  proxy      on;
# }
#
# server {
#  listen     localhost:143;
#  protocol   imap;
#  proxy      on;
# }
#}
```

##### /etc/nginx/sites-available/website.com.conf

```.txt
server {
    # Custom nginx config
    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    # Well-known URI
    location ^~ /.well-known/acme-challenge/ {
        allow all;
        default_type "text/plain";
        add_header Content-Type text/plain;
        try_files $uri =404;
    }

    # Security headers
    add_header Content-Security-Policy "connect-src 'self' https://api.wordpress.org https://pagead2.googlesyndication.com https://*.google-analytics.com https://www.google-analytics.com https://www.analytics.google.com https://www.googletagmanager.com https://googleads.g.doubleclick.net https://www.googleadservices.com https://www.google.com https://*.googleapis.com https://www.paypal.com https://www.sandbox.paypal.com https://*.stripe.com; default-src 'self';  font-src 'self' data: https://fonts.gstatic.com; worker-src 'self' blob:; frame-src 'self' https://www.google.com https://www.googletagmanager.com https://td.doubleclick.net https://www.google.com/recaptcha/ https://recaptcha.google.com/recaptcha/ https://www.youtube-nocookie.com https://www.paypal.com https://*.stripe.com; img-src 'self' data: https://ps.w.org https://s.w.org https://t.paypal.com https://www.paypalobjects.com https://www.google-analytics.com https://www.googletagmanager.com https://googleads.g.doubleclick.net https://pagead2.googlesyndication.com https://www.google.com https://*.stripe.com; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://api.cloudflare.com https://static.cloudflareinsights.com https://www.googletagmanager.com https://www.google-analytics.com https://www.gstatic.com https://www.google.com/recaptcha/ https://www.gstatic.com/recaptcha/ https://www.youtube.com https://www.youtube-nocookie.com https://www.paypal.com https://www.paypalobjects.com https://sdk.mercadopago.com https://www.mercadopago.com https://http2.mlstatic.com https://www.googleadservices.com https://www.google.com https://pagead2.googlesyndication.com https://*.stripe.com https://*.googleapis.com; style-src 'self' 'unsafe-inline' https://*.googleapis.com https://www.gstatic.com;" always;
    add_header Permissions-Policy "geolocation=(),midi=(),sync-xhr=(),microphone=(),camera=(),magnetometer=(),gyroscope=(),fullscreen=(self)" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Strict-Transport-Security "max-age=15552000; includeSubDomains; preload" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;

    # Wordfence
    location ~ ^/\.user\.ini {
        deny all;
    }

    location ~ "\.php(/|$)" {
        try_files $uri $fastcgi_script_name =404;
        fastcgi_pass unix:/run/php/174285551812977.sock;
        include fastcgi_params;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;

        # Caching
        fastcgi_cache MYCACHE;
        fastcgi_cache_valid 200 301 302 10m;
        fastcgi_cache_valid 404 1m;
        fastcgi_cache_use_stale error timeout updating;

        # Header cleanup
        more_clear_headers "Cache-Control" "Expires" "Set-Cookie" "Link" "cf-edge-cache";
        add_header Cache-Control "public, s-maxage=3600" always;
        add_header X-FastCGI-Cache $upstream_cache_status;
        add_header CF-Cache-Status $upstream_cache_status always;
    }

    # Cache
    location ~* ^(?!.*phast\.php).*\.(ac3|avi|avif|bmp|bz2|css|cue|dat|doc|docx|dts|eot|exe|flv|gif|gz|htm|html|ico|img|iso|jpeg|jpg|js|mkv|mp3|mp4|mpeg|mpg|ogg|otf|pdf|png|ppt|pptx|qt|rar|rm|rtf|svg|swf|tar|tgz|ttf|txt|wav|woff|woff2|xls|xlsx|zip|webm|webp)$ {
        etag on;
        if_modified_since exact;
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    location ~* \.(xml|json|txt|rss)$ {
        expires 5m;
        add_header Cache-Control "public";
    }

    # Prevent caching of error responses
    error_page 500 502 503 504 @no_cache;

    location @no_cache {
        internal;
        add_header Cache-Control "no-store, no-cache, must-revalidate" always;
        return 500 "Internal Server Error";
    }
}
```

```.sh
# Restart Nginx
sudo systemctl reload nginx
```

### SSL Certificate

```.sh
mkdir -p $website_root_path/.well-known/acme-challenge
chmod -R 755 $website_root_path/.well-known
```

```.sh
sudo certbot certonly --manual --preferred-challenges dns -d autodiscover.$website
```

Copy the generated TXT for `_acme-challenge.autodiscover.$website` value and add it as a DNS `TXT` record with the name `_acme-challenge` in Cloudflare.

```.sh
# Verify the TXT Record
dig TXT _acme-challenge.autodiscover.$website
```

`Virtualmin` > Choose Virtual Server > `Manage Virtual Server` > `Setup SSL Certificate` > `SSL Providers`.

- Enable `Automatically renew certificate`.
- `Send email on renewal` > `Only on failure`.
- `Request Certificate`.

```.sh
# Remove .htaccess
rm $website_root_path/.well-known/acme-challenge/.htaccess
```

### Change default website for server IP address

- `Virtualmin` > Choose Virtual Server > `Web Configuration` > `Website Options` > `Default website for IP address` > `Yes`.

### PHP settings

- `Virtualmin` > Choose Virtual Server > `Web Configuration` > `PHP-FPM Configuration` > `Resource Limits`.
- `Virtualmin` > Choose Virtual Server > `Web Configuration` > `PHP-FPM Configuration` > `Error Logging` > `Error types to display` > `All errors and warnings`.

## WordPress migration

### Database migration

```.sh
# Import .sql
mysql -u "$system_user" -p "$database_name" < $website_root_path/"$database_name".sql

# Delete dataset
rm $website_root_path/"$database_name".sql
```

### Files migration

```.sh
# Extract the contents of the "wordpress_export.zip" file to the $website_root_path folder
unzip "$website_root_path/wordpress_export.zip" "*" -d $website_root_path

# Delete .zip file
rm "$website_root_path/wordpress_export.zip"
```

### Ownership and permission

```.sh
# Change ownership
chown -R "$system_user" $website_root_path

# Change permissions
find $website_root_path -type d -exec chmod 755 {} \;
find $website_root_path -type f -exec chmod 644 {} \;
chmod 600 $website_root_path/wp-config.php
```

### Tools

```.sh
# Delete empty folders recursively
# find /var/www/vhosts/"$website"/httpdocs/wp-content/uploads -type d -empty -delete
```

## Emails migration

```.sh
imapsync --host1 "imap.server1.com" --user1 "email@website.com" --password1 "password-server1" \
  --host2 $server_ip --user2 "email@website.com" --password2 "password-server2" \
  --exclude "Spam"
```

## Server stress test

```.sh
ab -n 10 $website
```
