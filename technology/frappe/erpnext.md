# ERP Next Installation (Development Mode)

> [!NOTE]
> Last update: 2025-01-28
> Installation realized on Debian and Plesk.\
> Database name and username need to be the same.

## Settings

```.sh
website="website.com"
system_user=""
system_group=""
site_name=""
bench_directory="/var/www/vhosts/$website"/httpdocs/erpnext
database_name=""
database_host="localhost"
database_port=3306
database_password=""
erpnext_admin_password=""
```

### Install required packages (using root)

```.sh
apt install mariadb-client && apt install default-libmysqlclient-dev
```

### Install Frappe bench (using root)

```.sh
python -m pip install frappe-bench --break-system-packages
bench --version
```

#### Setup Supervisor (using root)

```.sh
# Initialize supervisor setup
bench setup supervisor

# Check supervisor status
sudo supervisorctl status
```

#### Give user permission to supervisor (using root)

```.sh
id $system_user
```

```.sh
# Edit supervisor configuration
sudo nano /etc/supervisor/supervisord.conf
```

Add this row to supervisord.conf (set up variables above will not work here):

```.txt
chown=$system_user:$system_group
```

```.sh
# Restart supervisor service
sudo service supervisor restart
```

#### Setup Frappe bench

```.sh
# Create bench directory
mkdir -p $bench_directory

# Change current directory
cd $bench_directory

# Initialize Frappe bench
bench init frappe-bench --frappe-branch version-15

# Change current directory
cd $bench_directory/frappe-bench

# Get ERPNext app
bench get-app erpnext --branch version-15

# Get Payments app
bench get-app payments --branch version-15
```

#### Create a new Frappe site

> [!WARNING]
> MySQL `root` user needs access authorization to host `127.0.0.1`, as `localhost` access is treated separately and may not suffice.

```.sh
# Create a new Frappe site
bench new-site $site_name \
  --db-type mariadb \
  --db-host $database_host \
  --db-port $database_port \
  --db-name $database_name \
  --db-password $database_password \
  --admin-password $erpnext_admin_password

# Set $site_name as default site
bench use $site_name
```

```.sh
# Set admin password
# bench --site $site_name set-admin-password $erpnext_admin_password
```

#### (If required) Edit the `$bench_directory/frappe-bench/sites/common_site_config.json` file, like updating the redis ports to `6379`

```.txt
"redis_cache": "redis://127.0.0.1:6379",
"redis_queue": "redis://127.0.0.1:6379",
"redis_socketio": "redis://127.0.0.1:6379",
```

#### (If required) Edit the `$bench_directory/frappe-bench/sites/$site_name/site_config.json` file and include `host_name`

```.txt
"host_name": "/erpnext"
```

#### Install ERPNext app

```.sh
bench --site $site_name install-app erpnext
```

#### ERPNext modules install

```.sh
# WooCommerce Fusion
bench get-app --branch version-15 https://github.com/dvdl16/woocommerce_fusion
bench --site $site_name install-app woocommerce_fusion

# WooCommerce Integration for ERPNext
# bench get-app --branch version-15 https://github.com/alyf-de/woocommerce_integration
# bench --site $site_name install-app woocommerce_integration

# ERPNext WooCommerce Connector
# bench get-app --branch version-15 https://github.com/libracore/woocommerceconnector.git
# bench --site $site_name install-app woocommerceconnector

# Migrate the database
bench --site $site_name migrate

# Clear cache
bench --site $site_name clear-cache
```

#### ERPNext modules uninstall

```.sh
# bench --site $site_name uninstall-app woocommerce_fusion
```

#### Permissions

##### Change ownership

```.sh
chown -R $system_user:$system_group $bench_directory
```

##### Change files and folders permissions

```.sh
find $bench_directory -type d -exec chmod 755 {} \;
find $bench_directory -type f -exec chmod 644 {} \;
chmod +x $bench_directory/frappe-bench/apps/frappe/node_modules/esbuild-linux-64/bin/esbuild
```

#### Additional Apache directives for HTTP/HTTPS

```.txt
<Location />
 ProxyPass http://127.0.0.1:8000/
 ProxyPassReverse http://127.0.0.1:8000/
</Location>
```

### Initiate ERPNext (development mode)

```.sh
# Change current directory
cd /var/www/vhosts/$website/httpdocs

# Create virtual environment
python3 -m venv venv
source venv/bin/activate

# Change current directory
cd $bench_directory/frappe-bench

# Serve the site
bench serve
```
