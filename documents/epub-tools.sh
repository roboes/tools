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


# Batch Rename ePubs
shopt -s nullglob
for file in *.epub; do
    meta=$(ebook-meta "$file" 2>/dev/null)

    author=$(sed -n 's/^Author(s)[[:space:]]*:[[:space:]]*//p' <<< "$meta")
    title=$(sed -n 's/^Title[[:space:]]*:[[:space:]]*//p' <<< "$meta")

    # Strip Calibre's sort suffix, e.g. "Jane Doe [Doe, Jane]"
    author=${author%% \[*}

    # Convert "Lastname, Firstname" to "Firstname Lastname" (single author only)
    if [[ "$author" =~ ^([^,&]+),[[:space:]]*([^&]+)$ ]]; then
        author="${BASH_REMATCH[2]} ${BASH_REMATCH[1]}"
    fi

    if [[ -z "$author" || -z "$title" ]]; then
        echo "Skipping (missing metadata): $file"
        continue
    fi

    # Remove characters that are problematic in filenames
    newname=$(tr -d '/\\:*?"<>|' <<< "$author - $title").epub

    [[ "$file" == "$newname" ]] && continue
    mv -n -v -- "$file" "$newname"
done