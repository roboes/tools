## SharePoint - Output all distinct label values for compliance tag
# Last update: 2024-09-30


# Function to get unique compliance tags
function Get-UniqueComplianceTags {
    param (
        [string]$sharePointUrl,
        [string]$libraryName,
        [string]$labelName
    )

    # Import the PnP.PowerShell module
    Import-Module PnP.PowerShell

    # Connect to the SharePoint site
    Connect-PnPOnline -Url $sharePointUrl -UseWebLogin
    # Connect-PnPOnline -Url $sharePointUrl -Interactive

    # Get the library
    $library = Get-PnPList -Identity $libraryName

    # Get all items in the library
    $items = Get-PnPListItem -List $library -PageSize 500

    # Filter unique compliance tags
    $complianceTags = @{}
    foreach ($item in $items) {
        $complianceTag = $item.FieldValues[$labelName]
        if (-not $complianceTags.ContainsKey($complianceTag)) {
            $complianceTags[$complianceTag] = $complianceTag
        }
    }

    # Return objects
    return $complianceTags.Keys | Sort-Object
}


# Settings
$sharePointUrl = "https://ms.sharepoint.com/teams/1234/"
$libraryName = "Documents"
$labelName = "_ComplianceTag" # "ComplianceAssetId"

# Get unique compliance tags
$uniqueLabels = Get-UniqueComplianceTags -sharePointUrl $sharePointUrl -libraryName $libraryName -labelName $labelName

# Display the unique labels
$uniqueLabels | ForEach-Object {
    Write-Host "'$_'"
}
