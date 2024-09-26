## SharePoint - Update field values for all files in a given SharePoint library using PowerShell Module (Set-PnPListItem)
# Last update: 2024-09-26

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

    $totalItems = $items.Count

    # Separate items that need label clearing
    $itemsToClearLabel = @()
    $itemsToUpdateFields = @()

    foreach ($item in $items) {
        $needsClearLabel = $false
        $needsUpdate = @{}

        foreach ($fieldName in $fieldUpdates.Keys) {
            $currentValue = $item.FieldValues[$fieldName]
            $newValue = $fieldUpdates[$fieldName]

            if ($fieldName -eq "Document_Class") {
                if ($currentValue.Label -ne $newValue.Label) {
                    $needsUpdate[$fieldName] = $newValue.Document_Class
                }

            } elseif ($currentValue -is [System.String[]]) {
                # Explicitly cast $newValue to System.String[] if needed
                if ($newValue -is [System.Object[]]) {
                    $newValue = [System.String[]]$newValue
                }

                if ($currentValue -ne $newValue) {
                    $needsUpdate[$fieldName] = $newValue
                }

            } else {
                $currentValue = if ($currentValue -eq $null) { "" } else { $currentValue }

                if ($currentValue.ToString() -ne $newValue.ToString()) {
                    if ($fieldName -eq "_ComplianceTag" -and $newValue -eq "") {
                        $needsClearLabel = $true
                    } else {
                        $needsUpdate[$fieldName] = $newValue
                    }
                }
            }
        }

        if ($needsUpdate.Count -gt 0) {
            $itemsToUpdateFields += [PSCustomObject]@{
                Id = $item.Id
                Values = $needsUpdate
                FileLeafRef = $item.FieldValues["FileLeafRef"]
            }
        }

        if ($needsClearLabel) {
            $itemsToClearLabel += $item
        }
    }

    # Start the batch process for field updates
    if ($itemsToUpdateFields.Count -gt 0) {
        $totalUpdates = $itemsToUpdateFields.Count
        $currentUpdate = 0

        Write-Host "Total items to be updated (field updates): $totalUpdates" -ForegroundColor Cyan

        # Process in batches
        $batchSize = 50
        $batches = [math]::Ceiling($totalUpdates / $batchSize)

        for ($batchIndex = 0; $batchIndex -lt $batches; $batchIndex++) {
            $batch = New-PnPBatch

            $startIndex = $batchIndex * $batchSize
            $endIndex = [math]::Min($startIndex + $batchSize - 1, $totalUpdates - 1)
            $batchItems = $itemsToUpdateFields[$startIndex..$endIndex]

            foreach ($updateItem in $batchItems) {
                foreach ($fieldName in $updateItem.Values.Keys) {
                    # Queuing updates
                    $currentLabel = $item.FieldValues[$fieldName]
                    $updatedLabel = $updateItem.Values[$fieldName]
                    Write-Host "$($updateItem.Id) - '$($updateItem.FileLeafRef)': '$fieldName' updated from '$currentLabel' to '$updatedLabel'" -ForegroundColor Green
                    Set-PnPListItem -List $library -Identity $updateItem.Id -Values @{$fieldName = $updateItem.Values[$fieldName]} -Batch $batch -UpdateType UpdateOverwriteVersion
                }

                # Update progress bar
                $currentUpdate++
                $percentComplete = [math]::Round(($currentUpdate / $totalUpdates) * 100, 2)
                Write-Progress -Activity "Updating fields" -Status "$percentComplete% Complete" -PercentComplete $percentComplete
            }

            # Execute the batch for field updates
            Write-Host "Executing batch $($batchIndex + 1) of $batches for field updates..." -ForegroundColor Yellow
            Invoke-PnPBatch -Batch $batch
            Write-Host "Batch $($batchIndex + 1) execution for field updates completed." -ForegroundColor Green
        }
    } else {
        Write-Host "No items to update fields." -ForegroundColor Cyan
    }

    # Process label clearing separately
    if ($itemsToClearLabel.Count -gt 0) {
        Write-Host "Clearing labels for items..." -ForegroundColor Yellow

        $totalClears = $itemsToClearLabel.Count
        $currentClear = 0

        Write-Host "Total items to be updated (label clearing): $totalClears" -ForegroundColor Cyan

        foreach ($item in $itemsToClearLabel) {
            Set-PnPListItem -List $library -Identity $item.Id -ClearLabel -UpdateType UpdateOverwriteVersion
            Write-Host "$($item.Id) - '$($item.FieldValues["FileLeafRef"])': '_ComplianceTag' cleared" -ForegroundColor Green

            # Update progress bar for clearing labels
            $currentClear++
            $percentComplete = [math]::Round(($currentClear / $totalClears) * 100, 2)
            Write-Progress -Activity "Clearing labels" -Status "$percentComplete% Complete" -PercentComplete $percentComplete
        }

        Write-Host "Label clearing process completed." -ForegroundColor Green
    } else {
        Write-Host "No labels to clear." -ForegroundColor Cyan
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
$excludeFileTypes = @()
$fieldUpdates = @{
    "_ComplianceTag" = ""
    "ContentTypeId" = "Relevant Document"
    "Document_Class" = @{
        Document_Class = "Document management|1234|Document Class|Business Information|Business knowledge [Retain - Indefinite]"
        Label = "Business knowledge [Retain - Indefinite]"
    }
}

## Run function
Update-SharePointLibraryFields -sharePointUrl $sharePointUrl -libraryName $libraryName -filePath $filePath -filePathRecursive $filePathRecursive -fileObject $fileObject -includeFileTypes $includeFileTypes -fieldUpdates $fieldUpdates
