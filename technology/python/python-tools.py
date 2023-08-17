## Python Tools
# Last update: 2023-08-17


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


def rename_paths(*, pattern, repl, path_rename=False):
    # Get all directories and files from current directory
    paths = glob.glob(pathname=os.path.join('**', '*'), recursive=True)

    # Filter for directories with specific regular expression pattern
    directories_rename = [
        path
        for path in paths
        if os.path.isdir(path) and re.search(pattern, Path(path).name)
    ]

    if len(directories_rename) > 0:
        print('Directories to be renamed:')
        print('\n'.join(directories_rename))
        print('')

    else:
        print('No directories to be renamed.')
        print('')

    # Filter for files with specific regular expression pattern
    files_rename = [
        path
        for path in paths
        if os.path.isdir(path) is False and re.search(pattern, Path(path).stem)
    ]

    if len(files_rename) > 0:
        print('Files to be renamed:')
        print('\n'.join(files_rename))
        print('')

    else:
        print('No files to be renamed.')
        print('')

    # Rename directories
    if len(directories_rename) > 0:

        if path_rename is False:
            print('New directory names (preview):')

        if path_rename is True:
            print('New directory names:')

        for path in directories_rename:
            path = Path(path)

            path_name = path.name
            path_name = re.sub(pattern, repl, path_name)
            path_name_new = Path(path.parent, f'{path_name}')

            print(path_name_new)

            if path_rename is True:
                path.rename(path_name_new)

        print('')

    # Rename files
    if len(files_rename) > 0:

        if path_rename is False:
            print('New file names (preview):')

        if path_rename is True:
            print('New file names:')

        # Get all directories and files from current directory (updated in case directory names changed)
        paths = glob.glob(pathname=os.path.join('**', '*'), recursive=True)

        # Filter for files with specific regular expression pattern (updated in case directory names changed)
        files_rename = [
            path
            for path in paths
            if os.path.isdir(path) is False and re.search(pattern, Path(path).stem)
        ]

        for path in files_rename:
            path = Path(path)

            path_name = path.stem
            path_name = re.sub(pattern, repl, path_name)
            path_name_new = Path(path.parent, f'{path_name}{path.suffix}')

            print(path_name_new)

            if path_rename is True:
                path.rename(path_name_new)

        print('')


# Remove leading, trailing and double spaces from directories and files
rename_paths(pattern=r'^\s+|\s+$', repl=r'', path_rename=False)
rename_paths(pattern=r'  ', repl=r' ', path_rename=False)


# Rename directories and files from YYYY.MM.DD to YYYY-MM-DD
rename_paths(
    pattern=r'([0-9]{4})\.([0-9]{2})\.([0-9]{2})',
    repl=r'\1-\2-\3',
    path_rename=False,
)


# Rename directories and files from YYYY.MM to YYYY-MM
rename_paths(pattern=r'([0-9]{4})\.([0-9]{2})', repl=r'\1-\2', path_rename=False)


# Rename directories and files from DD.MM.YYYY to YYYY-MM-DD
rename_paths(
    pattern=r'([0-9]{2})\.([0-9]{2})\.([0-9]{4})',
    repl=r'\3-\2-\1',
    path_rename=False,
)
