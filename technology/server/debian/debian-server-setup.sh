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


# Virtualmin - Installation

wget http://software.virtualmin.com/gpl/scripts/install.sh
chmod a+x install.sh
./install.sh

sudo apt install webmin --install-recommends -y


# Virtualmin > Manage Web Apps
# Install phpMyAdmin, RoundCube,

# Enable Two-Factor Authentication (2FA)
# Webmin > Webmin > Webmin Configuration > Two-Factor Authentication > Authentication provider: "Google Authenticator"
# Webmin > Webmin > Webmin Users > Two-Factor Authentication

# Disable POP3
# Webmin > Servers > Dovecot IMAP/POP3 Server > Networking and Protocols > Uncheck "POP3"

## Backup
