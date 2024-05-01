## Geocoder Tools
# Last update: 2024-03-27


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
from urllib.request import Request, urlopen
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

        # Execution time
        print(f'Execution time: {datetime.now() - execution_start}')

        if len(df) == df_len:
            # Return objects
            return df


def countries_alpha_3_to_2():
    """Download and import world countries in alpha-3 and alpha-2 codes."""
    # Download and import
    countries_alpha_3_to_2_df = (
        pd.read_json(
            path_or_buf=BytesIO(
                urlopen(
                    url=Request(
                        url='https://github.com/annexare/Countries/blob/main/dist/minimal/countries.3to2.min.json?raw=true',
                        headers={'User-Agent': 'Mozilla'},
                    ),
                ).read(),
            ),
            orient='index',
            convert_dates=False,
            dtype='unicode',
            encoding='utf-8',
        )
        .rename(columns={0: 'alpha2'})
        .reset_index(level=None, drop=False, names=['alpha3'])
        .assign(
            alpha3=lambda row: row['alpha3'].str.lower(),
            alpha2=lambda row: row['alpha2'].str.lower(),
        )
    )

    # Test duplicates
    # print(len(countries_alpha_3_to_2_df[countries_alpha_3_to_2_df.duplicated(subset=['alpha2'], keep=False)]) == 0)
    # print(len(countries_alpha_3_to_2_df[countries_alpha_3_to_2_df.duplicated(subset=['alpha3'], keep=False)]) == 0)

    # Return objects
    return countries_alpha_3_to_2_df
