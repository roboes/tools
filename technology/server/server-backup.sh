## Server Backup
# Last update: 2025-08-25


# Start Bash (Unix Shell)
bash


# Install rsync
# sudo apt install rsync


# Settings

settings_vps_host="ssh.website.com"
settings_vps_user="user"
settings_vps_backup_directory="/backup"
settings_local_backup_directory="/mnt/c/Users/${USER}/Downloads/virtualmin_backups"

function rsync_remote() {
  rsync -avz -e 'sshpass -P passphrase -p "PASSPHRASE" -v -- ssh -i ~/.ssh/id_rsa -o ProxyCommand="cloudflared access ssh --hostname ssh.website.com"' --progress "$@"
}



# Functions

backup_sync_latest_locally() {
  local settings_vps_host="$1"
  local settings_vps_user="$2"
  local settings_vps_backup_directory="$3"
  local settings_local_backup_directory="$4"

  # Find newest remote backup folder
  local latest_remote_backup
  latest_remote_backup=$("${settings_vps_ssh_command[@]}" "$settings_vps_user@$settings_vps_host" \
    "ls -1dt $settings_vps_backup_directory/backup-* 2>/dev/null | head -n 1")

  if [[ -z "$latest_remote_backup" ]]; then
    echo "❌ No backup folder found on remote server"
    return 1
  fi

  echo "✅ Newest backup on server: $latest_remote_backup"

  # rsync from VPS to local
  mkdir -p "$settings_local_backup_directory"
  rsync_remote \
    "$settings_vps_user@$settings_vps_host:$latest_remote_backup/" \
    "$settings_local_backup_directory/"
}


# Backup

backup_sync_latest_locally "$settings_vps_host" "$settings_vps_user" "$settings_vps_backup_directory" "$settings_local_backup_directory"
