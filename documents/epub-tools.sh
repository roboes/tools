## ePub Tools
# Last update: 2026-09-05


# Install packages
# sudo apt install -y calibre-bin libimage-exiftool-perl


# Settings
if grep -qi microsoft /proc/version; then
    cd "/mnt/c/Users/${USER}/Downloads"
else
    cd "${HOME}/Downloads"
fi
settings_book_filename="book.epub"


# View ePub metadata
# exiftool "${settings_book_filename}"
ebook-meta "${settings_book_filename}" | grep -E "^(Title|Author\(s\))"

# Update metadata
ebook-meta "${settings_book_filename}" \
    --author-sort "$(ebook-meta "${settings_book_filename}" | grep -E "^Author\(s\)" | sed -E 's/^Author\(s\)\s*:\s*//; s/ \[.*\]//')"
    # --title="New Title"
    # --authors="Author Name"

# Print Author - Title
exiftool -p '$Creator - $Title' "${settings_book_filename}"
