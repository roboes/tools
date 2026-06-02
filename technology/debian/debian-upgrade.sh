## Debian upgrade
# Last update: 2025-08-18


# Pre-upgrade system preparation

## Create System Backup
mkdir -p /backup
tar -czf /backup/etc-backup-$(date +%Y%m%d).tar.gz /etc
dpkg --get-selections > /backup/package-selections-$(date +%Y%m%d).txt

## Update current system
apt update
apt upgrade
apt dist-upgrade

## Clean package cache
apt clean
apt autoremove

## Check system status
apt --fix-broken install
dpkg --configure -a


# Repository configuration update

## Backup sources configuration
cp /etc/apt/sources.list /etc/apt/sources.list.bookworm-backup
cp -r /etc/apt/sources.list.d /etc/apt/sources.list.d.bookworm-backup

## Update main sources list and additional repositories
sed -i 's/bookworm/trixie/g' /etc/apt/sources.list
find /etc/apt/sources.list.d -name "*.list" -exec sed -i 's/bookworm/trixie/g' {} \;

## Refresh package database
apt update


# Debian 13 upgrade process

## Minimal upgrade first
apt upgrade --without-new-pkgs

## Full distribution upgrade
apt full-upgrade


# Post-upgrade verification

## Verify system version
cat /etc/debian_version
lsb_release -a
cat /etc/os-release

## Clean obsolete packages
apt autoremove
apt autoclean

## Update package cache
apt update
apt list --upgradable

## System reboot
systemctl reboot


# Remove backups
# rm -rf /backup
# rm /etc/apt/sources.list.bookworm-backup
# rm -rf /etc/apt/sources.list.d.bookworm-backup



# Reset Dovecot to default settings

## Remove packages and all config files
sudo apt purge --autoremove dovecot-core dovecot-imapd dovecot-pop3d

## Install packages with fresh config files
sudo apt install dovecot-core dovecot-imapd dovecot-pop3d

## Restart the service
systemctl restart dovecot

## Check service status
systemctl status dovecot
