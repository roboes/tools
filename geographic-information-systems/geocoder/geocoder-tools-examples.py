## Geocoder Tools Examples
# Last update: 2026-09-13


"""About: Geocoder Tools Examples."""


###############
# Initial Setup
###############

# Erase all declared global variables
globals().clear()


# Import packages
import os
import sys
from importlib.util import module_from_spec, spec_from_file_location

import pandas as pd

# Import custom packages
sys.dont_write_bytecode = True

geocoder_tools_spec = spec_from_file_location(
    name='geocoder_tools',
    location=os.path.join(os.path.dirname(__file__), 'geocoder-tools.py'),
)
if geocoder_tools_spec is None or geocoder_tools_spec.loader is None:
    raise ImportError('Could not load geocoder-tools.py')

geocoder_tools = module_from_spec(geocoder_tools_spec)
geocoder_tools_spec.loader.exec_module(geocoder_tools)


download_world_boundaries_shapefile = geocoder_tools.download_world_boundaries_shapefile
geocoder_country_code = geocoder_tools.geocoder_country_code
countries = geocoder_tools.countries

# Delete objects
del geocoder_tools_spec, module_from_spec, spec_from_file_location, geocoder_tools


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
countries_df = countries()
