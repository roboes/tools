:::: Windows Package Manager Applications
:: Last update: 2023-07-10


:: Install 
winget install --exact --id=Python.Python.3.11
winget install --exact --id=Posit.RStudio
winget install --exact --id=7zip.7zip
winget install --exact --id=WinSCP.WinSCP
winget install --exact --id=VideoLAN.VLC
winget install --exact --id=IDRIX.VeraCrypt
winget install --exact --id=RProject.R
winget install --exact --id=RProject.Rtools


:: Install Windows Subsystem for Linux (WSL)
:: wsl --install

:: Start Windows Subsystem for Linux (WSL) (required only on Windows)
wsl

# Homebrew install
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"

# ulimit current limit
ulimit -n

# ulimit increase limit
ulimit -n 8192

# Install apps
brew install exiftool
brew install gh
brew install git
# brew install imagemagick
# brew install qpdf

# Homebrew update
brew update && brew upgrade && brew cleanup
