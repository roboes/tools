## Photo Tools
# Last update: 2023-07-10


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


# Settings
directory="/mnt/c/Users/${USER}/Pictures/Import"
cd $directory




## ExifTool
# %e - extension
# %c - increment option starting from space
# %+.nc - increment option starting from 1
# To include subdirectories (recursively): -r

# Install exiftool
# brew install exiftool


# Check exiftool version
exiftool -ver


# Photos rename - Rename only photos and videos which contain DateTimeOriginal metadata
exiftool \
	'-FileName<${DateTimeOriginal}%+.nc.%e' \
	'-FileName<${DateTimeOriginal}.${SubSecTimeOriginal}%+.nc.%e' \
	-dateFormat '%Y.%m.%d, %H.%M.%S' \
	-r \
	$directory


# Photos rename - Rename all photos and videos given available metadata (where FileModifyDate metadata is the least relevant parameter for the file name and DateTimeOriginal the most relevant)
exiftool \
	-if 'defined $ContentIdentifier' and '$FileType eq "MOV"' \
	'-FileName<Apple Live Photo ${FileModifyDate}%+.nc.%e' \
	-execute \
	-if 'not defined $ContentIdentifier' and '$FileType eq "MOV"' \
	'-FileName<${CreationDate}%+.nc.%e' \
	'-FileName<${CreationDate}.${SubSecTime}%+.nc.%e' \
	-execute \
	-if 'not defined $ContentIdentifier' and '$FileType ne "MOV"' \
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
	-dateFormat '%Y.%m.%d, %H.%M.%S' \
	-r \
	$directory



# Photo metadata tool - ModifyDate to DateTimeOriginal if Model = 'Redmi Note 8 Pro'
exiftool \
	# -if '$Model eq "Redmi Note 8 Pro"'
	-if 'not defined $DateTimeOriginal' \
	-overwrite_original \
	'-DateTimeOriginal<FileCreateDate' \
	# '-SubSecTimeOriginal<SubSecModifyDate'
	$directory



## Tools

# Metadata info
exiftool -s -G $directory

# Test for metadata
exiftool '-DateTimeOriginal' '-GPSDateTime' '-SubSecModifyDate' '-SubSecTimeOriginal' '-MediaCreateDate' $directory

# Detect Apple Live Photos
exiftool -ContentIdentifier $directory

# Manually change DateTimeOriginal
exiftool -overwrite_original '-DateTimeOriginal=2023.05.07, 13.00.00' -dateFormat '%Y.%m.%d, %H.%M.%S' $directory

# Add time to DateTimeOriginal (1 year, 12 month, 28 days, 14 hours, 54 minutes, 32 seconds)
exiftool -overwrite_original '-DateTimeOriginal+=1:12:28 14:54:32' $directory

# Rotate video from vertical to horizontal
exiftool -overwrite_original -rotation=0 $directory

# Rotate video from horizontal to vertical
exiftool -overwrite_original -rotation=90 $directory

# FileName to Title
exiftool -overwrite_original '-title<${FileName;s/ \([0-9]{1,5}\)(\.[^.]*)$//}' $directory

# Title to FileName
exiftool '-FileName<${xmp:Title}%+.nc.%e' $directory

# FileModifyDate to DateTimeOriginal
exiftool -overwrite_original '-DateTimeOriginal<FileModifyDate' $directory

# FileName to DateTimeOriginal (including regular expression to remove SubSecTimeOriginal and n incremental FileName)
exiftool \
	-if 'not defined $DateTimeOriginal' \
	-overwrite_original \
	'-DateTimeOriginal<${FileName; s/([0-9]{4}\.[0-9]{2}\.[0-9]{2}, [0-9]{2}\.[0-9]{2})\.([0-9]+)(_[0-9]+)?(\.[^.]*)$/$1$4/}' \
	'-SubSecTimeOriginal<${FileName; s/([0-9]{4}\.[0-9]{2}\.[0-9]{2}, [0-9]{2}\.[0-9]{2})\.([0-9]+)(_[0-9]+)?(\.[^.]*)$/$2/}' \
	$directory

# Delete RAW if .jpg exists
exiftool \
	-directory=trash \
	-srcfile %d%f.cr2 \
	-ext jpg \
	$directory



## Regular expressions

# Remove double space
exiftool '-FileName<${FileName; s/ / /}' $directory

# Remove space between FileName and extension
exiftool '-FileName<${FileName; s/ (\.[^.]*)$/$1/}' $directory

# Remove (
exiftool '-FileName<${FileName; s/\(/$1/}' $directory

# Remove )
exiftool '-FileName<${FileName; s/\)/$1/}' $directory

# Replace _ by space
exiftool '-FileName<${FileName; s/_/ /}' $directory

# Remove SubSec and increment
exiftool '-FileName<${FileName; s/\.[0-9]{1,5}_[0-9]{1,5}(\.[^.]*)$/$1/}' $directory



# Move photos without DateTimeOriginal
exiftool \
	'-directory=$directory/New Folder' \
	-if '(not $DateTimeOriginal)' \
	-r \
	$directory

# Move photos to Make Model folder
exiftool '-directory<$directory/${Make} ${Model}' $directory

# Metadata to .csv
exiftool '-Directory' '-FileName' '-Make' '-Model' '-GPSPosition' '-Title' -csv $directory > '$directory/New Folder/File.csv'

# List all Google Photos files
# Metadata information added by Google Photos: ImageUniqueID, GPSVersionID, XMPToolkit, InstanceID
# https://photo.stackexchange.com/questions/101037/how-to-distinguish-images-compressed-by-google-photos-vs-the-original-using-meta
exiftool -if '($XMPToolkit eq "XMP Core 5.5.0") and ($ImageUniqueID)' -FileName -FilePath -ext jpg $directory

# Move photos-vs-the-original-using-meta
exiftool -if '($XMPToolkit eq "XMP Core 5.5.0") and ($ImageUniqueID)' '-directory<$directory/New Folder' $directory

exiftool '-XMPToolkit' -csv $directory > '$directory/File.csv'




### FFmpeg
# Useful to get CreateDate for .avi and .mpg files

# Install ffmpeg
# brew install ffmpeg


# Metadata info
ffmpeg -i "$directory/Movie.avi" -dump




## ImageMagick

# Install imagemagick
# brew install imagemagick


# Identify ICC profile
# magick identify -verbose -format %[profile:icc] "$directory/001.HEIC"

# Convert RAW (.cr2) to .jpg
magick mogrify -monitor -format jpg -quality 85 -density 72 "$directory/*.CR2"

# Convert .heic to .jpg
magick mogrify -monitor -format jpg "$directory/*.HEIC"

# Reduce file size
magick mogrify -monitor -resize 50% "$directory/*.HEIC"

# Replace all colors except background in .png image with white
magick convert "$directory/input.png" -fill white -colorize 100 "$directory/output.png"
