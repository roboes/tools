# WordPress Maintenance

> [!NOTE]  
> Last update: 2025-11-18

```.sh
# Settings
domain="website.com"
domain_root_path="/home/$domain/public_html"
wordpress_username="username"

wordpress_sites_root_path="/home"
wordpress_site_subdir="public_html"
```

## WP-CLI installation

[Recommended installation](https://make.wordpress.org/cli/handbook/guides/installing/#recommended-installation)

### WP-CLI maintenance commands

```.sh
# Change current directory
cd "$domain_root_path"


# Activate woocommerce plugin
# wp plugin activate woocommerce

# WooCommerce cache
wp cache flush

# Redis
redis-cli FLUSHALL

# Clear transients
wp wc tool run clear_transients --user=$wordpress_username

# Clear expired transients
wp wc tool run clear_expired_transients --user=$wordpress_username

# Regenerates posts data from HPOS tables
# wp wc hpos sync
# nohup wp wc hpos sync > /tmp/hpos_sync.log 2>&1 &

# wp wc hpos status
# wp wc hpos verify_data
# wp wc hpos cleanup all

# Regenerate product lookup tables
wp wc tool run regenerate_product_lookup_tables --user=$wordpress_username

# Clear analytics cache
wp wc tool run clear_woocommerce_analytics_cache --user=$wordpress_username

# After clearing the analytics cache, reimport historical data for Analytics to show correct numbers: WordPress → Analytics → Settings → Import historical data

# Clear template cache
wp wc tool run clear_template_cache --user=$wordpress_username
```

```.sh
for site in "$wordpress_sites_root_path"/*; do
  if [ -d "$site/$wordpress_site_subdir" ]; then
    echo "Processing site in $site/$wordpress_site_subdir"
    cd "$site/$wordpress_site_subdir" || continue

    # Check if this is a WordPress site
    if [ -f "wp-config.php" ]; then
      echo "Running maintenance commands for $(basename "$site")..."

      # Maintenance commands
      wp transient delete --all
      wp cache flush
      wp db optimize
      wp core verify-checksums

      # Security checks
      find . -type f \( -name "*.php" -o -name "*.htaccess" \) -perm /o=w -ls

      # Reset permissions
      find . -type d -exec chmod 755 {} \;
      find . -type f -exec chmod 644 {} \;
      chmod 600 wp-config.php

    else
      echo "Skipping $site/$wordpress_site_subdir: wp-config.php not found."
    fi
  else
    echo "Skipping $site: $wordpress_site_subdir folder not found."
  fi
done
```

```.sh
# Find and replace
# wp search-replace 'https://oldwebsite.com' 'https://website.com' --dry-run
```
