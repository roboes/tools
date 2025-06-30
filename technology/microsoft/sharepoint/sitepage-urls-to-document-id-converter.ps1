## SharePoint - Replace SitePages file path URLs with Document ID URLs
# Last update: 2025-06-04

# Uninstall all versions of PnP.PowerShell module
# Uninstall-Module PnP.PowerShell -AllVersions -Force

# Install PnP.PowerShell version 2.5.0 for the current user
# Install-Module -Name PnP.PowerShell -RequiredVersion 2.5.0 -Scope CurrentUser -Force -AllowClobber

# List all available versions of PnP.PowerShell module
# Get-Module -ListAvailable -Name PnP.PowerShell


# Settings
$sharePointUrl = "https://ms.sharepoint.com/teams/1234/"
$sitePagesPath = "SitePages"
$siteRelativeUrl = "/teams/1234"
$relativeFilePattern = '/r/teams/1234/Shared%20Documents/[^"''<]*?\.[^"''<?]+(\?[^"''<]*)?'

# Connect to the SharePoint site
Connect-PnPOnline -Url $sharePointUrl -UseWebLogin

# Get all .aspx files from the SitePages directory
$sitePagesFiles = Get-PnPListItem -List $sitePagesPath | Where-Object { $_.FieldValues["FileLeafRef"] -like "*.aspx" }

# Filter for the specific test file "Computers-and-Electronic-Equipment.aspx"
$testFileName = "Computers-and-Electronic-Equipment.aspx"
$sitePagesFiles = $sitePagesFiles | Where-Object { $_.FieldValues["FileLeafRef"] -eq $testFileName }

# Check if any files are retrieved
if ($sitePagesFiles.Count -eq 0) {
    Write-Host "No files found matching the criteria."
    return
}

foreach ($file in $sitePagesFiles) {
    $fileName = $file.FieldValues["FileLeafRef"]
    $filePath = "$sitePagesPath/$fileName"
    $wasModified = $false

    Write-Host "`nProcessing file: $filePath`n"

    $fileContent = Get-PnPFile -Url $filePath -AsString
    $allMatches = Select-String -InputObject $fileContent -Pattern $relativeFilePattern -AllMatches

    if (-not $allMatches) {
        Write-Host "No relative links found inside $filePath."
        continue
    }

    foreach ($matchInfo in $allMatches) {
        foreach ($m in $matchInfo.Matches) {
            $rawUrl = $m.Value
            # Write-Host "Found exact relative URL: $rawUrl"

            $serverRelativePath = ($rawUrl -replace '^/r', '') -replace '\?.*$', ''
            Write-Host "→ Server-relative path for PnP: $serverRelativePath"

            try {
                $listItem = Get-PnPFile -Url $serverRelativePath -AsListItem
            }
            catch {
                Write-Host "→ ERROR: Could not retrieve ListItem for $serverRelativePath"
                continue
            }

            if ($listItem -and $listItem.FieldValues["_dlc_DocId"]) {
                $docId = $listItem.FieldValues["_dlc_DocId"]
                $docIdUrl = $sharePointUrl.TrimEnd("/") + "/_layouts/DocIdRedir.aspx?ID=$docId"

                # Write-Host "→ Found Document ID: $docId"
                Write-Host "→ DocIdRedir link: $docIdUrl"

                $escapedRawUrl = [regex]::Escape($rawUrl)
                $fileContent = $fileContent -replace $escapedRawUrl, $docIdUrl
                $wasModified = $true
            }
            else {
                Write-Host "→ No Document ID found on that ListItem (or ListItem was null)."
            }
        }
    }

    if ($wasModified) {
        Write-Host "`nUploading modified content back to $filePath..."

        if ((Get-Module -Name PnP.PowerShell).Version -ge [Version]"3.0.0") {
            Set-PnPFile -Url $filePath -Content $fileContent -Force
        }
        else {
			$localFilePath = Join-Path ([Environment]::GetFolderPath("UserProfile") + "\Downloads") $fileName

			Write-Host "Saving modified content locally to $localFilePath"

			# Save the content with UTF8 encoding
			[System.IO.File]::WriteAllText($localFilePath, $fileContent, [System.Text.Encoding]::UTF8)
        }
    }

    Write-Host "`nDone processing $filePath.`n"
}
