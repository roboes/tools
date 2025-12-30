# Python

## PyInstaller

```.sh
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

## Useful links

[15 Python Tips To Take Your Code To The Next Level!](https://gist.github.com/Julynx/dd500d8ae7e335c3c84684ede2293e1f)
