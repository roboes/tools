## Debian Setup
# Last update: 2026-05-22


# Start Bash (Unix shell)
[ -z "$BASH" ] && exec bash


# Update package lists, upgrade installed packages, remove unused packages, and clean cache
sudo apt update && sudo apt upgrade -y && sudo apt autoremove -y && sudo apt clean


# Install core tools and programming languages
sudo apt install -y composer \
  curl \
  git \
  nodejs \
  npm \
  python3 \
  python-is-python3 \
  python3-pip \
  python3-venv \
  wget


# PHP
sudo apt-get -y install apt-transport-https lsb-release ca-certificates curl && sudo curl -sSL -o /usr/share/keyrings/debsuryorg-archive-keyring.gpg https://packages.sury.org/php/apt.gpg && sudo sh -c 'echo "deb [signed-by=/usr/share/keyrings/debsuryorg-archive-keyring.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/sury-debian-php-$(lsb_release -sc).list' && sudo apt-get update
sudo apt-get install php8.5

# Install GitHub CLI
sudo apt install -y gh

# pre-commit
sudo apt install -y codespell \
  libxml2-utils \
  pre-commit

# php-cs-fixer
composer global require friendsofphp/php-cs-fixer
nano ~/.bashrc
# Then add to the bottom: export PATH="$PATH:$HOME/.config/composer/vendor/bin"

# Node.js
nvm install node
node -v
nvm alias default 25

# Prettier
sudo npm install -g prettier
sudo npm install -g glob


# Homebrew install
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"

# ulimit current limit
ulimit -n

# ulimit increase limit
ulimit -n 8192

# Homebrew update
brew update && brew upgrade && brew cleanup


# Install apps
sudo apt install -y dolphin \
  konsole \
  plasma-desktop \
  sddm

# Install tools for SSH and remote server connectivity - for Cloudflared: https://pkg.cloudflare.com/index.html
sudo apt install -y cloudflared \
  sshpass

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
