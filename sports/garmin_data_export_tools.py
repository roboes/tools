## Garmin Data Export Tools
# Last update: 2023-06-15


###############
# Initial Setup
###############

# Erase all declared global variables
globals().clear()


# Import packages
import glob
from io import BytesIO
import json
import os
from pathlib import Path
import re
import shutil
from zipfile import ZipFile

from dateutil import parser
import numpy as np
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

        with open(file, mode='rb', encoding=None) as file_in:

            file_text = file_in.readlines()

            # index_activity_start = [index for index, item in enumerate(file_text) if item.endswith(b'<Activities>\n')][0]
            # index_activity_end = [index for index, item in enumerate(file_text) if item.endswith(b'</Activities>\n')][0]

            text.extend(file_text)
            text.append(b'\n')
            text.append(b'\n')


    # text.append(b'\n')
    # text.append(b'</TrainingCenterDatabase>')

    with open(os.path.join(directory, file_name), mode='wb', encoding=None) as file_out:
        file_out.writelines(text)



# Import Garmin Connect activities to DataFrame
def activities_garmin_import(*, directory=os.path.join('DI_CONNECT', 'DI-Connect-Fitness', 'summarizedActivities.json')):

    with open(directory) as file_in:
        file = json.load(fp=file_in)

    file = json.dumps(obj=file)
    file = re.sub(pattern=r'\[{"summarizedActivitiesExport": ', repl='', string=file)
    file = re.sub(pattern=r'}]$', repl='', string=file)


    activities_garmin = (pd.json_normalize(data=json.loads(s=file), max_level=None)

        # Rename columns
        .rename(columns={'activityId': 'activity_id', 'name': 'activity_name', 'activityType': 'activity_type', 'userProfileId': 'athlete_id', 'startTimeGmt': 'activity_date_gmt', 'startTimeLocal': 'activity_date', 'elevationGain': 'elevation_gain', 'avgSpeed': 'average_speed', 'maxSpeed': 'max_speed', 'avgHr': 'average_heart_rate', 'maxHr':'max_heart_rate', 'avgRunCadence': 'average_cadence', 'maxRunCadence':  'max_cadence', 'elapsedDuration': 'elapsed_time', 'movingDuration': 'moving_time', 'deviceId': 'device_id', 'locationName': 'activity_location', 'manufacturer': 'activity_device'})

        # Change dtypes
        .assign(activity_date_gmt = lambda row: pd.to_datetime(row['activity_date_gmt'], unit='ms'))
        .assign(activity_date = lambda row: pd.to_datetime(row['activity_date'], unit='ms'))

        # Create 'treadmill_running' column
        .assign(treadmill_running = lambda row: np.where(row['activity_type'] == 'treadmill_running', 1, 0))


        ## Transform columns

        # activity_type
        .assign(activity_type = lambda row: row['activity_type'].replace(to_replace=r'^running$|^treadmill_running$', value='Run', regex=True))
        .assign(activity_type = lambda row: row['activity_type'].replace(to_replace=r'^other$', value='Other', regex=True))

        # elapsed_time
        .assign(elapsed_time = lambda row: row['elapsed_time']/1000) # to seconds

        # moving_time
        .assign(moving_time = lambda row: row['moving_time']/1000) # to seconds

        # duration
        .assign(duration = lambda row: row['duration']/1000) # to seconds

        # distance
        .assign(distance = lambda row: row['distance']/100) # to meters

        # elevation_gain
        .assign(elevation_gain = lambda row: row['elevation_gain']/100) # to meters


        # Select columns
        .filter(items=['activity_date_gmt', 'activity_date', 'athlete_id', 'activity_type', 'treadmill_running', 'activity_id', 'activity_name', 'activity_location', 'elapsed_time', 'moving_time', 'duration', 'distance', 'max_speed', 'average_speed', 'steps', 'elevation_gain', 'max_heart_rate', 'average_heart_rate', 'max_cadence', 'average_cadence', 'calories', 'activity_device', 'device_id'])

        # Remove columns
        .drop(columns=['activity_date_gmt'], axis=1, errors='ignore')

        # Rearrange rows
        .sort_values(by=['activity_date'], ignore_index=True)

    )


    # Return objects
    return activities_garmin



# Check which activities from Garmin Connect are already on Strava (https://www.statshunters.com/activities)
def activities_garmin_compare(*, activities_garmin, activities_strava='activities_strava.xlsx'):

    activities_garmin = (activities_garmin

        # Create 'activity_date_cleaned' column
        .assign(activity_date_cleaned = lambda row: row['activity_date'].dt.strftime('%Y-%m-%d 00:%M:00'))

    )


    activities_strava = (pd.read_excel(io=activities_strava, sheet_name='Activities', header=0, index_col=None, skiprows=0, skipfooter=0, dtype=None, engine='openpyxl')

        # Rename columns
        .rename(columns={'Date': 'activity_date', 'Name': 'activity_name_strava', 'Moving time': 'moving_time_strava', 'Elapsed time': 'elapsed_time_strava', 'Distance (m)': 'distance_strava', 'Elevation (m)': 'elevation_gain_strava', 'Type': 'activity_type', 'Max heartrate': 'max_heart_rate_strava', 'Avg heartrate': 'average_heart_rate_strava'})

        # Change dtypes
        .assign(activity_date = lambda row: row['activity_date'].apply(parser.parse))

        # Select columns
        .filter(items=['activity_date', 'activity_date_cleaned', 'activity_type', 'activity_name_strava', 'elapsed_time_strava', 'moving_time_strava', 'distance_strava', 'elevation_gain_strava', 'max_heart_rate_strava', 'average_heart_rate_strava'])

        # Rearrange rows
        .sort_values(by=['activity_date'], ignore_index=True)

    )


    activities_strava = (activities_strava

        # Create 'activity_date_cleaned' column
        .assign(activity_date_cleaned = lambda row: row['activity_date'].dt.strftime('%Y-%m-%d 00:%M:00'))

    )


    activities_garmin_compare_1 = (activities_garmin
        .query('activity_type != "Other"')
        .merge(activities_strava.drop(columns=['activity_date'], axis=1, errors='ignore'), how='left', on=['activity_date_cleaned', 'activity_type'], indicator=True)
    )


    activities_garmin_compare_2 = (activities_garmin
        .query('activity_type == "Other"')
        .merge(activities_strava.drop(columns=['activity_date', 'activity_type'], axis=1, errors='ignore'), how='left', on=['activity_date_cleaned'], indicator=True)
    )


    activities_garmin_compare = (pd.concat(objs=[activities_garmin_compare_1, activities_garmin_compare_2], axis=0, ignore_index=True, sort=False)

        # Transform columns
        .assign(
            elapsed_time_difference = lambda row: row['elapsed_time'] - row['elapsed_time_strava'],
            moving_time_difference = lambda row: row['moving_time'] - row['moving_time_strava'],
            distance_difference = lambda row: round(number=row['distance'], ndigits=2) - round(number=row['distance_strava'], ndigits=2)
        )

        # Select columns
        .filter(items=['_merge', 'activity_date', 'athlete_id', 'activity_type', 'treadmill_running', 'activity_id', 'activity_name', 'activity_name_strava', 'activity_location', 'elapsed_time', 'elapsed_time_strava', 'elapsed_time_difference', 'moving_time', 'moving_time_strava', 'moving_time_difference', 'duration', 'distance', 'distance_strava', 'distance_difference', 'max_speed', 'average_speed', 'steps', 'elevation_gain', 'elevation_gain_strava', 'max_heart_rate', 'max_heart_rate_strava', 'average_heart_rate', 'average_heart_rate_strava', 'max_cadence', 'average_cadence', 'calories', 'activity_device', 'device_id'])

        # Rearrange rows
        .sort_values(by=['_merge', 'activity_date'], ignore_index=True)

    )


    # Delete objects
    del activities_garmin_compare_1, activities_garmin_compare_2

    # Return objects
    return activities_garmin_compare



# For not matched activities, use Torben's Strava Äpp (https://entorb.net/strava/) to import remaining activities (template Excel: https://entorb.net/strava/download/StravaImportTemplate.xlsx)
def activities_garmin_compare_not_matched(*, activities_garmin_compare):

    activities_garmin_strava_excel_import = (activities_garmin_compare

        # Filter rows
        .query('_merge == "left_only"')

        # Create empty 'activity_description' column
        .assign(activity_description = '')

        # Create empty 'commute' column
        .assign(commute = 0)

        # Create empty 'activity_gear' column
        .assign(activity_gear = '')

        # Rename columns
        .rename(columns={'activity_date': 'Date', 'activity_type': 'Type', 'treadmill_running': 'OnTrainer*', 'activity_name': 'Name', 'activity_description': 'Description*', 'commute': 'Commute*', 'activity_gear': 'Gear ID*', 'moving_time': 'Duration (s) (1)', 'duration': 'Duration (s) (2)', 'distance': 'Distance (m)*', 'elevation_gain': 'Elevation gain*'
        })


        # Select columns
        .filter(items=['Type', 'Date', 'Duration (s) (1)', 'Duration (s) (2)', 'Distance (m)*', 'Name', 'Description*', 'Commute*', 'OnTrainer*', 'Elevation gain*', 'Gear ID*'])

    )


    # Return objects
    return activities_garmin_strava_excel_import




##########################
# Garmin Data Export Tools
##########################

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


# Import Garmin Connect activities to DataFrame
activities_garmin = activities_garmin_import()


# Check which activities from Garmin Connect are already on Strava (https://www.statshunters.com/activities)
# 'distance_difference' of up to 20 meters is acceptable
activities_garmin_test = activities_garmin_compare(activities_garmin=activities_garmin, activities_strava='activities_strava.xlsx')
# activities_garmin_test.to_clipboard(excel=True, sep=None, index=False)


# For not matched activities, use Torben's Strava Äpp (https://entorb.net/strava/) to import remaining activities (template Excel: https://entorb.net/strava/download/StravaImportTemplate.xlsx)
# activities_garmin_strava_excel_import = activities_garmin_compare_not_matched(activities_garmin_compare=activities_garmin_test)
# activities_garmin_strava_excel_import.to_clipboard(excel=True, sep=None, index=False)
