## Geocoder Country Code
# Last update: 2024-02-06


"""About: Get country code given a latitude/longitude input using Eurostat's Geographical Information and Maps (GISCO) Shapefile."""


###############
# Initial Setup
###############

# Erase all declared global variables
globals().clear()


# Import packages
from datetime import datetime
from io import BytesIO
import os
from zipfile import ZipFile, ZIP_DEFLATED

import geopandas as gpd
import pandas as pd
import requests


# Settings

## Copy-on-Write (will be enabled by default in version 3.0)
if pd.__version__ >= '1.5.0' and pd.__version__ < '3.0.0':
    pd.options.mode.copy_on_write = True


###########
# Functions
###########


def download_world_boundaries_shapefile(*, shapefile_path):
    """Download Eurostat's Geographical Information and Maps (GISCO) Shapefile, Scale 1:1 Million (Source: https://ec.europa.eu/eurostat/web/gisco/geodata/reference-data/administrative-units-statistical-units/countries)."""
    # Download
    with ZipFile(
        file=BytesIO(
            initial_bytes=requests.get(
                url='https://gisco-services.ec.europa.eu/distribution/v2/countries/download/ref-countries-2020-01m.shp.zip',
                timeout=5,
                verify=True,
            ).content,
        ),
        mode='r',
        compression=ZIP_DEFLATED,
    ) as outer_zip_file, outer_zip_file.open(
        name='CNTR_RG_01M_2020_4326.shp.zip',
        mode='r',
    ) as inner_zip_file:
        inner_zip_data = inner_zip_file.read()
        zip_file = ZipFile(
            BytesIO(initial_bytes=inner_zip_data),
            mode='r',
            compression=ZIP_DEFLATED,
        )
        zip_file.extractall(path=os.path.join(shapefile_path))


def geocoder_country_code(*, df, shapefile_path):
    """Get country code given a latitude/longitude input."""
    # Import Shapefile
    world_boundaries = (
        gpd.read_file(
            filename=shapefile_path,
            layer=None,
            include_fields=['ISO3_CODE', 'geometry'],
            driver=None,
            encoding='utf-8',
        )
        # Rename columns
        .rename(columns={'ISO3_CODE': 'country_code'})
        # Rearrange rows
        .sort_values(by=['country_code'], ignore_index=True)
    )

    # Generate GeometryArray of shapely Point geometries from latitude, longitude coordinates
    df = gpd.GeoDataFrame(
        data=df,
        geometry=gpd.points_from_xy(
            x=df.longitude,
            y=df.latitude,
        ),
        crs='EPSG:4326',
    )

    if world_boundaries.crs == 'EPSG:4326':
        # Create variables
        execution_start = datetime.now()
        df_len = len(df)

        df = (
            gpd.sjoin(
                left_df=df,
                right_df=world_boundaries,
                how='left',
                predicate='within',
            )
            # Remove columns
            .drop(columns=['index_right', 'geometry'], axis=1, errors='ignore')
        )

        if len(df) == df_len:
            # Execution time
            execution_time = datetime.now() - execution_start
            print(f'Execution time: {execution_time}')

            # Return objects
            return df


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
