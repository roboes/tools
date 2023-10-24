## Documents Tools
# Last update: 2023-10-24


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

# Install ghostscript
# brew install ghostscript

# Install ocrmypdf
# brew install ocrmypdf

# Install qpdf
# brew install qpdf

# Install tesseract-lang
# brew install tesseract-lang


# Settings
cd "/mnt/c/Users/${USER}/Downloads"


# Optical Character Recognition (OCR) PDF document
ocrmypdf -l por file_A.pdf file_B.pdf


# Decrypt PDF password
qpdf "input.pdf" --password="1234" --decrypt "output.pdf"


# Reduce PDF size and quality
gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.5 -dPDFSETTINGS=/ebook -dNOPAUSE -dBATCH -dQUIET -sOutputFile="./output.pdf" "./input.pdf"
