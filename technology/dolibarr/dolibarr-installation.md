# Dolibarr Installation

> [!NOTE]
> Last update: 2025-01-30
> Installation realized on Debian and Plesk.

## Settings

```.sh
website="website.com"
website_root_path="/var/www/vhosts/$website/httpdocs"
system_user=""
system_group=""
```

## Download and Install Dolibarr

```.sh
# Change current directory
cd $website_root_path

# Get the latest release tag dynamically
latest_version=$(curl -s https://api.github.com/repos/Dolibarr/dolibarr/releases/latest | grep '"tag_name":' | cut -d '"' -f 4)

# Download and save the latest Dolibarr version as "dolibarr.zip"
wget -O dolibarr.zip "https://github.com/Dolibarr/dolibarr/archive/refs/tags/$latest_version.zip"

# Extract directly into the working directory
unzip dolibarr.zip -d $website_root_path

# Create "dolibarr" directory
mkdir -p $website_root_path/dolibarr

# Move extracted files to "dolibarr" directory
mv $website_root_path/dolibarr-${latest_version}/* $website_root_path/dolibarr/

# Clean up
rm -rf $website_root_path/dolibarr-${latest_version} dolibarr.zip

# Rename configuration file
mv "$website_root_path/dolibarr/htdocs/conf/conf.php.example" "$website_root_path/dolibarr/htdocs/conf/conf.php"

# Set correct permissions for the configuration file
chmod 666 "$website_root_path/dolibarr/htdocs/conf/conf.php"
```

## Edit `$website_root_path/dolibarr/conf/conf.php` file

```.sh
nano "$website_root_path/dolibarr/conf/conf.php"
```

Add this row to conf.php (set up variables above will not work here):

```.txt
$dolibarr_main_url_root='https://$website';
$dolibarr_main_document_root = '$website_root_path/dolibarr/htdocs';
$dolibarr_main_data_root = '$website_root_path/dolibarr/documents';
```

### Additional nginx directives

(set up variables above will not work here)

```.txt
location /dolibarr/ {
 root $website_root_path;
 index index.php;
}
```

### Permissions

#### Change ownership

```.sh
chown -R $system_user:$system_group $website_root_path/dolibarr
```

#### Change files and folders permissions

```.sh
find $website_root_path/dolibarr -type d -exec chmod 755 {} \;
find $website_root_path/dolibarr -type f -exec chmod 644 {} \;
```
