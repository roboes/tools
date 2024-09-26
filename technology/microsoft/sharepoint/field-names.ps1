## SharePoint - Output all field names for a given item ID
# Last update: 2024-09-04


# Settings
$sharePointUrl = "https://ms.sharepoint.com/teams/1234/"
$libraryName = "Documents"
$itemId = 1234


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


## Checks if a particular field is a TaxonomyFieldValue. If it is, output the current value of that field

# Settings
$fieldName = "Document_Class"

# Retrieve the Document_Class field value
$currentFieldValue = $item.FieldValues[$fieldName]

# Check if the Document_Class field is a TaxonomyFieldValue
if ($currentFieldValue -is [Microsoft.SharePoint.Client.Taxonomy.TaxonomyFieldValue]) {
    Write-Host "Current field value for '$fieldName': $($currentFieldValue | Out-String)"
    # Write-Host "Document_Class TypeId: $($currentFieldValue.TypeId)"
} else {
    Write-Host "'$fieldName' does not appear to be a TaxonomyFieldValue or contains unexpected data." -ForegroundColor Yellow
}
