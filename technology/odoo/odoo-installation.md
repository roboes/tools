# Odoo Installation

> [!NOTE]
> Last update: 2025-02-05
> Installation realized on Debian and Plesk.

## Settings

```.sh
website="website.com"
website_root_directory="/var/www/vhosts/$website/httpdocs"
system_user=""
system_group=""
odoo_version="16.0"
database_name=""
database_host="localhost"
database_port=5432
database_username=""
database_password=""
odoo_database_manager_password=""
```

## Install Dependencies

```.sh
apt update && apt upgrade -y
apt install -y python3 python3-pip python3-venv \
  git wget nodejs npm libldap2-dev libsasl2-dev \
  libssl-dev libjpeg-dev libpq-dev \
  build-essential libxml2-dev libxslt1-dev \
  libffi-dev libtiff5-dev \
  zlib1g-dev libopenjp2-7-dev
```

```.sh
#
sudo -i -u postgres psql -c "CREATE USER $database_username WITH PASSWORD '$database_password';"
sudo -i -u postgres psql -c "ALTER USER $database_username WITH CREATEDB;"
sudo -i -u postgres psql -c "CREATE DATABASE $database_name OWNER $database_username;"
```

## Download and Install Odoo

```.sh
# Change current directory
cd "$website_root_directory"

#
git clone --depth 1 --branch $odoo_version https://www.github.com/odoo/odoo.git "$website_root_directory/odoo"
```

## Install Python Requirements

```.sh
# Change current directory
cd "$website_root_directory/odoo"

#
python -m venv "./venv"

#
source "./venv/bin/activate"

#
python -m pip install -r "./requirements.txt"
python -m pip install woocommerce

#
deactivate
```

## Configuration File

```.sh
#
touch "/var/log/odoo.log"
```

```.sh
cat <<EOF > "/etc/odoo.conf"
[options]
proxy_mode = True
http_port = 8069
admin_passwd = $odoo_database_manager_password
db_name = $database_name
db_host = $database_host
db_port = $database_port
db_user = $database_username
db_password = $database_password
addons_path = $website_root_directory/odoo/addons
logfile = /var/log/odoo.log
workers = 2
server_wide_modules = web,queue_job

[queue_job]
channels = root:2
EOF
```

## Create Systemd Service

```.sh
cat <<EOF > "/etc/systemd/system/odoo.service"
[Unit]
Description=Odoo
After=network.target postgresql.service

[Service]
Type=simple
User=$system_user
Group=$system_group
ExecStartPre=$website_root_directory/odoo/venv/bin/python3 $website_root_directory/odoo/odoo-bin --config=/etc/odoo.conf --database $database_name --init base --without-demo=all
ExecStart=$website_root_directory/odoo/venv/bin/python3 $website_root_directory/odoo/odoo-bin --config=/etc/odoo.conf --without-demo=all
Restart=always

[Install]
WantedBy=multi-user.target
EOF
```

### Permissions

#### Change ownership

```.sh
chown -R $system_user:$system_group "$website_root_directory/odoo"
chown $system_user:$system_group "/etc/odoo.conf"
chown $system_user:$system_group "/var/log/odoo.log"

sudo chown root:$system_group /etc/systemd/system/odoo.service
```

#### Change files and folders permissions

```.sh
find "$website_root_directory/odoo" -type d -exec chmod 755 {} \;
find "$website_root_directory/odoo" -type f -exec chmod 644 {} \;
chmod 644 "/etc/odoo.conf"
chmod 644 "/var/log/odoo.log"

sudo chmod 644 /etc/systemd/system/odoo.service
```

## Start and Enable Odoo Service

```.sh
systemctl daemon-reload
# systemctl start odoo
systemctl enable odoo

# Check if Odoo is running
systemctl status odoo.service

# systemctl disable odoo
# systemctl stop odoo
# systemctl restart odoo

# Remove the service file
# rm "/etc/systemd/system/odoo.service"
```

### Additional Apache directives for HTTP/HTTPS

```.txt
<Location />
 ProxyPass http://127.0.0.1:8069/
 ProxyPassReverse http://127.0.0.1:8069/
</Location>
```
