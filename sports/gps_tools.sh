## GPS Tools
# Last update: 2023-08-09


# Rename: ExifTool


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


# Settings
cd "/mnt/c/Users/${USER}/Downloads"


# Install gpsbabel
# brew install gpsbabel


# Add faketime with 2 seconds increment between each trackpoin to .gpx files and export it to .tcx
for file in ./*.gpx; do
	gpsbabel -t -i gpx -f "$file" -x track,faketime=f20220605200000+2 -o gtrnctr,course=0  -F "${file%.*}.tcx"
	echo ${file%.*}
done
