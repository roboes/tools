## Windows Subsystem for Linux Packages
# Last update: 2026-02-22


# Start Windows Subsystem for Linux (WSL)
[ -z "$BASH" ] && exec bash


# Update package lists, upgrade installed packages, remove unused packages, and clean cache
sudo apt update && sudo apt upgrade -y && sudo apt autoremove -y && sudo apt clean


# Install packages
sudo apt install composer \
  curl \
  git \
  nodejs \
  npm \
  python3 \
  python-is-python3 \
  python3-pip \
  python3-virtualenv \
  wget

# pre-commit
sudo apt install pre-commit

# codespell
sudo apt install codespell

# php-cs-fixer
composer global require friendsofphp/php-cs-fixer
export PATH="$PATH:$HOME/.config/composer/vendor/bin"

# xmllint
sudo apt install libxml2-utils

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


# Install GitHub CLI
# sudo apt install -y gh


# GitHub login
# gh auth login

# Display git custom settings
# git config --list

# Remove git custom settings
# rm ~/.gitconfig


# Git settings
# git config --global user.email "email@example.com"
# git config --global user.name "username"
# git config --global --list


# Git ignore
# https://github.com/github/gitignore/blob/main/Python.gitignore
