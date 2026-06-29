## ePub Tools
# Last update: 2026-06-29


# Install ExifTool
# sudo apt install -y libimage-exiftool-perl


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
# exiftool -Title="New Title" -Creator="Author Name" "${settings_book_filename}"

# Print Author - Title
exiftool -p '$Creator - $Title' "${settings_book_filename}"
