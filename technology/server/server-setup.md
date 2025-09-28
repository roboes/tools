# Debian and Virtualmin Server Setup

> [!NOTE]
> Last update: 2025-09-10

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
sudo apt install curl \
  dnsutils \
  git \
  wget \
  python-is-python3 \
  python3-pip \
  python3-venv

# python -m pip install pipenv --break-system-packages

# Install specific Python version
# sudo apt install -y pyenv
# pyenv install 3.11
# pyenv versions
```

## Virtualmin

```.sh
# Installation
wget http://software.virtualmin.com/gpl/scripts/install.sh
chmod a+x install.sh
./install.sh

sudo apt install webmin --install-recommends -y
```

After installation, login to Virtualmin and run the "Post-Installation Wizard".

### Nginx webserver

- [Configure nginx as default webserver](https://www.virtualmin.com/docs/server-components/configuring-nginx-as-default-webserver/). Note: If the "Disable Apache as a Virtualmin feature" step fails with an error stating that the feature is in use, you may need to delete the existing virtual server first by running `virtualmin delete-domain --domain 100.00.000.01.vps.com`.
- Check if it worked: `Virtualmin` → `System Settings` → `Features and Plugins` → Ensure that `Nginx Website` and `Nginx SSL Website are enabled`.

### Virtualmin

#### General settings

- Home subdirectory: `Virtualmin` → `System Settings` → `Virtualmin Configuration` → `Configuration category`: `Defaults for new domains` → Set the "Home subdirectory" to `${DOM}`.

- Timezone: `Webmin` → `Hardware` → `System Time` → `Change Timezone`.

- Disable POP3: `Webmin` → `Servers` → `Dovecot IMAP/POP3 Server` → `Networking and Protocols` → Uncheck `POP3`.

#### Security

#### Webmin Configuration

- IP Access Control: `Webmin` → `Webmin` → `Webmin Configuration` → `IP Access Control`
  - `Allowed IP addresses` → `Only allow from listed addresses` → [IP Access Control](https://www.ipdeny.com/ipblocks/) (download aggregated IP blocks).
  - Enable `Include local network in list`.

- Two-Factor Authentication (2FA):
  - Enable: `Webmin` → `Webmin` → `Usermin Configuration` → `Available Modules` → Enable `Two-Factor Authentication`.
  - Authentication provider: `Webmin` → `Webmin` → `Webmin Configuration` → `Two-Factor Authentication` → `Authentication provider`: `TOTOP Authenticator`.
  - Setup: `Webmin` → `Webmin` → `Webmin Users` → `Two-Factor Authentication` → `Enroll For Two-Factor Authentication`.

- Scheduled Upgrades:
  - Virtualmin → Dashboard → Package updates → Scheduled Upgrades:
    - `Check for updates on schedule`: `Yes, every week`.
    - `Action when update needed`: `Install security updates`.

##### Fail2Ban

- Fail2Ban: `Webmin` → `Networking` → `Fail2Ban Intrusion Detector` → `Edit Config Files` → `/etc/fail2ban/jail.local`

```.toml
[DEFAULT]
bantime = 1440m
findtime = 60m
maxretry = 3

[dovecot]
enabled = true

[postfix]
enabled = true

[postfix-sasl]
enabled = true
backend = systemd
journalmatch = _SYSTEMD_UNIT=postfix@-.service

[proftpd]
enabled = false
backend = auto
logpath = /var/log/proftpd/proftpd.log

[nginx-bad-request]
enabled = true

[nginx-http-auth]
enabled = true

[recidive]
enabled = true
bantime = 4w
findtime = 7d
maxretry = 5

[sshd]
enabled = true
port = ssh

[webmin-auth]
enabled = true
port = 10000
journalmatch = _SYSTEMD_UNIT=webmin.service
```

```.sh
sudo systemctl restart fail2ban
```

#### SASL Authentication Daemon

```.sh
# sudo systemctl status saslauthd
# sudo systemctl enable saslauthd
```

#### SSH

```.sh
# Generate the SSH Key Pair
ssh-keygen -t rsa -b 4096 -C "root@$server_ip"

# Add the Public Key to the Authorized Keys
cat /root/.ssh/id_rsa.pub >> /root/.ssh/authorized_keys
chmod 700 /root/.ssh
chmod 600 /root/.ssh/authorized_keys
```

Save the RSA private key (`id_rsa`) on your local machine. Rename it as needed.
PuTTYgen can convert the RSA private key to `.ppk`.

```.sh
# Copy SSH key in Windows Subsystem for Linux (WSL)
# mkdir -p ~/.ssh
# chmod 700 ~/.ssh
# cp "/mnt/c/Users/${USER}/Downloads/id_rsa" ~/.ssh/
# chmod 600 ~/.ssh/id_rsa
```

Configure SSH to Use Key-Based Authentication

```.sh
nano /etc/ssh/sshd_config
```

```.txt
# This is the sshd server system-wide configuration file. See
# sshd_config(5) for more information.

Include /etc/ssh/sshd_config.d/*.conf


# Connection settings

## Port
Port 22

## Timeout and connection limits
LoginGraceTime 60
MaxAuthTries 3
ClientAliveInterval 300
ClientAliveCountMax 3
MaxStartups 10:30:100
AllowTcpForwarding no
UseDNS no


# Authentication settings

## Disable password authentication and enable key-based login
PasswordAuthentication no
PubkeyAuthentication yes

## Disable password-based root login
PermitRootLogin prohibit-password

## Allow a specific user to log in via SSH
AllowUsers root


# Other settings

## Enable Pluggable Authentication Modules (PAM) authentication
UsePAM yes

## Disables printing the message of the day upon login
PrintMotd no

## Override default of no subsystems
Subsystem sftp /usr/lib/openssh/sftp-server
```

Restart server.

#### Cloudflare Zero Trust

Cloudflare → `Zero Trust`

##### Tunnels

`Networks` → `Tunnels` → `Create a tunnel` → `Cloudflared`.

Public hostnames:

1) `Public hostname`: `ssh.website.com`; `Service`: `ssh://localhost:22`.
2) `Public hostname`: `virtualmin.website.com`; `Service`: `https://localhost:10000`; `Additional application settings` → `TLS` → Enable `No TLS Verify`.

```.sh
sudo systemctl status cloudflared
```

##### Applications

`Access` → `Applications` → `Add an application` → `Self-hosted`

1) SSH Access
`Application name`: `SSH Access`.
`Session Duration`: `24 hours`.
`Public hostname`: `ssh.website.com`.
`Access policies`: `Select existing policies` or `Create new policy`.

2) Virtualmin Access
`Application name`: `Virtualmin Access`.
`Session Duration`: `2 weeks`.
`Public hostname`: `virtualmin.website.com`.
`Access policies`: `Select existing policies` or `Create new policy`.

##### Webmin

###### /etc/webmin/config

```.sh
sudo nano /etc/webmin/config
```

Add to the end of the file:

```.txt
referers=virtualmin.website.com
```

###### /etc/webmin/miniserv.conf

```.sh
sudo nano /etc/webmin/miniserv.conf
```

```.txt
redirect_host=virtualmin.website.com
```

```.sh
sudo systemctl restart webmin
```

#### FirewallD

```.sh
# sudo apt update
# sudo apt install firewalld -y

# Remove SSH service
sudo firewall-cmd --zone=public --remove-service=ssh --permanent

# Remove Webmin port
sudo firewall-cmd --zone=public --remove-port=10000/tcp --permanent

# Remove unnecessary services
sudo firewall-cmd --zone=public --remove-service=ftp --permanent
sudo firewall-cmd --zone=public --remove-service=mdns --permanent
sudo firewall-cmd --zone=public --remove-service=dns --permanent
sudo firewall-cmd --zone=public --remove-service=dns-over-tls --permanent
sudo firewall-cmd --zone=public --remove-service=dhcpv6-client --permanent
sudo firewall-cmd --zone=public --remove-service=imap --permanent
# sudo firewall-cmd --zone=public --remove-service=imaps --permanent
sudo firewall-cmd --zone=public --remove-service=pop3 --permanent
sudo firewall-cmd --zone=public --remove-service=pop3s --permanent
sudo firewall-cmd --zone=public --remove-service=smtp --permanent
# sudo firewall-cmd --zone=public --remove-service=smtps --permanent
# sudo firewall-cmd --zone=public --remove-service=smtp-submission --permanent

# Remove unnecessary ports
sudo firewall-cmd --zone=public --remove-port=20/tcp --permanent
sudo firewall-cmd --zone=public --remove-port=2222/tcp --permanent
sudo firewall-cmd --zone=public --remove-port=20000/tcp --permanent
sudo firewall-cmd --zone=public --remove-port=49152-65535/tcp --permanent
sudo firewall-cmd --zone=public --remove-port=10001-10100/tcp --permanent

# Reload firewall to apply changes
sudo firewall-cmd --reload

# Show interfaces
firewall-cmd --list-interfaces

# Show active rules
sudo firewall-cmd --list-all
```

Now "Create Virtual Server".

### Virtualmin settings (optional)

- Apps: `Virtualmin` → Choose Virtual Server → `Manage Web Apps` → Install `phpMyAdmin` and `RoundCube`.

### PHP

[Configuring Multiple PHP Versions](https://www.virtualmin.com/docs/server-components/configuring-multiple-php-versions/)

```.sh
# Remove older PHP Versions
php_version_old="8.2"
sudo apt purge php${php_version_old} php${php_version_old}-cli php${php_version_old}-fpm php${php_version_old}-common php${php_version_old}-mysql php${php_version_old}-xml php${php_version_old}-opcache php${php_version_old}-curl php${php_version_old}-mbstring
sudo apt autoremove
sudo apt clean
```

```.sh
php_version_current="8.3"
sudo apt install php${php_version_current}-sqlite3
```

### Packages

```.sh
sudo apt install htop \
  libnginx-mod-http-brotli-filter \
  redis
```

## Email Server

### DNS Configuration

Obtain core mail DNS records (`A` and `AAAA` records for the mail server; `MX` record; and `TXT` DKIM and SPF records) from Virtualmin (`Virtualmin` → Choose Virtual Server → `DNS Settings` → `Suggested DNS Records`). Then, add these records to Cloudflare DNS.

When adding the `A` and `AAAA` records for the mail server (e.g. `mail.website.com`) to Cloudflare, ensure its Proxy Status is set to `DNS only`. This is crucial for proper mail flow, as mail servers require direct IP connections.

Additionally, add the following record:

1) DMARC record

- Type: `TXT`
- Name: `_dmarc`
- Content: `v=DMARC1; p=none; fo=1; adkim=s; aspf=s`

### Troubleshooting

To diagnose general email sending/receiving issues: Open server's mail log in real-time to monitor activity:

```.sh
sudo tail -f /var/log/mail.log
```

While the logs are open, send a test email from your mail client (e.g. Roundcube) to an external address and also to an address on own domain.

When configuring the email client (e.g. Thunderbird), ensure the server hostname entered matches the Common Name (CN) or a Subject Alternative Name (SAN) on the server's SSL certificate to prevent certificate mismatch errors. Sometimes, this requires using `website.com` as the SMTP hostname (instead of the typical `mail.website.com`) if the certificate only covers the root domain.

Additional troubleshooting:

```.sh
dovecot -n
sudo journalctl -u dovecot.service -f
sudo journalctl -u postfix.service -f
```

#### Architecture mismatch

To fix "Command died with status 126: Exec format error" that prevents local mail delivery, check for an architecture mismatch:

```.sh
# Check the procmail-wrapper binary's type:
file /usr/bin/procmail-wrapper
# file /usr/share/webmin/virtual-server/procmail-wrapper


# Check server's actual architecture
dpkg --print-architecture
```

If an architecture mismatch is detected (e.g. `file` shows a 32-bit x86 executable, but `dpkg` shows `arm64`), recompile procmail-wrapper for your server's correct architecture:

```.sh
# Navigate to a temporary directory
cd /tmp/

# Download the source file
wget http://software.virtualmin.com/lib/procmail-wrapper.c

# Compile the source code
gcc -o procmail-wrapper procmail-wrapper.c

# Verify the newly compiled binary
file procmail-wrapper

# Replace the old binary
sudo mv /usr/bin/procmail-wrapper /usr/bin/procmail-wrapper.old  # Backup the old one
sudo mv /tmp/procmail-wrapper /usr/bin/                         # Move the new one from /tmp
sudo chmod 4755 /usr/bin/procmail-wrapper                     # Set permissions
sudo chown root:root /usr/bin/procmail-wrapper                # Set ownership
```

### Backup

`Virtualmin` → `Backup and Restore` → `Scheduled Backups` → `Add a new backup schedule`.

- `Backup description`: `Backup Weekly`.
- `Servers to save`: `All virtual servers`.
- `Features to backup`: `Backup all features` (if the backup fails with an error about missing logrotate config, uncheck `Logrotate configuration for log file`).
- `Backup destinations`: `Local file or directory` - `/backup/backup-%Y-%m-%d/`.
- `Delete old backups`: `Yes, after 30 days`.
- `Additional destination options`:
  - Enable `Do strftime-style time substitutions on file or directory name`.
  - Enable `Transfer each virtual server after it is backed up`.
- `Backup format`: Select `One file per server`.
- `Action on error`: Select `Halt the backup immediately`.
- `Backup compression format`: Select `Default`.
- `Backup level`: `Full (all files)`.
- `Scheduled backup time` → `Simple schedule`: `Weekly (on Sundays)`.

### Bootup and Shutdown

`Webmin` → `System` → `Bootup and Shutdown`.

```.sh
# Disable services
services=(
  dovecot.service
  postfix.service
)

for srv in "${services[@]}"; do
  echo "Stopping and disabling $srv..."
  systemctl stop "$srv" 2>/dev/null
  systemctl disable "$srv" 2>/dev/null
  systemctl mask "$srv" 2>/dev/null
done
```

### Nginx directives

#### Webmin → Servers → Nginx Webserver → Edit Configuration Files

##### /etc/nginx/nginx.conf

```.nginx
# Global Settings

## User and worker processes
user www-data;
worker_processes auto;
pid /run/nginx.pid;

## Error logging
error_log /var/log/nginx/error.log;
# error_log /var/log/nginx/error.log debug;

## Load dynamic modules
include /etc/nginx/modules-enabled/*.conf;


# Event Loop Configuration
events {
    worker_connections 4096;
    multi_accept on;
    use epoll;
}


# HTTP Server Configuration
http {
    # Performance and Optimization
    sendfile on;
    tcp_nopush on;
    tcp_nodelay on;
    types_hash_max_size 2048;
    server_names_hash_bucket_size 128;
    # server_name_in_redirect off;

    # Keep-Alive Settings
    keepalive_timeout 30s;
    keepalive_requests 500;
    reset_timedout_connection on;

    # Client Request Limits
    client_max_body_size 50M;

    # MIME Types
    include /etc/nginx/mime.types;
    default_type application/octet-stream;

    # Logging
    access_log /var/log/nginx/access.log;


    # SSL/TLS Settings
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers on;


    # Security Headers & Settings
    server_tokens off;
    add_header Vary "Accept-Encoding";
    add_header X-Cache-Status $upstream_cache_status;


    # Compression

    ## Brotli
    brotli on;
    brotli_comp_level 4;
    brotli_types text/plain text/css text/javascript application/javascript application/json application/xml application/rss+xml;

    ## Gzip
    gzip on;
    gzip_min_length 1024;
    gzip_comp_level 2;
    gzip_types text/plain text/css text/javascript application/javascript application/json application/xml application/rss+xml;

    # Proxy Settings
    proxy_http_version 1.1;
    proxy_set_header Connection "keep-alive";
    proxy_buffering on;
    proxy_cache_revalidate on;
    proxy_buffer_size 512k;
    proxy_buffers 16 512k;
    proxy_busy_buffers_size 512k;
    proxy_read_timeout 30s;
    proxy_send_timeout 30s;

    # FastCGI Global Settings

    ## FastCGI cache path definition
    fastcgi_cache_path /var/cache/nginx levels=1:2 keys_zone=MYCACHE:100m inactive=4h max_size=2g use_temp_path=off loader_files=500 loader_sleep=50ms loader_threshold=300ms;
    fastcgi_cache_key "$scheme$request_method$host$request_uri";

    ## FastCGI timeouts
    fastcgi_connect_timeout 30s;
    fastcgi_read_timeout 30s;
    fastcgi_send_timeout 30s;

    ## FastCGI buffers
    fastcgi_buffer_size 16k;
    fastcgi_buffers 4 16k;
    fastcgi_busy_buffers_size 48k;
    fastcgi_temp_file_write_size 64k;

    ## FastCGI cache lock settings
    fastcgi_cache_lock on;
    fastcgi_cache_lock_timeout 5s;
    fastcgi_cache_background_update on;

    # Include Virtual Host Configurations
    include /etc/nginx/conf.d/*.conf;
    include /etc/nginx/sites-enabled/*;
}
```

##### /etc/nginx/sites-available/domain.com.conf

```.nginx
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
    set $content_security_policy "default-src 'self'; connect-src 'self' https://api.wordpress.org https://google.com https://pagead2.googlesyndication.com https://*.google-analytics.com https://analytics.google.com https://www.googletagmanager.com https://googleads.g.doubleclick.net https://www.googleadservices.com https://*.googleapis.com https://www.paypal.com https://www.sandbox.paypal.com https://*.stripe.com https://*.mercadopago.com https://*.mercadolibre.com https://api.mercadolibre.com; font-src 'self' data: https://fonts.gstatic.com; worker-src 'self' blob:; frame-src 'self' https://www.google.com https://www.googletagmanager.com https://td.doubleclick.net https://recaptcha.google.com https://www.youtube-nocookie.com https://www.paypal.com https://*.stripe.com https://www.mercadolibre.com https://api-static.mercadopago.com; img-src 'self' data: https://ps.w.org https://s.w.org https://t.paypal.com https://www.paypalobjects.com https://www.google.com https://www.google.de https://www.google-analytics.com https://www.googletagmanager.com https://googleads.g.doubleclick.net https://pagead2.googlesyndication.com https://*.stripe.com https://*.mercadopago.com https://*.mercadolibre.com https://http2.mlstatic.com; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://*.cloudflare.com https://static.cloudflareinsights.com https://www.google.com https://www.googletagmanager.com https://www.google-analytics.com https://www.gstatic.com https://googleads.g.doubleclick.net https://www.youtube.com https://www.youtube-nocookie.com https://www.paypal.com https://www.paypalobjects.com https://*.mercadopago.com https://http2.mlstatic.com https://www.googleadservices.com https://pagead2.googlesyndication.com https://*.stripe.com https://*.googleapis.com; style-src 'self' 'unsafe-inline' https://*.googleapis.com https://www.gstatic.com https://http2.mlstatic.com;";


    # Main Web Root Setup
    root ${domain_root_path};
    index index.php index.htm index.html;

    # Logging
    access_log /var/log/virtualmin/${domain}_access_log;
    error_log /var/log/virtualmin/${domain}_error_log warn;

    # Enable HTTP/2 protocol support
    http2 on;


    # Security Headers
    add_header Strict-Transport-Security "max-age=15552000; includeSubDomains; preload" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Permissions-Policy "geolocation=(),midi=(),sync-xhr=(),microphone=(),camera=(),magnetometer=(),gyroscope=(),fullscreen=(self)" always;
    add_header Content-Security-Policy $content_security_policy always;


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


    # Location Blocks - General & Security

    ## Main application entry point
    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    ## Block access to sensitive files
    location ~ ^/\.user\.ini {
        deny all;
    }

    ## Block access to .yml files
    location ~* \.(yml)$ {
        deny all;
        access_log off;
        log_not_found off;
    }

    ## Well-known and ACME challenges for SSL certificate renewal
    location ^~ /.well-known/acme-challenge/ {
        allow all;
        auth_basic off;
        default_type "text/plain";
        add_header Content-Type text/plain;
        try_files $uri =404;
    }

    location ^~ /.well-known/ {
        try_files $uri /;
    }

    # Block access to xmlrpc.php
    location = /xmlrpc.php {
        deny all;
        access_log off;
        log_not_found off;
    }

    # PHP Processing
    fastcgi_split_path_info "^(.+\\.php)(/.+)$";

    location ~ "\.php(/|$)" {
        try_files $uri $fastcgi_script_name =404;

        # FastCGI core settings
        include fastcgi_params;
        fastcgi_pass ${php_socket_path};
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        fastcgi_intercept_errors on;

        # Security and header handling
        fastcgi_hide_header 'X-Powered-By';
        fastcgi_param HTTP_PROXY "";

        # Caching controls
        set $skip_cache 0;
        if ($request_method ~* "DELETE|POST|PUT") { set $skip_cache 1; }
        if ($http_cookie ~* "PHPSESSID") { set $skip_cache 1; }
        if ($request_uri ~* "/wp-admin/|/wp-login\\.php|/wp-cron\\.php|/wp-json/|/wc-api/|/admin-ajax\\.php") { set $skip_cache 1; }
        if ($http_cookie ~* "wordpress_logged_in_|wordpress_sec_|wp-settings-|wp-settings-time-") { set $skip_cache 1; }
        if ($http_cookie ~* "woocommerce_|wp_woocommerce_session_") { set $skip_cache 1; }

        fastcgi_cache MYCACHE;
        fastcgi_cache_valid 200 301 302 1h;
        fastcgi_cache_valid 404 1m;
        fastcgi_cache_use_stale error timeout updating;
        fastcgi_cache_bypass $skip_cache;
        fastcgi_no_cache $skip_cache;
        add_header X-Nginx-Cache-Status $upstream_cache_status always;
    }


    # Static Asset Caching
    location ~* ^(?!.*phast\\.php).*\.(ac3|avi|avif|bmp|bz2|css|cue|dat|doc|docx|dts|eot|exe|flv|gif|gz|htm|html|ico|img|iso|jpeg|jpg|js|mkv|mp3|mp4|mpeg|mpg|ogg|otf|pdf|png|ppt|pptx|qt|rar|rmf|rtf|svg|swf|tar|tgz|ttf|wav|woff|woff2|zip|webm|webp)$ {
        etag on;
        if_modified_since exact;
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    location ~* \.(csv|json|rss|txt|xls|xlsx|xml)$ {
        expires 5m;
        add_header Cache-Control "public";
    }


    # Error Handling
    error_page 500 502 503 504 @no_cache;
    location @no_cache {
        internal;
        add_header Cache-Control "no-store, no-cache, must-revalidate" always;
        return 500 "Internal Server Error";
    }
}
```

##### /etc/nginx/sites-available/subdomain.domain.com.conf

This Nginx configuration serves as a template for a subdomain (`subdomain.domain.com`) that redirects all traffic to a specific path on the main domain (e.g. <https://domain.com/path>). Its primary roles are to facilitate SSL certificate issuance via ACME challenges and to provide these redirects.

```.nginx
server {
    # Settings
    set $domain website.com;
    set $domain_root_path /home/${domain}/domains/subdomain.${domain}/public_html;
    set $php_socket_id 100000000000000;
    set $php_socket_path unix:/run/php/${php_socket_id}.sock;
    server_name website.com www.website.com mail.website.com webmail.website.com admin.website.com;
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
    #location / {
    #    return 301 https://website.com/$request_uri;
    #}

    ## Block access to sensitive files
    location ~ ^/\.user\.ini {
        deny all;
    }

}
```

```.sh
# Restart Nginx
sudo systemctl reload nginx
```

#### Clear cache

```.sh
sudo rm -rf /var/cache/nginx/*
sudo systemctl reload nginx
```

### Cloudflare

#### DNS

Obtain core DNS records (`A` and `AAAA` records) from Virtualmin (`Virtualmin` → Choose Virtual Server → `DNS Settings` → `Suggested DNS Records`). Then, add these records to Cloudflare DNS.

#### Security

Cloudflare → Website → `Security` → `Security rules`.

1) ACME Challenge Passthrough

- `Rule name`: `ACME Challenge Passthrough`.
- `Expression`: `http.request.uri.path contains "/.well-known/acme-challenge/"`.
- `Choose action`: `Skip` (select all `WAF components to skip`).

2) Block AI Scrapers and Crawlers

- `Rule name`: `Block AI Scrapers and Crawlers`.
- `Expression`: `(cf.verified_bot_category eq "AI Crawler")`.
- `Choose action`: `Block`.

3) Managed Access

- `Rule name`: `Managed Access`.
- `Expression`: `(not cf.client.bot and not ip.geoip.country in {"AT" "CH" "DE" "LU"} and ip.src ne $server_ip)`.
- `Choose action`: `Managed Challenge`.

4) WordPress

- `Rule name`: `WordPress`.
- `Expression`: `(http.request.uri.path contains "/wp-admin/" or http.request.uri.path contains "/wp-login.php" or http.request.uri.path contains "/xmlrpc.php" or http.request.uri.path contains "/my-account/" or http.request.uri.path contains "/mein-account/" or http.request.uri.path contains "/gift-card-redemption/" or http.request.uri.path contains "/gutschein-einlosen/")`
- `Choose action`: `Managed Challenge`.

#### Caching

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

#### Local

```.txt
[100000000000000]
user = $system_user
group = $system_user
listen.owner = $system_user
listen.group = $system_user
listen.mode = 0660
listen = /run/php/100000000000000.sock
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
php_admin_value[display_errors] = Off
php_admin_value[error_reporting] = E_ALL & ~E_NOTICE & ~E_STRICT
php_admin_value[memory_limit] = 256M
php_admin_value[upload_max_filesize] = 10M
request_terminate_timeout = 30s
catch_workers_output = yes

; request_slowlog_timeout = 10s
; slowlog = /home/$domain/logs/php_slow.log
```

```.sh
# touch /home/$domain/logs/php_slow.log
# chown $system_user:$system_user /home/$domain/logs/php_slow.log
# chmod 664 /home/$domain/logs/php_slow.log
# sudo systemctl restart php8.3-fpm
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

`Virtualmin` → Choose Virtual Server → `Manage Virtual Server` → `Setup SSL Certificate` → `SSL Providers`.

```.sh
# virtualmin generate-letsencrypt-cert --domain $domain --renew --email-error
```

- Enable `Automatically renew certificate`.
- `Send email on renewal` → `Only on failure`.
- `Request Certificate`.

If it doesn't work, temporarily set the Cloudflare DNS mode from "Proxied" (orange cloud) to "DNS only" (gray cloud) for the domain.

```.sh
# Remove .htaccess
rm $domain_root_path/.well-known/acme-challenge/.htaccess
```

```.sh
# Delete certificate
# sudo certbot delete --cert-name autodiscover.$domain
```

### Change default domain for server IP address

- `Virtualmin` → Choose Virtual Server → `Web Configuration` → `Website Options` → `Default website for IP address` → `Yes`.

### PHP settings

- `Virtualmin` → Choose Virtual Server → `Web Configuration` → `PHP-FPM Configuration` → `Resource Limits`.
- `Virtualmin` → Choose Virtual Server → `Web Configuration` → `PHP-FPM Configuration` → `Error Logging` → `Error types to display` → `All errors and warnings`.

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
