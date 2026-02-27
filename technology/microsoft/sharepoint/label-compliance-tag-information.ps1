## SharePoint - Output all distinct label values for compliance tag
# Last update: 2026-02-10


function Get-SharePointRetentionLabels {
    param (
        [string]$sharePointUrl,
        [string]$libraryName,
        [string[]]$filePath = @(),
        [switch]$filePathRecursive = $false,
        [string[]]$fileObject = @(),
        [string[]]$includeFileTypes = @(),
        [string[]]$excludeFileTypes = @(),
        [switch]$exportToCSV = $false,
        [string]$csvPath = "C:\Users\$([System.Environment]::UserName)\Downloads"
    )

    # Capture start time
    $startTime = Get-Date

    # Connect to the SharePoint site
    Connect-PnPOnline -Url $sharePointUrl -UseWebLogin
    # Connect-PnPOnline -Url $SharePointUrl -Interactive

    # Get the library
    $library = Get-PnPList -Identity $libraryName

    # Get all items in the library
    $items = Get-PnPListItem -List $library -PageSize 5000

    # Apply your existing filters
    if ($fileObject.Count -gt 0) {
        $items = $items | Where-Object { $fileObject -contains $_.FileSystemObjectType }
    }

    if ($filePath.Count -gt 0) {
        if ($filePathRecursive) {
            $items = $items | Where-Object {
                $itemPath = $_.FieldValues["FileDirRef"]
                $filePath | Where-Object { $itemPath -like "$_/*" -or $itemPath -eq $_ } | Measure-Object | Select-Object -ExpandProperty Count | Where-Object { $_ -gt 0 }
            }
        } else {
            $items = $items | Where-Object { $filePath -contains $_.FieldValues["FileDirRef"] }
        }
    }

    if ($includeFileTypes.Count -gt 0 -or $excludeFileTypes.Count -gt 0) {
        $items = $items | Where-Object {
            if ($includeFileTypes.Count -gt 0) {
                $includeCondition = $includeFileTypes -contains $_.FieldValues["File_x0020_Type"]
            } else {
                $includeCondition = $true
            }

            if ($excludeFileTypes.Count -gt 0) {
                $excludeCondition = $excludeFileTypes -notcontains $_.FieldValues["File_x0020_Type"]
            } else {
                $excludeCondition = $true
            }

            $includeCondition -and $excludeCondition
        }
    }

    Write-Host "Total items after filtering: $($items.Count)" -ForegroundColor Cyan

    # Get unique retention labels
    $retentionLabels = $items | ForEach-Object {
        [PSCustomObject]@{
            Label = $_.FieldValues["_ComplianceTag"]
            LabelDisplay = $_.FieldValues["_ComplianceTagWrittenTime"]
        }
    } | Group-Object -Property Label | Select-Object Name, Count | Sort-Object Count -Descending

    Write-Host "`nUnique Retention Labels:" -ForegroundColor Yellow
    $retentionLabels | Format-Table -AutoSize

    # Find files with NO retention label (null or empty)
    $unlabelledFiles = $items | Where-Object { 
        [string]::IsNullOrEmpty($_.FieldValues["_ComplianceTag"]) 
    }

    Write-Host "`nUnlabelled files: $($unlabelledFiles.Count)" -ForegroundColor Cyan

    # Export all files to CSV if flag is set
    if ($exportToCSV) {
        $csvFileName = "SharePointFiles_$(Get-Date -Format 'yyyyMMdd_HHmmss').csv"
        $fullCsvPath = Join-Path -Path $csvPath -ChildPath $csvFileName

        $items | Select-Object @{
            Name = "ID"; Expression = { $_.Id }
        }, @{
            Name = "FileName"; Expression = { $_.FieldValues["FileLeafRef"] }
        }, @{
            Name = "FilePath"; Expression = { $_.FieldValues["FileDirRef"] }
        }, @{
            Name = "FileType"; Expression = { $_.FieldValues["File_x0020_Type"] }
        }, @{
            Name = "Modified"; Expression = { $_.FieldValues["Modified"] }
        }, @{
            Name = "RetentionLabel"; Expression = { $_.FieldValues["_ComplianceTag"] }
        } | Export-Csv -Path $fullCsvPath -NoTypeInformation

        Write-Host "All files exported to: $fullCsvPath" -ForegroundColor Green
    }

    # Find files with specific retention label
    $specificLabel = "Non-relevant content - delete after 6 years"
    $filesWithSpecificLabel = $items | Where-Object { 
        $_.FieldValues["_ComplianceTag"] -eq $specificLabel
    }

    Write-Host "`nFiles with '$specificLabel': $($filesWithSpecificLabel.Count)" -ForegroundColor Cyan

    # Capture end time
    $endTime = Get-Date
    $executionTime = $endTime - $startTime
    Write-Host "`nExecution time: $($executionTime.ToString())" -ForegroundColor Cyan
}

## Settings
$sharePointUrl = "https://ms.sharepoint.com/teams/1234/"
$libraryName = "Documents"
$fileObject = @("File")
$excludeFileTypes = @("msg")

## Run function
Get-SharePointRetentionLabels `
    -sharePointUrl $sharePointUrl `
    -libraryName $libraryName `
    -fileObject $fileObject `
    -excludeFileTypes $excludeFileTypes `
    -exportToCSV `
    -csvPath "C:\Users\$([System.Environment]::UserName)\Downloads"
