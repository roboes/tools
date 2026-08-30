# Python

## Positron

```sh
# Change current directory
cd ~/.local/bin

# Create a virtual environment
# uv venv

# Activate the virtual environment in the terminal (macOS/Linux)
source .venv/bin/activate

# Install packages
uv pip install pandas
```

Select Session > New Console Session...

## pip cache

```sh
# Check cache size
python -m pip cache info

# Clear entire pip cache (safe - won't affect installed packages)
python -m pip cache purge
```

## PyInstaller

```sh
# Create a virtual environment
python -m venv "./venv"

# Activate the virtual environment
source "./venv/bin/activate"
# ./venv/Scripts/Activate.ps1

# Install required packages
python -m pip install pandas requests xlsxwriter pyinstaller

# Run PyInstaller
pyinstaller --onefile "file.py" --hidden-import="xlsxwriter"
```

## Test for FutureWarning

```sh
python -m pip install pytest
pytest --override-ini "python_files=*.py python_classes=* python_functions=*" -W error::FutureWarning
```

## requirements.txt

```sh
# Auto-generate requirements.txt from imports found in .py files
python -m pip install pipreqs
if find . -type f -name "*.py" | grep -q .; then
    pipreqs --encoding utf-8 --force "./"
    # Check if "janitor" is in requirements.txt and replace it with pyjanitor
    if grep -q "janitor" "requirements.txt"; then
        sed -i '/janitor/c\pyjanitor==0.32.23' requirements.txt
        pre-commit run --files "./requirements.txt"
    fi
fi
```

```sh
# # Resolves and pins all transitive dependencies from requirements.txt into a new file
pip-compile --no-header --output-file=requirements-updated.txt requirements.txt
sed -i '/^ *#/d' requirements-updated.txt
```

## Useful links

[15 Python Tips To Take Your Code To The Next Level!](https://gist.github.com/Julynx/dd500d8ae7e335c3c84684ede2293e1f)
