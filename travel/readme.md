# Travel Tools

## GPSmyCity to GPX converter

### Usage

[gpsmycity-to-gpx-converter.py](gpsmycity-to-gpx-converter.py) is a script that downloads one or multiple self-guided GPSmyCity tours URLs as .gpx files. The .gpx files can be used in multiple apps (see [here](#apps)).

### Python dependencies

```.ps1
python -m pip install gpxpy pandas werkzeug
```

### Functions

#### gpsmycity_tour_import

```.py
gpsmycity_tour_import(urls=['https://www.gpsmycity.com/tours/munich-introduction-walking-tour-6446.html', 'https://www.gpsmycity.com/blog/main-sights-to-see-in-augsburg-3414.html', 'https://www.gpsmycity.com/tours/edinburgh-introduction-walking-tour-6397.html'])
```

#### Description

- Imports GPSmyCity guide tours, converting these to a .gpx file.

#### Parameters

- `urls`: _str list_. List of GPSmyCity guide tours URLs to be imported and converted to .gpx.

## Apps

### Organic Maps

([Website](https://organicmaps.app) | [GitHub](https://github.com/organicmaps/organicmaps) | [Android](https://play.google.com/store/apps/details?id=app.organicmaps) | [iOS](https://apps.apple.com/app/organic-maps/id1567437057))

#### Description

Simple and intuitive app for accessing the OpenStreetMap (OSM) map base offline. In some countries, OSM is better than Google Maps and HERE WeGo (e.g. the Morocco's medinas were not mapped in Google Maps/HERE WeGo maps base). It also allows to easily find Sight amenities (e.g. all Plitvice Lakes view points).

## Useful links

### Tools

[direkt.bahn.guru](https://direkt.bahn.guru/?origin=8000261&local=true): All direct long-distance railway connections from a given city.\
[Overpass Turbo](https://wiki.openstreetmap.org/wiki/Overpass_turbo): Run Overpass API queries in OpenStreetMap (OSM).\
[Level0](https://wiki.openstreetmap.org/wiki/Level0): OpenStreetMap (OSM) browser-based editor, useful for adding Point of Interests (POIs) given latitude and longitude.

### Trip Planners

[Wanderlog](https://wanderlog.com)\
[GPSmyCity](https://www.gpsmycity.com)\
[Visit A City](https://www.visitacity.com)\
[Tripomatic](https://maps.tripomatic.com)\
[Lonely Planet](https://www.lonelyplanet.com)\
[RoutePerfect](https://www.routeperfect.com)\
[Smarter Travel](https://www.smartertravel.com)\
[Culture Trip](https://theculturetrip.com)
