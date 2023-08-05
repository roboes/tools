## Python Tools
# Last update: 2023-08-05


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
from pathlib import Path
import re



def rename_paths(*, type='file', pattern, repl, path_rename=False):

    # Get all directories and files from current directory
    paths = glob.glob(pathname=os.path.join('**', '*'), recursive=True)

    # Filter for directories with specific regular expression pattern
    if type == 'directory':
        paths_rename = [path for path in paths if os.path.isdir(path) and re.search(pattern, Path(path).name)]
        
        if len(paths_rename) > 0:
            print('Directories to be renamed:')
            print('\n'.join(paths_rename))
        
        else:
            print('No directories to be renamed.')

    # Filter for files with specific regular expression pattern
    if type == 'file':
        paths_rename = [path for path in paths if os.path.isdir(path) == False and re.search(pattern, Path(path).stem)]
        
        if len(paths_rename) > 0:
            print('Files to be renamed:')
            print('\n'.join(paths_rename))
        
        else:
            print('No files to be renamed.')

    # Rename paths
    if path_rename == True and len(paths_rename) > 0:

        print('')
        print('New names:')

        for path in paths_rename:

            if type == 'directory':
                path_name = Path(path).name
                path_name = re.sub(pattern, repl, path_name)
                path_name_new = Path(Path(path).parent, f'{path_name}')

            if type == 'file':
                path_name = Path(path).stem
                path_name = re.sub(pattern, repl, path_name)
                path_name_new = Path(Path(path).parent, f'{path_name}{Path(path).suffix}')

            Path(path).rename(path_name_new)

            print(path_name_new)


# Remove leading, trailing and double spaces from directories
rename_paths(type='directory', pattern=r'^ | $', repl=r'', path_rename=False)
rename_paths(type='directory', pattern=r'  ', repl=r' ', path_rename=False)


# Remove leading, trailing and double spaces from files
rename_paths(type='file', pattern=r'^ | $', repl=r'', path_rename=False)
rename_paths(type='file', pattern=r'  ', repl=r' ', path_rename=False)


# Rename files from YYYY.MM.DD to YYYY-MM-DD
rename_paths(type='file', pattern=r'(20[0-9]{2})\.([0-9]{2})\.([0-9]{2})', repl=r'\1-\2-\3', path_rename=False)
