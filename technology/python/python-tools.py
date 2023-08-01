## Python Tools
# Last update: 2023-08-01


"""Script containing useful tools."""


###############
# Initial Setup
###############

# Erase all declared global variables
globals().clear()


# Import packages
import os


# Set working directory
os.chdir(path=os.path.join(os.path.expanduser('~'), 'Downloads'))


#######
# Tools
#######

## Rename files

# Import packages
import glob
import re

# Get all files from a given directory
files = glob.glob(pathname=os.path.join('**', '*'), recursive=True)

# Filter for files with specific regular expression pattern
files = [
    file for file in files if re.search(r'20[0-9]{2}\.[0-9]{2}\.[0-9]{2}.*\.pdf', file)
]
print('\n'.join(files))

# Rename files
for filename in files:
    new_name = re.sub(r'([0-9]{4})\.([0-9]{2})\.([0-9]{2})', r'\1-\2-\3', filename)
    os.rename(filename, new_name)
