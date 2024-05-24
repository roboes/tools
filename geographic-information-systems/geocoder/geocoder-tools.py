## Geocoder Tools
# Last update: 2024-05-24


"""About: Geocoder Tools."""


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
        file=BytesIO(initial_bytes=requests.get(url='https://gisco-services.ec.europa.eu/distribution/v2/countries/download/ref-countries-2020-01m.shp.zip', headers=None, timeout=5, verify=True).content),
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

        # Execution time
        print(f'Execution time: {datetime.now() - execution_start}')

        if len(df) == df_len:
            # Return objects
            return df


def countries():
    """Download and import world countries in country name (english), alpha-2 and alpha-3 codes."""

    # Download and import "country_name" and "country_code_alpha_2"
    country_name_df = (
        pd.read_json(
            path_or_buf=BytesIO(initial_bytes=requests.get(url='https://github.com/annexare/Countries/blob/main/dist/minimal/countries.en.min.json?raw=true', headers=None, timeout=5, verify=True).content),
            orient='index',
            convert_dates=False,
            dtype='unicode',
            encoding='utf-8',
        )
        .rename(columns={0: 'country_name'})
        .reset_index(level=None, drop=False, names=['country_code_alpha_2'])
    )

    # Download and import
    countries_df = (
        (
            pd.read_json(
                path_or_buf=BytesIO(
                    initial_bytes=requests.get(url='https://raw.githubusercontent.com/annexare/Countries/main/dist/minimal/countries.2to3.min.json?raw=true', headers=None, timeout=5, verify=True).content,
                ),
                orient='index',
                convert_dates=False,
                dtype='unicode',
                encoding='utf-8',
            )
            .rename(columns={0: 'country_code_alpha_3'})
            .reset_index(level=None, drop=False, names=['country_code_alpha_2'])
        )
        .merge(right=country_name_df, how='left', on=['country_code_alpha_2'], indicator=False)
        .assign(country_code_alpha_3=lambda row: row['country_code_alpha_3'].str.lower(), country_code_alpha_2=lambda row: row['country_code_alpha_2'].str.lower())
    )

    # Delete objects
    del country_name_df

    # Test duplicates
    if len(countries_df[countries_df.duplicated(subset=['country_code_alpha_2'], keep=False)]) == 0 and len(countries_df[countries_df.duplicated(subset=['country_code_alpha_3'], keep=False)]) == 0:
        print("Columns 'country_code_alpha_2', 'country_code_alpha_3' and 'country_name' have unique values.")

    else:
        print("WARNING: Columns 'country_code_alpha_2', 'country_code_alpha_3' and 'country_name' do not have unique values.")

    # Return objects
    return countries_df
