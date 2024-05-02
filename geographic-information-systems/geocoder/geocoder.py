## Geocoder
# Last update: 2024-03-18


"""About: Geocoder."""


###############
# Initial Setup
###############

# Import packages
from datetime import datetime
from itertools import batched

from geopy.extra.rate_limiter import RateLimiter
from geopy.geocoders import Nominatim
import pandas as pd


# Settings

## Geocoder
geolocator = Nominatim(
    domain='nominatim.openstreetmap.org',  # Public Nominatim instance
    scheme='https',
    user_agent='python-geocoder',
)
geocode = RateLimiter(
    func=geolocator.geocode,
    min_delay_seconds=(1 if geolocator.domain == 'nominatim.openstreetmap.org' else 0),
)
reverse = RateLimiter(
    func=geolocator.reverse,
    min_delay_seconds=(1 if geolocator.domain == 'nominatim.openstreetmap.org' else 0),
)

## Copy-on-Write (will be enabled by default in version 3.0)
if pd.__version__ >= '1.5.0' and pd.__version__ < '3.0.0':
    pd.options.mode.copy_on_write = True


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


def geocoder_query(*, df, row, query_type, foreign_territories_mapping=False):
    """Pass arguments to the geocode query."""
    if foreign_territories_mapping is True and 'address_country_codes_filter' in df.columns:
        # Foreign territories mapping dictionary - Source: https://github.com/scaleway/postal-address/blob/master/postal_address/territory.py
        foreign_territories_mapping = {
            'cc': 'au',  # Cocos Island,                      Australian territory
            'hm': 'au',  # Heard Island and McDonald Islands, Australian territory
            'hk': 'cn',  # Hong Kong,                         Chinese territory
            'mo': 'cn',  # Macao,                             Chinese territory
            'fo': 'dk',  # Faroe Islands,                     Danish territory
            'ax': 'fi',  # Åland,                             Finnish territory
            'aq': 'fr',  # Antarctica,                        French territory
            'bl': 'fr',  # Saint Barthelemy,                  French territory
            'gf': 'fr',  # French Guiana,                     French territory
            'gp': 'fr',  # Guadeloupe,                        French territory
            'gy': 'fr',  # Guyana,                            French territory
            'mf': 'fr',  # Saint Martin,                      French territory
            'mq': 'fr',  # Martinique,                        French territory
            'nc': 'fr',  # New Caledonia,                     French territory
            'pf': 'fr',  # French Polynesia,                  French territory
            'pm': 'fr',  # Saint Pierre and Miquelon,         French territory
            're': 'fr',  # Reunion,                           French territory
            'tf': 'fr',  # French Southern Territories,       French territory
            'wf': 'fr',  # Wallis and Futuna,                 French territory
            'yt': 'fr',  # Mayotte,                           French territory
            'gi': 'gb',  # Gibraltar,                         British territory
            'im': 'gb',  # Isle of Man,                       British territory
            'io': 'gb',  # British Indian Ocean Territory,    British territory
            'je': 'gb',  # Jersey,                            British territory
            'pn': 'gb',  # Pitcairn,                          British territory
            'sh': 'gb',  # Saint Helena,                      British territory
            'vg': 'gb',  # British Virgin Islands,            British territory
            'bq': 'nl',  # Bonaire,                           Dutch territory
            'sx': 'nl',  # Sint Maarten,                      Dutch territory
            'bv': 'no',  # Bouvet Island,                     Norwegian territory
            'sj': 'no',  # Svalbard and Jan Mayen,            Norwegian territory
            'as': 'us',  # American Samoa,                    American territory
            'gu': 'us',  # Guam,                              American territory
            'mp': 'us',  # Northern Mariana Islands,          American territory
            'vi': 'us',  # US Virgin Islands,                 American territory
        }

    else:
        foreign_territories_mapping = {}

    geolocation = geocode(
        query=(
            {
                **(
                    {
                        'countrycodes': foreign_territories_mapping.get(
                            row['address_country_codes_filter'].lower(),
                            row['address_country_codes_filter'],
                        ),
                    }
                    if 'address_country_codes_filter' in df.columns and pd.notna(row['address_country_codes_filter']) and row['address_country_codes_filter'] != ''
                    else {}
                ),
                **({'country': row['address_country']} if 'address_country' in df.columns and pd.notna(row['address_country']) and row['address_country'] != '' else {}),
                **({'state': row['address_state']} if 'address_state' in df.columns and pd.notna(row['address_state']) and row['address_state'] != '' else {}),
                **({'county': row['address_county']} if 'address_county' in df.columns and pd.notna(row['address_county']) and row['address_county'] != '' else {}),
                **({'city': row['address_city']} if 'address_city' in df.columns and pd.notna(row['address_city']) and row['address_city'] != '' else {}),
                **({'postalcode': row['address_postal_code']} if 'address_postal_code' in df.columns and pd.notna(row['address_postal_code']) and row['address_postal_code'] != '' else {}),
                **({'street': row['address_street']} if 'address_street' in df.columns and pd.notna(row['address_street']) and row['address_street'] != '' else {}),
                **({'amenity': row['address_amenity']} if 'address_amenity' in df.columns and pd.notna(row['address_amenity']) and row['address_amenity'] != '' else {}),
            }
            if query_type == 'structured'
            else row['address_location']
        ),
        exactly_one=True,
        addressdetails=True,
        extratags=False,
        namedetails=True,
        language='en',
        timeout=None,
    )

    # Return objects
    if geolocation is not None:
        return geolocation

    else:
        return None


def geocoder(
    *,
    df,
    query_type=None,
    chunk_size=None,
    filepath=None,
    fillna=None,
    foreign_territories_mapping=False,
):
    """Given a DataFrame input with location columns, split it into multiple chunks and run the geocoder, saving all chunks where the geocoder has already been run as a pickle file."""
    # Create variables
    execution_start = datetime.now()

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

    # Create 'geocoding_match_level' column if non-existent:
    if 'geocoding_match_level' not in df.columns:
        df['geocoding_match_level'] = None

    # Create empty DataFrame
    if chunk_size is not None and filepath is not None:
        df_geolocation = pd.DataFrame(data=None, index=None, columns=None, dtype=None)

    if query_type == 'structured':
        # Address columns
        address_columns = [
            'address_amenity',
            'address_street',
            'address_postal_code',
            'address_city',
            'address_county',
            'address_state',
            'address_country',
        ]

        # Geocoding match level mapping dictionary
        geocoding_match_level_mapping = {
            'address_amenity': 'Amenity',
            'address_street': 'Street',
            'address_postal_code': 'Postal Code',
            'address_city': 'City',
            'address_county': 'County',
            'address_state': 'State',
            'address_country': 'Country',
        }

    # Slice DataFrame into multiple chunks and run the geocoder for empty 'location_geolocation'
    if chunk_size is not None and filepath is not None:
        for batch in batched(iterable=range(len(df)), n=chunk_size):
            df_chunk = df.iloc[min(batch) : max(batch) + 1]

            for index, row in df_chunk.iterrows():
                if query_type == 'structured':
                    for i, address_column in enumerate(address_columns):
                        if pd.isna(row['location_geolocation']) and address_column in df_chunk.columns:
                            if pd.notna(row[address_column]):
                                geolocation = geocoder_query(
                                    df=df_chunk.drop(
                                        columns=address_columns[:i],
                                        axis=1,
                                        errors='ignore',
                                    ),
                                    row=row,
                                    query_type=query_type,
                                    foreign_territories_mapping=foreign_territories_mapping,
                                )

                                if geolocation is not None:
                                    df_chunk.at[index, 'location_geolocation'] = geolocation
                                    df_chunk.at[
                                        index,
                                        'geocoding_match_level',
                                    ] = geocoding_match_level_mapping.get(
                                        address_column,
                                        address_column,
                                    )

                                    break

                            else:
                                pass

                elif query_type == 'free-form':
                    row['location_geolocation'] = geocoder_query(
                        df=df_chunk,
                        row=row,
                        query_type=query_type,
                        foreign_territories_mapping=foreign_territories_mapping,
                    )

                    geolocation = geocoder_query(
                        df=df_chunk,
                        row=row,
                        query_type=query_type,
                        foreign_territories_mapping=foreign_territories_mapping,
                    )

                    if geolocation is not None:
                        df_chunk.at[index, 'location_geolocation'] = geolocation
                        df_chunk.at[index, 'geocoding_match_level'] = None

                if fillna is not None:
                    # Fill not found locations with value
                    df_chunk['location_geolocation'] = df_chunk['location_geolocation'].fillna(value=fillna, method=None, axis=0)

            # Concatenate DataFrames
            if not df_chunk.empty:
                df_geolocation = pd.concat(
                    [df_geolocation, df_chunk],
                    axis=0,
                    ignore_index=False,
                    sort=False,
                )

            # Save all chunks where the geocoder has already been run
            if not df_geolocation.empty:
                df_geolocation.to_pickle(path=filepath)

    else:
        for index, row in df.iterrows():
            if query_type == 'structured':
                for i, address_column in enumerate(address_columns):
                    if pd.isna(row['location_geolocation']) and address_column in df.columns:
                        if pd.notna(row[address_column]):
                            geolocation = geocoder_query(
                                df=df.drop(
                                    columns=address_columns[:i],
                                    axis=1,
                                    errors='ignore',
                                ),
                                row=row,
                                query_type=query_type,
                                foreign_territories_mapping=foreign_territories_mapping,
                            )

                            if geolocation is not None:
                                df.at[index, 'location_geolocation'] = geolocation
                                df.at[
                                    index,
                                    'geocoding_match_level',
                                ] = geocoding_match_level_mapping.get(
                                    address_column,
                                    address_column,
                                )

                                break

                        else:
                            pass

            elif query_type == 'free-form':
                row['location_geolocation'] = geocoder_query(
                    df=df,
                    row=row,
                    query_type=query_type,
                    foreign_territories_mapping=foreign_territories_mapping,
                )

                geolocation = geocoder_query(
                    df=df,
                    row=row,
                    query_type=query_type,
                    foreign_territories_mapping=foreign_territories_mapping,
                )

                if geolocation is not None:
                    df.at[index, 'location_geolocation'] = geolocation
                    df.at[index, 'geocoding_match_level'] = None

            if fillna is not None:
                # Fill not found locations with value
                df['location_geolocation'] = df['location_geolocation'].fillna(
                    value=fillna,
                    method=None,
                    axis=0,
                )

            # Concatenate DataFrames
            if not df.empty:
                df_geolocation = df

            else:
                df_geolocation = None

    # Execution time
    print(f'Execution time: {datetime.now() - execution_start}')

    # Return objects
    return df_geolocation


def geocoder_location_columns(*, df_geo):
    """Given the 'location_geolocation' column, split the location information into multiple location columns."""
    # location_country
    df_geo['location_country'] = df_geo.apply(
        lambda row: (row['location_geolocation'].raw.get('address').get('country') if pd.notna(row['location_geolocation']) else None),
        axis=1,
    )

    # location_country_code
    df_geo['location_country_code'] = df_geo.apply(
        lambda row: (row['location_geolocation'].raw.get('address').get('country_code') if pd.notna(row['location_geolocation']) else None),
        axis=1,
    )

    # location_state
    df_geo['location_state'] = df_geo.apply(
        lambda row: (row['location_geolocation'].raw.get('address').get('state') if pd.notna(row['location_geolocation']) else None),
        axis=1,
    )

    # location_county
    df_geo['location_county'] = df_geo.apply(
        lambda row: (row['location_geolocation'].raw.get('address').get('county') if pd.notna(row['location_geolocation']) else None),
        axis=1,
    )

    # location_city
    df_geo['location_city'] = df_geo.apply(
        lambda row: (row['location_geolocation'].raw.get('address').get('city') if pd.notna(row['location_geolocation']) else None),
        axis=1,
    )

    # location_town
    df_geo['location_town'] = df_geo.apply(
        lambda row: (row['location_geolocation'].raw.get('address').get('town') if pd.notna(row['location_geolocation']) else None),
        axis=1,
    )

    # location_suburb
    df_geo['location_suburb'] = df_geo.apply(
        lambda row: (row['location_geolocation'].raw.get('address').get('suburb') if pd.notna(row['location_geolocation']) else None),
        axis=1,
    )

    # location_borough
    df_geo['location_borough'] = df_geo.apply(
        lambda row: (row['location_geolocation'].raw.get('address').get('borough') if pd.notna(row['location_geolocation']) else None),
        axis=1,
    )

    # location_province
    df_geo['location_province'] = df_geo.apply(
        lambda row: (row['location_geolocation'].raw.get('address').get('province') if pd.notna(row['location_geolocation']) else None),
        axis=1,
    )

    # location_district
    df_geo['location_district'] = df_geo.apply(
        lambda row: (row['location_geolocation'].raw.get('address').get('district') if pd.notna(row['location_geolocation']) else None),
        axis=1,
    )

    # location_subdistrict
    df_geo['location_subdistrict'] = df_geo.apply(
        lambda row: (row['location_geolocation'].raw.get('address').get('subdistrict') if pd.notna(row['location_geolocation']) else None),
        axis=1,
    )

    # location_industrial
    df_geo['location_industrial'] = df_geo.apply(
        lambda row: (row['location_geolocation'].raw.get('address').get('industrial') if pd.notna(row['location_geolocation']) else None),
        axis=1,
    )

    # location_postal_code
    df_geo['location_postal_code'] = df_geo.apply(
        lambda row: (row['location_geolocation'].raw.get('address').get('postcode') if pd.notna(row['location_geolocation']) else None),
        axis=1,
    )

    # location_road
    df_geo['location_road'] = df_geo.apply(
        lambda row: (row['location_geolocation'].raw.get('address').get('road') if pd.notna(row['location_geolocation']) else None),
        axis=1,
    )

    # location_hamlet
    df_geo['location_hamlet'] = df_geo.apply(
        lambda row: (row['location_geolocation'].raw.get('address').get('hamlet') if pd.notna(row['location_geolocation']) else None),
        axis=1,
    )

    # location_house_number
    df_geo['location_house_number'] = df_geo.apply(
        lambda row: (row['location_geolocation'].raw.get('address').get('house_number') if pd.notna(row['location_geolocation']) else None),
        axis=1,
    )

    # location_amenity
    df_geo['location_amenity'] = df_geo.apply(
        lambda row: (row['location_geolocation'].raw.get('address').get('amenity') if pd.notna(row['location_geolocation']) else None),
        axis=1,
    )

    # location_building
    df_geo['location_building'] = df_geo.apply(
        lambda row: (row['location_geolocation'].raw.get('address').get('building') if pd.notna(row['location_geolocation']) else None),
        axis=1,
    )

    # location_addresstype
    df_geo['location_addresstype'] = df_geo.apply(
        lambda row: (row['location_geolocation'].raw.get('addresstype') if pd.notna(row['location_geolocation']) else None),
        axis=1,
    )

    # location_type
    df_geo['location_type'] = df_geo.apply(
        lambda row: (row['location_geolocation'].raw.get('type') if pd.notna(row['location_geolocation']) else None),
        axis=1,
    )

    # location_class
    df_geo['location_class'] = df_geo.apply(
        lambda row: (row['location_geolocation'].raw.get('class') if pd.notna(row['location_geolocation']) else None),
        axis=1,
    )

    # location_name
    df_geo['location_name'] = df_geo.apply(
        lambda row: (row['location_geolocation'].raw.get('name') if pd.notna(row['location_geolocation']) else None),
        axis=1,
    )

    # location_latitude
    df_geo['location_latitude'] = df_geo.apply(
        lambda row: (row['location_geolocation'].raw.get('lat') if pd.notna(row['location_geolocation']) else None),
        axis=1,
    )

    # location_longitude
    df_geo['location_longitude'] = df_geo.apply(
        lambda row: (row['location_geolocation'].raw.get('lon') if pd.notna(row['location_geolocation']) else None),
        axis=1,
    )

    # Return objects
    return df_geo
