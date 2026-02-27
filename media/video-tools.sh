## Video Tools
# Last update: 2026-02-25


# Start Bash (Unix Shell)
[ -z "$BASH" ] && exec bash


# Install FFmpeg
# sudo apt install -y ffmpeg


# Settings
if grep -qi microsoft /proc/version; then
    cd "/mnt/c/Users/${USER}/Videos"
else
    cd "${HOME}/Videos"
fi


# Trim video without re-encoding
ffmpeg -ss 00:08:49.200 -i "./input.mp4" -c copy "./output.mp4"

# Show summary of video/audio codec, resolution, bitrate and duration
ffprobe -v error -show_entries format=duration:stream=codec_type,codec_name,width,height,bit_rate,sample_rate,channels -of default=noprint_wrappers=1 "./input.avi"

# Convert .avi to .mp4 preserving quality and deinterlacing (lossy compression)
ffmpeg -i "./input.avi" -vf yadif -c:v libx264 -crf 18 -preset slow -c:a aac -b:a 192k "./output.mp4"

# Batch convert .avi to .mp4 preserving quality and deinterlacing (lossy compression) (recursive)
find . -type f -name "*.avi" | while IFS= read -r file; do
  ffmpeg -nostdin -i "$file" -vf yadif -c:v libx264 -crf 18 -preset slow -c:a aac -b:a 192k "${file%.*}.mp4"
done

# Batch convert .avi to .mp4 preserving quality and deinterlacing (lossy compression)
find . -maxdepth 1 -type f -name "*.avi" | while IFS= read -r file; do
  ffmpeg -nostdin -i "$file" -vf yadif -c:v libx264 -crf 18 -preset slow -c:a aac -b:a 192k "${file%.*}.mp4"
done

# Reduce video size (web)
ffmpeg -i "./input.mp4" -vcodec libx264 -crf 33 -preset slow -vf "scale=1920:-2" -an -movflags +faststart "./output_compressed.mp4"
