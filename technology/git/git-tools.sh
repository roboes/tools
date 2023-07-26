## Git Tools
# Last update: 2023-07-20


## GitHub login
# gh auth setup-git
# gh auth login

# Display git custom settings
# git config --list

# Remove git custom settings
# rm ~/.gitconfig


## Git settings
# git config --global user.email "you@example.com"
# git config --global user.name "Your Name"


# Git ignore
# https://github.com/github/gitignore/blob/main/Python.gitignore


# Start Windows Subsystem for Linux (WSL) (required only on Windows)
wsl


# Settings
github_username="roboes"
repository="tools"


# Set working directory
cd "/mnt/c/Users/${USER}/Documents/Documents/Projects"

# Clone repository if directory does not exist
if [ ! -d "${repository}" ]; then
	git clone "https://github.com/${github_username}/${repository}"
fi

# Set working directory
cd $repository


## Pre-commit
# git init
# git add --all
# pre-commit install
pre-commit run --all-files


## Git push

# Virtual Python environment - Create
# python -m virtualenv "env"
# .\env\Scripts\activate

# Virtual Python environment - Install Python dependencies
# .\env\Scripts\python -m pip install --upgrade pip
# .\env\Scripts\python -m pip install pandas

# Virtual Python environment - Update Python packages
# .\env\Scripts\python -m pip_review --local --auto
# .\env\Scripts\python -m pip_review --local --interactive

# Virtual Python environment - create requirements.txt file
# .\env\Scripts\python -m pip freeze --local > requirements.txt

# Start git repository
git init

# Add all files from the working directory to the staging area
git add --all

# Create a snapshot of all staged committed changes
git commit --all --message="Update"

# Change git remote repository URL
# git remote add origin "https://github.com/${github_username}/${repository}.git"
git remote set-url origin "https://github.com/${github_username}/${repository}.git"

# Write commits to remote repository
git push --force origin main



## Squash commit history per day - https://stackoverflow.com/a/56878987/9195104

# Count of commits
git rev-list --count HEAD main


# Extracts the timestamps of the commits to keep (the last of the day)
export TOKEEP=`mktemp`
DATE=
for time in `git log --date=raw --pretty=format:%cd|cut -d\  -f1` ; do
   CDATE=`date -d @$time +%Y%m%d`
   if [ "$DATE" != "$CDATE" ] ; then
       echo @$time >> $TOKEEP
       DATE=$CDATE
   fi
done


# Scan the repository keeping only selected commits
git filter-branch --force --commit-filter '
    if grep -q ${GIT_COMMITTER_DATE% *} $TOKEEP ; then
        git commit-tree "$@"
    else
        skip_commit "$@"
    fi' HEAD
rm --force $TOKEEP


# Repository force update
git push --force origin main


# New count of commits
git rev-list --count HEAD main
