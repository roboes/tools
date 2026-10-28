## Git Settings
# Last update: 2026-10-28


# GitHub account to commit with
git_account="roboes"

# Log in once (first time only), then select the account
# gh auth login
gh auth switch --user "${git_account}"

# Show current settings
# git config --global --list

# Reset all global settings
# rm ~/.gitconfig


# Identity: GitHub masked noreply email (https://github.com/settings/emails)
git_id="$(gh api "users/${git_account}" --jq '.id')"
git config --global user.name "${git_account}"
git config --global user.email "${git_id}+${git_account}@users.noreply.github.com"

# Default editor
git config --global core.editor "nano"

# Ignore executable bit changes
git config --global core.filemode false

# Line endings: convert CRLF to LF on commit (Linux/macOS/WSL)
git config --global core.autocrlf input

# Git ignore templates: https://github.com/github/gitignore