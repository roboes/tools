## Debian Cleanup
# Last update: 2026-05-22


# Update package lists, upgrade installed packages, remove unused packages, and clean cache
sudo apt update && sudo apt upgrade -y && sudo apt autoremove -y && sudo apt clean

# Homebrew update
brew update && brew upgrade && brew cleanup

# Journald logs (keep last 7 days)
# sudo journalctl --vacuum-time=7d

# Update all Python packages
python -m pip_review --local --auto

# Clear pip cache
python -m pip cache purge
