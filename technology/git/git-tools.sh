## Git Tools
# Last update: 2025-01-29


# Start Windows Subsystem for Linux (WSL) (required only on Windows)
wsl


# Ignore certificate validation
# echo insecure >> ~/.curlrc
# HOMEBREW_CURLRC=1
# export HOMEBREW_CURLRC


# Homebrew update
# ulimit -n 8192
# brew update && brew upgrade && brew cleanup


# Install GitHub CLI
# brew install git
# brew install gh

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


# Settings
git_hostname="github.com"
git_account=$(git config user.name) # Username or Organization
git_repository="tools"
git_branch="main"
local_repository=$git_repository


# Set working directory
cd "/mnt/c/Users/${USER}/Documents/Documents/Projects"

# Clone repository if directory does not exist
if [ ! -d "${local_repository}" ]; then
	git clone "https://${git_hostname}/${git_account}/${git_repository}" ${local_repository}
fi

# Set working directory
cd $local_repository


## Pre-commit
# git init
# git add --all
# python -m pip install pre-commit
# brew install pre-commit
# pre-commit install

# Download .pre-commit-config.yaml file
curl -o "./.pre-commit-config.yaml" --remote-name --location "https://raw.githubusercontent.com/roboes/tools/main/technology/git/pre-commit/.pre-commit-config.yaml"

# Download pre-commit-workflow.yaml
mkdir -p "./.github/workflows"
curl -o "./.github/workflows/pre-commit-workflow.yaml" --remote-name --location "https://raw.githubusercontent.com/roboes/tools/main/technology/git/pre-commit/pre-commit-workflow.yaml"

# pre-commit autoupdate

markdownlint-cli2 "**/*.md" --fix --disable MD013 MD024 MD033 MD045

pre-commit run --all-files


## Test for FutureWarning
# python -m pip install pytest
# pytest --override-ini "python_files=*.py python_classes=* python_functions=*" -W error::FutureWarning


## Python requirements.txt file
# python -m pip install pipreqs
if find . -type f -name "*.py" | grep -q "/."; then
	pipreqs --encoding utf-8 --force "./"

    # Check if "janitor" is in requirements.txt and replace it with pyjanitor==0.30.0
    if grep -q "janitor" "requirements.txt"; then
        sed -i '/janitor/c\pyjanitor==0.30.0' requirements.txt
		pre-commit run --files "./requirements.txt"
    fi

fi

## Update requirements.txt
# pip-compile --no-header --output-file=requirements-updated.txt requirements.txt
# sed -i '/^ *#/d' requirements-updated.txt


# Set working directory
# cd ..


# Delete files
rm "./.php-cs-fixer.cache"
find . -path './venv' -prune -o -name "__pycache__" -type d -exec rm -r {} +


## Git push

# Start git repository
git init

# Add all files from the working directory to the staging area
git add --all

# Create a snapshot of all staged committed changes
git commit --all --message="Update"

# Change git remote repository URL
# git remote add origin "https://${git_hostname}/${git_account}/${git_repository}.git"
git remote set-url origin "https://${git_hostname}/${git_account}/${git_repository}.git"

# Write commits to remote repository
git push --force origin ${git_branch}



## Squash commit history - https://stackoverflow.com/a/56878987/9195104

# Count of commits
git rev-list --count HEAD ${git_branch}


# Create a temporary file to store commits to keep
export TOKEEP=$(mktemp)


# Extracts the timestamps of the commits to keep (the last of the day)
DATE=
for time in $(git log --date=raw --pretty=format:%cd|cut -d\  -f1); do
   CDATE=$(date -d @$time +%Y%m%d) # Per day
   # CDATE=$(date -d @$time +%Y%m) # Per month
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


# Remove the temporary file
rm --force $TOKEEP


# Repository force update
git push --force origin ${git_branch}


# New count of commits
git rev-list --count HEAD ${git_branch}
