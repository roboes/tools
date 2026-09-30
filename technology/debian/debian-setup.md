# Debian Setup

> [!NOTE]  
> Last update: 2026-07-05

```sh
# Start Bash (Unix shell)
[ -z "${BASH}" ] && exec bash
```

```sh
# Update package lists, upgrade installed packages, remove unused packages, and clean cache
sudo apt update && sudo apt full-upgrade -y && sudo apt autoremove -y && sudo apt clean

# Refresh all installed snap packages to their latest versions
sudo snap refresh

# Update all installed Flatpak packages and remove unused dependencies
flatpak update -y && flatpak uninstall --unused -y
```

```sh
# Install Snap
sudo apt install -y snapd
sudo snap install core

# Install Flatpak package
sudo apt install -y flatpak
```

```sh
# Install core tools
sudo apt install -y composer \
  curl \
  git \
  python3 \
  python3-pip \
  python3-venv \
  unzip \
  wget
```

```sh
# PHP
sudo apt install -y apt-transport-https lsb-release ca-certificates curl && sudo curl -sSL -o /usr/share/keyrings/debsuryorg-archive-keyring.gpg https://packages.sury.org/php/apt.gpg && sudo sh -c 'echo "deb [signed-by=/usr/share/keyrings/debsuryorg-archive-keyring.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/sury-debian-php-$(lsb_release -sc).list' && sudo apt-get update
sudo apt install -y php8.5
```

```sh
# Install GitHub CLI
sudo apt install -y gh
```

```
# pre-commit
sudo apt install -y codespell \
  libxml2-utils \
  pre-commit
```

```sh
# php-cs-fixer
composer global require friendsofphp/php-cs-fixer
nano ~/.bashrc
# Then add to the bottom: export PATH="$PATH:$HOME/.config/composer/vendor/bin"
```

```sh
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

```sh
# Install apps
sudo apt install -y dolphin \
  konsole \
  plasma-desktop \
  sddm

# Install Notepad++
# sudo snap install notepad-plus-plus

# Install NotepadNext
flatpak install flathub com.github.dail8859.NotepadNext
```

```sh
# Install tools for SSH and remote server connectivity - for Cloudflared: https://pkg.cloudflare.com/index.html
sudo apt install -y cloudflared \
  sshpass
```

```sh
# R
sudo apt install -y r-base r-base-dev

# R Studio
sudo snap install rstudio --classic

# Quarto
QUARTO_VERSION=$(curl -s https://api.github.com/repos/quarto-dev/quarto-cli/releases/latest | grep -oP '"tag_name": "v\K[^"]+')
wget "https://github.com/quarto-dev/quarto-cli/releases/download/v${QUARTO_VERSION}/quarto-${QUARTO_VERSION}-linux-amd64.deb"
sudo dpkg -i "quarto-${QUARTO_VERSION}-linux-amd64.deb"
sudo apt install -y -f
rm "quarto-${QUARTO_VERSION}-linux-amd64.deb"
```

```sh
# ONLYOFFICE - https://helpcenter.onlyoffice.com/desktop/installation/desktop-install-ubuntu.aspx

# Add GPG key
mkdir -p -m 700 ~/.gnupg
gpg --no-default-keyring --keyring gnupg-ring:/tmp/onlyoffice.gpg --keyserver hkp://keyserver.ubuntu.com:80 --recv-keys CB2DE8E5
chmod 644 /tmp/onlyoffice.gpg
sudo chown root:root /tmp/onlyoffice.gpg
sudo mv /tmp/onlyoffice.gpg /usr/share/keyrings/onlyoffice.gpg

# Add desktop editors repository
echo 'deb [signed-by=/usr/share/keyrings/onlyoffice.gpg] https://download.onlyoffice.com/repo/debian squeeze main' | sudo tee -a /etc/apt/sources.list.d/onlyoffice.list

# Update the package manager cache
sudo apt update

# Install
sudo apt install -y onlyoffice-desktopeditors
```

```sh
# dupeGuru
sudo add-apt-repository ppa:dupeguru/ppa
sudo apt update
sudo apt install -y dupeguru
```

```sh
# FreeFileSync
sudo apt install -y freefilesync
```

```sh
# Wine - https://wiki.debian.org/Wine

## Check architecture
dpkg --print-architecture

## Enable 32-bit architecture
sudo dpkg --add-architecture i386 && sudo apt update

## Install Wine
sudo apt install -y \
  wine \
  wine32 \
  wine64 \
  libwine \
  libwine:i386 \
  fonts-wine
```

## Raspberry Pi

Raspberry Pi removal of unneeded applications for headless setup:

```sh
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
