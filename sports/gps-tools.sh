## GPS Tools
# Last update: 2025-07-08


# Start Bash (Unix Shell)
bash


# Install gpsbabel
# brew install gpsbabel


# Settings
if grep -qi microsoft /proc/version; then
	cd "/mnt/c/Users/${USER}/Downloads"
else
	cd "${HOME}/Downloads"
fi


# For all .gpx files, add faketime with 2 seconds increment between each trackpoint and export it to .tcx
for file in ./*.gpx; do
	gpsbabel -t -i gpx -f "$file" -x track,faketime=f20220605200000+2 -o gtrnctr,course=0  -F "${file%.*}.tcx"
	echo ${file%.*}
done


# Combine multiple .tcx activity files into one .tcx file (for bulk upload to Strava - Strava will automatically separate/split these activities after upload)
# Notes: combined activity file output does not work with file types .gpx and .fit; .gpx to .tcx loses heart rate data; Strava automatically detects duplicate activities, even when the original file format was converted and combined from .tcx, .gpx and .fit to .tcx
# Strava seems to accept combined files of up to 75 megabytes

file_type="tcx"

files=""
for file in ./*."$file_type"; do
	files="$files -f $file"
done

if [ "$file_type" == "tcx" ]; then format="gtrnctr"; elif [ "$file_type" == "fit" ]; then format="garmin_fit"; elif [ "$file_type" == "gpx" ]; then format="gpx"; fi

gpsbabel -t -r -w -i $format $files -o gtrnctr,course=0 -F activities_combined.tcx


# Merge two .fit files (including heart rate data) into a single .gpx file for Strava upload (order matters)
gpsbabel -t -r -w -i garmin_fit -f activity_file_1.fit -f activity_file_2.fit -o gpx,garminextensions -F activity_merged.gpx
