## Documents Tools
# Last update: 2025-11-23


# Start Bash (Unix Shell)
bash


# Install ghostscript
# sudo apt install -y ghostscript

# Install ImageMagick
# sudo apt install -y imagemagick

# Install OCRmyPDF
# sudo apt install -y ocrmypdf

# Install QPDF
# sudo apt install -y qpdf

# Install Tesseract with all language packs
# sudo apt install -y tesseract-ocr-all

# Install Poppler utilities (provides pdfinfo instead of xpdf)
# sudo apt install -y poppler-utils

# Install DiffPDF
# sudo apt install -y diffpdf


# Settings
if grep -qi microsoft /proc/version; then
	cd "/mnt/c/Users/${USER}/Downloads"
else
	cd "${HOME}/Downloads"
fi


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
ocrmypdf -l por "file_A.pdf" "file_B.pdf"


# Decrypt PDF password
qpdf "input.pdf" --password="1234" --decrypt "output.pdf"


# Extract PDF pages
qpdf "input.pdf" --pages . 2-5 -- "output.pdf"


# Reduce PDF size and quality
gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.5 -dPDFSETTINGS=/ebook -dNOPAUSE -dQUIET -sOutputFile="./output.pdf" -dBATCH "./input.pdf"


# Merge PDFs
gs -sDEVICE=pdfwrite -dNOPAUSE -dQUIET -sOUTPUTFILE="./output.pdf" -dBATCH "./file_A.pdf" "./file_B.pdf"


# Extract embedded images from a PDF
pdfimages -all -p -print-filenames "./input.pdf" "./output"


# Compare PDFs
diff-pdf --output-diff=diff.pdf file_A.pdf file_B.pdf


# Convert .pdf to .pptx
soffice --infilter=impress_pdf_import --convert-to ppt "./input.pdf"


# Count the number of files categorized by their root-level directory and file type
find . -type f | while IFS= read -r file; do
    # Extract the first directory and the file extension
    dir=$(echo "$file" | cut -d'/' -f2- | awk -F/ '{print $1}')
    ext="${file##*.}"

    # Ensure the extension is valid (not the whole filename) and is not a path
    if [ "$ext" != "$file" ] && [[ "$ext" != */* ]]; then
        ext=$(echo "$ext" | tr '[:upper:]' '[:lower:]')  # Convert extension to lowercase
        echo "$dir,.$ext"
    fi
done | sort | uniq -c | awk '{count=$1; $1=""; sub(/^ /, ""); gsub(/ ,/, ","); print $0 "," count}'
