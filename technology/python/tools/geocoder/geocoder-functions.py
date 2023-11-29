## Geocoder
# Last update: 2023-11-24


"""Geocoder tools."""


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
    user_agent='python-geocoder',
)

geocode = RateLimiter(func=geolocator.geocode, min_delay_seconds=1)
reverse = RateLimiter(func=geolocator.reverse, min_delay_seconds=1)


###########
# Functions
###########


def df_concatenate(*, df_original, df_new):
    """Replace rows of an original DataFrame with a modified DataFrame."""
    if not df_original.empty and not df_new.empty:
        df_concatenated = pd.concat(
            [df_new, df_original[~df_original.index.isin(df_new.index)]],
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


def geocoder(*, df, query_type=None, chunk_size=50, filepath=None, fillna=None):
    """Given a DataFrame input with location columns, split it into multiple chunks and run the geocoder, saving all chunks where the geocoder has already been run as a pickle file."""
    # Create variables
    execution_start = datetime.now()

    # Make a copy of this object's indices and data
    df = df.copy(deep=True)

    for column in df.columns[df.columns.str.startswith('address_')].tolist():
        # Remove leading/trailing whitespaces
        df[column] = df[column].replace(to_replace=r'^ +| +$', value=r'', regex=True)

        # Replace multiple whitespaces by single whitespace
        df[column] = df[column].replace(to_replace=r'\s+', value=r' ', regex=True)

        # Replace blank by None
        df[column] = df[column].replace(to_replace=r'^$', value=None, regex=True)

    # Create 'location_geolocation' column if non-existent:
    if 'location_geolocation' not in df.columns:
        df['location_geolocation'] = None

    # Create empty DataFrame
    df_geolocation = pd.DataFrame(data=None, index=None, columns=None, dtype=None)

    # Slice DataFrame into multiple chunks and run the geocoder for empty 'location_geolocation'
    for batch in batched(iterable=range(len(df)), n=chunk_size):
        df_chunk = df.iloc[min(batch) : max(batch) + 1].copy()
        df_chunk['location_geolocation'] = df_chunk.apply(
            lambda row: geocode(
                query={
                    **(
                        {'countrycodes': row['address_country_code']}
                        if 'address_country_code' in df.columns
                        and pd.notna(row['address_country_code'])
                        else {}
                    ),
                    **(
                        {'country': row['address_country']}
                        if 'address_country' in df.columns
                        and pd.notna(row['address_country'])
                        else {}
                    ),
                    **(
                        {'state': row['address_state']}
                        if 'address_state' in df.columns
                        and pd.notna(row['address_state'])
                        else {}
                    ),
                    **(
                        {'county': row['address_county']}
                        if 'address_county' in df.columns
                        and pd.notna(row['address_county'])
                        else {}
                    ),
                    **(
                        {'city': row['address_city']}
                        if 'address_city' in df.columns
                        and pd.notna(row['address_city'])
                        else {}
                    ),
                    **(
                        {'postalcode': row['address_postal_code']}
                        if 'address_postal_code' in df.columns
                        and pd.notna(row['address_postal_code'])
                        else {}
                    ),
                    **(
                        {'street': row['address_street']}
                        if 'address_street' in df.columns
                        and pd.notna(row['address_street'])
                        else {}
                    ),
                    **(
                        {'amenity': row['address_amenity']}
                        if 'address_amenity' in df.columns
                        and pd.notna(row['address_amenity'])
                        else {}
                    ),
                }
                if query_type == 'structured'
                else row['address_location'],
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
    """Given the 'location_geolocation' column, split the location information into multiple location columns."""
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
