# Android TV Setup

> [!NOTE]  
> Last update: 2026-06-28

```.sh
# Settings
settings_tv_ip="192.168.178.28"
settings_custom_launcher="com.overdevs.at4k"
settings_stock_launcher="com.google.android.tvlauncher"
```

## Initial connection

```.sh
# Connect via ADB over network
adb connect $settings_tv_ip:5555

# List connected devices
adb devices
```

## Identifying installed packages

```.sh
# List all installed packages
adb shell pm list packages

# List only disabled packages
adb shell pm list packages -d

# Filter by keyword
adb shell pm list packages | grep -i <keyword>
```

## Removing/disabling bloatware

```.sh
# Remove packages
adb shell pm uninstall -k --user 0 com.xiaomi.mimusic2
adb shell pm uninstall -k --user 0 com.xiaomi.statistic
adb shell pm uninstall -k --user 0 com.xiaomi.floatingframe
adb shell pm uninstall -k --user 0 com.xiaomi.mitv.mediaexplorer
adb shell pm uninstall -k --user 0 com.xiaomi.android.tvsetup.partnercustomizer
adb shell pm uninstall -k --user 0 com.mitv.gallery
adb shell pm uninstall -k --user 0 com.google.android.youtube.tvmusic
adb shell pm uninstall -k --user 0 com.google.android.videos
adb shell pm uninstall -k --user 0 com.google.android.play.games

adb shell pm disable-user --user 0 com.mitv.tvhome.mitvplus
adb shell pm disable-user --user 0 com.mitv.dream
adb shell pm disable-user --user 0 android.autoinstalls.config.xiaomi.amelie
```
