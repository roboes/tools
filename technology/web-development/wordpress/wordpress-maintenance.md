# WordPress Maintenance

> [!NOTE]  
> Last update: 2025-11-25

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
wp wc tool run clear_expired_transients --user=$wordpress_username

# Regenerate product lookup tables
wp wc tool run regenerate_product_lookup_tables --user=$wordpress_username

# Clear analytics cache
wp wc tool run clear_woocommerce_analytics_cache --user=$wordpress_username

# After clearing the analytics cache, reimport historical data for Analytics to show correct numbers: WordPress → Analytics → Settings → Import historical data

# Clear template cache
wp wc tool run clear_template_cache --user=$wordpress_username

# Verify HPOS
wp wc hpos status


# Regenerates posts data from HPOS tables
# wp wc hpos sync
# nohup wp wc hpos sync > /tmp/hpos_sync.log 2>&1 &

# wp wc hpos verify_data
# wp wc hpos cleanup all
```

```.sh
for site in "$wordpress_sites_root_path"/*; do
  if [ -d "$site/$wordpress_site_subdir" ]; then
    echo "========================================="
    echo "Processing: $(basename "$site")"
    echo "========================================="
    cd "$site/$wordpress_site_subdir" || continue

    # Check if this is a WordPress site
    if [ -f "wp-config.php" ]; then

      # =====================================
      # MAINTENANCE COMMANDS
      # =====================================
      echo "Running maintenance commands..."

      wp transient delete --all --allow-root 2>/dev/null || echo "⚠️  Transient delete failed"
      wp cache flush --allow-root 2>/dev/null || echo "⚠️  Cache flush failed"

      # DB optimize - specify path explicitly
      wp db optimize --allow-root --path="$PWD" 2>/dev/null || echo "ℹ️  DB optimize skipped (needs direct DB access)"

      # =====================================
      # NGINX-SPECIFIC: REMOVE .HTACCESS FILES
      # =====================================
      echo "Checking for .htaccess files (useless on Nginx)..."
      HTACCESS_COUNT=$(find . -name ".htaccess" -type f 2>/dev/null | wc -l)
      if [ "$HTACCESS_COUNT" -gt 0 ]; then
        echo "Found $HTACCESS_COUNT .htaccess files - removing..."
        find . -name ".htaccess" -type f -delete 2>/dev/null
        echo "✓ Removed $HTACCESS_COUNT .htaccess files"
      else
        echo "✓ No .htaccess files found"
      fi

      # =====================================
      # SECURITY CHECKS
      # =====================================
      echo "Running security checks..."

      # Check core file integrity
      echo "Verifying WordPress core files..."
      CORE_CHECK=$(wp core verify-checksums --allow-root 2>&1)
      if echo "$CORE_CHECK" | grep -q "Success"; then
        echo "✓ WordPress core files verified"
      else
        echo "⚠️  Core files modified or verification failed"
      fi

      # Find world-writable files (SECURITY RISK!)
      echo "Checking for world-writable files..."
      WRITABLE_FILES=$(find . -type f \( -name "*.php" -o -name "*.conf" -o -name "wp-config.php" \) -perm /o=w 2>/dev/null)
      if [ -n "$WRITABLE_FILES" ]; then
        echo "⚠️  CRITICAL: Found world-writable files:"
        echo "$WRITABLE_FILES"
      else
        echo "✓ No world-writable critical files found"
      fi

      # Check for SUSPICIOUS PHP files in uploads (exclude legitimate ones)
      echo "Checking for suspicious PHP files in uploads..."
      PHP_IN_UPLOADS=$(find ./wp-content/uploads -type f -name "*.php" \
          ! -name "index.php" \
          ! -path "*/matomo/*" \
          ! -path "*/fonts/*" \
          ! -name "config.ini.php" \
          2>/dev/null)

      if [ -n "$PHP_IN_UPLOADS" ]; then
        echo "⚠️  SUSPICIOUS: Non-standard PHP files in uploads:"
        echo "$PHP_IN_UPLOADS" | head -10
        echo "   (Review these manually - could be malware)"
      else
        echo "✓ Only legitimate PHP files in uploads"
      fi

      # Check for recently modified PHP files (potential backdoors)
      echo "Checking for recently modified PHP files (last 30 days)..."
      RECENT_PHP=$(find ./wp-content/themes ./wp-content/plugins -name "*.php" -mtime -30 -type f 2>/dev/null | wc -l)
      if [ "$RECENT_PHP" -gt 0 ]; then
        echo "ℹ️  Found $RECENT_PHP PHP files modified in last 30 days"
        echo "   (Normal if you recently updated plugins/themes)"
      fi

      # Check for base64 encoded payloads in custom code (skip vendor/node_modules)
      echo "Scanning for suspicious patterns in custom code..."
      SUSPICIOUS=$(grep -r "eval(base64_decode\|eval(gzinflate\|eval(str_rot13\|preg_replace.*\/e" \
          --include="*.php" \
          --exclude-dir="vendor" \
          --exclude-dir="node_modules" \
          wp-content/themes wp-content/plugins 2>/dev/null | \
          grep -v "matomo\|complianz" | head -3)

      if [ -n "$SUSPICIOUS" ]; then
        echo "⚠️  WARNING: Found suspicious patterns (review manually):"
        echo "$SUSPICIOUS" | cut -c1-120
      else
        echo "✓ No obvious malware patterns detected"
      fi

      # =====================================
      # PERMISSION RESET
      # =====================================
      echo "Resetting permissions..."

      # Get the proper owner
      SITE_OWNER=$(stat -c '%U' wp-config.php)
      SITE_GROUP=$(stat -c '%G' wp-config.php)

      echo "Site owner: $SITE_OWNER:$SITE_GROUP"

      # Reset directory permissions (755)
      find . -type d -exec chmod 755 {} \; 2>/dev/null

      # Reset file permissions (644)
      find . -type f -exec chmod 644 {} \; 2>/dev/null

      # Secure wp-config.php (600 - owner read/write only)
      chmod 600 wp-config.php 2>/dev/null

      # Secure other sensitive files
      [ -f ".env" ] && chmod 600 .env 2>/dev/null
      [ -f "php.ini" ] && chmod 644 php.ini 2>/dev/null

      # Restore proper ownership (critical!)
      if [ "$SITE_OWNER" != "root" ]; then
        echo "Restoring ownership to $SITE_OWNER:$SITE_GROUP..."
        chown -R "$SITE_OWNER:$SITE_GROUP" . 2>/dev/null || echo "⚠️  Could not change ownership"
      else
        echo "⚠️  CRITICAL: Site owned by root! Change to web server user!"
      fi

      # =====================================
      # WORDPRESS SECURITY CHECKS
      # =====================================
      echo "WordPress configuration checks..."

      # Check for debug mode in production
      if grep -q "define.*WP_DEBUG.*true" wp-config.php 2>/dev/null; then
        echo "⚠️  WARNING: WP_DEBUG is enabled in production!"
      else
        echo "✓ WP_DEBUG is disabled"
      fi

      # Check for display errors
      if grep -q "define.*WP_DEBUG_DISPLAY.*true" wp-config.php 2>/dev/null; then
        echo "⚠️  WARNING: WP_DEBUG_DISPLAY is enabled!"
      fi

      # Check for outdated software
      echo "Checking for updates..."
      OUTDATED_PLUGINS=$(wp plugin list --update=available --format=count --allow-root 2>/dev/null)
      OUTDATED_THEMES=$(wp theme list --update=available --format=count --allow-root 2>/dev/null)
      CORE_UPDATE=$(wp core check-update --format=count --allow-root 2>/dev/null)

      if [ "$OUTDATED_PLUGINS" -gt 0 ] || [ "$OUTDATED_THEMES" -gt 0 ] || [ "$CORE_UPDATE" -gt 0 ]; then
        echo "⚠️  Updates available:"
        [ "$CORE_UPDATE" -gt 0 ] && echo "   - WordPress core: $CORE_UPDATE update(s)"
        [ "$OUTDATED_PLUGINS" -gt 0 ] && echo "   - Plugins: $OUTDATED_PLUGINS update(s)"
        [ "$OUTDATED_THEMES" -gt 0 ] && echo "   - Themes: $OUTDATED_THEMES update(s)"
      else
        echo "✓ All software up to date"
      fi

      # Check for inactive plugins (security risk if vulnerable)
      INACTIVE=$(wp plugin list --status=inactive --format=count --allow-root 2>/dev/null)
      if [ "$INACTIVE" -gt 0 ]; then
        echo "ℹ️  $INACTIVE inactive plugins (consider removing to reduce attack surface)"
      fi

      # =====================================
      # NGINX SECURITY RECOMMENDATIONS
      # =====================================
      echo "Nginx security recommendations..."

      # Check if xmlrpc.php exists (common attack vector)
      if [ -f "xmlrpc.php" ]; then
        echo "ℹ️  xmlrpc.php exists - ensure it's blocked in Nginx"
        echo "   Add to Nginx: location = /xmlrpc.php { deny all; }"
      fi

      # Verify directory listing is disabled
      DOMAIN=$(basename "$site")
      echo "Testing directory listing for $DOMAIN..."
      HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "https://$DOMAIN/wp-content/uploads/" 2>/dev/null)

      if [ "$HTTP_CODE" = "200" ]; then
        LISTING=$(curl -s "https://$DOMAIN/wp-content/uploads/" 2>/dev/null | grep -i "Index of\|Directory listing")
        if [ -n "$LISTING" ]; then
            echo "⚠️  CRITICAL: Directory listing ENABLED!"
        else
            echo "✓ Directory listing disabled"
        fi
      elif [ "$HTTP_CODE" = "403" ]; then
        echo "✓ Directory access forbidden"
      else
        echo "ℹ️  Directory check: HTTP $HTTP_CODE"
      fi

      echo "✓ Completed: $(basename "$site")"
      echo ""

    else
      echo "⚠️  Skipping $(basename "$site"): wp-config.php not found"
    fi
  fi
done

echo "========================================="
echo "All sites processed!"
echo "========================================="
```

```.sh
# Find and replace
# wp search-replace 'https://oldwebsite.com' 'https://website.com' --dry-run
```
