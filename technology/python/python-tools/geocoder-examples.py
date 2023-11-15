## Geocoder Examples
# Last update: 2023-11-15


"""Geolocation tools."""


###############
# Initial Setup
###############

# Erase all declared global variables
globals().clear()

# Import packages
import os
import sys

from geopy.extra.rate_limiter import RateLimiter
from geopy.geocoders import Nominatim
import overpy
import pandas as pd

sys.path.append(os.path.join(os.path.expanduser('~'), 'Documents', 'python-tools'))
from geocoder import df_geolocation_concatenate, geocoder, geocoder_location_columns


# Geocoder setup
geolocator = Nominatim(
    domain='nominatim.openstreetmap.org',
    scheme='https',
    user_agent='python-tools',
)

geocode = RateLimiter(func=geolocator.geocode, min_delay_seconds=1)
reverse = RateLimiter(func=geolocator.reverse, min_delay_seconds=1)


##########
# Geocoder
##########

# Create example DataFrame
df = pd.DataFrame(
    data=[
        ['Germany', 'Bavaria', 'München', '85356', 'Nordallee 25'],
    ],
    index=None,
    columns=[
        'address_country',
        'address_state',
        'address_city',
        'address_postal_code',
        'address_street',
    ],
    dtype='str',
)

# Create sample
# df = df.sample(n=1000, ignore_index=False)

# Import chunks where the geocoder has already been run and concatenate it with the original dataset
try:
    df = df_geolocation_concatenate(
        df=df,
        df_slice=pd.read_pickle(
            filepath_or_buffer=os.path.join(
                os.path.expanduser('~'),
                'Downloads',
                'df_geolocation_slice.pkl',
            ),
        ),
    )

except Exception:
    pass

# Run geocoder
df_geo = geocoder(df=df, chunk_size=50, filepath='df_geolocation_slice.pkl', matching_level_flag=None, fillna='#')
print(df_geo)

df_geo = geocoder_location_columns(df_geo=df_geo)

# Replace # by None
# df_geo = df_geo.assign(location_geolocation=lambda row: row['location_geolocation'].mask(row['location_geolocation'] == '#'))

# Split geolocation information into multiple location columns
# df_geo = geocoder_location_columns(df_geo=df_geo)


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
        'country': 'Germany',
        'state': 'Bavaria',
        'city': 'München',
        'postalcode': '85356',
        'street': 'Nordallee 25',
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
