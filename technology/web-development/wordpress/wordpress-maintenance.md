# WordPress Maintenance

> [!NOTE]
> Last update: 2025-03-28

```.sh
# Settings
wordpress_sites_root_path="/home"
wordpress_site_subdir="public_html"
```

## WP-CLI installation

[Recommended installation](https://make.wordpress.org/cli/handbook/guides/installing/#recommended-installation)

### WP-CLI maintenance commands

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
