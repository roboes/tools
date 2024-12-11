## Debian Server Setup
# Last update: 2024-11-15


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




# Virtualmin - Installation

wget http://software.virtualmin.com/gpl/scripts/install.sh
chmod a+x install.sh
./install.sh

sudo apt install webmin --install-recommends -y


# Virtualmin > Manage Web Apps
# Install phpMyAdmin, RoundCube

# Enable Two-Factor Authentication (2FA)
# Webmin > Webmin > Webmin Configuration > Two-Factor Authentication > Authentication provider: "Google Authenticator"
# Webmin > Webmin > Webmin Users > Two-Factor Authentication

# Disable POP3
# Webmin > Servers > Dovecot IMAP/POP3 Server > Networking and Protocols > Uncheck "POP3"

# Fail2Ban
# Fail2Ban Intrusion Detector > Filter Action Jails > Jail name > sshd
# Matches before applying action: 3
# Max delay between matches: 60
# Time to ban IP for: 86400


# GeoIP
# Webmin > Servers > Apache Webserver > Global configuration > Configure Apache Modules > Enable "geoip"








## Backup
