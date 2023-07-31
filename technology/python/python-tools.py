## Python Tools
# Last update: 2023-07-31


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
import glob
import re


for filename in glob.glob('*.jpg'):
	new_name = re.sub(r'\. \(([0-9]+)\)\.jpg', r'.\1.jpg', filename)
	os.rename(filename, new_name)
