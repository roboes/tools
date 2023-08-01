## File Management
# Last update: 2023-08-01


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

# Install exiftool
# brew install qpdf


# Settings
cd "/mnt/c/Users/${USER}/Downloads"


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


# Decrypt PDF password
qpdf "input.pdf" --password="1234" --decrypt "output.pdf"
