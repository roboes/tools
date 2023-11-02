## Geolocation
# Last update: 2023-11-02


"""Geolocation tools."""


###############
# Initial Setup
###############

# Erase all declared global variables
globals().clear()


# Import packages
from datetime import datetime
from itertools import batched
# import os

from geopy.extra.rate_limiter import RateLimiter
from geopy.geocoders import Nominatim
import overpy
import pandas as pd


# Set working directory
# os.chdir(path=os.path.join(os.path.expanduser('~'), 'Downloads'))


# Geocoder setup
geolocator = Nominatim(
    domain='nominatim.openstreetmap.org', scheme='https', user_agent='python-tools'
)

geocode = RateLimiter(func=geolocator.geocode, min_delay_seconds=1)
reverse = RateLimiter(func=geolocator.reverse, min_delay_seconds=1)


###########
# Functions
###########

def geocode_batch(*, df, chunk_size=100, filepath=None):
    """Given a DataFrame input with location columns, split it into multiple chunks and run the geocoder, saving all chunks where the geocoder has already been run as a pickle file."""
    # Create variables
    execution_start = datetime.now()

    # Create 'location_geolocation' column if non-existent:
    if 'location_geolocation' not in df:
        df['location_geolocation'] = None

    # Data transformation
    df = df.fillna(value={'location_street': '', 'location_city': '', 'location_state': '', 'location_country': ''}, method=None, axis=0)

    # Create empty DataFrame
    df_geolocation = pd.DataFrame(data=None, index=None, columns=None, dtype=None)

    # Slice DataFrame into multiple chunks and run the geocoder for empty 'location_geolocation'
    for batch in batched(iterable=range(len(df)), n=chunk_size):
        df_chunk = df.iloc[min(batch):max(batch)+1].copy()
        df_chunk['location_geolocation'] = df_chunk.apply(lambda row: geocode(query={'street': row['location_street'], 'city': row['location_city'], 'state': row['location_state'], 'country': row['location_country']}, exactly_one=True, addressdetails=True, extratags=False, namedetails=True, language='en', timeout=None) if pd.isna(row['location_geolocation']) else row['location_geolocation'], axis=1)
        df_geolocation = pd.concat([df_geolocation, df_chunk], axis=0, ignore_index=True, sort=False)

        # Save all chunks where the geocoder has already been run
        if filepath is not None:
            df_geolocation.to_pickle(path='{}.pkl'.format(filepath))

    # Execution time
    execution_time = datetime.now() - execution_start
    print('Execution time: {}'.format(execution_time))

    # Return objects
    return df_geolocation


#############
# Geolocation
#############

# Create example DataFrame
df = pd.DataFrame(
    data=[
        ['Nordallee 25', 'München', 'Bavaria', 'Germany'],
    ],
    index=None,
    columns=['location_street', 'location_city', 'location_state', 'location_country'],
    dtype='str',
)

df_geo = geocode_batch(df=df, chunk_size=100, filepath='df_geolocation')


# Search - free-form query - https://nominatim.org/release-docs/latest/api/Search/#free-form-query
geolocation = geocode(
    query='Munich International Airport',
    exactly_one=True,
    addressdetails=True,
    extratags=False,
    namedetails=True,
    language='en',
    timeout=None,
)

print(geolocation.raw)


# Search - structured query - https://nominatim.org/release-docs/latest/api/Search/#structured-query
geolocation = geocode(
    query={
        'amenity': 'Munich International Airport',
        'street': 'Nordallee 25',
        'city': 'München',
        'state': 'Bavaria',
        'country': 'Germany',
    },
    exactly_one=True,
    addressdetails=True,
    extratags=False,
    namedetails=True,
    language='en',
    timeout=None,
)

print(geolocation.raw)


# Reverse geocoding - get geolocation given a latitude and longitude - https://github.com/openstreetmap/Nominatim/edit/master/docs/api/Reverse.md
geolocation = reverse(
    query=f'{48.3539}, {11.7785}',
    exactly_one=True,
    addressdetails=True,
    namedetails=True,
    language='en',
    timeout=None,
)

print(geolocation.raw)


# Overpass Turbo - https://overpass-turbo.eu
api = overpy.Overpass()
result = api.query(
    query="""
[out:json];
nwr["vity"="Freising"];
nwr["name"="Flughafen München"];
out;""",
)
