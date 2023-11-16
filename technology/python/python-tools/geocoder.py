## Geocoder
# Last update: 2023-11-16


"""Geolocation tools."""


###############
# Initial Setup
###############

# Import packages
from datetime import datetime
from itertools import batched

from geopy.extra.rate_limiter import RateLimiter
from geopy.geocoders import Nominatim
import pandas as pd


# Geocoder setup
geolocator = Nominatim(
    domain='nominatim.openstreetmap.org',
    scheme='https',
    user_agent='python-tools',
)

geocode = RateLimiter(func=geolocator.geocode, min_delay_seconds=1)
reverse = RateLimiter(func=geolocator.reverse, min_delay_seconds=1)


###########
# Functions
###########


def df_geolocation_concatenate(*, df, df_slice):
    """Import chunks where the geocoder has already been run and concatenate it with the original dataset."""
    if not df.empty and not df_slice.empty:
        df_concatenated = pd.concat(
            [df_slice, df[df.index.isin(df_slice.index) == False]],
            axis=0,
            ignore_index=False,
            sort=False,
        )
        df_concatenated = df_concatenated.sort_index(
            axis=0,
            level=None,
            ascending=True,
            kind='quicksort',
            ignore_index=False,
        )

        # Return objects
        return df_concatenated

    else:
        pass


def geocoder(*, df, chunk_size=50, filepath=None, fillna=None):
    """Given a DataFrame input with location columns, split it into multiple chunks and run the geocoder, saving all chunks where the geocoder has already been run as a pickle file."""
    # Create variables
    execution_start = datetime.now()

    # Create 'location_geolocation' column if non-existent:
    if 'location_geolocation' not in df:
        df['location_geolocation'] = None

    # Data transformation
    df = df.fillna(
        value={
            'address_country': '',
            'address_state': '',
            'address_city': '',
            'address_postal_code': '',
            'address_street': '',
        },
        method=None,
        axis=0,
    )

    # Create empty DataFrame
    df_geolocation = pd.DataFrame(data=None, index=None, columns=None, dtype=None)

    # Slice DataFrame into multiple chunks and run the geocoder for empty 'location_geolocation'
    for batch in batched(iterable=range(len(df)), n=chunk_size):
        df_chunk = df.iloc[min(batch) : max(batch) + 1].copy()
        df_chunk['location_geolocation'] = df_chunk.apply(
            lambda row: geocode(
                query={
                    'country': row['address_country'],
                    'state': row['address_state'],
                    'city': row['address_city'],
                    'postalcode': row['address_postal_code'],
                    'street': row['address_street'],
                },
                exactly_one=True,
                addressdetails=True,
                extratags=False,
                namedetails=True,
                language='en',
                timeout=None,
            )
            if pd.isna(row['location_geolocation'])
            else row['location_geolocation'],
            axis=1,
        )

        if fillna is not None:
            # Fill not found locations with value
            df_chunk['location_geolocation'] = df_chunk['location_geolocation'].fillna(
                value=fillna,
                method=None,
                axis=0,
            )

        # Concatenate DataFrames
        if not df_chunk.empty:
            df_geolocation = pd.concat(
                [df_geolocation, df_chunk],
                axis=0,
                ignore_index=False,
                sort=False,
            )

        # Save all chunks where the geocoder has already been run
        if not df_geolocation.empty and filepath is not None:
            df_geolocation.to_pickle(path=filepath)

    # Execution time
    execution_time = datetime.now() - execution_start
    print(f'Execution time: {execution_time}')

    # Return objects
    return df_geolocation


def geocoder_location_columns(*, df_geo):
    """Given the 'location_geolocation' column, split geolocation information into multiple location columns."""
    # location_country
    df_geo['location_country'] = df_geo.apply(
        lambda row: row['location_geolocation'].raw.get('address').get('country')
        if pd.notna(row['location_geolocation'])
        else None,
        axis=1,
    )

    # location_country_code
    df_geo['location_country_code'] = df_geo.apply(
        lambda row: row['location_geolocation'].raw.get('address').get('country_code')
        if pd.notna(row['location_geolocation'])
        else None,
        axis=1,
    )

    # location_state
    df_geo['location_state'] = df_geo.apply(
        lambda row: row['location_geolocation'].raw.get('address').get('state')
        if pd.notna(row['location_geolocation'])
        else None,
        axis=1,
    )

    # location_county
    df_geo['location_county'] = df_geo.apply(
        lambda row: row['location_geolocation'].raw.get('address').get('county')
        if pd.notna(row['location_geolocation'])
        else None,
        axis=1,
    )

    # location_city
    df_geo['location_city'] = df_geo.apply(
        lambda row: row['location_geolocation'].raw.get('address').get('city')
        if pd.notna(row['location_geolocation'])
        else None,
        axis=1,
    )

    # location_suburb
    df_geo['location_suburb'] = df_geo.apply(
        lambda row: row['location_geolocation'].raw.get('address').get('suburb')
        if pd.notna(row['location_geolocation'])
        else None,
        axis=1,
    )

    # location_borough
    df_geo['location_borough'] = df_geo.apply(
        lambda row: row['location_geolocation'].raw.get('address').get('borough')
        if pd.notna(row['location_geolocation'])
        else None,
        axis=1,
    )

    # location_province
    df_geo['location_province'] = df_geo.apply(
        lambda row: row['location_geolocation'].raw.get('address').get('province')
        if pd.notna(row['location_geolocation'])
        else None,
        axis=1,
    )

    # location_district
    df_geo['location_district'] = df_geo.apply(
        lambda row: row['location_geolocation'].raw.get('address').get('district')
        if pd.notna(row['location_geolocation'])
        else None,
        axis=1,
    )

    # location_subdistrict
    df_geo['location_subdistrict'] = df_geo.apply(
        lambda row: row['location_geolocation'].raw.get('address').get('subdistrict')
        if pd.notna(row['location_geolocation'])
        else None,
        axis=1,
    )

    # location_industrial
    df_geo['location_industrial'] = df_geo.apply(
        lambda row: row['location_geolocation'].raw.get('address').get('industrial')
        if pd.notna(row['location_geolocation'])
        else None,
        axis=1,
    )

    # location_postal_code
    df_geo['location_postal_code'] = df_geo.apply(
        lambda row: row['location_geolocation'].raw.get('address').get('postcode')
        if pd.notna(row['location_geolocation'])
        else None,
        axis=1,
    )

    # location_road
    df_geo['location_road'] = df_geo.apply(
        lambda row: row['location_geolocation'].raw.get('address').get('road')
        if pd.notna(row['location_geolocation'])
        else None,
        axis=1,
    )

    # location_hamlet
    df_geo['location_hamlet'] = df_geo.apply(
        lambda row: row['location_geolocation'].raw.get('address').get('hamlet')
        if pd.notna(row['location_geolocation'])
        else None,
        axis=1,
    )

    # location_house_number
    df_geo['location_house_number'] = df_geo.apply(
        lambda row: row['location_geolocation'].raw.get('address').get('house_number')
        if pd.notna(row['location_geolocation'])
        else None,
        axis=1,
    )

    # location_amenity
    df_geo['location_amenity'] = df_geo.apply(
        lambda row: row['location_geolocation'].raw.get('address').get('amenity')
        if pd.notna(row['location_geolocation'])
        else None,
        axis=1,
    )

    # location_building
    df_geo['location_building'] = df_geo.apply(
        lambda row: row['location_geolocation'].raw.get('address').get('building')
        if pd.notna(row['location_geolocation'])
        else None,
        axis=1,
    )

    # location_addresstype
    df_geo['location_addresstype'] = df_geo.apply(
        lambda row: row['location_geolocation'].raw.get('addresstype')
        if pd.notna(row['location_geolocation'])
        else None,
        axis=1,
    )

    # location_type
    df_geo['location_type'] = df_geo.apply(
        lambda row: row['location_geolocation'].raw.get('type')
        if pd.notna(row['location_geolocation'])
        else None,
        axis=1,
    )

    # location_class
    df_geo['location_class'] = df_geo.apply(
        lambda row: row['location_geolocation'].raw.get('class')
        if pd.notna(row['location_geolocation'])
        else None,
        axis=1,
    )

    # location_name
    df_geo['location_name'] = df_geo.apply(
        lambda row: row['location_geolocation'].raw.get('name')
        if pd.notna(row['location_geolocation'])
        else None,
        axis=1,
    )

    # location_latitude
    df_geo['location_latitude'] = df_geo.apply(
        lambda row: row['location_geolocation'].raw.get('lat')
        if pd.notna(row['location_geolocation'])
        else None,
        axis=1,
    )

    # location_longitude
    df_geo['location_longitude'] = df_geo.apply(
        lambda row: row['location_geolocation'].raw.get('lon')
        if pd.notna(row['location_geolocation'])
        else None,
        axis=1,
    )

    # Return objects
    return df_geo
