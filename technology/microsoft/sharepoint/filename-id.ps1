## SharePoint - Get ID of a given file
# Last update: 2024-07-30


# Settings
$sharePointUrl = "https://ms.sharepoint.com/teams/1234/"
$libraryName = "Documents"
$searchFileName = "FileA.pdf"


# Install the PnP.PowerShell module
# Install-Module -Name PnP.PowerShell -Force -AllowClobber

# Import the PnP.PowerShell module
Import-Module PnP.PowerShell

try {
    # Connect to the SharePoint site
    Connect-PnPOnline -Url $sharePointUrl -Interactive

    # Get all items in the library
    $items = Get-PnPListItem -List $libraryName -PageSize 500

    # Initialize a flag to check if the file is found
    $fileFound = $false

    # Output header
    Write-Host "ID`tFull Path"

    # Iterate through each item in the library
    foreach ($item in $items) {
        $itemId = $item.Id
        $fileName = $item.FieldValues["FileLeafRef"]
        $filePath = $item.FieldValues["FileRef"]

        # Check if the current file name matches the search file name
        if ($fileName -eq $searchFileName) {
            # Output the ID and Full Path
            Write-Host "$itemId`t$filePath"
            $fileFound = $true
            break  # Exit the loop once the file is found
        }
    }

    # If the file is not found, output a message
    if (-not $fileFound) {
        Write-Host "File not found: $searchFileName"
    }
} catch {
    # Handle any errors that occur during the script execution
    Write-Error "An error occurred: $_"
} finally {
    # Disconnect from SharePoint
    Disconnect-PnPOnline
}
