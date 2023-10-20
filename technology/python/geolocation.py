## Geolocation
# Last update: 2023-10-19


"""Geolocation tools."""


###############
# Initial Setup
###############

# Erase all declared global variables
globals().clear()


# Import packages
# import os

from geopy.extra.rate_limiter import RateLimiter
from geopy.geocoders import Nominatim
import overpy


# Set working directory
# os.chdir(path=os.path.join(os.path.expanduser('~'), 'Downloads'))


#############
# Geolocation
#############

# Geocoder setup
geolocator = Nominatim(
    domain='nominatim.openstreetmap.org', scheme='https', user_agent='python-tools'
)

geocode = RateLimiter(func=geolocator.geocode, min_delay_seconds=1)
reverse = RateLimiter(func=geolocator.reverse, min_delay_seconds=1)


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
