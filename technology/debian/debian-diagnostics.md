# Debian Diagnostics

> [!NOTE]  
> Last update: 2026-06-02

## System Check

```.sh
# Failed services
systemctl --failed

# Boot warnings
journalctl -b -p warning --no-pager | tail -50

# Kernel errors
dmesg | grep -iE "error|warn|fail"

# Disk space
df -h

# CPU temperature
vcgencmd measure_temp

# RAM usage
free -h

# Open ports
sudo ss -tlnp

# Docker containers
sudo docker ps --format "table {{.Names}}\t{{.Ports}}"
```

## Fixes

```.sh
# Disable rpcbind
if systemctl is-active --quiet rpcbind; then
    sudo systemctl disable rpcbind --now
    sudo systemctl disable rpcbind.socket --now
    echo "rpcbind disabled"
else
    echo "rpcbind is already inactive"
fi
```
