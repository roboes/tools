## Git Tools
# Last update: 2023-08-22


# Install GitHub command-line tool
# brew install git

# Install GitLab Runner
# brew install gitlab-runner


# GitHub login
# gh auth setup-git
# gh auth login

# Display git custom settings
# git config --list

# Remove git custom settings
# rm ~/.gitconfig


# Git settings
# git config --global user.email "email@example.com"
# git config --global user.name "username"
# git config --global --list


# Git ignore
# https://github.com/github/gitignore/blob/main/Python.gitignore


# Start Windows Subsystem for Linux (WSL) (required only on Windows)
wsl


# Settings
git_service="github"
git_username="git config user.name"
git_repository="tools"
git_branch="main"
local_repository=$git_repository


# Set working directory
cd "/mnt/c/Users/${USER}/Documents/Documents/Projects"

# Clone repository if directory does not exist
if [ ! -d "${local_repository}" ]; then
	git clone "https://${git_service}.com/${git_username}/${git_repository}" ${local_repository}
fi

# Set working directory
cd $local_repository


## Python Virtual Environment

# Create
# python -m virtualenv "env"
# .\env\Scripts\activate

# Install Python dependencies
# .\env\Scripts\python -m pip install --upgrade pip
# .\env\Scripts\python -m pip install pandas

# Update Python packages
# .\env\Scripts\python -m pip_review --local --auto
# .\env\Scripts\python -m pip_review --local --interactive


## Python requirements.txt file

# Generate requirements.txt file based on imports
# python -m pip install pipreqs
pipreqs . --force

# Python Virtual Environment - Create requirements.txt file
# .\env\Scripts\python -m pip freeze --local > requirements.txt


## Pre-commit
# git init
# git add --all
# pre-commit install
pre-commit run --all-files

# Set working directory
# cd ..


## Git push

# Start git repository
git init

# Add all files from the working directory to the staging area
git add --all

# Create a snapshot of all staged committed changes
git commit --all --message="Update"

# Change git remote repository URL
# git remote add origin "https://${git_service}.com/${git_username}/${git_repository}.git"
git remote set-url origin "https://${git_service}.com/${git_username}/${git_repository}.git"

# Write commits to remote repository
git push --force origin ${git_branch}



## Squash commit history per day - https://stackoverflow.com/a/56878987/9195104

# Count of commits
git rev-list --count HEAD ${git_branch}


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
git push --force origin ${git_branch}


# New count of commits
git rev-list --count HEAD ${git_branch}
