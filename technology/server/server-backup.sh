## Server Backup
# Last update: 2026-02-22


# Start Bash (Unix Shell)
[ -z "$BASH" ] && exec bash


# Install rsync
# sudo apt install rsync


# Settings
settings_vps_host="ssh.website.com"
settings_vps_user="sysadmin"
settings_vps_passphrase="passphrase"
settings_vps_ssh_key="$HOME/.ssh/id_ed25519_website.com"
settings_vps_cloudflared="/home/linuxbrew/.linuxbrew/bin/cloudflared"
settings_vps_backup_directory="/backups"
settings_local_backup_directory="/mnt/c/Users/${USER}/Downloads/virtualmin_backups"
settings_files_to_skip=(
  "photos.website.com.tar.gz*"
)
settings_vps_ssh_command=(
  sshpass -P passphrase -p "$settings_vps_passphrase" -v --
  ssh -i "$settings_vps_ssh_key"
  -o StrictHostKeyChecking=no
  -o UserKnownHostsFile=/dev/null
  -o "ProxyCommand=$settings_vps_cloudflared access ssh --hostname $settings_vps_host"
)
settings_vps_rsync_e="sshpass -P passphrase -p \"$settings_vps_passphrase\" -v -- ssh -i \"$settings_vps_ssh_key\" -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -o \"ProxyCommand=$settings_vps_cloudflared access ssh --hostname $settings_vps_host\""


# Functions

rsync_remote() {
  local exclude_args=()
  for file in "${settings_files_to_skip[@]}"; do
    exclude_args+=(--exclude="$file")
  done

  rsync -avz \
    -e "$settings_vps_rsync_e" \
    --rsync-path="sudo rsync" \
    "${exclude_args[@]}" \
    --progress "$@"
}

backup_sync_latest_locally() {
  local latest_remote_backup
  latest_remote_backup=$("${settings_vps_ssh_command[@]}" "$settings_vps_user@$settings_vps_host" \
    "ls -1dt $settings_vps_backup_directory/backup-* 2>/dev/null | head -n 1")

  if [[ -z "$latest_remote_backup" ]]; then
    echo "❌ No backup folder found on remote server"
    return 1
  fi
  echo "✅ Newest backup on server: $latest_remote_backup"

  mkdir -p "$settings_local_backup_directory"
  rsync_remote "$settings_vps_user@$settings_vps_host:$latest_remote_backup/" "$settings_local_backup_directory/"
}


# Backup
backup_sync_latest_locally
