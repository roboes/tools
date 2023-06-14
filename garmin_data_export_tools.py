## Garmin Data Export Tools
# Last update: 2023-06-14


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

from dateutil import parser
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


    if len(files) > 0:

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


    if len(files) > 0:

        for file in files:

            file_name = str(Path(file).with_suffix(suffix=''))
            file_type = Path(file).suffix

            if file_type == '.txt':
                os.rename(src=file, dst=(file_name + '.tcx'))



# Empty .fit activities files: move to 'ACTIVITIES_EMPTY' folder or delete
def activities_empty(*, directory=os.path.join('DI_CONNECT', 'DI-Connect-Uploaded-Files'), action='delete'):

    files = glob.glob(pathname=os.path.join(directory, '**', '*.fit'), recursive=True)


    if len(files) > 0:

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
        activities_empty = pd.DataFrame(data=data, index=None, dtype=None)

        if not activities_empty.empty:

            activities_empty = (activities_empty
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



# Combine multiple .tcx activity files into one .tcx file (for bulk upload to Strava - Strava will automatically separate/split these activities after upload)
def tcx_combine(*, directory=os.path.join('DI_CONNECT', 'DI-Connect-Uploaded-Files'), file_name='all_activities_tcx.tcx'):

    # List of .tcx files including path
    files = glob.glob(pathname=os.path.join(directory, '**', '*.tcx'), recursive=True)


    # Create .tcx file content
    text = []
    # text.append(b'<?xml version="1.0" encoding="UTF-8"?>\n')
    # text.append(b'<TrainingCenterDatabase xmlns="http://www.garmin.com/xmlschemas/TrainingCenterDatabase/v2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.garmin.com/xmlschemas/TrainingCenterDatabase/v2 http://www.garmin.com/xmlschemas/TrainingCenterDatabasev2.xsd">\n')
    # text.append(b'\n')


    # Combine files
    for file in files:

        with open(path=file, mode='rb', encoding=None) as file_in:

            file_text = file_in.readlines()

            # index_activity_start = [index for index, item in enumerate(file_text) if item.endswith(b'<Activities>\n')][0]
            # index_activity_end = [index for index, item in enumerate(file_text) if item.endswith(b'</Activities>\n')][0]

            text.extend(file_text)
            text.append(b'\n')
            text.append(b'\n')


    # text.append(b'\n')
    # text.append(b'</TrainingCenterDatabase>')

    with open(path=os.path.join(directory, file_name), mode='wb', encoding=None) as file_out:
        file_out.writelines(text)



# Extract .zip files
zip_extract()

# Change filetype from .txt to .tcx
change_filetype()

# Empty .fit activities files: move to 'ACTIVITIES_EMPTY' folder or delete
activities_empty(action='delete')

# Distribute files into multiple subfolders of up to 15 activities
# distribute_files(increment=15)

# Combine multiple .tcx activity files into one .tcx file (for bulk upload to Strava - Strava will automatically separate/split these activities after upload)
tcx_combine(file_name='all_activities_tcx.tcx')

# Check which activities from Garmin Connect (https://connect.garmin.com/modern/activities) are already on Strava (https://www.statshunters.com/activities)
activities_garmin = (pd.read_csv(filepath_or_buffer='activities_garmin.csv', sep=',', header=0, index_col=None, skiprows=0, skipfooter=0, dtype=None, engine='python', encoding='utf8')
    .rename(columns={'Date': 'activity_date', 'Activity Type': 'activity_type', 'Title': 'activity_name_garmin', 'Distance': 'distance_garmin'})
    .assign(activity_date = lambda row: row['activity_date'].apply(parser.parse))
    .sort_values(by=['activity_date'], ignore_index=True)
    .assign(activity_date_cleaned = lambda row: row['activity_date'].dt.strftime('%Y-%m-%d 00:%M:00'))
    .filter(items=['activity_date', 'activity_date_cleaned', 'activity_type', 'activity_name_garmin', 'distance_garmin'])
    .assign(activity_type = lambda row: row['activity_type'].replace(to_replace=r'^Running$|^Treadmill Running$', value='Run', regex=True))
)

activities_strava = (pd.read_excel(io='activities_strava.xlsx', sheet_name='Activities', header=0, index_col=None, skiprows=0, skipfooter=0, dtype=None, engine='openpyxl')
    .rename(columns={'Date': 'activity_date', 'Type': 'activity_type', 'Name': 'activity_name_strava', 'Distance (m)': 'distance_strava'})
    .assign(activity_date = lambda row: row['activity_date'].apply(parser.parse))
    .sort_values(by=['activity_date'], ignore_index=True)
    .assign(activity_date_cleaned = lambda row: row['activity_date'].dt.strftime('%Y-%m-%d 00:%M:00'))
    .filter(items=['activity_date_cleaned', 'activity_type', 'activity_name_strava', 'distance_strava'])
    .assign(distance_strava = lambda row: row['distance_strava']/1000)
)

activities_garmin = (activities_garmin
    .merge(activities_strava, how='left', on=['activity_date_cleaned', 'activity_type'], indicator=True)
    .assign(distance_difference = lambda row: row['distance_strava'] - row['distance_garmin'])
    .filter(items=['activity_date', 'activity_type', 'activity_name_garmin', 'activity_name_strava', 'distance_garmin', 'distance_strava', 'distance_difference', '_merge'])
)
