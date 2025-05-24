# Debian and Virtualmin Server Setup

> [!NOTE]
> Last update: 2025-05-24

```.sh
# Settings
server_ip="100.00.000.01"
domain="website.com"
domain_root_path="/home/$domain/public_html"
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

- Timezone: `Webmin` > `Hardware` > `System Time` > `Change Timezone`.

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
sudo apt-get install htop \
  libnginx-mod-http-brotli-filter \
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
# error_log /var/log/nginx/error.log debug;
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
    proxy_buffering on;
    proxy_buffer_size 16k;
    proxy_buffers 8 16k;
    proxy_busy_buffers_size 32k;
    proxy_read_timeout 30;
    proxy_send_timeout 30;

    # FastCGI core configuration
    fastcgi_read_timeout 60s;
    fastcgi_send_timeout 60s;

    # FastCGI buffers
    fastcgi_buffer_size 16k;
    fastcgi_buffers 4 16k;
    fastcgi_busy_buffers_size 48k;
    fastcgi_temp_file_write_size 64k;

    client_max_body_size 50M;

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

##### /etc/nginx/sites-available/domain.com.conf

```.txt
server {
    # Settings
    set $domain website.com;
    set $domain_root_path /home/${domain}/public_html;
    set $php_socket_id 100000000000000;
    set $php_socket_path unix:/run/php/${php_socket_id}.sock;
    server_name website.com www.website.com mail.website.com webmail.website.com admin.website.com;
    listen 100.00.000.01;
    listen 100.00.000.01:443 ssl;
    listen [1000:0000:0000:0000:0000:0000:0000:0000];
    listen [1000:0000:0000:0000:0000:0000:0000:0000]:443 ssl;
    ssl_certificate /etc/ssl/virtualmin/100000000000000/ssl.cert;
    ssl_certificate_key /etc/ssl/virtualmin/100000000000000/ssl.key;

    root ${domain_root_path};
    index index.php index.htm index.html;
    access_log /var/log/virtualmin/${domain}_access_log;
    error_log /var/log/virtualmin/${domain}_error_log warn;
    fastcgi_param GATEWAY_INTERFACE CGI/1.1;
    fastcgi_param SERVER_SOFTWARE nginx;
    fastcgi_param QUERY_STRING $query_string;
    fastcgi_param REQUEST_METHOD $request_method;
    fastcgi_param CONTENT_TYPE $content_type;
    fastcgi_param CONTENT_LENGTH $content_length;
    fastcgi_param SCRIPT_FILENAME "${domain_root_path}$fastcgi_script_name";
    fastcgi_param SCRIPT_NAME $fastcgi_script_name;
    fastcgi_param REQUEST_URI $request_uri;
    fastcgi_param DOCUMENT_URI $document_uri;
    fastcgi_param DOCUMENT_ROOT ${domain_root_path};
    fastcgi_param SERVER_PROTOCOL $server_protocol;
    fastcgi_param REMOTE_ADDR $remote_addr;
    fastcgi_param REMOTE_PORT $remote_port;
    fastcgi_param SERVER_ADDR $server_addr;
    fastcgi_param SERVER_PORT $server_port;
    fastcgi_param SERVER_NAME $server_name;
    fastcgi_param PATH_INFO $fastcgi_path_info;
    fastcgi_param HTTPS $https;
    location ^~ /.well-known/ {
        try_files $uri /;
    }
    fastcgi_split_path_info "^(.+\.php)(/.+)$";
    if ($host = webmail.${domain}) {
        rewrite "^/(.*)$" "https://${domain}:20000/$1" redirect;
    }
    if ($host = admin.${domain}) {
        rewrite "^/(.*)$" "https://${domain}:10000/$1" redirect;
    }
    rewrite /awstats/awstats.pl /cgi-bin/awstats.pl;


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
    add_header Content-Security-Policy "default-src 'self'; connect-src 'self' https://api.wordpress.org https://google.com https://pagead2.googlesyndication.com https://*.google-analytics.com https://analytics.google.com https://www.googletagmanager.com https://googleads.g.doubleclick.net https://www.googleadservices.com https://*.googleapis.com https://www.paypal.com https://www.sandbox.paypal.com https://*.stripe.com https://*.mercadopago.com https://*.mercadolibre.com https://api.mercadolibre.com; font-src 'self' data: https://fonts.gstatic.com; worker-src 'self' blob:; frame-src 'self' https://www.google.com https://www.googletagmanager.com https://td.doubleclick.net https://recaptcha.google.com https://www.youtube-nocookie.com https://www.paypal.com https://*.stripe.com https://www.mercadolibre.com https://api-static.mercadopago.com; img-src 'self' data: https://ps.w.org https://s.w.org https://t.paypal.com https://www.paypalobjects.com https://www.google.com https://www.google.de https://www.google-analytics.com https://www.googletagmanager.com https://googleads.g.doubleclick.net https://pagead2.googlesyndication.com https://*.stripe.com https://*.mercadopago.com https://*.mercadolibre.com https://http2.mlstatic.com; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://*.cloudflare.com https://static.cloudflareinsights.com https://www.google.com https://www.googletagmanager.com https://www.google-analytics.com https://www.gstatic.com https://googleads.g.doubleclick.net https://www.youtube.com https://www.youtube-nocookie.com https://www.paypal.com https://www.paypalobjects.com https://*.mercadopago.com https://http2.mlstatic.com https://www.googleadservices.com https://pagead2.googlesyndication.com https://*.stripe.com https://*.googleapis.com; style-src 'self' 'unsafe-inline' https://*.googleapis.com https://www.gstatic.com https://http2.mlstatic.com;" always;
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

        # FastCGI core configuration
        fastcgi_pass ${php_socket_path};
        include fastcgi_params;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_intercept_errors on;
        fastcgi_connect_timeout 30s;
        fastcgi_read_timeout 30s;
        fastcgi_send_timeout 30s;

        # FastCGI buffers
        fastcgi_buffer_size 16k;
        fastcgi_buffers 4 16k;
        fastcgi_busy_buffers_size 48k;
        fastcgi_temp_file_write_size 64k;

        # FastCGI security and header handling
        fastcgi_hide_header 'X-Powered-By';
        fastcgi_param HTTP_PROXY "";

        # FastCGI caching
        set $skip_cache 0;

        if ($request_method ~* "DELETE|POST|PUT") {
            set $skip_cache 1;
        }

        if ($http_cookie ~* "PHPSESSID") {
            set $skip_cache 1;
        }

        if ($request_uri ~* "/wp-admin/|/wp-login\.php|/wp-cron\.php|/wp-json/|/wc-api/|/admin-ajax\.php") {
            set $skip_cache 1;
        }

        if ($http_cookie ~* "wordpress_logged_in_|wordpress_sec_|wp-settings-|wp-settings-time-") {
            set $skip_cache 1;
        }

        if ($http_cookie ~* "woocommerce_|wp_woocommerce_session_") {
             set $skip_cache 1;
        }

        fastcgi_cache MYCACHE;
        fastcgi_cache_valid 200 301 302 1h;
        fastcgi_cache_valid 404 1m;
        fastcgi_cache_use_stale error timeout updating;

        fastcgi_cache_bypass $skip_cache;
        fastcgi_no_cache $skip_cache;

        # FastCGI cache headers and cleanup
        add_header X-Nginx-Cache-Status $upstream_cache_status always;
    }

    # Caching: static assets
    location ~* ^(?!.*phast\.php).*\.(ac3|avi|avif|bmp|bz2|css|cue|dat|doc|docx|dts|eot|exe|flv|gif|gz|htm|html|ico|img|iso|jpeg|jpg|js|mkv|mp3|mp4|mpeg|mpg|ogg|otf|pdf|png|ppt|pptx|qt|rar|rm|rtf|svg|swf|tar|tgz|ttf|wav|woff|woff2|zip|webm|webp)$ {
        etag on;
        if_modified_since exact;
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    # Caching: feeds and text files
    location ~* \.(csv|json|rss|txt|xls|xlsx|xml)$ {
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

## Cloudflare

### Cloudflare Security

Cloudflare > Website > `Security` > `Security rules`.

1) Block AI Scrapers and Crawlers

- `Rule name`: `Block AI Scrapers and Crawlers`.
- `Expression`: `(cf.verified_bot_category eq "AI Crawler")`.
- `Choose action`: `Block`.

2) Managed Access

- `Rule name`: `Managed Access`.
- `Expression`: `(not cf.client.bot and not ip.geoip.country in {"AT" "CH" "DE" "LU"} and ip.src ne $server_ip)`.
- `Choose action`: `Managed Challenge`.

3) WordPress Login (Countries Allowed)

- `Rule name`: `WordPress Login (Countries Allowed)`.
- `Expression`: `(http.request.uri.path in {"/wp-login.php"} and not ip.geoip.country in {"BR" "DE"})`.
- `Choose action`: `Block`.

4) WordPress Login (Captcha)

- `Rule name`: `WordPress Login (Captcha)`.
- `Expression`: `(http.request.uri.path in {"/wp-login.php" "/xmlrpc.php"}) or (http.request.uri.path contains "/mein-account/") or (http.request.uri.path contains "/my-account/")`.
- `Choose action`: `Managed Challenge`.

### Cloudflare Caching

- Rule name: `Cache Bypass`.

- Custom filter expression:

```.txt
(http.request.method in {"POST" "PUT" "DELETE"}) or
(http.cookie contains "PHPSESSID") or
(http.request.uri.path contains "/wp-admin") or
(http.request.uri.path contains "/wp-login.php") or
(http.request.uri.path contains "/wp-cron.php") or
(http.request.uri.path contains "/wp-json/") or
(http.request.uri.path contains "/wc-api/") or
(http.request.uri.path contains "/admin-ajax.php") or
(http.cookie contains "wordpress_logged_in_") or
(http.cookie contains "wordpress_sec_") or
(http.cookie contains "wp-settings-") or
(http.cookie contains "wp-settings-time-") or
(http.cookie contains "woocommerce_") or
(http.cookie contains "wp_woocommerce_session_")
```

- Then... `Bypass cache`.

### PHP-FPM Configuration

#### Global

```.sh
sudo nano /etc/php/8.4/fpm/php-fpm.conf
```

```.txt
[global]
emergency_restart_threshold = 10
emergency_restart_interval = 60s
process_control_timeout = 10s
; log_level = notice
log_level = warning
```

#### Local

```.txt
pm = dynamic
pm.max_children = 16
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 8
pm.max_requests = 500
pm.process_idle_timeout = 60s
php_value[upload_tmp_dir] = /home/$domain/tmp
php_value[session.save_path] = /home/$domain/tmp
php_value[error_log] = /home/$domain/logs/php_log
php_value[log_errors] = On
php_admin_value[memory_limit] = 256M
php_admin_value[error_reporting] = E_ALL
request_terminate_timeout = 30s
catch_workers_output = yes

; request_slowlog_timeout = 10s
; slowlog = /home/$domain/logs/php_slow.log
```

```.sh
# touch /home/$domain/logs/php_slow.log
# chown $system_user:$system_user /home/$domain/logs/php_slow.log
# chmod 664 /home/$domain/logs/php_slow.log
# sudo systemctl restart php8.4-fpm
```

### SSL Certificate

```.sh
mkdir -p $domain_root_path/.well-known/acme-challenge
chmod -R 755 $domain_root_path/.well-known
```

```.sh
sudo certbot certonly --manual --preferred-challenges dns -d autodiscover.$domain
```

Copy the generated TXT for `_acme-challenge.autodiscover.$domain` value and add it as a DNS `TXT` record with the name `_acme-challenge` in Cloudflare.

```.sh
# Verify the TXT Record
dig TXT _acme-challenge.autodiscover.$domain +short
```

`Virtualmin` > Choose Virtual Server > `Manage Virtual Server` > `Setup SSL Certificate` > `SSL Providers`.

```.sh
# virtualmin generate-letsencrypt-cert --domain $domain --renew --email-error
```

- Enable `Automatically renew certificate`.
- `Send email on renewal` > `Only on failure`.
- `Request Certificate`.

```.sh
# Remove .htaccess
rm $domain_root_path/.well-known/acme-challenge/.htaccess
```

```.sh
# Delete certificate
# sudo certbot delete --cert-name autodiscover.$domain
```

### Change default domain for server IP address

- `Virtualmin` > Choose Virtual Server > `Web Configuration` > `Website Options` > `Default website for IP address` > `Yes`.

### PHP settings

- `Virtualmin` > Choose Virtual Server > `Web Configuration` > `PHP-FPM Configuration` > `Resource Limits`.
- `Virtualmin` > Choose Virtual Server > `Web Configuration` > `PHP-FPM Configuration` > `Error Logging` > `Error types to display` > `All errors and warnings`.

## WordPress migration

### Database migration

```.sh
# Import .sql
mysql -u "$system_user" -p "$database_name" < $domain_root_path/"$database_name".sql

# Delete dataset
rm $domain_root_path/"$database_name".sql
```

### Files migration

```.sh
# Extract the contents of the "wordpress_export.zip" file to the $domain_root_path folder
unzip "$domain_root_path/wordpress_export.zip" "*" -d $domain_root_path

# Delete .zip file
rm "$domain_root_path/wordpress_export.zip"
```

### Ownership and permission

```.sh
# Change ownership
chown -R "$system_user" $domain_root_path

# Change permissions
find $domain_root_path -type d -exec chmod 755 {} \;
find $domain_root_path -type f -exec chmod 644 {} \;
chmod 600 $domain_root_path/wp-config.php
```

### Tools

```.sh
# Delete empty folders recursively
# find /var/www/vhosts/"$domain"/httpdocs/wp-content/uploads -type d -empty -delete
```

## Emails migration

```.sh
imapsync --host1 "imap.server1.com" --user1 "email@domain.com" --password1 "password-server1" \
  --host2 $server_ip --user2 "email@domain.com" --password2 "password-server2" \
  --exclude "Spam"
```

## Server stress test

```.sh
ab -n 10 $domain
```

## Export MariaDB database

```.sh
# Create dump
mysqldump -u root -p $database_name > $(dirname "$domain_root_path")/backup.sql

# Delete file after downloading it
rm $(dirname "$domain_root_path")/backup.sql
```

## Cache

```.sh
# Clear nginx cache
sudo rm -rf /var/cache/nginx/*
sudo systemctl reload nginx
```

## Logs

```.sh
# Nginx
tail -n 50 /var/log/nginx/error.log
tail -n 50 /var/log/virtualmin/${domain}_error_log

# Clear log file
# > /var/log/virtualmin/${domain}_error_log


# PHP
tail -n 50 /var/log/php8.4-fpm.log
tail -n 50 $(dirname "$domain_root_path")/logs/php_log
# tail -n 50 $(dirname "$domain_root_path")/logs/php_slow.log
```
