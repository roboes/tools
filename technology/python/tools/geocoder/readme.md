# Geocoder

## Description

This geocoder tool aims to geocode structured (i.e. an address stored into multiple location variables/columns, such as country, state, city and street) and unstructured addresses (i.e. an address that is stored into one single variable/column) to a geocoded location using Nominatim/OpenStreetMap. It supports Nominatim's [structured query](https://nominatim.org/release-docs/latest/api/Search/#structured-query) and [free-form query](https://nominatim.org/release-docs/latest/api/Search/#free-form-query). The main features are:
- Geocoding (given a structured/unstructured address) and reverse geocoding (given latitude and longitude) into geocoded location using Nominatim/OpenStreetMap.
- Possibility to run the geocoder into multiple chunks, saving all chunks where the geocoder has already been run as a pickle file.
- Once a geocoded location is obtained from an address, split the location information into multiple location columns (e.g. 'location_country', 'location_state').


## Usage

The [geocoder_test.py](geocoder_test.py) contains some examples and use cases on how to use the [geocoder_functions.py](geocoder_functions.py).

This geocoder tool requires Python 3.12 because of the [`itertools.batched` function]([https://docs.python.org/3/library/itertools.html#itertools.batched).


## Limitations

[OSM's Nominatim](https://nominatim.openstreetmap.org) supports [an absolute maximum of 1 request per second](https://operations.osmfoundation.org/policies/nominatim/). For a higher use, a local instance of Nominatim could be installed (e.g. [nominatim-docker](https://github.com/mediagis/nominatim-docker)).


## See also

[Foreign Territories Mapping](https://github.com/scaleway/postal-address/blob/master/postal_address/territory.py): List of ISO 3166-1 alpha-2 foreign territories and their respective ISO 3166-1 alpha-2 country.
