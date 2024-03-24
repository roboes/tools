# Geocoder

## Description

This geocoder aims to geocode structured (i.e. an address stored into multiple location variables/columns, such as country, state, city and street) and unstructured addresses (i.e. an address that is stored into one single variable/column) to a geocoded location using Nominatim/OpenStreetMap. It supports Nominatim's [structured query](https://nominatim.org/release-docs/latest/api/Search/#structured-query) and [free-form query](https://nominatim.org/release-docs/latest/api/Search/#free-form-query). The main features are:

- Geocoding (given a structured/unstructured address) and reverse geocoding (given latitude and longitude) into geocoded location using Nominatim/OpenStreetMap.
- Possibility to run the geocoder into multiple chunks, saving all chunks where the geocoder has already been run as a pickle file.
- Once a geocoded location is obtained from an address, split the location information into multiple location columns (e.g. 'location_country', 'location_state').

For structured addresses, the geocoder can be provided with the following address information/columns (from the most granular to the less granular level): `'address_amenity'`, `'address_street'`, `'address_postal_code'`, `'address_city'`, `'address_county'`, `'address_state'` and `'address_country'`. The geocoder initially uses all the provided address variables to try and find a result. If no result is found, it will remove the most granular level provided variable and iterate until a result is found or no address variables remain. The corresponding geocoding match level (e.g. "Street" level) is then assigned to each geocoded address accordingly (if no match is found, the geocoding match level is set to `None`).

The Geocoder can also be used with alternative mapping data sources, such as the Google Maps Platform API and HERE Maps API (requires code adaptation).

## Usage

The [geocoder-examples.py](./geocoder-examples.py) contains some examples and use cases on how to use the [geocoder.py](./geocoder.py).

This geocoder tool requires Python 3.12 because of the [`itertools.batched` function]([https://docs.python.org/3/library/itertools.html#itertools.batched).

## Limitations

[OSM's Nominatim](https://nominatim.openstreetmap.org) supports [an absolute maximum of 1 request per second](https://operations.osmfoundation.org/policies/nominatim/). For a higher use, a local instance of Nominatim could be installed (e.g. [nominatim-docker](https://github.com/mediagis/nominatim-docker)).

## See also

[Foreign Territories Mapping](https://github.com/scaleway/postal-address/blob/master/postal_address/territory.py): List of ISO 3166-1 alpha-2 foreign territories and their respective ISO 3166-1 alpha-2 country.
