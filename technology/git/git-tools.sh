## Git Tools
# Last update: 2026-01-02


# Start Bash (Unix Shell)
[ -z "$BASH" ] && exec bash


# Ignore certificate validation
# echo insecure >> ~/.curlrc
# HOMEBREW_CURLRC=1
# export HOMEBREW_CURLRC


# Settings
git_hostname="github.com"
git_account="$(git config user.name)" # Username or Organization
git_repository="tools"
git_branch="main"
# git_branch="18.0"
local_repository="$git_repository"


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
cd "$local_repository"


# Download .pre-commit-config.yaml file
curl -o "./.pre-commit-config.yaml" --remote-name --location "https://raw.githubusercontent.com/roboes/tools/main/technology/git/pre-commit/.pre-commit-config.yaml"

# Download pre-commit-workflow.yaml
mkdir -p "./.github/workflows"
curl -o "./.github/workflows/pre-commit-workflow.yaml" --remote-name --location "https://raw.githubusercontent.com/roboes/tools/main/technology/git/pre-commit/pre-commit-workflow.yaml"

pre-commit autoupdate

if [ "$git_repository" == "tools" ]; then
    cp "./.pre-commit-config.yaml" "./technology/git/pre-commit/.pre-commit-config.yaml"
fi


## Prettier
prettier --write --print-width 220 --prose-wrap never --semi true --single-quote true --tab-width 2 --trailing-comma es5 '**/*.{js,jsx,ts,tsx,mjs,cjs,json,css,scss,html,md,yaml}'

## PHP
find . -type f -name "*.php" | grep -q . && php-cs-fixer fix . --rules=@PSR12 --using-cache=no --no-interaction

## XML
find . -type f -name "*.xml" -exec xmllint --format --output {} {} \;

## codespell
codespell . --ignore-words-list=alle,als,bootup,datas,deine,dokument,doubleclick,fo,ges,ist,itens,nax,oder,produkt,referers,ressource,ser,sie,tage,termine # The --ignore-words-list argument has a bug where it needs to be lowercase, see: https://github.com/codespell-project/codespell/issues/1390

## pre-commit
pre-commit run --all-files


## Test for FutureWarning
# python -m pip install pytest
# pytest --override-ini "python_files=*.py python_classes=* python_functions=*" -W error::FutureWarning


## Python requirements.txt file
# python -m pip install pipreqs
if [ $(basename "$PWD") != "odoo-woocommerce-sync" ]; then
    if find . -type f -name "*.py" | grep -q .; then
        pipreqs --encoding utf-8 --force "./"

        # Check if "janitor" is in requirements.txt and replace it with pyjanitor
        if grep -q "janitor" "requirements.txt"; then
            sed -i '/janitor/c\pyjanitor==0.32.9' requirements.txt
            pre-commit run --files "./requirements.txt"
        fi

    fi
else
    echo "Skipping requirements update"
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

# Create the target branch locally if it doesn't exist
git checkout -b "${git_branch}"

# Switch to the target branch
git switch "${git_branch}"

# Add all files from the working directory to the staging area
git add --all

# Create a snapshot of all staged committed changes
git commit --all --message="Update"

# Change git remote repository URL
# git remote add origin "https://${git_hostname}/${git_account}/${git_repository}.git"
git remote set-url origin "https://${git_hostname}/${git_account}/${git_repository}.git"

# Write commits to remote repository
git push --force origin "${git_branch}"




## Squash commit history - https://stackoverflow.com/a/56878987/9195104

# Count of commits
git rev-list --count HEAD "${git_branch}"


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
git push --force origin "${git_branch}"


# New count of commits
git rev-list --count HEAD "${git_branch}"
