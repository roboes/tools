# Debian Setup

> [!NOTE]  
> Last update: 2026-10-18

```sh
# Keep system awake (allows screen dimming) until Ctrl+C
systemd-inhibit --what=idle:sleep:handle-lid-switch --who="Terminal" --why="Manual keep awake" sleep infinity
```
