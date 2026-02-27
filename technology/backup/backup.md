# Backup

## Recovery

Recovery script for copying all files from a damaged external drive to a healthy one, skipping bad sectors and logging results.

```.ps1
$drive_damaged = "A:\"
$drive_output = "B:\RescueBackup"

# Ensure the output directory exists
New-Item -ItemType Directory -Force -Path $drive_output

robocopy $drive_damaged $drive_output /E /COPY:DAT /R:0 /W:0 /MT:8 /XJ /FFT /LOG:"C:\Users\$([System.Environment]::UserName)\Downloads\rescue_log.txt" /TEE
```
