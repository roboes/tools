## Plesk Obsidian Tools WordPress Migration
# Last update: 2024-07-14


# Switch to the root user account
# su root


# Settings
website="website.com"
system_user="website_user"
# export PATH=$PATH:/usr/sbin # PATH environment variable (plesk executable /usr/sbin/plesk)


# Change current directory
cd /var/www/vhosts/"$website"/


# Database migration

## Import .sql
mysql -u website_wordpress_user -p website_wordpress < "website_wordpress.sql"

## Create a copy of the dataset inside Plesk with a new name: Domains > website.com > Databases.


# WordPress files migration

## Extract the contents of the "website_wordpress" contained inside the "website_wordpress_export.zip" file to the "httpdocs" folder
unzip "./website_wordpress_export.zip" "website_wordpress/*" -d "./httpdocs"

## Move files (incl. hidden files) and folders
mv /var/www/vhosts/"$website"/httpdocs/website_wordpress/{*,.*} /var/www/vhosts/"$website"/httpdocs/


# Tools

## Copy the PHP executable
mkdir -p /var/www/vhosts/"$website"/php
cp /opt/plesk/php/8.3/bin/php /var/www/vhosts/"$website"/php/php

## Changes the ownership
chown -R "$system_user" /var/www/vhosts/"$website"/httpdocs

## Change permissions
find /var/www/vhosts/"$website"/httpdocs -type d -exec chmod 755 {} \;
find /var/www/vhosts/"$website"/httpdocs -type f -exec chmod 644 {} \;

## Delete empty folders recursively
# find /var/www/vhosts/"$website"/httpdocs/wp-content/uploads -type d -empty -delete
