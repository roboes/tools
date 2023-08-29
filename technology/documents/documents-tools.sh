## Documents Tools
# Last update: 2023-08-28


# Start Windows Subsystem for Linux (WSL) (required only on Windows)
wsl


# Homebrew install
# /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"

# ulimit current limit
# ulimit -n

# ulimit increase limit
# ulimit -n 8192

# Homebrew update
brew update && brew upgrade && brew cleanup

# Install ocrmypdf
# brew install ocrmypdf

# Install tesseract-lang
# brew install tesseract-lang


# Settings
cd "/mnt/c/Users/${USER}/Downloads"


# Optical Character Recognition (OCR) PDF document
ocrmypdf -l por file_A.pdf file_B.pdf
