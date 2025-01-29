## Plesk Obsidian Tools
# Last update: 2024-11-09


# Switch to the root user account
# su root

# Update packages
sudo apt update && sudo apt upgrade -y && sudo apt dist-upgrade -y && sudo apt autoremove -y && sudo apt clean

# Settings
# export PATH=$PATH:/usr/sbin # PATH environment variable (plesk executable /usr/sbin/plesk)


# Set up SSH keys for Plesk server
# https://support.plesk.com/hc/en-us/articles/12377591587863-How-to-set-up-SSH-keys-for-Plesk-server

## Create the RSA Key Pair
# ssh-keygen -t rsa -b 2048 -f /root/.ssh/id_rsa

## Create authorized_keys file if it does not exist
# touch /root/.ssh/authorized_keys

## Append the contents of "id_rsa.pub" file to the end of "authorized_keys"
# cat /root/.ssh/id_rsa.pub >> /root/.ssh/authorized_keys

## Fix permissions
# chmod 600 /root/.ssh/authorized_keys

## After successful login using the SSH key, restrict the password-based login by setting "PermitRootLogin" to "without-password"
# vi /etc/ssh/sshd_config

## Apply the changes
# service sshd reload


# Run SSH

# Execute the following command to copy the keys from Windows to Windows Subsystem for Linux (WSL)
# cp -r "/mnt/c/Users/${USER}/Documents/id_rsa" ~/.ssh/id_rsa

# Fix permissions
# chmod 600 ~/.ssh/id_rsa

# ssh root@00.00.000.000 -i ~/.ssh/id_rsa


# List of all installed Plesk extensions
# plesk bin extension --list

# Available Plesk components for the current installed version
# plesk installer --select-release-current --show-components


# Fail2Ban

## Install bsdutils (required for running Fail2Ban)
apt-get install bsdutils

# Enable
plesk bin ip_ban --enable


# HTTP/2

## Enable
plesk bin http2_pref enable


# Panel.ini Editor

## Install
plesk installer --select-release-current --install-component panel-ini-editor

## Enable
plesk bin extension --enable panel-ini-editor

## Add these lines to /opt/psa/admin/conf/panel.ini using Panel.ini Editor
# [ext-firewall]
# confirmTimeout = 120
# confirmTimeoutCli = 120


# Plesk Firewall

## Install
plesk installer --select-release-current --install-component psa-firewall

## Enable
plesk bin extension --enable firewall
/usr/local/psa/bin/modules/firewall/settings -e


# Enable Nginx caching
# https://support.plesk.com/hc/en-us/articles/12377223196439-How-to-enable-Nginx-caching-in-Plesk


# Brotli PHP Extension
# https://support.plesk.com/hc/en-us/articles/12387812757143-Does-Plesk-support-BROTLI-compression-for-Apache-or-Nginx-web-server

## List installed PHP versions
ls "/opt/plesk/php"

## Install Brotli PHP Extension
apt-get install plesk-php83-dev make gcc git
git clone --recursive --depth=1 https://github.com/kjdev/php-ext-brotli.git
cd php-ext-brotli
/opt/plesk/php/8.3/bin/phpize
./configure --with-php-config=/opt/plesk/php/8.3/bin/php-config
make
cp /root/php-ext-brotli/modules/brotli.so /opt/plesk/php/8.3/lib/php/modules/
echo "extension=brotli.so" > /opt/plesk/php/8.3/etc/php.d/brotli.ini
plesk bin php_handler --reread

# Test: Check that brotli PHP module is loaded
/opt/plesk/php/8.3/bin/php -m | grep brotli


# Redis
# https://support.plesk.com/hc/en-us/articles/12388573926423-How-to-install-Redis-on-Plesk-for-Linux
# apt install plesk-php83-redis
# apt install redis
# systemctl start redis-server
# systemctl enable redis-server

# Test
# redis-server --version
# lsof -i tcp:6379
# redis-cli ping


# Redirect from Web Server's Default Page
# https://www.plesk.com/kb/support/how-to-change-the-web-servers-default-page-for-domains-with-no-hosting-and-in-disabled-status-in-plesk/
# https://www.plesk.com/kb/support/how-to-configure-redirect-from-web-servers-default-page-or-existing-domains-to-the-plesk-login-page-on-port-8443-on-plesk-for-linux/


# Enable "keep-alive" requests in Apache
# https://support.plesk.com/hc/en-us/articles/12377795259287-TTFB-for-site-in-Plesk-is-too-high-What-can-be-done-to-improve-it



# Memcached PHP
# https://support.plesk.com/hc/en-us/articles/12377651968023-How-to-install-uninstall-memcached-PHP-extension-for-Plesk-PHP-handlers
# apt install memcached autoconf automake gcc libmemcached-dev libhashkit-dev pkg-config plesk-php*-dev zlib1g-dev make
# /opt/plesk/php/8.3/bin/pecl install memcached
# echo "extension=memcached.so" > /opt/plesk/php/8.3/etc/php.d/memcached.ini
# plesk bin php_handler --reread
# service plesk-php83-fpm restart
# service apache2 restart


# The Python support is missing from domain’s Hosting Settings page in Plesk
# https://www.plesk.com/kb/support/the-python-support-is-missing-from-domains-hosting-settings-page-in-plesk/


# How to apply PHP-FPM pool settings for all domains in Plesk?
# https://www.plesk.com/kb/support/how-to-apply-php-fpm-pool-settings-for-all-domains-in-plesk/
pm.max_children = 50
pm.max_requests = 100

# How to troubleshoot slow performance of MySQL/MariaDB on a Plesk server?
# https://www.plesk.com/kb/support/how-to-troubleshoot-slow-performance-of-mysql-mariadb-on-a-plesk-server/
# https://gist.github.com/fevangelou/fb72f36bbe333e059b66


# How to enable the MySQL/MariaDB slow query log and analyze it on a Plesk for Linux server
# https://www.plesk.com/kb/support/how-to-enable-the-mysql-mariadb-slow-query-log-and-analyze-it-on-a-plesk-for-linux-server/
