# Debian Settings

> [!NOTE]  
> Last update: 2026-05-22

## Keyboard - US International

```.sh
# Install fcitx5 - Wayland-native input method (required for XCompose on Wayland/KDE Plasma 6)
sudo apt install fcitx5 fcitx5-frontend-qt5
im-config -n fcitx5

# XCompose rules: maps 'c = ç (Windows US International behavior) (https://github.com/raelgc/win_us_intl)
wget https://raw.githubusercontent.com/raelgc/win_us_intl/master/.XCompose

# Restart PC, then open fcitx5-configtool and add: "English (US, intl., with dead keys)"
fcitx5-configtool
```

## Change locale

```.sh
# Check Current Locale Settings
locale

# Reconfigure Locales (en_US.UTF-8)
sudo dpkg-reconfigure locales

# Update the Environment Variables
nano ~/.bashrc

# Add or update the following line
# export LANG=en_US.UTF-8
```

## KDE Plasma

```.sh
# Clock Widget (Digital Clock Applet)

## ISO 8601
kwriteconfig6 --file plasma-org.kde.plasma.desktop-appletsrc \
  --group Containments \
  --group 3 \
  --group Applets \
  --group 22 \
  --group Configuration \
  --group Appearance \
  --key dateFormat "isoDate"

## Show seconds
kwriteconfig6 --file plasma-org.kde.plasma.desktop-appletsrc \
  --group Containments \
  --group 3 \
  --group Applets \
  --group 22 \
  --group Configuration \
  --group Appearance \
  --key showSeconds "2"

## 24 hours format
kwriteconfig6 --file plasma-org.kde.plasma.desktop-appletsrc \
  --group Containments \
  --group 3 \
  --group Applets \
  --group 22 \
  --group Configuration \
  --group Appearance \
  --key use24hFormat "true"


# Activate Num Lock
kwriteconfig6 --file kcminputrc --group Keyboard --key NumLock 0

# Restart KDE Plasma
plasmashell --replace &
```

## Fingerprint

```.sh
# Check if fingerprint reader is supported
lsusb | grep -i finger

# Install fprintd
sudo apt install fprintd libpam-fprintd

# Enroll fingerprint
fprintd-enroll
```
