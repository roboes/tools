## SharePoint - Update field values for all files in a given SharePoint library using REST API (Invoke-PnPSPRestMethod)
# Last update: 2024-09-11

function Update-SharePointLibraryFields {
    param (
        [string]$sharePointUrl,
        [string]$libraryName,
        [string[]]$filePath = @(),
        [switch]$filePathRecursive = $false,
        [string[]]$fileObject = @(),
        [string[]]$includeFileTypes = @(),
        [string[]]$excludeFileTypes = @(),
        [switch]$clearRetentionLabel = $false,
        [string]$contentData
    )

    # Capture start time
    $startTime = Get-Date

    # Import PnP.PowerShell module if not already imported
    if (-not (Get-Module -Name PnP.PowerShell -ListAvailable)) {
        Import-Module PnP.PowerShell
    }

    # Connect to the SharePoint site
    Connect-PnPOnline -Url $sharePointUrl -UseWebLogin
    # Connect-PnPOnline -Url $sharePointUrl -Interactive

    # Get the library
    $library = Get-PnPList -Identity $libraryName

    # Get all items in the library
    $items = Get-PnPListItem -List $library -PageSize 5000

    # For testing
    # $items = Get-PnPListItem -List $library -Id 1234

    if ($fileObject.Count -gt 0) {
        $items = $items | Where-Object { ($_.FileSystemObjectType) -contains $fileObject }
    }

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

    # Initialize progress bar variables for the first section
    $totalUpdates = $items.Count
    $currentUpdate = 0

    foreach ($item in $items) {
        $updateNeeded = $false
        $logMessages = @()

        # Check if item already has the fields defined in $contentData
        $contentDataObj = $contentData | ConvertFrom-Json
        foreach ($field in $contentDataObj.formValues) {
            if ($field.FieldName -eq "Document_Class") {
                $currentValue = $item.FieldValues[$field.FieldName].Label + "|" + $item.FieldValues[$field.FieldName].TermGuid
                if ($currentValue -ne $field.FieldValue) {
                    $updateNeeded = $true
                    $logMessages += "$($item.Id) - '$($item.FieldValues["FileLeafRef"])': '$($field.FieldName)' updated from '$currentValue' to '$($field.FieldValue)'"
                }
            } else {
                if ($item.FieldValues[$field.FieldName].ToString() -ne $field.FieldValue) {
                    $updateNeeded = $true
                    $logMessages += "$($item.Id) - '$($item.FieldValues["FileLeafRef"])': '$($field.FieldName)' updated from '$($item.FieldValues[$field.FieldName])' to '$($field.FieldValue)'"
                }
            }
        }

        if ($updateNeeded) {
            # Log current values before update
            foreach ($message in $logMessages) {
                Write-Host $message -ForegroundColor Green
            }

            $apiCall = $sharePointUrl + "/_api/web/lists/getbytitle('$libraryName')/items($($item.Id))/ValidateUpdateListItem"
            Invoke-PnPSPRestMethod -Url $apiCall -Method post -Content $contentData -ContentType "application/json;odata=verbose"  | Out-Null

            # Update first progress bar
            $currentUpdate++
            $percentComplete = [math]::Round(($currentUpdate / $totalUpdates) * 100, 2)
            Write-Progress -Activity "Updating fields" -Status "$percentComplete% Complete" -PercentComplete $percentComplete
        }
    }

    if ($filePathRecursive) {
        # Initialize progress bar variables for the second section
        $totalClears = $items.Count
        $currentClear = 0

        foreach ($item in $items) {
            # Skip items where retention labels are already cleared
            if ($item.FieldValues["_ComplianceTag"] -eq "") {
                continue
            }

            $apiCall = $sharePointUrl + "/_api/web/lists/getbytitle('$libraryName')/items($($item.Id))/SetComplianceTag()"
            Invoke-PnPSPRestMethod -Url $apiCall -Method Post -Content '{"complianceTag":""}' -ContentType "application/json;odata=verbose" | Out-Null

            # Log cleared retention label
            Write-Host "$($item.Id) - '$($item.FieldValues["FileLeafRef"])': '_ComplianceTag' cleared" -ForegroundColor Green

            # Update second progress bar
            $currentClear++
            $percentComplete = [math]::Round(($currentClear / $totalClears) * 100, 2)
            Write-Progress -Activity "Clearing Retention Labels" -Status "$percentComplete% Complete" -PercentComplete $percentComplete
        }
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
$clearRetentionLabel = $true
$contentData = @"
{
    "formValues": [
        {
            "FieldName": "ContentTypeId",
            "FieldValue": "Relevant Document",
            "HasException": false,
            "ErrorMessage": null
        },
        {
            "FieldName": "Document_Class",
            "FieldValue": "Business knowledge [Retain - Indefinite]|84744f1d-24ea-4584-844a-33968b3a1a1c",
            "HasException": false,
            "ErrorMessage": null
        }
    ],
    "bNewDocumentUpdate": false,
    "checkInComment": null
}
"@

## Run function
Update-SharePointLibraryFields -sharePointUrl $sharePointUrl -libraryName $libraryName -filePath $filePath -filePathRecursive $filePathRecursive -fileObject $fileObject -includeFileTypes $includeFileTypes -clearRetentionLabel $clearRetentionLabel -contentData $contentData
