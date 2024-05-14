## Apple Health Export Tools
# Last update: 2023-11-26


"""About: Script that performs a series of transformations to the Apple Health .xml Export."""


###############
# Initial Setup
###############

# Erase all declared global variables
globals().clear()


# Import packages
import os
import defusedxml.ElementTree as ET

from dateutil import parser
import numpy as np
import pandas as pd


# Settings

## Set working directory
os.chdir(path=os.path.join(os.path.expanduser('~'), 'Downloads'))

## Copy-on-Write (will be enabled by default in version 3.0)
if pd.__version__ >= '1.5.0' and pd.__version__ < '3.0.0':
    pd.options.mode.copy_on_write = True


###########
# Functions
###########


# Import Apple Health workouts/activities to DataFrame
def activities_apple_health_import(*, file, remove_duplicates=True):
    # Create ElementTree object
    root = ET.parse(source=file).getroot()

    workouts = []

    workout_list = list(root.iter('Workout'))

    for i in range(len(workout_list)):
        workout = workout_list[i].attrib

        # MetadataEntry
        metadata_entries = list(workout_list[i].iter('MetadataEntry'))

        for metadata_entry in metadata_entries:
            workout.update(
                {metadata_entry.attrib['key']: metadata_entry.attrib['value']},
            )

        # WorkoutStatistics
        workout_statistics = list(workout_list[i].iter('WorkoutStatistics'))

        for workout_statistic in workout_statistics:
            workout.update(
                {workout_statistic.attrib['type']: workout_statistic.attrib['sum']},
            )
            workout.update(
                {
                    ''.join(
                        '{}{}'.format(workout_statistic.attrib['type'], 'Unit'),
                    ): workout_statistic.attrib['unit'],
                },
            )

        workouts.append(workout)

    activities_apple_health = pd.DataFrame(data=workouts, index=None, dtype=None)

    # Rename columns
    activities_apple_health.columns = activities_apple_health.columns.str.replace(
        pat='^HKQuantityTypeIdentifier|^HK',
        repl='',
        regex=True,
    )
    activities_apple_health.columns = activities_apple_health.columns.str.replace(
        pat='^ExternalUUID',
        repl='ExternalUuid',
        regex=True,
    )
    activities_apple_health.columns = activities_apple_health.columns.str.replace(
        pat='^([A-Z])',
        repl=(lambda column: column.group(1).lower()),
        regex=True,
    )
    activities_apple_health.columns = activities_apple_health.columns.str.replace(
        pat='([A-Z])',
        repl=(lambda column: '_' + column.group(1).lower()),
        regex=True,
    )
    activities_apple_health = activities_apple_health.rename(
        columns={
            'workout_activity_type': 'activity_type',
            'creation_date': 'activity_creation_date',
            'start_date': 'activity_date',
            'end_date': 'activity_end_date',
            'indoor_workout': 'indoor',
            'maximum_speed': 'max_speed',
            'elevation_ascended': 'elevation_gain',
        },
    )

    # Merge distance columns into one
    activities_apple_health['distance'] = activities_apple_health['distance_cycling'].fillna(
        value=activities_apple_health['distance_walking_running'],
        method=None,
        axis=0,
    )

    activities_apple_health['distance_unit'] = activities_apple_health['distance_cycling_unit'].fillna(
        value=activities_apple_health['distance_walking_running_unit'],
        method=None,
        axis=0,
    )

    # Create 'max_speed_unit' column
    activities_apple_health['max_speed_unit'] = activities_apple_health['max_speed'].str.extract(pat=r'(m/s)$', flags=0, expand=True)
    activities_apple_health['max_speed'] = activities_apple_health['max_speed'].str.replace(pat='m/s$', repl='', regex=True)

    # Create 'average_speed_unit' column
    activities_apple_health['average_speed_unit'] = activities_apple_health['average_speed'].str.extract(pat=r'(m/s)$', flags=0, expand=True)
    activities_apple_health['average_speed'] = activities_apple_health['average_speed'].str.replace(pat='m/s$', repl='', regex=True)

    # Create 'elevation_gain_unit' column
    activities_apple_health['elevation_gain_unit'] = activities_apple_health['elevation_gain'].str.extract(pat=r'(m)$', flags=0, expand=True)
    activities_apple_health['elevation_gain'] = activities_apple_health['elevation_gain'].str.replace(pat='m$', repl='', regex=True)

    # Create 'elevation_descended_unit' column
    activities_apple_health['elevation_descended_unit'] = activities_apple_health['elevation_descended'].str.extract(pat=r'(m)$', flags=0, expand=True)
    activities_apple_health['elevation_descended'] = activities_apple_health['elevation_descended'].str.replace(pat='m$', repl='', regex=True)

    activities_apple_health = (
        activities_apple_health
        # Change dtypes
        .assign(
            activity_creation_date=lambda row: row['activity_creation_date'].apply(
                parser.parse,
            ),
        )
        .assign(activity_date=lambda row: row['activity_date'].apply(parser.parse))
        .assign(
            activity_end_date=lambda row: row['activity_end_date'].apply(parser.parse),
        )
        .astype(
            dtype={
                'duration': 'float',
                'distance': 'float',
                'max_speed': 'float',
                'average_speed': 'float',
                'elevation_gain': 'float',
                'elevation_descended': 'float',
                'active_energy_burned': 'float',
                'basal_energy_burned': 'float',
            },
        )
        ## Transform columns
        # activity_type
        .assign(
            activity_type=lambda row: row['activity_type'].replace(
                to_replace=r'^HKWorkoutActivityType',
                value='',
                regex=True,
            ),
        )
        # Remove columns
        .drop(
            columns=[
                'distance_cycling',
                'distance_cycling_unit',
                'distance_walking_running',
                'distance_walking_running_unit',
            ],
            axis=1,
            errors='ignore',
        )
        # Rearrange rows
        .sort_values(by=['activity_date', 'activity_creation_date'], ignore_index=True)
    )

    # Rearrange columns
    columns = [
        'activity_date',
        'activity_end_date',
        'activity_creation_date',
        'activity_type',
        'source_name',
        'source_version',
        'indoor',
        'duration',
        'duration_unit',
        'distance',
        'distance_unit',
        'max_speed',
        'max_speed_unit',
        'average_speed',
        'average_speed_unit',
        'elevation_gain',
        'elevation_gain_unit',
        'elevation_descended',
        'elevation_descended_unit',
        'active_energy_burned',
        'active_energy_burned_unit',
        'basal_energy_burned',
        'basal_energy_burned_unit',
        'metadata_key_sync_version',
        'metadata_key_sync_identifier',
        'external_uuid',
    ]

    activities_apple_health = activities_apple_health.reindex(
        columns=(columns + list(activities_apple_health.columns.difference(other=columns, sort=True))),
    )

    # Remove duplicate rows
    if remove_duplicates is True:
        activities_apple_health = (
            activities_apple_health
            # Remove duplicate rows for activities with multiple 'source_version'
            .drop_duplicates(
                subset=[
                    'activity_date',
                    'activity_end_date',
                    'activity_type',
                    'source_name',
                ],
                keep='last',
                ignore_index=True,
            )
        )

        # Remove duplicate rows for activities already in Strava
        activities_apple_health = activities_apple_health[~(activities_apple_health.duplicated(subset=['activity_date'], keep=False) & activities_apple_health['source_name'].eq('Strava'))]

    # Return objects
    return activities_apple_health


def activities_apple_health_to_strava(
    *,
    activities_apple_health,
    output_directory='Activities Output',
):
    """Create .tcx files from activities."""
    activities_apple_health = (
        activities_apple_health
        # Change dtypes
        .assign(
            activity_creation_date=lambda row: row['activity_creation_date'].dt.tz_convert(tz='UTC'),
        )
        .assign(activity_date=lambda row: row['activity_date'].dt.tz_convert(tz='UTC'))
        .assign(
            activity_end_date=lambda row: row['activity_end_date'].dt.tz_convert(tz='UTC'),
        )
        ## Transform columns
        # activity_type
        .assign(
            activity_type=lambda row: row['activity_type'].replace(
                to_replace=r'^Cycling$',
                value='Biking',
                regex=True,
            ),
        )
    )

    activities_apple_health = activities_apple_health.assign(
        duration=lambda row: np.where(
            row['duration_unit'] == 'min',
            row['duration'] * 60,
            row['duration'],
        ),
        distance=lambda row: np.where(
            row['distance_unit'] == 'km',
            row['distance'] * 1000,
            row['distance'],
        ),
    )

    # Change dtypes
    activities_apple_health = activities_apple_health.fillna(
        value='',
        method=None,
        axis=0,
    )

    for index, row in activities_apple_health.iterrows():
        # Create .tcx file content (https://developers.strava.com/docs/uploads/)
        text = []
        text.append('<?xml version="1.0" encoding="UTF-8"?>\n')
        text.append('<TrainingCenterDatabase\n')
        text.append(
            '  xsi:schemaLocation="http://www.garmin.com/xmlschemas/TrainingCenterDatabase/v2 http://www.garmin.com/xmlschemas/TrainingCenterDatabasev2.xsd"\n',
        )
        text.append('  xmlns:ns5="http://www.garmin.com/xmlschemas/ActivityGoals/v1"\n')
        text.append(
            '  xmlns:ns3="http://www.garmin.com/xmlschemas/ActivityExtension/v2"\n',
        )
        text.append('  xmlns:ns2="http://www.garmin.com/xmlschemas/UserProfile/v2"\n')
        text.append(
            '  xmlns="http://www.garmin.com/xmlschemas/TrainingCenterDatabase/v2"\n',
        )
        text.append(
            '  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:ns4="http://www.garmin.com/xmlschemas/ProfileExtension/v1">\n',
        )
        text.append('  <Activities>\n')
        text.append('    <Activity Sport="{}">\n'.format(row['activity_type']))
        text.append(
            '      <Id>{}</Id>\n'.format(
                row['activity_date'].strftime(format='%Y-%m-%dT%H:%M:%SZ'),
            ),
        )
        text.append(
            '      <Lap StartTime="{}">\n'.format(
                row['activity_date'].strftime(format='%Y-%m-%dT%H:%M:%SZ'),
            ),
        )
        text.append(
            '        <TotalTimeSeconds>{}</TotalTimeSeconds>\n'.format(row['duration']),
        )  # 'duration' for 'source_name' = 'Connect' means 'moving_time'; for 'source_name' = 'Strava' means 'elapsed_time'
        text.append(
            '        <DistanceMeters>{}</DistanceMeters>\n'.format(row['distance']),
        )
        text.append(
            '        <MaximumSpeed>{}</MaximumSpeed>\n'.format(row['max_speed']),
        )
        text.append(
            '        <Calories>{}</Calories>\n'.format(row['active_energy_burned']),
        )
        # text.append('        <AverageHeartRateBpm>\n')
        # text.append('          <Value>{}</Value>\n'.format(row['average_heart_rate']))
        # text.append('        </AverageHeartRateBpm>\n')
        # text.append('        <MaximumHeartRateBpm>\n')
        # text.append('          <Value>{}</Value>\n'.format(row['max_heart_rate']))
        # text.append('        </MaximumHeartRateBpm>\n')
        # text.append('        <Intensity>Active</Intensity>\n')
        # text.append('        <TriggerMethod>Manual</TriggerMethod>\n')
        text.append('        <Track>\n')
        text.append('          <Trackpoint>\n')
        text.append(
            '            <Time>{}</Time>\n'.format(
                row['activity_date'].strftime(format='%Y-%m-%dT%H:%M:%SZ'),
            ),
        )
        text.append('            <AltitudeMeters></AltitudeMeters>\n')
        text.append('            <DistanceMeters></DistanceMeters>\n')
        text.append('            <Extensions>\n')
        text.append('              <ns3:TPX/>\n')
        text.append('            </Extensions>\n')
        text.append('          </Trackpoint>\n')
        text.append('          <Trackpoint>\n')
        text.append(
            '            <Time>{}</Time>\n'.format(
                row['activity_end_date'].strftime(format='%Y-%m-%dT%H:%M:%SZ'),
            ),
        )
        text.append('            <AltitudeMeters></AltitudeMeters>\n')
        text.append('            <DistanceMeters></DistanceMeters>\n')
        text.append('            <Extensions>\n')
        text.append('              <ns3:TPX/>\n')
        text.append('            </Extensions>\n')
        text.append('          </Trackpoint>\n')
        text.append('        </Track>\n')
        text.append('        <Extensions>\n')
        text.append('          <ns3:LX>\n')
        text.append(
            '            <ns3:AvgSpeed>{}</ns3:AvgSpeed>\n'.format(
                row['average_speed'],
            ),
        )
        text.append('            <ns3:Speed>{}</ns3:Speed>\n'.format(row['max_speed']))
        text.append('          </ns3:LX>\n')
        text.append('        </Extensions>\n')
        text.append('      </Lap>\n')
        text.append('      <Creator xsi:type="Device_t">\n')
        text.append('        <Name>{}</Name>\n'.format(row['source_name']))
        # text.append('        <UnitId></UnitId>\n')
        # text.append('        <ProductID></ProductID>\n')
        # text.append('        <Version>\n')
        # text.append('          <VersionMajor>{}</VersionMajor>\n'.format(row['source_version']))
        # text.append('          <VersionMinor>{}</VersionMinor>\n'.format(row['source_version']))
        # text.append('          <BuildMajor></BuildMajor>\n')
        # text.append('          <BuildMinor></BuildMinor>\n')
        # text.append('        </Version>\n')
        text.append('      </Creator>\n')
        text.append('    </Activity>\n')
        text.append('  </Activities>\n')
        text.append('  <Author xsi:type="Application_t">\n')
        text.append('    <Name>{}</Name>\n'.format(row['source_name']))
        # text.append('    <Build>\n')
        # text.append('      <Version>\n')
        # text.append('        <VersionMajor>0</VersionMajor>\n')
        # text.append('        <VersionMinor>0</VersionMinor>\n')
        # text.append('        <BuildMajor>0</BuildMajor>\n')
        # text.append('        <BuildMinor>0</BuildMinor>\n')
        # text.append('      </Version>\n')
        # text.append('    </Build>\n')
        # text.append('    <LangID>en</LangID>\n')
        # text.append('    <PartNumber></PartNumber>\n')
        text.append('  </Author>\n')
        text.append('</TrainingCenterDatabase>\n')

        with open(
            file=os.path.join(
                output_directory,
                row['activity_date'].strftime(format='%Y-%m-%dT%H-%M-%SZ') + '_' + row['activity_type'] + '.tcx',
            ),
            mode='w',
            encoding='utf-8',
        ) as file_out:
            file_out.writelines(text)

    # Return objects
    return activities_apple_health


###########################
# Apple Health Export Tools
###########################

# Import Apple Health workouts/activities to DataFrame
activities_apple_health = activities_apple_health_import(
    file=os.path.join('Export', 'apple_health_export', 'Export.xml'),
    remove_duplicates=False,
)


# Check for duplicated activities
activities_apple_health[activities_apple_health.duplicated(subset=['activity_date'], keep=False)]


# Create 'Activities Output' folder
os.makedirs(name='Activities Output', exist_ok=True)

# Create .tcx files from activities
activities_apple_health_import = activities_apple_health_to_strava(
    activities_apple_health=activities_apple_health.query(
        expr='source_name.isin(["Daily Yoga", "Nike Run Club"])',
    ),
    output_directory='Activities Output',
)
