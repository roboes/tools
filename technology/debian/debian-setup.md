# Debian Setup

> [!NOTE] Last update: 2026-07-05

```.sh
# Start Bash (Unix shell)
[ -z "$BASH" ] && exec bash
```

```.sh
# Update package lists, upgrade installed packages, remove unused packages, and clean cache
sudo apt update && sudo apt upgrade -y && sudo apt autoremove -y && sudo apt clean
sudo snap refresh
```

```.sh
# Install Snap
sudo apt install -y snapd
```

```.sh
# Install core tools and programming languages
sudo apt install -y composer \
  curl \
  git \
  python3 \
  python-is-python3 \
  python3-pip \
  python3-venv \
  wget
```

```.sh
# PHP
sudo apt-get -y install apt-transport-https lsb-release ca-certificates curl && sudo curl -sSL -o /usr/share/keyrings/debsuryorg-archive-keyring.gpg https://packages.sury.org/php/apt.gpg && sudo sh -c 'echo "deb [signed-by=/usr/share/keyrings/debsuryorg-archive-keyring.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/sury-debian-php-$(lsb_release -sc).list' && sudo apt-get update
sudo apt-get install php8.5
```

```.sh
# Install GitHub CLI
sudo apt install -y gh
```

```
# pre-commit
sudo apt install -y codespell \
  libxml2-utils \
  pre-commit
```

```.sh
# php-cs-fixer
composer global require friendsofphp/php-cs-fixer
nano ~/.bashrc
# Then add to the bottom: export PATH="$PATH:$HOME/.config/composer/vendor/bin"
```

```.sh
# Install nvm (Node Version Manager)
NVM_LATEST=$(curl -s https://api.github.com/repos/nvm-sh/nvm/releases/latest | grep '"tag_name"' | cut -d'"' -f4)
curl -o- "https://raw.githubusercontent.com/nvm-sh/nvm/${NVM_LATEST}/install.sh" | bash

# Reload .bashrc to load nvm without restarting terminal
source ~/.bashrc

# Install latest Node.js and set as default
nvm install node
nvm alias default node
node -v
```

```.sh
# Install apps
sudo apt install -y dolphin \
  konsole \
  notepadqq \
  plasma-desktop \
  sddm
```

```.sh
# Install tools for SSH and remote server connectivity - for Cloudflared: https://pkg.cloudflare.com/index.html
sudo apt install -y cloudflared \
  sshpass
```

```.sh
# R
sudo apt install -y r-base r-base-dev

# R Studio
sudo snap install rstudio --classic
```

```.sh
# Wine - https://wiki.debian.org/Wine

## Check architecture
dpkg --print-architecture

## Enable 32-bit architecture
sudo dpkg --add-architecture i386 && sudo apt update

## Install Wine
sudo apt install \
  wine \
  wine32 \
  wine64 \
  libwine \
  libwine:i386 \
  fonts-wine
```

## Raspberry Pi

Raspberry Pi removal of unneeded applications for headless setup:

```.sh
sudo apt purge -y \
  adwaita-icon-theme adwaita-icon-theme-legacy \
  chromium chromium-common chromium-l10n \
  colord colord-data \
  cups-common cups-pk-helper \
  eom eom-common \
  firefox rpi-firefox-mods \
  g++-14-aarch64-linux-gnu \
  gcc-14-aarch64-linux-gnu \
  galculator geany geany-common \
  gvfs gvfs-backends gvfs-common gvfs-daemons gvfs-libs \
  hicolor-icon-theme \
  ipp-usb \
  libwidevinecdm0 \
  lynx lynx-common \
  mkvtoolnix \
  pixtrix-icons pixtrix-theme \
  pocketsphinx-en-us \
  printer-driver-escpr rpinters \
  rpi-connect rpi-connect-lite \
  rpi-imager \
  rpd-wallpaper rpd-wallpaper-trixie \
  squeekboard \
  system-config-printer-common \
  xarchiver \
  lxtask
```
