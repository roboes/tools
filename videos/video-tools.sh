## Video Tools
# Last update: 2025-03-14


# Start Windows Subsystem for Linux (WSL) (required only on Windows)
wsl


# Homebrew update
brew update && brew upgrade && brew cleanup

# Install ffmpeg
# brew install ffmpeg


# Trim video without re-encoding
ffmpeg -ss 00:08:49.200 -i "./input.mp4" -c copy "./output.mp4"
