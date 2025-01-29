## Windows Package Manager Applications
# Last update: 2025-01-26


# Format writing zeros to drive - https://www.lifewire.com/use-the-format-command-to-write-zeros-to-a-hard-drive-2626162
# format d: /fs:exFAT /p:1


# List all installed applications
# winget list


# Find applications
# winget list --query="PDFsam"
# winget search "Apple" --source=msstore

# Install applications
winget install --exact --id=Microsoft.PowerShell
winget install --exact --id Microsoft.WindowsTerminal
winget install --exact --id=Microsoft.PCManager
winget install --exact --id=Python.Python.3.13
winget install --exact --id=Spyder.Spyder
winget install --exact --id=Notepad++.Notepad++
winget install --exact --id=WinSCP.WinSCP
winget install --exact --id=IDRIX.VeraCrypt
winget install --exact --id=XPDP273C0XHQH2 # Adobe Acrobat
winget install --exact --id=KDE.Okular
winget install --exact --id=PDFsam.PDFsam
winget install --exact --id=DupeGuru.DupeGuru
winget install --exact --id=XPDM27W10192Q0 # GIMP
winget install --exact --id=Microsoft.HEVCVideoExtension_8wekyb3d8bbwe
winget install --exact --id=VideoLAN.VLC
winget install --exact --id=Mozilla.Firefox
winget install --exact --id=5319275A.WhatsAppDesktop_cv1g1gvanyjgm
winget install --exact --id=Telegram.TelegramDesktop
winget install --exact --id=Google.NearbyShare
winget install --exact --id=Sigil-Ebook.Sigil
winget install --exact --id=SpotifyAB.SpotifyMusic_zpdnekdrzrea0
winget install --exact --id=9NP83LWLPZ9K # Apple Devices
winget install --exact --id=TheDocumentFoundation.LibreOffice
winget install --exact --id=LocalSend.LocalSend
winget install --exact --id=9N93MRMSXBF0 # KDE Connect

# Applications not available on winget
# https://freefilesync.org/download.php
# https://github.com/marktext/marktext/releases

# Archive
# winget install --exact --id=Google.Chrome
# winget install --exact --id=Google.ChromeRemoteDesktop
# winget install --exact --id=Microsoft.PowerToys
# winget install --exact --id=7zip.7zip
# winget install --exact --id=DuongDieuPhap.ImageGlass
# winget install --exact --id=RProject.R
# winget install --exact --id=RProject.Rtools
# winget install --exact --id=Posit.RStudio

# Update applications
winget upgrade -h --all --accept-package-agreements
# winget upgrade -h --all --include-unknown
# winget upgrade --exact --id=GIMP.GIMP


# Update all Python packages
python -m pip_review --local --auto


# Install Windows Subsystem for Linux (WSL)
# wsl --install -d Debian

# Start Windows Subsystem for Linux (WSL) (required only on Windows)
wsl

# Homebrew install
sudo apt install curl
sudo apt install git
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"

# ulimit current limit
ulimit -n

# ulimit increase limit
ulimit -n 8192

# Homebrew update
brew update && brew upgrade && brew cleanup

# Install apps
brew install exiftool
brew install gh
brew install git
brew install python
# brew unlink python@3.11 && brew link python@3.12
# sudo update-alternatives --config python
sudo update-alternatives --install /usr/bin/python python $(readlink -f $(which python3)) 3
# brew install imagemagick
# brew install qpdf
