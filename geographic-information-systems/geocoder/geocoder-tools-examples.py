## Geocoder Tools Examples
# Last update: 2024-03-27


"""About: Geocoder Tools Examples."""


###############
# Initial Setup
###############

# Erase all declared global variables
globals().clear()


# Import packages
from importlib.util import spec_from_file_location
import os
import sys

import pandas as pd


# Import custom packages
sys.dont_write_bytecode = True

geocoder_tools = spec_from_file_location(
    name='geocoder_tools',
    location=os.path.join(
        os.path.expanduser('~'),
        'Documents',
        'Tools',
        'geocoder',
        'geocoder-tools.py',
    ),
).loader.load_module()


download_world_boundaries_shapefile = geocoder_tools.download_world_boundaries_shapefile
geocoder_country_code = geocoder_tools.geocoder_country_code
countries_alpha_3_to_2 = geocoder_tools.countries_alpha_3_to_2

# Delete objects
del spec_from_file_location, geocoder_tools


# Settings

## Copy-on-Write (will be enabled by default in version 3.0)
if pd.__version__ >= '1.5.0' and pd.__version__ < '3.0.0':
    pd.options.mode.copy_on_write = True


#######################
# Geocoder Country Code
#######################

# Create example DataFrame with latitude and longitude
df = pd.DataFrame(
    data=[
        [
            '47.47290454150727',
            '13.002988843557109',
        ],
        [
            '48.77318931264',
            '13.814437606275643',
        ],
        [
            '48.772602496031155',
            '13.818782738961987',
        ],
        [
            '50.0889147084637',
            '14.417553922646446',
        ],
    ],
    index=None,
    columns=[
        'latitude',
        'longitude',
    ],
    dtype=None,
)


# Download Eurostat's Geographical Information and Maps (GISCO) Shapefile, Scale 1:1 Million
download_world_boundaries_shapefile(
    shapefile_path=os.path.join(
        os.path.expanduser('~'),
        'Downloads',
        'World Boundaries',
    ),
)


# Get country codes given latitude/longitude
df_geo = geocoder_country_code(
    df=df,
    shapefile_path=os.path.join(
        os.path.expanduser('~'),
        'Downloads',
        'World Boundaries',
        'CNTR_RG_01M_2020_4326.shp',
    ),
)

# Download and import world countries in multiple languages with associated alpha-2, alpha-3, and numeric codes as defined by the ISO 3166 standard
countries_alpha_3_to_2_df = countries_alpha_3_to_2()
