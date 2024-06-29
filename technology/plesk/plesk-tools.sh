## Plesk Tools
# Last update: 2024-06-28


# Settings
website="website.com"
system_user="website_user"


# su root
su root

# Change current directory
cd /var/www/vhosts/"$website"/


# Database migration

## Import .sql
mysql -u website_wordpress_user -p website_wordpress < "website_wordpress.sql"

## Create a copy of the dataset inside Plesk with a new name: Domains > website.com > Databases.


# WordPress files migration

## Extract the contents of the "cafefazendasantoantonio" contained inside the "cafefazenda.zip" file to the "httpdocs" folder
unzip "./website_wordpress_export.zip" "website_wordpress/*" -d "./httpdocs"

## Move files (incl. hidden files) and folders
mv /var/www/vhosts/"$website"/httpdocs/website_wordpress/{*,.*} /var/www/vhosts/"$website"/httpdocs/

## Changes the ownership
chown -R "$system_user":psacln /var/www/vhosts/"$website"/httpdocs

## Change permissions
find /var/www/vhosts/"$website"/httpdocs -type d -exec chmod 755 {} \;
find /var/www/vhosts/"$website"/httpdocs -type f -exec chmod 644 {} \;


# Enable HTTP/2
# /usr/sbin/plesk bin http2_pref enable
