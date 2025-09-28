## Windows Subsystem for Linux Packages
# Last update: 2025-05-14


# Start Windows Subsystem for Linux (WSL)
bash


# Update package lists, upgrade installed packages, remove unused packages, and clean cache
sudo apt update && sudo apt upgrade -y && sudo apt autoremove -y && sudo apt clean


# Install packages
sudo apt install curl \
  git \
  python3 \
  python-is-python3 \
  python3-pip \
  wget


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
