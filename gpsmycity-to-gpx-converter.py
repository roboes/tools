## GPSmyCity to GPX converter
# Last update: 2023-05-31


###############
# Initial Setup
###############

# Erase all declared global variables
globals().clear()


# Import packages
import os
import re
from urllib.request import Request, urlopen

import gpxpy
import pandas as pd
from werkzeug.utils import secure_filename


# Set working directory
os.chdir(path=os.path.join(os.path.expanduser('~'), 'Downloads'))




###########
# Functions
###########

# GPSmyCity to GPX converter
def gpsmycity_tour_import(*, urls):

    for url in urls:

        # Import page source
        page_source = urlopen(url=Request(url=url, headers={'User-Agent': 'Mozilla'})).read().decode(encoding='utf8')
        page_source = page_source.split(sep='\n')


        # Create variables

        # tour_name
        tour_name = [s for s in page_source if s.startswith('<TITLE>') and s.endswith('</TITLE>\r')][0]
        tour_name = re.sub(pattern=r'^<TITLE>', repl=r'', string=tour_name)
        tour_name = re.sub(pattern=r'</TITLE>\r$', repl=r'', string=tour_name)

        # tour_map
        tour_map = [s for s in page_source if s.startswith('jarr')][0]
        tour_map = re.sub(pattern=r'^jarr = ', repl=r'', string=tour_map)
        tour_map = re.sub(pattern=r';\r$', repl=r'', string=tour_map)


        # Create DataFrame
        df_tour_map = (pd.read_json(path_or_buf=tour_map, orient='index', convert_dates=False, dtype='unicode', encoding='utf8')
            .transpose())
        df_tour_map['pins'] = df_tour_map['pins'].replace(to_replace=r'^None$', value=None, regex=True)


        # Split df_tour_map into df_segments and df_waypoints DataFrames
        df_segments = df_tour_map.filter(items=['path'])
        
        df_waypoints = (df_tour_map
            .query('pins.notnull()')
            .filter(items=['pins'])
        )


        # Delete objects
        del page_source, tour_map, df_tour_map


        ## df_segments

        if df_segments.drop_duplicates(subset=None, keep='first', ignore_index=True).shape == (1, 1) and df_segments.drop_duplicates(subset=None, keep='first', ignore_index=True)['path'][0] == 'None':
            pass

        else:

            # Split columns
            df_segments['path'] = df_segments['path'].str.strip('[\']')
            df_segments[['latitude', 'longitude']] = df_segments['path'].str.split(pat='\', \'', expand=True)
            df_segments = df_segments.drop(columns=['path'], axis=1)

            # Change dtypes
            df_segments = df_segments.astype(dtype={'latitude': 'float', 'longitude': 'float'})


        ## df_waypoints

        # Split columns
        df_waypoints['pins'] = df_waypoints['pins'].str.strip('[\']')

        # df_waypoints[['latitude', 'longitude', 'name', 'number', 'id']] = df_waypoints['pins'].str.split(pat='\', "|\', \'|", \'', expand=True)
        # df_waypoints = df_waypoints.drop(columns=['pins', 'number', 'id'], axis=1)
        df_waypoints[['latitude', 'longitude', 'name']] = df_waypoints['pins'].str.split(pat='\', "|\', \'|", \'', expand=True).iloc[:, 0:3]


        # Change dtypes
        df_waypoints = df_waypoints.astype(dtype={'latitude': 'float', 'longitude': 'float'})


        # Create .gpx file
        gpx = gpxpy.gpx.GPX()
        gpx.creator = 'GPSmyCity'
        gpx.description = tour_name


        # Create first track in .gpx
        gpx_track = gpxpy.gpx.GPXTrack()
        gpx.tracks.append(gpx_track)


        # Create first segment in .gpx track
        gpx_segment = gpxpy.gpx.GPXTrackSegment()
        gpx_track.segments.append(gpx_segment)


        # Write segments to .gpx
        if df_segments.drop_duplicates(subset=None, keep='first', ignore_index=True).shape == (1, 1) and df_segments.drop_duplicates(subset=None, keep='first', ignore_index=True)['path'][0] == 'None':
            pass

        else:
            for row in df_segments.index:
               gpx_segment.points.append(gpxpy.gpx.GPXTrackPoint(latitude=df_segments.loc[row, 'latitude'], longitude=df_segments.loc[row, 'longitude']))


        # Write waypoints to .gpx
        for row in df_waypoints.index:
           gpx.waypoints.append(gpxpy.gpx.GPXWaypoint(name=df_waypoints.loc[row, 'name'], latitude=df_waypoints.loc[row, 'latitude'], longitude=df_waypoints.loc[row, 'longitude']))


        # Save .gpx file
        with open('{}.gpx'.format(secure_filename(filename=tour_name)), mode='w', encoding='utf8') as file_out:
            file_out.write(gpx.to_xml())




############################
# GPSmyCity to GPX converter
############################

# Import GPSmyCity tours to .gpx
gpsmycity_tour_import(urls=['https://www.gpsmycity.com/tours/munich-introduction-walking-tour-6446.html', 'https://www.gpsmycity.com/blog/main-sights-to-see-in-augsburg-3414.html', 'https://www.gpsmycity.com/tours/edinburgh-introduction-walking-tour-6397.html'])
