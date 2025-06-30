# Odoo Uninstallation on Debian

> [!NOTE]
> Last update: 2025-06-17

## Settings

```.sh
website="website.com"
website_root_path="/home/$website/public_html"
system_user=""
system_group=""
odoo_version="16.0"
database_name=""
database_host="localhost"
database_port=5432
database_username=""
database_password=""
odoo_database_manager_password=""
odoo_conf="/etc/odoo/$website.conf"
addons_path="$website_root_path/odoo/addons"
```

```.sh
# Stop and remove systemd service if it exists
if [ -f "/etc/systemd/system/odoo@$website.service" ]; then
  systemctl stop "odoo@$website"
  systemctl disable "odoo@$website"
  rm -f "/etc/systemd/system/odoo@$website.service"
  systemctl daemon-reload
fi
```

```.sh
# Drop the PostgreSQL database and user
sudo -u postgres psql <<EOF
DROP DATABASE IF EXISTS $database_name;
DROP USER IF EXISTS $database_username;
EOF
```

```.sh
# Remove Odoo files, config, and logs
rm -rf "$website_root_path/odoo"
rm -f "$odoo_conf"
rm -f "/var/log/odoo/$website.log"
```
