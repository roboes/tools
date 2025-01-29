## Debian Server Setup
# Last update: 2025-01-13


# Settings
server_ip="100.00.000.01"
website="website.com"
system_user="www-data:www-data"


# Check Debian version
cat /etc/os-release


# Update packages
sudo apt update && sudo apt upgrade -y && sudo apt dist-upgrade -y && sudo apt autoremove -y && sudo apt clean


# Change locale

## Check Current Locale Settings
locale

## Reconfigure Locales
sudo dpkg-reconfigure locales

## Update the Environment Variables
nano ~/.bashrc

# Add or update the following line
# export LANG=en_US.UTF-8


# Install packages
sudo apt-get install curl
sudo apt-get install apache2
sudo apt-get install python3 python3-pip
sudo apt-get install php php-mysql php-mbstring php-intl
sudo apt-get install fail2ban
sudo apt-get install libauthen-oath-perl
sudo apt-get install geoip-bin libapache2-mod-geoip
sudo apt-get install geoip-database
sudo apt-get install geoip-database-extra
sudo apt-get install python-is-python3
sudo apt-get install sqlite3
sudo apt-get install libapache2-mod-wsgi-py3




# Virtualmin

## Installation
wget http://software.virtualmin.com/gpl/scripts/install.sh
chmod a+x install.sh
./install.sh

sudo apt install webmin --install-recommends -y


## Virtualmin > System Settings > Virtualmin Configuration > Configuration category: Defaults for new domains > Set the "Home subdirectory" to ${DOM}


## Virtualmin > Manage Web Apps
# Install phpMyAdmin, RoundCube


## Enable Two-Factor Authentication (2FA)
# Webmin > Webmin > Webmin Configuration > Two-Factor Authentication > Authentication provider: "Google Authenticator"
# Webmin > Webmin > Webmin Users > Two-Factor Authentication


## Disable POP3
# Webmin > Servers > Dovecot IMAP/POP3 Server > Networking and Protocols > Uncheck "POP3"


## Fail2Ban
# Fail2Ban Intrusion Detector > Filter Action Jails > Jail name > sshd
# Matches before applying action: 3
# Max delay between matches: 60
# Time to ban IP for: 86400


## IP Access Control - https://www.ipdeny.com/ipblocks/
# Webmin > Webmin > Webmin Configuration > IP Access Control > Allowed IP addresses > Only allow from listed addresses


## Generate the SSH Key Pair
ssh-keygen -t rsa -b 4096 -C "root@$server_ip"

### Add the Public Key to the Authorized Keys
cat /root/.ssh/id_rsa.pub >> /root/.ssh/authorized_keys
chmod 700 /root/.ssh
chmod 600 /root/.ssh/authorized_keys

### Save the private key (id_rsa) on your local machine

### Configure SSH to Use Key-Based Authentication
nano /etc/ssh/sshd_config

###
PasswordAuthentication no
PubkeyAuthentication yes
PermitRootLogin prohibit-password
###


## GeoIP
# Webmin > Servers > Apache Webserver > Global configuration > Configure Apache Modules > Enable "geoip"


## Let's Encrypt
mkdir -p /home/$website/public_html/.well-known/acme-challenge
chmod -R 755 /home/$website/public_html/.well-known
touch /home/$website/public_html/.htaccess

###
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteRule ^\.well-known/acme-challenge/ - [L]
</IfModule>
###

sudo certbot certonly --manual --preferred-challenges dns -d autodiscover.$website

# Verify the TXT Record
dig TXT _acme-challenge.autodiscover.$website


## Changes the ownership
chown -R "$system_user" /home/"$website"/public_html

## Change permissions
find /home/"$website"/public_html -type d -exec chmod 755 {} \;
find /home/"$website"/public_html -type f -exec chmod 644 {} \;


# Migrate emails from one server to another by backing up emails locally, updating DNS records, and restoring them to the new server

## Create backup directory
mkdir -p "$HOME/Downloads/EmailsBackup"

## Backup emails from "Server 1" by downloading all emails to the backup directory
imap-backup --host imap.server1.com --user "info@website.com" --password "password-server1" --backup-dir "$HOME/Downloads/EmailsBackup"

## Update the MX records in the DNS settings to point to the "Server 2". Wait for DNS propagation to complete before proceeding.

## Restore emails from "Server 1" to "Server 2" by uploading the backed-up emails from the backup directory
imap-backup --host imap.server2.com --user "info@website.com" --password "password-server2" --restore-dir "$HOME/Downloads/EmailsBackup"
