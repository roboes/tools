## Git Pull Request Contribute
# Last update: 2026-10-29


# Start Bash (Unix Shell)
[ -z "${BASH}" ] && exec bash


# Settings
git_hostname="github.com"
git_account="roboes"
git_repository="tools"
git_branch="main"
# git_branch="18.0"
git_pr_number=1                         # Contributor's PR number
git_pr_branch="pr-${git_pr_number}"     # Local branch name
local_repository="${git_repository}"


# GitHub account to commit with
gh auth switch --user "${git_account}"
gh auth setup-git


# Set working directory
if grep -qi microsoft /proc/version; then
    cd "/mnt/c/Users/${USER}/Documents/Documents/Projects"
else
    cd "${HOME}/Documents/Documents/Projects"
fi


# Clone repository if directory does not exist
if [ ! -d "${local_repository}" ]; then
    git clone --branch "${git_branch}" "https://${git_hostname}/${git_account}/${git_repository}.git" "${local_repository}"
fi

# Set working directory
cd "${local_repository}"


# Configure commit identity for this repository only (before making commits)
read -r git_user_id git_user_login <<< "$(gh api user --jq '"\(.id) \(.login)"')"
git config user.name "${git_user_login}"
git config user.email "${git_user_id}+${git_user_login}@users.noreply.github.com"


# Check that maintainers are allowed to push to the PR
gh pr view "${git_pr_number}" \
    --json maintainerCanModify,headRepositoryOwner,headRefName \
    --jq '"maintainerCanModify=\(.maintainerCanModify) | \(.headRepositoryOwner.login):\(.headRefName)"'


# Check out the PR (sets up fork tracking and push target automatically)
git fetch origin
gh pr checkout "${git_pr_number}" --branch "${git_pr_branch}" --force
git pull --ff-only


# Verify
git status
git branch -vv


# Make code changes
read -r -p "Make your code changes, then press Enter to review and commit... "


# Review changes
git status
git diff

# Stage (interactive; use `git add <files>` for specific paths)
git add -p

# Review exactly what will be committed
git diff --cached

# Commit
git commit --message="Update"


# Pull any new contributor commits, then push to the contributor's branch
git pull --ff-only
read -r -p "Push to the contributor's PR branch? [Y/N] " confirm
if [ "${confirm}" = "Y" ]; then
    git push
fi
