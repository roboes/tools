## File Management
# Last update: 2025-07-08


# Start Bash (Unix Shell)
bash


# Install rnr
# brew install rnr


# Settings
if grep -qi microsoft /proc/version; then
	cd "/mnt/c/Users/${USER}/Downloads"
else
	cd "${HOME}/Downloads"
fi


## find
# To disable recursive: add "-maxdepth 0" after "find ."


# List hidden files (recursive)
find . -type f -iname ".*" -print # -delete

# List Thumbs.db files (recursive)
find . -type f -iname "Thumbs.db" -print # -delete

# List empty folders and subfolders (recursive)
find . -type d -empty -print # -delete

# Move files from folders and subfolders to new folder
# find . -type f -exec mv --backup=numbered --target-directory="Output Folder" {} +


# Rename files and folders

## Define an array of patterns and replacements
patterns=(
    '\xA0' ' ' # Remove non-breaking space
    '^ ' '' # Remove leading spaces
	' $' '' # Remove trailing space
	'\.$' '' # Remove trailing dots
    ' (\..*$)' '${1}' # Remove spaces before file extension
    '\s{2,}' ' ' # Replace multiple spaces with a single space
	'[\\/*?"<>|]' ' ' # Remove Windows-forbidden characters
)

# patterns=(
    # '([0-9]{4})\.([0-9]{2})\.([0-9]{2})' '${1}-${2}-${3}'  # Rename from YYYY.MM.DD to YYYY-MM-DD
    # '([0-9]{2})\.([0-9]{2})\.([0-9]{4})' '${3}-${2}-${1}'  # Rename from DD.MM.YYYY to YYYY-MM-DD
    # '([0-9]{4})\.([0-9]{2})' '${1}-${2}'  # Rename from YYYY.MM to YYYY-MM
    # '([0-9]{4})([0-9]{2})([0-9]{2})' '${1}-${2}-${3}'  # Rename from YYYYMMDD to YYYY-MM-DD
# )

# patterns=(
    # '^[0-9]{4}-[0-9]{2}-[0-9]{2}_Kontoauszug_([0-9])_([0-9]{4})_vom_.*(\.pdf)' '${2}-0${1}${3}'  # Extract M_YYYY and rearrange to YYYY-0M
    # '^[0-9]{4}-[0-9]{2}-[0-9]{2}_Kontoauszug_([0-9]{2})_([0-9]{4})_vom_.*(\.pdf)' '${2}-${1}${3}'  # Extract MM_YYYY and rearrange to YYYY-MM
# )

## Loop through patterns and replacements
for ((i=0; i<${#patterns[@]}; i+=2)); do
    rnr regex --include-dirs --recursive "${patterns[$i]}" "${patterns[$i+1]}" './' # --force
done
