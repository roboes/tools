# Odoo Installation on Debian

> [!NOTE]
> Last update: 2025-08-07

## Settings

```.sh
website="website.com"
website_root_path="/home/$website/public_html"
system_user=""
system_group=""
odoo_version="18.0"
database_name=""
database_host="localhost"
database_port=5432
database_username=""
database_password=""
odoo_database_manager_password=""
odoo_conf="/etc/odoo/$website.conf"
addons_path="$website_root_path/odoo/addons"
```

## Install Dependencies

```.sh
apt update && apt upgrade -y
apt install -y python3 python3-pip python3-venv python-is-python3 \
  git wget nodejs npm libldap2-dev libsasl2-dev \
  libssl-dev libjpeg-dev libpq-dev \
  build-essential libxml2-dev libxslt1-dev \
  libffi-dev libtiff5-dev \
  zlib1g-dev libopenjp2-7-dev \
  postgresql postgresql-contrib \
  wkhtmltopdf
```

```.sh
#
sudo -i -u postgres psql <<EOF
CREATE USER $database_username WITH PASSWORD '$database_password';
ALTER USER $database_username WITH CREATEDB;
CREATE DATABASE $database_name OWNER $database_username;
EOF
```

## Download and Install Odoo

```.sh
# Change current directory
cd "$website_root_path"

#
git clone --depth 1 --branch $odoo_version https://www.github.com/odoo/odoo.git "$website_root_path/odoo"
```

## Install Python Requirements

```.sh
# Change current directory
cd "$website_root_path/odoo"

# Remove existing virtual environment
rm -rf ./venv

# Create a virtual environment
python -m venv "./venv"
# Alternative using pyenv: /root/.pyenv/versions/3.11.11/bin/python3.11 -m venv "./venv"

# Activate the virtual environment
source "./venv/bin/activate"

# Verify the Python version inside the virtual environment
python -V

# Install Python dependencies
python -m pip install -r "./requirements.txt"
python -m pip install filetype numpy opencv-python phonenumbers woocommerce
# python -m pip install brazilcep brazilfiscalreport email-validator erpbrasil.assinatura erpbrasil.base erpbrasil.edoc lxml_html_clean nfelib packaging pyyaml unidecode workalendar

# Exit the virtual environment
deactivate
```

## Configuration File

```.sh
# Create folders
mkdir -p /etc/odoo
mkdir -p /var/log/odoo

# Create log file
touch "/var/log/odoo/$website.log"
```

```.sh
cat <<EOF > "$odoo_conf"
[options]
proxy_mode = True
http_port = 8069
admin_passwd = $odoo_database_manager_password
db_name = $database_name
db_host = $database_host
db_port = $database_port
db_user = $database_username
db_password = $database_password
addons_path = $website_root_path/odoo/addons
logfile = /var/log/odoo/$website.log
workers = 2
server_wide_modules = web,queue_job

[queue_job]
channels = root:2
EOF
```

## Create Systemd Service

```.sh
cat <<EOF > "/etc/systemd/system/odoo@$website.service"
[Unit]
Description=Odoo Instance for $website
After=network.target postgresql.service

[Service]
Type=simple
User=$system_user
Group=$system_group
ExecStartPre=$website_root_path/odoo/venv/bin/python3 $website_root_path/odoo/odoo-bin --config=$odoo_conf --database $database_name --init base --without-demo=all
ExecStart=$website_root_path/odoo/venv/bin/python3 $website_root_path/odoo/odoo-bin --config=$odoo_conf --without-demo=all
Restart=always

[Install]
WantedBy=multi-user.target
EOF
```

### Permissions

#### Change ownership

```.sh
chown -R $system_user:$system_group "$website_root_path/odoo"
chown $system_user:$system_group "$odoo_conf"
chown $system_user:$system_group "/var/log/odoo/$website.log"

chown root:$system_group /etc/systemd/system/odoo@$website.service
```

#### Change files and folders permissions

```.sh
find "$website_root_path/odoo" -type d -exec chmod 755 {} \;
find "$website_root_path/odoo" -type f -exec chmod 644 {} \;
chmod +x $website_root_path/odoo/odoo-bin
chmod 644 "$odoo_conf"
chmod 644 "/var/log/odoo/$website.log"

chmod 644 /etc/systemd/system/odoo@$website.service
```

```.sh
# Change current directory
cd "$website_root_path/odoo"

# Activate the virtual environment
source "./venv/bin/activate"

# Run Odoo manually
./odoo-bin --config=$odoo_conf --database $database_name --load=base,web --without-demo=all --update=all
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
# rm "/etc/systemd/system/odoo@$website.service"
```

### Nginx directives

```.txt
server {
    location / {
        proxy_pass http://127.0.0.1:8069/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```
