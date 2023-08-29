## .tcx Tools
# Last update: 2023-07-26


"""Script that performs a series of transformations to the Training Center XML (.tcx) workout data file."""


###############
# Initial Setup
###############

# Erase all declared global variables
globals().clear()


# Import packages
import glob
import os
import re


# Set working directory
# os.chdir(path=os.path.join(os.path.expanduser('~'), 'Downloads', 'Nike Run Club Export'))


###########
# Functions
###########


def tcx_lstrip(*, input_path):
    """Remove leading first line blank spaces of .tcx activity files."""
    # List of .tcx files including path
    files = glob.glob(pathname=os.path.join(input_path, '*.tcx'), recursive=False)

    if len(files) > 0:
        for file in files:
            with open(file, encoding='utf-8') as file_in:
                file_text = file_in.readlines()
                file_text_0 = file_text[0]
                file_text[0] = file_text[0].lstrip()

            if file_text[0] != file_text_0:
                with open(file, mode='w', encoding='utf-8') as file_out:
                    file_out.writelines(file_text)


def tcx_combine(*, input_path, output_filepath):
    """Combine multiple .tcx activity files into one .tcx file (for bulk upload to Strava - Strava will automatically separate/split these activities after upload)."""
    # List of .tcx files including path
    files = glob.glob(pathname=os.path.join(input_path, '*.tcx'), recursive=False)

    # Create .tcx file content
    text = []
    text.append('<?xml version="1.0" encoding="UTF-8"?>\n')
    # text.append('<TrainingCenterDatabase xmlns="http://www.garmin.com/xmlschemas/TrainingCenterDatabase/v2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.garmin.com/xmlschemas/TrainingCenterDatabase/v2 http://www.garmin.com/xmlschemas/TrainingCenterDatabasev2.xsd">\n')
    text.append(
        '<TrainingCenterDatabase xmlns="http://www.garmin.com/xmlschemas/TrainingCenterDatabase/v2" xmlns:aex="http://www.garmin.com/xmlschemas/ActivityExtension/v2" xmlns:nax="https://www.nike.com/xmlschemas/NikeActivityExtension/v1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">\n',
    )
    text.append('<Activities>\n')
    text.append('\n')

    # Combine files
    for file in files:
        with open(file, encoding='utf-8') as file_in:
            file_text = file_in.readlines()

            file_text = ''.join(file_text)
            file_text = re.sub(
                pattern=r'<Activity Sport',
                repl=r'\n<Activity Sport',
                string=file_text,
            )
            file_text = re.sub(
                pattern=r'</Activity>',
                repl=r'</Activity>\n',
                string=file_text,
            )
            file_text = file_text.split(sep='\n')

            index_activity_start = [
                index
                for index, item in enumerate(file_text)
                if item.startswith('<Activity Sport')
            ][0]
            index_activity_end = [
                index
                for index, item in enumerate(file_text)
                if item.endswith('</Activity>')
            ][0]

            if index_activity_start == index_activity_end:
                file_text = ''.join(file_text[index_activity_start])

            else:
                file_text = ''.join(
                    file_text[index_activity_start : index_activity_end + 1],
                )

            text.extend(file_text)
            text.append('\n')
            text.append('\n')

    text.append('</Activities>\n')
    text.append('</TrainingCenterDatabase>\n')

    with open(output_filepath, mode='w', encoding='utf-8') as file_out:
        file_out.writelines(text)


############
# .tcx Tools
############

tcx_lstrip(
    input_path=os.path.join(
        os.path.expanduser('~'),
        'Downloads',
        'Nike Run Club Export',
    ),
)

tcx_combine(
    input_path=os.path.join(
        os.path.expanduser('~'),
        'Downloads',
        'Nike Run Club Export',
    ),
    output_filepath=os.path.join(
        os.path.expanduser('~'),
        'Downloads',
        'all_activities_tcx.tcx',
    ),
)
