## SharePoint - Update field value for all files from a given SharePoint library
# Last update: 2024-08-21


function Update-SharePointLibraryFields {
    param (
        [string]$sharePointUrl,
        [string]$libraryName,
        [string[]]$filePath = @(),
        [switch]$filePathRecursive = $false,
        [string[]]$fileObject = @(),
        [string[]]$includeFileTypes = @(),
        [string[]]$excludeFileTypes = @(),
        [hashtable]$fieldUpdates
    )

    # Capture start time
    $startTime = Get-Date

    # Import the PnP.PowerShell module
    Import-Module PnP.PowerShell

    # Connect to the SharePoint site
    Connect-PnPOnline -Url $sharePointUrl -Interactive

    # Get the library
    $library = Get-PnPList -Identity $libraryName

    # Get all items in the library
    $items = Get-PnPListItem -List $library -PageSize 500

    # Filter out folders
    $items = $items | Where-Object { $_.FileSystemObjectType -eq "File" }

    # Apply filters if specified
    if ($filePath.Count -gt 0) {
        if ($filePathRecursive) {
            # Recursive filtering
            $items = $items | Where-Object {
                $itemPath = $_.FieldValues["FileDirRef"]
                $filePath | Where-Object { $itemPath -like "$_/*" -or $itemPath -eq $_ } | Measure-Object | Select-Object -ExpandProperty Count | Where-Object { $_ -gt 0 }
            }
        } else {
            # Non-recursive filtering
            $items = $items | Where-Object { ($_.FieldValues["FileDirRef"]) -contains $filePath }
        }
    }

    if ($fileObject.Count -gt 0) {
        $items = $items | Where-Object { ($_.FileSystemObjectType) -contains $fileObject }
    }

    if ($includeFileTypes.Count -gt 0 -or $excludeFileTypes.Count -gt 0) {
        $items = $items | Where-Object {
            # Include only specified file types if includeFileTypes is set
            if ($includeFileTypes.Count -gt 0) {
                $includeCondition = $_.FieldValues["File_x0020_Type"] -contains $includeFileTypes
            } else {
                $includeCondition = $true
            }

            # Exclude specified file types if excludeFileTypes is set
            if ($excludeFileTypes.Count -gt 0) {
                $excludeCondition = $_.FieldValues["File_x0020_Type"] -notcontains $excludeFileTypes
            } else {
                $excludeCondition = $true
            }

            $includeCondition -and $excludeCondition
        }
    }

    # Output count of filtered items
    Write-Host "Total items after filtering: $($items.Count)" -ForegroundColor Cyan

    # Iterate through each item and update the field values
    foreach ($item in $items) {
        # Capture original modified date (does not work)
        # $originalModified = $item.FieldValues["Modified"]

        foreach ($fieldName in $fieldUpdates.Keys) {
            $fieldValueNew = $fieldUpdates[$fieldName]

            # Check if the field value is already equal to $fieldValueNew
            if ($item.FieldValues[$fieldName] -ne $fieldValueNew) {
                if ($fieldName -eq "_ComplianceTag") {
                    if ($fieldValueNew -eq $null -or $fieldValueNew -eq "") {
                        # Clear the compliance tag using -ClearLabel
                        Set-PnPListItem -List $library -Identity $item.Id -ClearLabel
                    } else {
                        # Set the compliance tag
                        Set-PnPListItem -List $library -Identity $item.Id -Label $fieldValueNew
                    }
                } else {
                    # Update field value
                    Set-PnPListItem -List $library -Identity $item.Id -Values @{$fieldName = $fieldValueNew}
                }

                # Output the item ID and file name to see the progress
                $fileName = $item.FieldValues["FileLeafRef"]
                Write-Host "'$fieldName' value updated for '$fileName'" -ForegroundColor Green
            }
        }

        # Reset the modified date to its original value (does not work)
        # Set-PnPListItem -List $library -Identity $item.Id -Values @{"Modified" = $originalModified}
    }

    # Capture end time
    $endTime = Get-Date

    # Calculate execution time
    $executionTime = $endTime - $startTime

    # Output execution time
    Write-Host "Execution time: $($executionTime.ToString())" -ForegroundColor Cyan
}

####################
# Field Value Update
####################

## Settings
$sharePointUrl = "https://ms.sharepoint.com/teams/1234/"
$libraryName = "Documents"
$filePath = @()
$filePathRecursive = $true
$fileObject = @("File")
$includeFileTypes = @()
$excludeFileTypes = @("msg")
$fieldUpdates = @{
	"_ComplianceTag" = $null
    "Document_Class" = $null
}

## Run function
Update-SharePointLibraryFields -sharePointUrl $sharePointUrl -libraryName $libraryName -filePath $filePath -filePathRecursive $filePathRecursive -fileObject $fileObject -includeFileTypes $includeFileTypes -fieldUpdates $fieldUpdates
