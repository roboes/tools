# Immich Update

> [!NOTE]  
> Last update: 2025-11-06

```.sh
# Settings
domain="website.com"
domain_root_path="/home/$domain"
subdomain="subdomain"
```

```.sh
cd $domain_root_path/domains/$subdomain.$domain/immich
```

```.sh
# Stop Immich
docker compose down

# Pull the latest Immich images
docker compose pull

# Restart with the new version
docker compose up -d
```

```.sh
# Clean up old images
docker image prune -f
```
