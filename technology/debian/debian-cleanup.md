# Debian Cleanup

> [!NOTE]  
> Last update: 2026-07-26

```sh
# Update package lists, upgrade installed packages, remove unused packages, and clean cache
sudo apt update && sudo apt upgrade -y && sudo apt autoremove -y && sudo apt clean

# Refresh all installed snap packages to their latest versions
sudo snap refresh

# Homebrew update
brew update && brew upgrade && brew cleanup

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

## Stopped containers
docker ps -a --filter "status=exited" --filter "status=created"
# docker container prune -f

## Dangling images (untagged, not used by any container)
docker images -f "dangling=true"
# docker image prune -f

## Unused networks
docker network ls
# docker network prune -f

## Build cache
docker builder du
# docker builder prune -f

## Volumes not attached to any container - extra caution, can affect stopped DBs
# docker volume prune -f
```
