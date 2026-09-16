# Debian Cleanup

> [!NOTE]  
> Last update: 2026-07-26

```sh
# Update package lists, upgrade installed packages, remove unused packages, and clean cache
sudo apt update && sudo apt full-upgrade -y && sudo apt autoremove -y && sudo apt clean

# Refresh all installed snap packages to their latest versions
sudo snap refresh

# Journald logs (keep last 7 days)
# sudo journalctl --vacuum-time=7d
```

```sh
# pip cache

## Check cache size
python -m pip cache info

## Clear entire pip cache (safe - won't affect installed packages)
python -m pip cache purge
```

```sh
# Docker cleanup

## Stopped containers - safe, only removes exited/created containers
docker ps -a --filter "status=exited" --filter "status=created"
docker container prune -f

## Dangling images (untagged, not used by any container) - safe, doesn't touch tagged images
docker images -f "dangling=true"
docker image prune -f

## Unused networks - safe, only removes unused user-defined networks
docker network ls
docker network prune -f

## Build cache - safe, but clears cache for future builds (slower next non---no-cache build)
docker builder du
docker builder prune -f

## --- Manual opt-in only below this line ---

## All unused images, not just dangling (-a) - removes tagged images not used by a running container,
## including old zeiterfassung:*-arm64 version tags you may still want. Review `docker images` first.
# docker image prune -a -f

## Volumes not attached to any container - DATA LOSS risk for stopped DB containers.
## Run `docker volume ls` and `docker ps -a` first to confirm nothing needed is stopped-but-kept.
# docker volume prune -f
```
