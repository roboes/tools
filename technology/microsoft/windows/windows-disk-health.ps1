## Windows Disk Health
# Last update: 2025-11-25


# Windows Partition

# Settings
$Drive = "D"


# Get the disk
$Disk = Get-Partition -DriveLetter $Drive | Get-Disk

# SMART status (summary)
Get-PhysicalDisk | Select-Object DeviceID, FriendlyName, HealthStatus, OperationalStatus

# SMART data
Get-PhysicalDisk | Get-StorageReliabilityCounter | Select-Object DeviceId, Temperature, ReadErrorsTotal, WriteErrorsTotal, Wear

# Check specific disk
$Disk | Get-StorageReliabilityCounter | Format-List

# Run disk check (CHKDSK) - Scan only (no fixes)
Repair-Volume -DriveLetter $Drive -Scan

# Run disk check (CHKDSK) Scan and repair
# Repair-Volume -DriveLetter $Drive -OfflineScanAndFix

# Check for bad sectors
# chkdsk "$Drive`:" /r /f



# External Disk (VeraCrypt Volume)

# Settings (Get-Disk | Select Number, FriendlyName, Size)
$DiskNumber = 1
$DriveMounted = "A"


# Unmounted VeraCrypt Drive

$Disk = Get-PhysicalDisk | Where-Object DeviceID -eq $DiskNumber
$Disk | Select-Object FriendlyName, HealthStatus, OperationalStatus
$Disk | Get-StorageReliabilityCounter | Format-List


# Mounted VeraCrypt Drive

# Get the disk
$Disk = Get-PhysicalDisk | Where-Object DeviceID -eq $DiskNumber

# Health status
$Disk | Select-Object FriendlyName, HealthStatus, OperationalStatus

# SMART data
$Disk | Get-StorageReliabilityCounter | Format-List

# Check the mounted volume
chkdsk "$DriveMounted`:" /f /x

