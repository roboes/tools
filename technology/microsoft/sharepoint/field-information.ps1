## SharePoint - Output all distinct field fieldValues for a given field name
# Last update: 2024-09-30


# Settings
$sharePointUrl = "https://ms.sharepoint.com/teams/1234/"
$libraryName = "Documents"
$fieldName = "Document_Class"


# Install the PnP.PowerShell module
# Install-Module -Name PnP.PowerShell -Force -AllowClobber

# Import the PnP.PowerShell module
Import-Module PnP.PowerShell

# Connect to the SharePoint site
Connect-PnPOnline -Url $sharePointUrl -UseWebLogin
# Connect-PnPOnline -Url $sharePointUrl  -Interactive

# Get the library
$library = Get-PnPList -Identity $libraryName

# Get all items in the library
$items = Get-PnPListItem -List $library -PageSize 5000


# Initialize an array to store the fieldName's fieldValues
$fieldValues = @()

# Iterate through each item and collect the fieldName's fieldValues
foreach ($item in $items) {
    $fieldValues += $item.FieldValues[$fieldName]
}

# Get the unique fieldName's fieldValues
$valuesUnique = $fieldValues | Sort-Object -Unique

# Output the unique fieldName's fieldValues
Write-Host "Unique $fieldName fieldValues:"
$valuesUnique | ForEach-Object {
    Write-Host "'$_'"
}

Write-Host "Total unique fieldName's fieldValues: $($valuesUnique.Count)"


# Field Information

## Get the library
$library = Get-PnPList -Identity $libraryName

## Get the field
$fieldValue = Get-PnPField -List $library -Identity $fieldName

## Output field information
$fieldValue.GetType()
$fieldValue | Format-List


# Set Field to Editable
# Set-PnPField -List $library -Identity $fieldName -Values @{ReadOnlyField = $false}


# Term GUIDs for the terms used in the "Document_Class" field

# Initialize an array to store the fieldName's fieldValues
$fieldValues = @()

# Iterate through each item and collect the fieldName's fieldValues
foreach ($item in $items) {
    $taxonomyFieldValue = $item.FieldValues[$fieldName]
    if ($taxonomyFieldValue -ne $null) {
        # Add the Term GUID and Label to the fieldValues array
        $fieldValues += @{
            TermGuid = $taxonomyFieldValue.TermGuid
            Label = $taxonomyFieldValue.Label
        }
    }
}

# Get the unique fieldName's fieldValues
$valuesUnique = $fieldValues | Sort-Object TermGuid -Unique

# Output the unique fieldName's fieldValues
Write-Host "Unique $fieldName fieldValues:"
$valuesUnique | ForEach-Object {
    Write-Host "Term GUID: $($_.TermGuid), Label: $($_.Label)"
}
