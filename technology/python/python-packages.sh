## Python Packages
# Last update: 2023-09-25


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
# python -m pip install -r requirements.txt

# Install packages (specific version)
# python -m pip install -Iv mvt==1.2.5


# Uninstall packages
# python -m pip uninstall janitor


## Python requirements.txt file

# Create requirements.txt file based on imports using pipreqs
# python -m pip install pipreqs
# pipreqs --encoding utf-8 --force "./"

# Create requirements.txt file using pip freeze
# python -m pip freeze --local > requirements.txt


# Get the names and default values of a Python function's parameters
# import inspect
# inspect.getfullargspec(os.path.join)


# Install pytest
# python -m pip install pytest

# pytest
# python -m pytest test.py

# pytest - FutureWarning
# python -m pytest -W error::FutureWarning test.py
