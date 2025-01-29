## Photo Tools
# Last update: 2024-12-22


# Rename: ExifTool
# Compare duplicates: DupeGuru
# Test for corrupted images: Bad Peggy
# RAW to .jpg: ImageMagick or Canon Digital Photo Professional 4 (File > Batch process... > Output resolution: 72 dpi; Image quality: 10)
# .heic to .jpg: ImageMagick or XnConvert (Settings: Keep original date/time attributes; JPG - JPEG/JFIF with quality 80)


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
# brew install exiftool

# Install ffmpeg
# brew install ffmpeg

# Install imagemagick
# brew install imagemagick


# Settings
# cd "$HOME"
cd "/mnt/c/Users/${USER}/Pictures/Import"


## ExifTool
# %e - extension
# %c - increment option starting from space
# %+.nc - increment option starting from 1
# To include subdirectories (recursively): -recurse


# Check exiftool version
exiftool -ver


# Photos rename - Rename only photos and videos which contain DateTimeOriginal metadata
exiftool \
	'-FileName<${DateTimeOriginal}%+.nc.%e' \
	'-FileName<${DateTimeOriginal}.${SubSecTimeOriginal}%+.nc.%e' \
	-dateFormat '%Y-%m-%d, %H.%M.%S' \
	-recurse \
	.


# Photos rename - Rename all photos and videos given available metadata (where FileModifyDate metadata is the least relevant parameter for the file name and DateTimeOriginal the most relevant)
exiftool \
	-if '($FileTypeExtension eq "mov" and defined $ContentIdentifier)' \
	'-FileName<Apple Live Photo ${CreationDate}%+.nc.%e' \
	'-FileName<Apple Live Photo ${CreationDate}.${SubSecTime}%+.nc.%e' \
	-execute \
	-if '($FileTypeExtension eq "mov" and not defined $ContentIdentifier)' \
	'-FileName<${CreationDate}%+.nc.%e' \
	'-FileName<${CreationDate}.${SubSecTime}%+.nc.%e' \
	-execute \
	-if '($FileTypeExtension ne "mov")' \
	'-FileName<${FileModifyDate}%+.nc.%e' \
	'-FileName<${ModifyDate}%+.nc.%e' \
	'-FileName<${ModifyDate}.${SubSecTime}%+.nc.%e' \
	'-FileName<${CreateDate}%+.nc.%e' \
	'-FileName<${CreateDate}.${SubSecTime}%+.nc.%e' \
	'-FileName<${FileCreateDate}%+.nc.%e' \
	'-FileName<${FileCreateDate}.${SubSecTime}%+.nc.%e' \
	'-FileName<${MediaCreateDate}%+.nc.%e' \
	'-FileName<${DateTimeOriginal}%+.nc.%e' \
	'-FileName<${DateTimeOriginal}.${SubSecTimeOriginal}%+.nc.%e' \
	-common_args \
	-dateFormat '%Y-%m-%d, %H.%M.%S' \
	-recurse \
	.



# Photo metadata tool - ModifyDate to DateTimeOriginal if Model = 'Redmi Note 8 Pro'
exiftool \
	# -if '$Model eq "Redmi Note 8 Pro"'
	-if 'not defined $DateTimeOriginal' \
	-overwrite_original \
	'-DateTimeOriginal<FileCreateDate' \
	# '-SubSecTimeOriginal<SubSecModifyDate'
	.



## Tools

# Metadata info
exiftool -s -G .

# Test for metadata
exiftool '-DateTimeOriginal' '-GPSDateTime' '-SubSecModifyDate' '-SubSecTimeOriginal' '-MediaCreateDate' .

# Detect Apple Live Photos
exiftool -ContentIdentifier .

# Manually change DateTimeOriginal
exiftool -overwrite_original '-DateTimeOriginal=2023-05-07, 13.00.00' -dateFormat '%Y-%m-%d, %H.%M.%S' .

# Add time to DateTimeOriginal (1 year, 12 month, 28 days, 14 hours, 54 minutes, 32 seconds)
exiftool -overwrite_original '-DateTimeOriginal+=1:12:28 14:54:32' .

# Rotate video from vertical to horizontal
exiftool -overwrite_original -rotation=0 .

# Rotate video from horizontal to vertical
exiftool -overwrite_original -rotation=90 .

# FileName to Title
exiftool -overwrite_original '-title<${FileName;s/ \([0-9]{1,5}\)(\.[^.]*)$//}' .

# Title to FileName
exiftool '-FileName<${xmp:Title}%+.nc.%e' .

# FileModifyDate to DateTimeOriginal
exiftool -overwrite_original '-DateTimeOriginal<FileModifyDate' .

# FileName to DateTimeOriginal (including regular expression to remove SubSecTimeOriginal and n incremental FileName)
exiftool \
	-if 'not defined $DateTimeOriginal' \
	-overwrite_original \
	'-DateTimeOriginal<${FileName; s/([0-9]{4}-[0-9]{2}-[0-9]{2}, [0-9]{2}\.[0-9]{2})\.([0-9]+)(_[0-9]+)?(\.[^.]*)$/$1$4/}' \
	'-SubSecTimeOriginal<${FileName; s/([0-9]{4}-[0-9]{2}-[0-9]{2}, [0-9]{2}\.[0-9]{2})\.([0-9]+)(_[0-9]+)?(\.[^.]*)$/$2/}' \
	.

# Delete RAW if .jpg exists
exiftool \
	-directory=trash \
	-srcfile %d%f.cr2 \
	-ext jpg \
	.



## Regular expressions

# Remove double space
exiftool '-FileName<${FileName; s/ / /}' .

# Remove space between FileName and extension
exiftool '-FileName<${FileName; s/ (\.[^.]*)$/$1/}' .

# Remove (
exiftool '-FileName<${FileName; s/\(/$1/}' .

# Remove )
exiftool '-FileName<${FileName; s/\)/$1/}' .

# Replace _ by space
exiftool '-FileName<${FileName; s/_/ /}' .

# Remove SubSec and increment
exiftool '-FileName<${FileName; s/\.[0-9]{1,5}_[0-9]{1,5}(\.[^.]*)$/$1/}' .



# Move photos without DateTimeOriginal
exiftool \
	'-directory=./New Folder' \
	-if '(not $DateTimeOriginal)' \
	-recurse \
	.

# Move photos to Make Model folder
exiftool '-directory<./${Make} ${Model}' .

# Metadata to .csv
exiftool '-Directory' '-FileName' '-Make' '-Model' '-GPSPosition' '-Title' -csv . > './New Folder/File.csv'

# List all Google Photos files
# Metadata information added by Google Photos: ImageUniqueID, GPSVersionID, XMPToolkit, InstanceID
# https://photo.stackexchange.com/questions/101037/how-to-distinguish-images-compressed-by-google-photos-vs-the-original-using-meta
exiftool -if '($XMPToolkit eq "XMP Core 5.5.0") and ($ImageUniqueID)' -FileName -FilePath -ext jpg .

# Move photos-vs-the-original-using-meta
exiftool -if '($XMPToolkit eq "XMP Core 5.5.0") and ($ImageUniqueID)' '-directory<./New Folder' .

exiftool '-XMPToolkit' -csv . > './File.csv'



### Czkawka

## Installation

# Fetch the latest version tag
VERSION=$(curl -fsSL https://api.github.com/repos/qarmin/czkawka/releases/latest | jq -r '.tag_name'); echo "${VERSION}"

# Download the package
curl -fsSL "https://github.com/qarmin/czkawka/releases/download/${VERSION}/linux_czkawka_gui_heif_libraw" -o "/tmp/czkawka-gui"

# Install the package
sudo install "/tmp/czkawka-gui" "/usr/local/bin/czkawka-gui"


## Use

Similar Images
- Resize algorithm > Lanczos3
- Hash size: 16
- Hash type: Mean



### FFmpeg
# Useful to get CreateDate for .avi and .mpg files

# Metadata info
ffmpeg -i "./Movie.avi" -dump


## Vectors

# Convert .eps to .svg
for file in *.eps; do
    dbus-launch inkscape "$file" --export-filename="${file%.eps}.svg";
done

# Convert .pdf to .svg
for file in *.pdf; do
	dbus-launch inkscape "$file" --export-filename="${file%.pdf}.svg";
done

# Convert .svg to .png
for file in *.svg; do
	dbus-launch inkscape "$file" --export-type=png --export-width=512 --export-filename="${file%.svg}.png";
done


## ImageMagick

# Identify ICC profile
# magick identify -verbose -format %[profile:icc] "./001.HEIC"

# Convert RAW (.cr2) to .jpg
magick mogrify -monitor -format jpg -quality 85 -density 72 "./*.CR2"

# Convert .heic to .jpg
magick mogrify -monitor -format jpg "./*.HEIC"

# Convert .pdf to .png
magick mogrify -monitor -density 300 -format png "./*.pdf"

# Reduce image file size (.jpg)
magick mogrify -monitor -resize 50% "./*.jpg"
magick mogrify -monitor -quality 80 -resize 800x "./*.jpg" # Web
magick mogrify -monitor -quality 80 -resize 1920x "./*.jpg" # Web
magick mogrify -monitor -format jpg -quality 80 -resize 800x "./*.HEIC" # Web
magick mogrify -monitor -format jpg -quality 80 -resize 1920x "./*.HEIC" # Web

# Reduce image file size (.png)
magick mogrify -monitor -resize 800x -colors 256 "./*.png" # Web

## Reduce image file size (.png) - if width > height, resize by width; else resize by height
image="image-1.png"

dimensions=$(magick identify -format "%wx%h" "$image")
width=$(echo $dimensions | cut -d'x' -f1)
height=$(echo $dimensions | cut -d'x' -f2)
if [ "$width" -gt "$height" ]; then
    magick mogrify -monitor -resize 400x -colors 256 "$image"
else
    magick mogrify -monitor -resize x440 -colors 256 "$image"
fi


## Resize the image to fit within the square dimensions and pad the remaining space
image="image-1.png"

width=$(magick identify -format "%w" "$image")
height=$(magick identify -format "%h" "$image")

if [ "$width" -gt "$height" ]; then
    size="$width"
else
    size="$height"
fi

magick convert "$image" -resize ${size}x${size} -gravity center -background white -extent ${size}x${size} "$image"


# Reduce image file dimensions (.svg)
for file in ./*.svg; do
    rsvg-convert --width 22 --height 13 --format svg --output "${file%.svg}_updated.svg" "$file"
done


# Replace all colors except background in .png image with white
magick convert "./input.png" -fill white -colorize 100 "./output.png"

# Replace a specific color by transparent background
magick mogrify -monitor -fuzz 10% -transparent "#ffffff" "./.png"

# Replace a specific color by semi-transparent
magick mogrify -monitor -fuzz 10% -fill "rgba(255,255,255,0.5)" -opaque "#ffffff" "./.png"

# Crop .png keeping only the shapes
magick mogrify -monitor -trim +repage "./*.png"

# Crop .svg keeping only the shapes
for file in ./*.svg; do
    inkscape "$file" --export-plain-svg="${file%.svg}_cropped.svg" --export-area-drawing
done

# Enhance image
magick -monitor "./input.png" -auto-gamma "./output.png"



## Other tools

# Download image using curl
curl "https://www.python.org/static/apple-touch-icon-144x144-precomposed.png" > "./precomposed.png"
