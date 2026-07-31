## ePub Tools
# Last update: 2026-07-06


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
exiftool "${settings_book_filename}"

# Update metadata
ebook-meta "${settings_book_filename}" \
    --title="New Title" \
    --authors="Author Name"
    --author-sort="Author Name"

# Print Author - Title
exiftool -p '$Creator - $Title' "${settings_book_filename}"
