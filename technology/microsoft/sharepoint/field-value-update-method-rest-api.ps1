## SharePoint - Update field values for all files in a given SharePoint library using REST API (Invoke-PnPSPRestMethod)
# Last update: 2026-02-10

function Update-SharePointLibraryFields {
    [CmdletBinding()]
    param (
        [Parameter(Mandatory = $true)]
        [string]$SharePointUrl,

        [Parameter(Mandatory = $true)]
        [string]$LibraryName,

        [string[]]$FilePath = @(),

        [switch]$FilePathRecursive,

        [ValidateSet("File", "Folder")]
        [string[]]$FileObject = @(),

        [string[]]$IncludeFileTypes = @(),

        [string[]]$ExcludeFileTypes = @(),

        [switch]$ClearRetentionLabel,

        # NEW: Apply retention label directly
        [string]$ApplyRetentionLabel = "",

        # Filter by specific retention labels
        [string[]]$OnlyRetentionLabels = @(),

        # Include items with empty retention labels
        [switch]$IncludeEmptyRetentionLabels,

        # Force update even if value is the same (to trigger policy re-evaluation)
        [switch]$ForceUpdate,

        [Parameter(Mandatory = $false)]
        [string]$ContentData = ""
    )

    begin {
        # Capture start time
        $startTime = Get-Date
        Write-Host "`n=== Starting SharePoint Library Field Update ===" -ForegroundColor Cyan
        Write-Host "Library: $LibraryName" -ForegroundColor Cyan
        Write-Host "Site: $SharePointUrl" -ForegroundColor Cyan
        if ($ForceUpdate) {
            Write-Host "Mode: Force Update (re-apply values to trigger policy evaluation)" -ForegroundColor Yellow
        }
        if ($ApplyRetentionLabel) {
            Write-Host "Retention Label: Will apply '$ApplyRetentionLabel' directly" -ForegroundColor Yellow
        }
        Write-Host ""

        # Import PnP.PowerShell module if not already imported
        if (-not (Get-Module -Name PnP.PowerShell -ListAvailable)) {
            Write-Host "Installing PnP.PowerShell module..." -ForegroundColor Yellow
            Install-Module -Name PnP.PowerShell -Force -Scope CurrentUser
        }

        if (-not (Get-Module -Name PnP.PowerShell)) {
            Import-Module PnP.PowerShell
        }

        # Parse content data if provided
        $contentDataObj = $null
        if ($ContentData) {
            try {
                $contentDataObj = $ContentData | ConvertFrom-Json
            }
            catch {
                Write-Error "Failed to parse ContentData JSON: $_"
                return
            }
        }
    }

    process {
        try {
            # Connect to the SharePoint site
            Write-Host "Connecting to SharePoint..." -ForegroundColor Yellow
            Connect-PnPOnline -Url $SharePointUrl -UseWebLogin
            # Connect-PnPOnline -Url $SharePointUrl -Interactive
            Write-Host "Connected successfully`n" -ForegroundColor Green

            # Get the library
            Write-Host "Retrieving library items..." -ForegroundColor Yellow
            $library = Get-PnPList -Identity $LibraryName
            $items = Get-PnPListItem -List $library -PageSize 5000

            Write-Host "Initial item count: $($items.Count)" -ForegroundColor Cyan

            # Apply filters
            $items = Apply-Filters -Items $items -FileObject $FileObject -FilePath $FilePath `
                                   -FilePathRecursive:$FilePathRecursive -IncludeFileTypes $IncludeFileTypes `
                                   -ExcludeFileTypes $ExcludeFileTypes `
                                   -OnlyRetentionLabels $OnlyRetentionLabels `
                                   -IncludeEmptyRetentionLabels:$IncludeEmptyRetentionLabels

            Write-Host "Items after filtering: $($items.Count)`n" -ForegroundColor Cyan

            if ($items.Count -eq 0) {
                Write-Host "No items to process after filtering" -ForegroundColor Yellow
                return
            }

            # Confirm before proceeding if applying retention labels
            if ($ApplyRetentionLabel) {
                Write-Host "WARNING: This will apply retention label '$ApplyRetentionLabel' to $($items.Count) files." -ForegroundColor Yellow
                $confirm = Read-Host "Do you want to continue? (yes/no)"
                if ($confirm -ne "yes") {
                    Write-Host "Operation cancelled by user." -ForegroundColor Red
                    return
                }
            }

            # Update fields if ContentData provided
            if ($ContentData) {
                Update-ItemFields -Items $items -SharePointUrl $SharePointUrl -LibraryName $LibraryName `
                                 -ContentData $ContentData -ContentDataObj $contentDataObj -ForceUpdate:$ForceUpdate
            }

            # Apply retention label directly if specified
            if ($ApplyRetentionLabel) {
                Apply-RetentionLabelToItems -Items $items -SharePointUrl $SharePointUrl -LibraryName $LibraryName `
                                           -RetentionLabelName $ApplyRetentionLabel
            }

            # Clear retention labels if requested
            if ($ClearRetentionLabel) {
                Clear-RetentionLabels -Items $items -SharePointUrl $SharePointUrl -LibraryName $LibraryName
            }
        }
        catch {
            Write-Error "An error occurred: $_"
            Write-Error $_.Exception.StackTrace
        }
        finally {
            # Clear progress bar
            Write-Progress -Activity "Processing" -Completed

            # Calculate and display execution time
            $endTime = Get-Date
            $executionTime = $endTime - $startTime
            Write-Host "`n=== Execution Complete ===" -ForegroundColor Cyan
            Write-Host "Total execution time: $($executionTime.ToString('hh\:mm\:ss'))" -ForegroundColor Cyan
        }
    }
}

function Apply-Filters {
    param (
        [Parameter(Mandatory = $true)]
        $Items,
        [string[]]$FileObject,
        [string[]]$FilePath,
        [switch]$FilePathRecursive,
        [string[]]$IncludeFileTypes,
        [string[]]$ExcludeFileTypes,
        [string[]]$OnlyRetentionLabels,
        [switch]$IncludeEmptyRetentionLabels
    )

    $filteredItems = $Items

    # Filter by file object type (File/Folder)
    if ($FileObject.Count -gt 0) {
        $filteredItems = $filteredItems | Where-Object { 
            $FileObject -contains $_.FileSystemObjectType 
        }
        Write-Host "After FileObject filter: $($filteredItems.Count)" -ForegroundColor Gray
    }

    # Filter by file path
    if ($FilePath.Count -gt 0) {
        $filteredItems = $filteredItems | Where-Object {
            $itemPath = $_.FieldValues["FileDirRef"]

            if ($FilePathRecursive) {
                # Check if item path matches or is under any specified path
                $matchFound = $false
                foreach ($path in $FilePath) {
                    if ($itemPath -eq $path -or $itemPath -like "$path/*") {
                        $matchFound = $true
                        break
                    }
                }
                $matchFound
            }
            else {
                # Exact match only
                $FilePath -contains $itemPath
            }
        }
        Write-Host "After FilePath filter: $($filteredItems.Count)" -ForegroundColor Gray
    }

    # Filter by file types
    if ($IncludeFileTypes.Count -gt 0 -or $ExcludeFileTypes.Count -gt 0) {
        $filteredItems = $filteredItems | Where-Object {
            $fileType = $_.FieldValues["File_x0020_Type"]

            # Include check
            $includeCondition = if ($IncludeFileTypes.Count -gt 0) {
                $IncludeFileTypes -contains $fileType
            } else {
                $true
            }

            # Exclude check
            $excludeCondition = if ($ExcludeFileTypes.Count -gt 0) {
                $ExcludeFileTypes -notcontains $fileType
            } else {
                $true
            }

            $includeCondition -and $excludeCondition
        }
        Write-Host "After FileType filter: $($filteredItems.Count)" -ForegroundColor Gray
    }

    # Filter by retention labels
    if ($OnlyRetentionLabels.Count -gt 0 -or $IncludeEmptyRetentionLabels) {
        $filteredItems = $filteredItems | Where-Object {
            $retentionLabel = $_.FieldValues["_ComplianceTag"]

            $matchesSpecificLabel = if ($OnlyRetentionLabels.Count -gt 0) {
                $OnlyRetentionLabels -contains $retentionLabel
            } else {
                $false
            }

            $isEmptyLabel = if ($IncludeEmptyRetentionLabels) {
                [string]::IsNullOrEmpty($retentionLabel)
            } else {
                $false
            }

            $matchesSpecificLabel -or $isEmptyLabel
        }
        Write-Host "After RetentionLabel filter: $($filteredItems.Count)" -ForegroundColor Gray
    }

    return $filteredItems
}

function Update-ItemFields {
    param (
        [Parameter(Mandatory = $true)]
        $Items,
        [string]$SharePointUrl,
        [string]$LibraryName,
        [string]$ContentData,
        $ContentDataObj,
        [switch]$ForceUpdate
    )

    $totalUpdates = $Items.Count
    $currentUpdate = 0
    $updatedCount = 0

    Write-Host "Starting field updates...`n" -ForegroundColor Yellow

    foreach ($item in $Items) {
        $updateNeeded = $ForceUpdate  # Force update if switch is set
        $logMessages = @()

        # Check each field for changes
        foreach ($field in $ContentDataObj.formValues) {
            $fieldName = $field.FieldName
            $newValue = $field.FieldValue

            try {
                # Handle Document_Class (managed metadata) specially
                if ($fieldName -eq "Document_Class") {
                    $currentValue = if ($item.FieldValues[$fieldName]) {
                        "$($item.FieldValues[$fieldName].Label)|$($item.FieldValues[$fieldName].TermGuid)"
                    } else {
                        $null
                    }
                }
                else {
                    $currentValue = $item.FieldValues[$fieldName]
                }

                # Compare values
                if ($currentValue -ne $newValue) {
                    $updateNeeded = $true
                    $displayOldValue = if ($null -eq $currentValue) { "(empty)" } else { $currentValue }
                    $logMessages += "  '$fieldName': $displayOldValue → $newValue"
                }
                elseif ($ForceUpdate) {
                    $logMessages += "  '$fieldName': Re-applying to trigger policy evaluation"
                }
            }
            catch {
                Write-Warning "Error checking field '$fieldName' for item $($item.Id): $_"
            }
        }

        # Perform update if needed
        if ($updateNeeded) {
            try {
                Write-Host "[$($item.Id)] '$($item.FieldValues["FileLeafRef"])'" -ForegroundColor Green
                foreach ($message in $logMessages) {
                    Write-Host $message -ForegroundColor Gray
                }

                $apiCall = "$SharePointUrl/_api/web/lists/getbytitle('$LibraryName')/items($($item.Id))/ValidateUpdateListItem"
                $result = Invoke-PnPSPRestMethod -Url $apiCall -Method Post -Content $ContentData -ContentType "application/json;odata=verbose"

                # Check for errors in response
                if ($result.value -and $result.value[0].HasException) {
                    Write-Warning "  Update had exceptions: $($result.value[0].ErrorMessage)"
                }

                $updatedCount++
            }
            catch {
                Write-Error "  Failed to update item $($item.Id): $_"
            }
        }

        # Update progress bar
        $currentUpdate++
        $percentComplete = [math]::Round(($currentUpdate / $totalUpdates) * 100, 2)
        Write-Progress -Activity "Updating fields" -Status "$percentComplete% Complete ($currentUpdate of $totalUpdates)" -PercentComplete $percentComplete
    }

    Write-Progress -Activity "Updating fields" -Completed
    Write-Host "`nField updates complete: $updatedCount items updated" -ForegroundColor Green
}

function Apply-RetentionLabelToItems {
    param (
        [Parameter(Mandatory = $true)]
        $Items,
        [string]$SharePointUrl,
        [string]$LibraryName,
        [string]$RetentionLabelName
    )

    $totalUpdates = $Items.Count
    $currentUpdate = 0
    $successCount = 0
    $errorCount = 0
    $errors = @()

    Write-Host "`nStarting retention label application...`n" -ForegroundColor Yellow

    foreach ($item in $Items) {
        try {
            $apiCall = "$SharePointUrl/_api/web/lists/getbytitle('$LibraryName')/items($($item.Id))/SetComplianceTag()"
            $body = '{"complianceTag":"' + $RetentionLabelName + '"}'

            Invoke-PnPSPRestMethod -Url $apiCall -Method Post -Content $body -ContentType "application/json;odata=verbose" | Out-Null

            Write-Host "[$($item.Id)] '$($item.FieldValues["FileLeafRef"])': Label applied" -ForegroundColor Green
            $successCount++
        }
        catch {
            $errorMsg = $_.Exception.Message
            Write-Host "[$($item.Id)] '$($item.FieldValues["FileLeafRef"])': FAILED - $errorMsg" -ForegroundColor Red
            $errorCount++
            $errors += [PSCustomObject]@{
                ItemId = $item.Id
                FileName = $item.FieldValues["FileLeafRef"]
                Error = $errorMsg
            }
        }

        # Update progress
        $currentUpdate++
        $percentComplete = [math]::Round(($currentUpdate / $totalUpdates) * 100, 2)
        Write-Progress -Activity "Applying Retention Labels" -Status "$percentComplete% Complete ($currentUpdate of $totalUpdates)" -PercentComplete $percentComplete
    }

    Write-Progress -Activity "Applying Retention Labels" -Completed

    # Summary
    Write-Host "`nRetention label application complete:" -ForegroundColor Cyan
    Write-Host "  Successfully applied: $successCount" -ForegroundColor Green
    Write-Host "  Failed: $errorCount" -ForegroundColor Red

    # Export errors if any
    if ($errorCount -gt 0) {
        $errorFile = "RetentionLabel_Errors_$(Get-Date -Format 'yyyyMMdd_HHmmss').csv"
        $errors | Export-Csv -Path $errorFile -NoTypeInformation
        Write-Host "  Errors exported to: $errorFile" -ForegroundColor Yellow
    }
}

function Clear-RetentionLabels {
    param (
        [Parameter(Mandatory = $true)]
        $Items,
        [string]$SharePointUrl,
        [string]$LibraryName
    )

    $totalClears = $Items.Count
    $currentClear = 0
    $clearedCount = 0

    Write-Host "`nStarting retention label clearing...`n" -ForegroundColor Yellow

    foreach ($item in $Items) {
        # Only process items with retention labels
        if (-not [string]::IsNullOrEmpty($item.FieldValues["_ComplianceTag"])) {
            try {
                $apiCall = "$SharePointUrl/_api/web/lists/getbytitle('$LibraryName')/items($($item.Id))/SetComplianceTag()"
                $body = '{"complianceTag": null}'
                Invoke-PnPSPRestMethod -Url $apiCall -Method Post -Content $body -ContentType "application/json;odata=verbose" | Out-Null

                Write-Host "[$($item.Id)] '$($item.FieldValues["FileLeafRef"])': Retention label cleared" -ForegroundColor Green
                $clearedCount++
            }
            catch {
                Write-Error "  Failed to clear retention label for item $($item.Id): $_"
            }
        }

        # Update progress bar
        $currentClear++
        $percentComplete = [math]::Round(($currentClear / $totalClears) * 100, 2)
        Write-Progress -Activity "Clearing Retention Labels" -Status "$percentComplete% Complete ($currentClear of $totalClears)" -PercentComplete $percentComplete
    }

    Write-Progress -Activity "Clearing Retention Labels" -Completed
    Write-Host "`nRetention label clearing complete: $clearedCount labels cleared" -ForegroundColor Green
}


####################
# Field Value Update
####################

####################
# Apply Retention Labels to Unlabelled Files
# Purpose: Updates files that are missing retention labels or have outdated labels
# This script:
#   1. Filters files to only those with empty labels OR "Retain - 10 years" label
#   2. Updates the Document_Class metadata field
#   3. Directly applies "Retain - indefinite" retention label via API
# Use case: Retroactively apply retention labels to files that weren't auto-labeled
####################

$sharePointUrl = "https://ms.sharepoint.com/teams/1234/"
$libraryName = "Documents"
$filePath = @()
$filePathRecursive = $true
$fileObject = @("File")
$includeFileTypes = @()
$excludeFileTypes = @("msg")
$clearRetentionLabel = $false
$onlyRetentionLabels = @("Retain - 10 years") # Target files with empty or "Retain - 10 years" retention labels
$includeEmptyRetentionLabels = $true
$applyRetentionLabel = "Retain - indefinite" # Apply retention label directly
$contentData = @"
{
    "formValues": [
        {
            "FieldName": "Document_Class",
            "FieldValue": "Business knowledge [Retain - Indefinite]|12345",
            "HasException": false,
            "ErrorMessage": null
        }
    ],
    "bNewDocumentUpdate": false,
    "checkInComment": null
}
"@

# Execute - This will:
# 1. Update Document_Class field (optional - comment out $contentData to skip)
# 2. Apply retention label directly via API
Update-SharePointLibraryFields `
    -SharePointUrl $sharePointUrl `
    -LibraryName $libraryName `
    -FilePath $filePath `
    -FilePathRecursive:$filePathRecursive `
    -FileObject $fileObject `
    -IncludeFileTypes $includeFileTypes `
    -ExcludeFileTypes $excludeFileTypes `
    -OnlyRetentionLabels $onlyRetentionLabels `
    -IncludeEmptyRetentionLabels:$includeEmptyRetentionLabels `
    -ApplyRetentionLabel $applyRetentionLabel `
    -ContentData $contentData
