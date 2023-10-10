## GPS Tools
# Last update: 2023-10-10


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


# For all .gpx files, add faketime with 2 seconds increment between each trackpoint and export it to .tcx
for file in ./*.gpx; do
	gpsbabel -t -i gpx -f "$file" -x track,faketime=f20220605200000+2 -o gtrnctr,course=0  -F "${file%.*}.tcx"
	echo ${file%.*}
done


# Combine multiple .tcx activity files into one .tcx file (for bulk upload to Strava - Strava will automatically separate/split these activities after upload - does not work with combined activity files of type .gpx and .fit)
files=""
for file in ./*.tcx; do
	files="$files -f $file"
done

gpsbabel -t -r -w -i gtrnctr $files -o gtrnctr,course=0 -F activities_combined.tcx # .tcx activity files
# gpsbabel -t -r -w -i gpx $files -o gtrnctr,course=0 -F activities_combined.tcx # .gpx activity files
# gpsbabel -t -r -w -i garmin_fit $files -o gtrnctr,course=0 -F activities_combined.tcx # .fit activity files


# Merge two .fit files (including heart rate data) into a single .gpx file for Strava upload (order matters)
gpsbabel -t -r -w -i garmin_fit -f activity_file_1.fit -f activity_file_2.fit -o gpx,garminextensions -F activity_merged.gpx
