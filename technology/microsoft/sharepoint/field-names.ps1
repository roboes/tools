## SharePoint - Output all field names for a given item ID
# Last update: 2024-07-30


# Settings
$sharePointUrl = "https://ms.sharepoint.com/teams/1234/"
$libraryName = "Documents"
$itemId = 420


# Install the PnP.PowerShell module
# Install-Module -Name PnP.PowerShell -Force -AllowClobber

# Import the PnP.PowerShell module
Import-Module PnP.PowerShell

# Connect to the SharePoint site
Connect-PnPOnline -Url $sharePointUrl  -Interactive

# Get the specific item by ID
$item = Get-PnPListItem -List $libraryName -Id $itemId

# Output all field names and values for the item
$item.FieldValues.GetEnumerator() | ForEach-Object {
    Write-Host "$($_.Key) : $($_.Value)"
}
