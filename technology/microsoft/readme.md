# Microsoft

## Windows Updates

```.ps1
# Update Windows applications
winget upgrade --all --accept-package-agreements --silent

# Alternative: Update only user-scoped apps (no admin rights needed)
# winget upgrade --all --accept-package-agreements --silent --scope user

# Update all Python packages
python -m pip_review --local --auto
```

### Windows Subsystem for Linux (WSL)

```.bash
# Start Windows Subsystem for Linux (WSL)
bash

# Update package lists, upgrade installed packages, remove unused packages, and clean cache
sudo apt update && sudo apt upgrade -y && sudo apt autoremove -y && sudo apt clean

# Homebrew update
brew update && brew upgrade && brew cleanup
```
