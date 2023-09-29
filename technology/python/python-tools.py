## Python Tools
# Last update: 2023-09-29


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
    # Get all files and folders from current directory
    paths = glob.glob(pathname=os.path.join('**', '*'), recursive=True)

    # Filter for folders with specific regular expression pattern
    folders_rename = [
        path
        for path in paths
        if os.path.isdir(path) and re.search(pattern, Path(path).name)
    ]

    if len(folders_rename) > 0:
        print('Folders to be renamed:')
        print('\n'.join(folders_rename))
        print('')

    else:
        print('No folders to be renamed.')
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

    # Rename folders
    if len(folders_rename) > 0:
        if path_rename is False:
            print('New folders name (preview):')

        if path_rename is True:
            print('New folders name:')

        for path in folders_rename:
            path = Path(path)

            path_name = path.name
            path_name = re.sub(pattern=pattern, repl=repl, string=path_name)
            path_name_new = Path(path.parent, f'{path_name}')

            print(path_name_new)

            if path_rename is True:
                path.rename(target=path_name_new)

        print('')

    # Rename files
    if len(files_rename) > 0:
        if path_rename is False:
            print('New files name (preview):')

        if path_rename is True:
            print('New files name:')

        # Get all files and folders from current directory (updated in case directories name changed)
        paths = glob.glob(pathname=os.path.join('**', '*'), recursive=True)

        # Filter for files with specific regular expression pattern (updated in case directories name changed)
        files_rename = [
            path
            for path in paths
            if os.path.isdir(path) is False and re.search(pattern, Path(path).stem)
        ]

        for path in files_rename:
            path = Path(path)

            path_name = path.stem
            path_name = re.sub(pattern=pattern, repl=repl, string=path_name)
            path_name_new = Path(path.parent, f'{path_name}{path.suffix}')

            print(path_name_new)

            if path_rename is True:
                path.rename(target=path_name_new)

        print('')


# Remove leading, trailing and double spaces from files and folders
rename_paths(pattern=r'^\s+|\s+$', repl=r'', path_rename=False)
rename_paths(pattern=r'  ', repl=r' ', path_rename=False)


# Rename files and folders from 'YYYY.MM.DD' to 'YYYY-MM-DD'
rename_paths(
    pattern=r'([0-9]{4})\.([0-9]{2})\.([0-9]{2})',
    repl=r'\1-\2-\3',
    path_rename=False,
)


# Rename files and folders from 'YYYY.MM' to 'YYYY-MM'
rename_paths(pattern=r'([0-9]{4})\.([0-9]{2})', repl=r'\1-\2', path_rename=False)


# Rename files and folders from 'DD.MM.YYYY' to 'YYYY-MM-DD'
rename_paths(
    pattern=r'([^\.])([0-9]{2})\.([0-9]{2})\.([0-9]{4})',
    repl=r'\1\4-\3-\2',
    path_rename=False,
)

# Rename files and folders from 'text YYYYMMDD' to 'YYYY-MM-DD'
rename_paths(
    pattern=r'^.* ([0-9]{4})([0-9]{2})([0-9]{2})',
    repl=r'\1-\2-\3',
    path_rename=True,
)
