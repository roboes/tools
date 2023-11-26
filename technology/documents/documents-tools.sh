## Documents Tools
# Last update: 2023-11-10


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

# Install imagemagick
# brew install imagemagick

# Install ocrmypdf
# brew install ocrmypdf

# Install qpdf
# brew install qpdf

# Install tesseract-lang
# brew install tesseract-lang

# Install xpdf (pdfinfo)
# brew install xpdf


# Settings
cd "/mnt/c/Users/${USER}/Downloads"


# Convert multiple images to a single .pdf
file_type="jpg"

files=""
for file in ./*."$file_type"; do
	files="$files $file"
done

convert $files -quality 100 -density 150 -define pdf:author="" -define pdf:creator="" -define pdf:producer="" -define pdf:title="" images_combined.pdf


# View .pdf metadata
pdfinfo images_combined.pdf


# Optical Character Recognition (OCR) PDF document
ocrmypdf -l por file_A.pdf file_B.pdf


# Decrypt PDF password
qpdf "input.pdf" --password="1234" --decrypt "output.pdf"


# Reduce PDF size and quality
gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.5 -dPDFSETTINGS=/ebook -dNOPAUSE -dBATCH -dQUIET -sOutputFile="./output.pdf" "./input.pdf"
