## Documents Tools
# Last update: 2026-09-05


# Start Bash (Unix Shell)
[ -z "${BASH}" ] && exec bash


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

# Install Pandoc
# sudo apt install -y pandoc


# Settings
if grep -qi microsoft /proc/version; then
    cd "/mnt/c/Users/${USER}/Downloads"
else
    cd "${HOME}/Downloads"
fi


# Convert multiple images to a single .pdf
file_type="jpg"

files=""
for file in ./*."${file_type}"; do
    files="${files} ${file}"
done

convert ${files} -quality 100 -density 150 -define pdf:author="" -define pdf:creator="" -define pdf:producer="" -define pdf:title="" images_combined.pdf


# View .pdf metadata
pdfinfo images_combined.pdf


# Optical Character Recognition (OCR) PDF document
ocrmypdf -l por "file_A.pdf" "file_B.pdf"
# for file in *.pdf; do ocrmypdf -l por "${file}" "${file%.pdf}_ocr.pdf"; done


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

# Compare folders
meld "folder_1" "folder_2"

# Compare PDFs
diff-pdf --output-diff=diff.pdf file_A.pdf file_B.pdf


# Convert .pdf to .pptx
soffice --infilter=impress_pdf_import --convert-to ppt "./input.pdf"


# Convert .md to .docx
pandoc "input.md" -o "output.docx"

# Convert .md to .html
pandoc "input.md" -o "output.html" \
  --metadata pagetitle="Document" \
  --standalone \
  --embed-resources \
  --css=https://cdn.jsdelivr.net/npm/water.css@2/out/light.css \
  --mathml


# Count the number of files categorized by their root-level directory and file type
find . -type f | while IFS= read -r file; do
    # Extract the first directory and the file extension
    dir=$(echo "${file}" | cut -d'/' -f2- | awk -F/ '{print $1}')
    ext="${file##*.}"

    # Ensure the extension is valid (not the whole filename) and is not a path
    if [ "${ext}" != "${file}" ] && [[ "${ext}" != */* ]]; then
        ext=$(echo "${ext}" | tr '[:upper:]' '[:lower:]')  # Convert extension to lowercase
        echo "${dir},.${ext}"
    fi
done | sort | uniq -c | awk '{count=$1; $1=""; sub(/^ /, ""); gsub(/ ,/, ","); print $0 "," count}'


# Recursively move all video files from the source folder to the destination, keeping the folder structure and not overwriting existing files
move_videos() {
  [[ $# -ge 2 ]] || { echo "Usage: move_videos <pictures_path> <videos_path> [--dry-run]" >&2; return 1; }
  [[ -d $1 ]]    || { echo "Not a directory: $1" >&2; return 1; }

  local src dst dry=false
  src=$(cd "$1" && pwd) && mkdir -p "$2" && dst=$(cd "$2" && pwd) || return 1
  [[ ${3:-} == --dry-run ]] && dry=true

  local -a names=()
  local ext
  for ext in mp4 mkv avi mov wmv flv webm m4v mpg mpeg 3gp mts m2ts vob ogv; do
    names+=(-o -iname "*.$ext")
  done

  local file target base n count=0
  while IFS= read -r -d '' file; do
    target=$dst/${file#"$src"/}

    # Avoid overwriting: add _1, _2, ... if the name already exists
    if [[ -e $target ]]; then
      base=${target%.*} ext=${target##*.} n=1
      while [[ -e ${base}_$n.$ext ]]; do n=$((n + 1)); done
      target=${base}_$n.$ext
    fi

    if $dry; then
      echo "[dry-run] $file -> $target"
    else
      mkdir -p "${target%/*}" && mv -- "$file" "$target"
      echo "Moved: $file -> $target"
    fi
    count=$((count + 1))
  done < <(find "$src" -type f -not -path "$dst/*" \( "${names[@]:1}" \) -print0)

  echo "Done. ${count} video(s) processed."
}

move_videos ~/Photos ~/Videos --dry-run
