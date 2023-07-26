## Python Packages
# Last update: 2023-07-10


# Test Python version
python -m pip --version


# Update pip
python -m pip install --upgrade pip


# Install pip-review
# python -m pip install pip-review


# Installed packages
# python -m pip list -v


# Check for packages versions
# python -m pip_review
# python -m pip list --outdated

# Update all packages
python -m pip_review --local --auto
# python -m pip_review --local --interactive


# Install packages
# python -m pip install openpyxl html5lib lxml numpy pandas pyjanitor selenium matplotlib seaborn sweetviz pytz

# Install packages (specific version)
# python -m pip install -Iv mvt==1.2.5


# Uninstall packages
# python -m pip uninstall janitor


# Requirements
# python -m pip freeze > requirements.txt
# python -m pip freeze --local > requirements.txt


# Install pytest
# python -m pip install pytest

# pytest
# python -m pytest test.py

# pytest - FutureWarning
# python -m pytest -W error::FutureWarning test.py
