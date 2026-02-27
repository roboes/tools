# Debian and Virtualmin Server Setup

> [!NOTE]  
> Last update: 2026-01-17

```.sh
# Settings
server_ip="100.00.000.01"
domain="website.com"
domain_root_path="/home/$domain/public_html"
admin_user="sysadmin"
system_user="website"
database_name="database_name"
```

## Notes

Check for new [virtualmin-nginx module releases](https://github.com/virtualmin/virtualmin-nginx/releases).

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
sudo apt install -y \
  curl \
  dnsutils \
  git \
  wget \
  wtmpdb \
  libpam-wtmpdb \
  python-is-python3 \
  python3-pip \
  python3-venv \
  resolvconf

# sudo apt install -y composer

# sudo apt install -y \
  # docker.io \
  # docker-compose

# Docker
# sudo systemctl enable --now docker
# docker --version
# docker compose version

# python -m pip install pipenv --break-system-packages

# Install specific Python version
# sudo apt install -y pyenv
# pyenv install 3.11
# pyenv versions
```

## DNS

```.sh
# Check if resolvconf is running
sudo systemctl status resolvconf.service
```

```.sh
# Edit the resolvconf "head" file
sudo nano /etc/resolvconf/resolv.conf.d/head
```

```.txt
# Cloudflare DNS (IPv4)
nameserver 1.1.1.1
nameserver 1.0.0.1

# Cloudflare DNS (IPv6)
nameserver 2606:4700:4700::1111
nameserver 2606:4700:4700::1001
```

```.sh
# Apply the changes
sudo resolvconf -u
```

```.sh
# Verify the DNS
time nslookup google.com
```

## User management

```.sh
# Create the admin user
sudo adduser $admin_user

# Add user to the sudo group
sudo usermod -aG sudo $admin_user
```

## SSH

(Local machine) Generate SSH key pair.

```.sh
if [ -n "$admin_user" ] && [ -n "$domain" ] && [ -n "$server_ip" ]; then
    ssh-keygen -t ed25519 -C "$admin_user@$server_ip" -f ~/.ssh/id_ed25519_$domain
else
    echo "Error: admin_user, domain, and/or server_ip is not defined"
fi
```

```.sh
# (Optional) Backup SSH key to another folder
cp ~/.ssh/id_ed25519_$domain /mnt/c/Users/$USER/Documents/
cp ~/.ssh/id_ed25519_$domain.pub /mnt/c/Users/$USER/Documents/
```

(Local machine) Get the SSH public key string.

```.sh
cat ~/.ssh/id_ed25519_$domain.pub
```

```.sh
# echo sshpass -P \"passphrase\" -p \"PASSPHRASE\" -v ssh -i \"~/.ssh/id_ed25519_$domain\" -o ProxyCommand=\"cloudflared access ssh --hostname ssh.$domain\" \"$admin_user@$server_ip\"
```

(Server) Add the ssh public key to the authorized keys.

```.sh
# The public key string you copied from your local machine
ssh_public_key="ssh-ed25519 AAAA... $admin_user@$server_ip"

# Create the directory for the admin user and set permissions
sudo mkdir -p /home/$admin_user/.ssh
chmod 700 /home/$admin_user/.ssh

# Append the key only if it doesn't already exist in the file
if ! grep -qF "$ssh_public_key" /home/$admin_user/.ssh/authorized_keys 2>/dev/null; then
    echo "$ssh_public_key" >> /home/$admin_user/.ssh/authorized_keys
fi

chmod 600 /home/$admin_user/.ssh/authorized_keys
```

Configure SSH to use key-based authentication by adding "$admin_user" to the `AllowUsers` directive.

```.sh
sudo nano /etc/ssh/sshd_config
```

```.txt
PubkeyAuthentication yes
AllowUsers $admin_user
```

```.sh
sudo systemctl reload sshd
```

(Local machine) Test the SSH connection.

```.sh
ssh -i ~/.ssh/id_ed25519_$domain $admin_user@$server_ip
```

Removing "root" from the `AllowUsers` directive. Completely disable "root" login.

```.sh
sudo nano /etc/ssh/sshd_config
```

Complete file:

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
KbdInteractiveAuthentication no

## Disable password-based root login
PermitRootLogin no

## Allow a specific user to log in via SSH
AllowUsers $admin_user


# Other settings

## Enable Pluggable Authentication Modules (PAM) authentication
UsePAM yes

## Disables printing the message of the day upon login
PrintMotd no

## Override default of no subsystems
Subsystem sftp /usr/lib/openssh/sftp-server
```

Tests before restarting the ssh:

```.sh
# Check SSH key existence
sudo ls -la /home/$admin_user/.ssh/authorized_keys

# Check permissions
sudo chown -R $admin_user:$admin_user /home/$admin_user/.ssh
sudo chmod 700 /home/$admin_user/.ssh
sudo chmod 600 /home/$admin_user/.ssh/authorized_keys

# Check for typos in the sshd config (if it returns nothing, config is valid)
sudo sshd -t
```

```.sh
sudo systemctl reload sshd
```

Keep current terminal window open and open a new terminal window trying to login as $admin_user.

(Server) Remove any server-generated SSH keys if needed. After confirming key-based login works, remove old server-generated keys if they exist.

```.sh
ls -la /root/.ssh/
ls -la /home/$admin_user/.ssh/
# sudo rm -f /root/.ssh/id_rsa
```

```.sh
sudo systemctl reload sshd
```

## Virtualmin

```.sh
# Installation
wget https://software.virtualmin.com/gpl/scripts/install.sh
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

- Change default domain for server IP address: `Virtualmin` → Choose Virtual Server → `Web Configuration` → `Website Options` → `Default website for IP address` → `Yes`.

- Enable HTTP2 protocol support: `Virtualmin` → Choose Virtual Server → `Web Configuration` → `Website Options` → `Enable HTTP2 protocol support` → `Yes`.

```.sh
# Alternatively
# virtualmin modify-web --domain $domain --protocols "http/1.1 h2"
# virtualmin list-domains --domain $domain --multiline | grep "HTTP protocols"
```

#### Security

#### Webmin Configuration

- IP Access Control: `Webmin` → `Webmin` → `Webmin Configuration` → `IP Access Control`
  - `Allowed IP addresses` → `Only allow from listed addresses` → [IP Access Control](https://www.ipdeny.com/ipblocks/) (download aggregated IP blocks).
  - Enable `Include local network in list`.

- Two-Factor Authentication (2FA):
  - Enable: `Webmin` → `Webmin` → `Usermin Configuration` → `Available Modules` → Enable `Two-Factor Authentication`.
  - Authentication provider: `Webmin` → `Webmin` → `Webmin Configuration` → `Two-Factor Authentication` → `Authentication provider`: `TOTOP Authenticator`.
  - Setup: `Webmin` → `Webmin` → `Webmin Users` → `Two-Factor Authentication` → `Enroll For Two-Factor Authentication`.

- Webmin Users:
  - Create a new privileged user: `Webmin` → `Webmin` → `Webmin Users` → `Create a new privileged user`:
  - `Webmin user access rights`:
    - `Username`: `$admin_user`.
    - `Password`: `Unix authentication`.
  - `Security and limits options`:
    - `Two-factor authentication type`: `Enable Two-Factor for User`.
  - `Available Webmin modules`: `Select all`.
  - Remove `root` Webmin user: Logout of the `root` Webmin user and login as the new `$admin_user` Webmin user. Then:
    - `Webmin` → `Webmin` → `Webmin Users` → `root` → `Delete`.

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

#### Cloudflare Zero Trust

Cloudflare → `Zero Trust`.

##### Connectors

`Networks` → `Connectors` → `Create a tunnel` → `Cloudflared`.

Public hostnames:

1. `Public hostname`: `ssh.website.com`; `Service`: `ssh://localhost:22`.
2. `Public hostname`: `virtualmin.website.com`; `Service`: `https://localhost:10000`; `Additional application settings` → `TLS` → Enable `No TLS Verify`.

```.sh
sudo systemctl status cloudflared
```

##### Second cloudflared

Optional second Cloudflared Zero Trust.

```.sh
cat <<EOF > /etc/systemd/system/cloudflared-website2.service
[Unit]
Description=cloudflared website2
After=network-online.target
Wants=network-online.target

[Service]
TimeoutStartSec=0
Type=notify
ExecStart=/usr/bin/cloudflared --no-autoupdate tunnel run --token 12345
Restart=on-failure
RestartSec=5s

[Install]
WantedBy=multi-user.target
EOF
```

```.sh
# Reload the system manager
sudo systemctl daemon-reload
```

```.sh
# Start the new service
sudo systemctl enable cloudflared-website2
sudo systemctl start cloudflared-website2
```

```.sh
sudo systemctl status cloudflared-website2
```

##### Policies

`Access controls` → `Policies` → `Add a policy`.

`Policy name`: `ACME Challenge Passthrough`. `Action`: `Bypass`. `Session duration`: `Same as application session timeout`.

`Add rules` → `Include`. `Selector`: `Everyone`.

##### Applications

`Access controls` → `Applications` → `Add an application` → `Self-hosted`

1. SSH Access `Application name`: `SSH Access`. `Session Duration`: `24 hours`. `Public hostname`: `ssh.website.com`. `Access policies`: `Select existing policies` or `Create new policy`.

2. Virtualmin Access `Application name`: `Virtualmin Access`. `Session Duration`: `2 weeks`. `Public hostname`: `virtualmin.website.com`. `Access policies`: `Select existing policies` or `Create new policy`.

3. website.com ACME Challenge Passthrough `Application name`: `website.com ACME Challenge Passthrough`. `Session Duration`: `No duration, expires immediately`. `Public hostname`: `website.com/.well-known/acme-challenge/*`. `Access policies`: `Select existing policies` → `ACME Challenge Passthrough`.

##### Webmin

See [How to set up Cloudflare Tunnel to work properly with Webmin?](https://webmin.com/faq/#how-to-set-up-cloudflare-tunnel-to-work-properly-with-webmin).

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

#### Login alerts

```.sh
# Display all recorded login sessions for root user
sudo wtmpdb last | grep root
```

##### Grafana

Email alerts for any `root` or `$admin_user` login attempt (successful or failed) on the server via SSH, Virtualmin web panel, or server VNC console. Logs are stored externally in Grafana Cloud's free tier, ensuring they remain accessible even if the server is compromised and local logs are deleted.

`Grafana` → `Alerts & IRM` → `Alerting` → `Manage contact points` → `Create contact point`:

- `Name`: `Email Notifications`.
- `Integration`: `Email`.
- `Notification settings`: Enable `Disable resolved message`.

`Grafana` → `Alerts & IRM` → `Alerting` → `Alert rules` → `New alert rule`:

Enter alert rule name:

- `Name`: `Server Login`.

Define query and alert condition:

- Loki query:

```.txt
sum by (instance, job, log_line) (
  count_over_time(
    {job="ssh_auth"}
    |~ "(?i)(sshd.*(accepted|failed).*for (root|$admin_user)|pam_unix\\((webmin|login):session\\).*session opened for user (root|$admin_user)|pam_unix\\(webmin:auth\\).*authentication failure.*user.*(root|$admin_user))"
    !~ "(?i)(sudo:session|systemd-user:session)"
    | label_format log_line="{{ __line__ }}"
    [5m]
  )
)
```

- Expressions: `Threshold`: `Input A IS ABOVE 0`.

Add folder and labels:

- `Labels` → `Add labels`:
  - `Choose key`: `severity`.
  - `Choose value`: `critical`.

Set evaluation behavior:

- `Evaluation interval`: `Every 1m`.
- `Pending period`: `None (0s)`.
- `Keep firing for`: `None (0s)`.
- `Alert state if no data or all values are null`: `Normal`.
- `Alert state if execution error or timeout`: `Alerting`.

Configure notification message:

- `Summary (optional)`: `Login Event: {{ $labels.instance }} ({{ $labels.job }})`.
- `Description (optional)`: `{{ $labels.log_line }}`.

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
sudo firewall-cmd --list-interfaces

# Show services
sudo firewall-cmd --list-services

# Show active rules
sudo firewall-cmd --list-all
```

Now "Create Virtual Server".

### Virtualmin settings (optional)

- Apps: `Virtualmin` → Choose Virtual Server → `Manage Web Apps` → Install `phpMyAdmin` and `RoundCube`.

### PHP

[Configuring Multiple PHP Versions](https://www.virtualmin.com/docs/server-components/configuring-multiple-php-versions/)

```.sh
php_version_current="8.5"
sudo apt install php${php_version_current}-sqlite3
```

Important: Upgrading or downgrading PHP versions via control panels like Virtualmin often triggers an automatic rewrite of Nginx configuration files, which can inadvertently strip out essential FastCGI parameters.

```.sh
# Remove older PHP Versions
php_version_old="8.4"
sudo apt purge "php${php_version_old}*"

# Clean up dependencies
sudo apt autoremove --purge
sudo apt clean

# Sync Virtualmin with system changes
virtualmin check-config
```

### Packages

```.sh
sudo apt install htop \
  libnginx-mod-http-brotli-filter \
  redis
```

## Email Server

### DNS Configuration

Obtain core mail DNS records (`A` and `AAAA` records for the mail server; `MX` record; and `TXT` DKIM and SPF records) from Virtualmin (`Virtualmin` → Choose Virtual Server → `DNS Settings` → `Suggested DNS Records`). Then, add these records to Cloudflare DNS (For the SPF record, change `?all` to `-all`).

When adding the `A` and `AAAA` records for the mail server (e.g. `mail.website.com`) to Cloudflare, ensure its Proxy Status is set to `DNS only`. This is crucial for proper mail flow, as mail servers require direct IP connections.

Additionally, enable `Email` → `DMARC Management`, which will add a DMARC record to the DNS.

### Sender Canonical Maps (Per-User Mapping)

#### Create Sender Canonical Map

```.sh
nano /etc/postfix/sender_canonical
```

Add mappings for each virtual server user:

```.txt
website    noreply@website.com
```

#### Configure Postfix

```.sh
nano /etc/postfix/main.cf
```

Add this line:

```.txt
sender_canonical_maps = hash:/etc/postfix/sender_canonical
```

Apply changes:

```.sh
postmap /etc/postfix/sender_canonical
systemctl reload postfix
```

To verify: `Webmin` → `Servers` → `Postfix Mail Server` → `Canonical Mapping` → `Tables for sender addresses`: `hash:/etc/postfix/sender_canonical`.

### Troubleshooting

To diagnose general email sending/receiving issues: Open server's mail log in real-time to monitor activity:

```.sh
# sudo tail -f /var/log/mail.log
journalctl -u postfix -f
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
- `Backup destinations`: `Local file or directory` - `/backups/backup-%Y-%m-%d/`.
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

    # Cloudflare Real IP Restoration (updated from https://www.cloudflare.com/ips/)
    set_real_ip_from 173.245.48.0/20;
    set_real_ip_from 103.21.244.0/22;
    set_real_ip_from 103.22.200.0/22;
    set_real_ip_from 103.31.4.0/22;
    set_real_ip_from 141.101.64.0/18;
    set_real_ip_from 108.162.192.0/18;
    set_real_ip_from 190.93.240.0/20;
    set_real_ip_from 188.114.96.0/20;
    set_real_ip_from 197.234.240.0/22;
    set_real_ip_from 198.41.128.0/17;
    set_real_ip_from 162.158.0.0/15;
    set_real_ip_from 104.16.0.0/13;
    set_real_ip_from 104.24.0.0/14;
    set_real_ip_from 172.64.0.0/13;
    set_real_ip_from 131.0.72.0/22;

    set_real_ip_from 2400:cb00::/32;
    set_real_ip_from 2606:4700::/32;
    set_real_ip_from 2803:f800::/32;
    set_real_ip_from 2405:b500::/32;
    set_real_ip_from 2405:8100::/32;
    set_real_ip_from 2a06:98c0::/29;
    set_real_ip_from 2c0f:f248::/32;

    real_ip_header CF-Connecting-IP;
    real_ip_recursive off; # Not needed for CF-Connecting-IP (single IP)

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
    server_name website.com www.website.com mail.website.com webmail.website.com;
    listen 100.00.000.01;
    listen 100.00.000.01:443 ssl;
    listen [1000:0000:0000:0000:0000:0000:0000:0000];
    listen [1000:0000:0000:0000:0000:0000:0000:0000]:443 ssl;
    ssl_certificate /etc/ssl/virtualmin/100000000000000/ssl.cert;
    ssl_certificate_key /etc/ssl/virtualmin/100000000000000/ssl.key;
    set $content_security_policy "default-src 'self'; connect-src 'self' https://api.wordpress.org https://google.com https://pagead2.googlesyndication.com https://*.google-analytics.com https://analytics.google.com https://www.googletagmanager.com https://googleads.g.doubleclick.net https://www.googleadservices.com https://*.googleapis.com https://www.paypal.com https://www.sandbox.paypal.com https://*.stripe.com https://*.mercadopago.com https://*.mercadolibre.com https://api.mercadolibre.com; font-src 'self' data: https://fonts.gstatic.com; worker-src 'self' blob:; frame-src 'self' https://www.google.com https://www.googletagmanager.com https://td.doubleclick.net https://recaptcha.google.com https://www.youtube-nocookie.com https://www.paypal.com https://*.stripe.com https://www.mercadolibre.com https://api-static.mercadopago.com; img-src 'self' data: https://ps.w.org https://s.w.org https://t.paypal.com https://www.paypalobjects.com https://www.google.com https://www.google.de https://www.google-analytics.com https://www.googletagmanager.com https://googleads.g.doubleclick.net https://pagead2.googlesyndication.com https://*.stripe.com https://*.mercadopago.com https://*.mercadolibre.com https://http2.mlstatic.com; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://*.cloudflare.com https://static.cloudflareinsights.com https://www.google.com https://www.googletagmanager.com https://www.google-analytics.com https://www.gstatic.com https://googleads.g.doubleclick.net https://www.youtube.com https://www.youtube-nocookie.com https://www.paypal.com https://www.paypalobjects.com https://*.mercadopago.com https://http2.mlstatic.com https://www.googleadservices.com https://pagead2.googlesyndication.com https://*.stripe.com https://*.googleapis.com; style-src 'self' 'unsafe-inline' https://*.googleapis.com https://www.gstatic.com https://http2.mlstatic.com;";

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

    location ^~ /wp-content/uploads/ {
        autoindex off;
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

    #location / {
    #    return 301 https://website.com/$request_uri;
    #}

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

##### DNSSEC

Cloudflare → Website → `DNS` → `Settings` → Enable `DNSSEC`.

#### Security

Cloudflare → Website → `Security` → `Security rules`.

1. ACME Challenge Passthrough

- `Rule name`: `ACME Challenge Passthrough`.
- `Expression`: `http.request.uri.path contains "/.well-known/acme-challenge/"`.
- `Choose action`: `Skip` (select all `WAF components to skip`).

2. Block AI Scrapers and Crawlers

- `Rule name`: `Block AI Scrapers and Crawlers`.
- `Expression`: `(cf.verified_bot_category eq "AI Crawler")`.
- `Choose action`: `Block`.

3. Managed Access

- `Rule name`: `Managed Access`.
- `Expression`: `(not cf.client.bot and not ip.geoip.country in {"AT" "CH" "DE" "LU"} and ip.src ne $server_ip)`.
- `Choose action`: `Managed Challenge`.

4. WordPress

- `Rule name`: `WordPress`.
- `Expression`: `(http.request.uri.path contains "/wp-admin/" or http.request.uri.path contains "/wp-login.php" or http.request.uri.path contains "/xmlrpc.php" or http.request.uri.path contains "/my-account/" or http.request.uri.path contains "/mein-account/" or http.request.uri.path contains "/gift-card-redemption/" or http.request.uri.path contains "/gutschein-einlosen/")`
- `Choose action`: `Managed Challenge`.

#### Caching

Cloudflare → Website → `Caching` → `Cache Rules`.

1. Cache Bypass

- Rule name: `Cache Bypass - Virtualmin`.
- If incoming requests match...: `Custom filter expression`:

```.txt
(starts_with(http.host, "virtualmin."))
```

- Then... `Bypass cache`.

- Browser TTL: `Respect origin TTL`.

2. Cache Bypass

- Rule name: `Cache Bypass`.
- If incoming requests match...: `Custom filter expression`:

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

3. Cache Everything

- Rule name: `Cache Everything`.
- If incoming requests match...: `All incoming requests`.
- Then... `Eligible for cache`.

### PHP-FPM Configuration

```.sh
# Create PHP slow log
sudo touch /home/$domain/logs/php_slow.log

# Set ownership
sudo chown $system_user:$system_user /home/$domain/logs/php_slow.log

# Set permissions
sudo chmod 664 /home/$domain/logs/php_slow.log
```

#### Local

```.txt
[100000000000000]
; Settings
user = $system_user
group = $system_user
listen.owner = $system_user
listen.group = $system_user
listen.mode = 0660
listen = /run/php/100000000000000.sock
php_value[upload_tmp_dir] = /home/$domain/tmp
php_value[session.save_path] = /home/$domain/tmp
php_value[error_log] = /home/$domain/logs/php_log
slowlog = /home/$domain/logs/php_slow.log

; Logging
php_value[log_errors] = On
php_admin_value[error_reporting] = E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED
php_admin_value[display_errors] = Off

; Timeouts
request_terminate_timeout = 60s
catch_workers_output = yes
decorate_workers_output = no

; PHP Values
; php_admin_value[memory_limit] = 200M ; Low-traffic
php_admin_value[memory_limit] = 380M ; Medium-traffic
php_admin_value[upload_max_filesize] = 32M
php_admin_value[post_max_size] = 32M
php_admin_value[max_input_vars] = 3000
php_value[max_execution_time] = 60

; Process Management (low-traffic)
; pm = ondemand
; pm.max_children = 8
; pm.max_requests = 500
; pm.process_idle_timeout = 30s

; Process Management (medium-traffic)
pm = dynamic
pm.max_children = 30
pm.start_servers = 8
pm.min_spare_servers = 6
pm.max_spare_servers = 12
pm.max_requests = 500
pm.process_idle_timeout = 30s

; Per-Domain OPcache Logic
php_admin_flag[opcache.enable] = on
php_admin_value[opcache.revalidate_freq] = 2
php_admin_value[opcache.validate_timestamps] = 1

; PHP slow log
request_slowlog_timeout = 5s
```

```.sh
# Restart PHP-FPM service
sudo systemctl restart php*-fpm
```

#### OPcache

`Webmin` → `Tools` → `PHP Configuration` → `Module config` (⚙) → `Configurable options`:

```.txt
/etc/php*/cgi/php.ini,/etc/php/*/cgi/php.ini=Configuration for CGI
/etc/php*/cli/php.ini,/etc/php/*/cli/php.ini=Configuration for CLI
/etc/php*/fpm/php.ini,/etc/php/*/fpm/php.ini=Configuration for PHP-FPM
```

Select `/etc/php/*/fpm/php.ini` → `Edit Manually`:

```.txt
[opcache]
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=384
opcache.interned_strings_buffer=48
opcache.max_accelerated_files=40000
opcache.validate_timestamps=1
opcache.revalidate_freq=2
opcache.enable_file_override=1
; opcache.optimization_level=0x7FFFBFFF
opcache.jit=off ; SIGSEGV (Signal 11) during loops/updates, see: https://github.com/php/php-src/issues/20166
opcache.jit_buffer_size=128M
opcache.save_comments=1
opcache.huge_code_pages=0
```

```.sh
sudo systemctl restart php*-fpm
```

### MariaDB

```.sh
sudo nano /etc/mysql/mariadb.conf.d/50-server.cnf
```

```.txt
[mariadbd]
# Connections
max_connections = 100
table_open_cache = 2000

# InnoDB Performance
innodb_buffer_pool_size = 4G
innodb_buffer_pool_instances = 4
innodb_log_file_size = 512M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT

# Query optimizations
tmp_table_size = 128M
max_heap_table_size = 128M
join_buffer_size = 4M
```

```.sh
sudo systemctl restart mariadb
```

### SSL Certificate

```.sh
mkdir -p $domain_root_path/public_html/.well-known/acme-challenge
chmod -R 755 $domain_root_path/public_html/.well-known
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
rm $domain_root_path/public_html/.well-known/acme-challenge/.htaccess
```

```.sh
# Delete certificate
# sudo certbot delete --cert-name autodiscover.$domain
```

### PHP settings

- `Virtualmin` → Choose Virtual Server → `Web Configuration` → `PHP Options` → `PHP script execution mode`: `FPM`.

```.sh
# Alternatively
# virtualmin modify-web --domain $domain --mode fpm
# virtualmin list-domains --domain $domain --multiline | grep "PHP mode"
```

- `Virtualmin` → Choose Virtual Server → `Web Configuration` → `PHP-FPM Configuration` → `Resource Limits`.
- `Virtualmin` → Choose Virtual Server → `Web Configuration` → `PHP-FPM Configuration` → `Error Logging` → `Error types to display` → `All errors and warnings`.

## WordPress migration

### Database migration

```.sh
# Import .sql
mysql -u "$system_user" -p "$database_name" < $domain_root_path/public_html/"$database_name".sql

# Delete dataset
rm $domain_root_path/public_html/"$database_name".sql
```

### Files migration

```.sh
# Extract the contents of the "wordpress_export.zip" file to the $domain_root_path/public_html folder
unzip "$domain_root_path/public_html/wordpress_export.zip" "*" -d $domain_root_path/public_html

# Delete .zip file
rm "$domain_root_path/public_html/wordpress_export.zip"
```

### Ownership and permission

```.sh
# Set ownership
chown -R "$system_user" $domain_root_path/public_html

# Set permissions
find $domain_root_path/public_html -type d -exec chmod 755 {} \;
find $domain_root_path/public_html -type f -exec chmod 644 {} \;
chmod 600 $domain_root_path/public_html/wp-config.php
```

## Tools

```.sh
# Delete empty folders recursively
# find /var/www/vhosts/"$domain"/httpdocs/wp-content/uploads -type d -empty -delete
```

### Emails migration

```.sh
imapsync --host1 "imap.server1.com" --user1 "email@domain.com" --password1 "password-server1" \
  --host2 $server_ip --user2 "email@domain.com" --password2 "password-server2" \
  --exclude "Spam"
```

### Export MariaDB database

```.sh
# Create dump
mysqldump -u root -p $database_name > $(dirname "$domain_root_path/public_html")/backup.sql

# Delete file after downloading it
rm $(dirname "$domain_root_path/public_html")/backup.sql
```

## Troubleshooting

### Logs

```.sh
# Nginx
tail -n 50 /var/log/nginx/error.log
tail -n 50 /var/log/virtualmin/${domain}_error_log

# Clear log file
# > /var/log/virtualmin/${domain}_error_log


# PHP
tail -n 50 /var/log/php8.5-fpm.log
tail -n 50 $(dirname "$domain_root_path/public_html")/logs/php_log
```

### Cache

```.sh
# Clear nginx cache
sudo rm -rf /var/cache/nginx/*
sudo systemctl reload nginx
```

### Server stress test

```.sh
ab -n 10 $domain
```

#### Virtualmin Nginx module

```.sh
# Verify version
cat /usr/share/webmin/virtualmin-nginx/module.info | grep version

# Backup current module
cp -r /usr/share/webmin/virtualmin-nginx /usr/share/webmin/virtualmin-nginx.bak

# Download latest from GitHub
cd /tmp
wget https://github.com/virtualmin/virtualmin-nginx/archive/refs/heads/master.zip
unzip master.zip

# Install it
cp -r virtualmin-nginx-master/* /usr/share/webmin/virtualmin-nginx/

# Restart Webmin
systemctl restart webmin

# Verify version
cat /usr/share/webmin/virtualmin-nginx/module.info | grep version

# Remove the backup
# rm -rf /usr/share/webmin/virtualmin-nginx.bak
```
