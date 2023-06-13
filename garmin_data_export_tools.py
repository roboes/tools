## Garmin Data Export Tools
# Last update: 2023-06-13


###############
# Initial Setup
###############

# Erase all declared global variables
globals().clear()


# Import packages
import glob
from io import BytesIO
import os
from pathlib import Path
import shutil
from zipfile import ZipFile

import pandas as pd
import requests

# fit2gpx
with ZipFile(file=BytesIO(initial_bytes=requests.get(url='https://github.com/dodo-saba/fit2gpx/archive/refs/heads/main.zip').content), mode='r') as zip_file:
    zip_file.extractall(path=os.path.join(os.path.expanduser('~'), 'Downloads', 'fit2gpx'))

os.chdir(path=os.path.join(os.path.expanduser('~'), 'Downloads', 'fit2gpx', 'fit2gpx-main', 'src'))

from fit2gpx import Converter


# Set working directory
os.chdir(path=os.path.join(os.path.expanduser('~'), 'Downloads', 'Garmin Export'))




###########
# Functions
###########

# Extract .zip files
def zip_extract(*, directory=os.path.join('DI_CONNECT', 'DI-Connect-Uploaded-Files')):

    # List of files including path
    files = glob.glob(pathname=os.path.join(directory, '*.zip'), recursive=False)


    for file in files:

        # Get file name without extension
        file_name = Path(file).stem

        # Extract file
        with ZipFile(file=file) as zip_file:
            zip_file.extractall(path=os.path.join(directory, file_name))

        # Delete file
        os.remove(path=file)



# Change filetype from .txt to .tcx
def change_filetype(*, directory=os.path.join('DI_CONNECT', 'DI-Connect-Uploaded-Files')):

    # List of files including path
    files = glob.glob(pathname=os.path.join(directory, '**', '*.txt'), recursive=True)


    for file in files:

        file_name = str(Path(file).with_suffix(suffix=''))
        file_type = Path(file).suffix

        if file_type == '.txt':
            os.rename(src=file, dst=(file_name + '.tcx'))



# Empty .fit activities files: move to 'ACTIVITIES_EMPTY' folder or delete
def activities_empty(*, directory=os.path.join('DI_CONNECT', 'DI-Connect-Uploaded-Files'), action='delete'):

    files = glob.glob(pathname=os.path.join(directory, '**', '*.fit'), recursive=True)

    conv = Converter()

    data = []


    for file in files:

        d = {}

        df_lap, df_point = conv.fit_to_dataframes(fname=file)

        if df_lap.empty and df_point.empty:
            d['filename'] = file

            data.append(d)

        else:
            pass


    # Create DataFrame
    activities_empty = (pd.DataFrame(data=data, index=None, dtype=None)
        .sort_values(by=['filename'], ignore_index=True)
    )


    # Move empty activities files to 'ACTIVITIES_EMPTY' folder
    if action == 'move':

        # Create 'ACTIVITIES_EMPTY' folder
        os.makedirs(name=os.path.join('ACTIVITIES_EMPTY'), exist_ok=True)

        for filename in activities_empty['filename'].to_list():
            shutil.move(src=os.path.join(filename), dst=os.path.join('ACTIVITIES_EMPTY'))


    # Delete file
    if action == 'delete':

        for filename in activities_empty['filename'].to_list():
            os.remove(path=os.path.join(filename))



# Distribute files into multiple subfolders of up to 15 activities
def distribute_files(*, directory=os.path.join('DI_CONNECT', 'DI-Connect-Uploaded-Files'), increment=15):

    files = glob.glob(pathname=os.path.join(directory, '**', '*.fit'), recursive=True)
    files.extend(glob.glob(pathname=os.path.join(directory, '**', '*.gpx'), recursive=True))
    files.extend(glob.glob(pathname=os.path.join(directory, '**', '*.tcx'), recursive=True))

    for i in range(0, len(files), increment):

        sub_folder = 'files_{}_{}'.format(i + 1, i + increment)

        for file in files[i:i + increment]:

            directory_new = os.path.join(Path(file).parent, sub_folder)

            if not os.path.exists(directory_new):
                os.makedirs(name=directory_new, exist_ok=True)

            file_path = os.path.join(file)
            shutil.move(src=file_path, dst=directory_new)



# Extract .zip files
zip_extract()

# Change filetype from .txt to .tcx
change_filetype()

# Empty .fit activities files: move to 'ACTIVITIES_EMPTY' folder or delete
activities_empty(action='delete')

# Distribute files into multiple subfolders of up to 15 activities
distribute_files(increment=15)
