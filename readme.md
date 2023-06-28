# Sports Travel Tools

## Description

This repository contains sports and travel tools and tips (including apps and utilities) used and recommended by me.

# Tools

## Apple Health Export Tools

### Usage

[apple_health_export_tools.py](https://github.com/roboes/sports-travel-tools/blob/main/apple_health_export_tools.py) is a script that perform a series of transformations to the [Apple Health .xml Export](https://support.apple.com/guide/iphone/share-your-health-data-iph5ede58c3d/ios). The main features are:
- Import Apple Health workouts/activities to a DataFrame and convert/save them as .tcx files (to upload to Strava).

### Python dependencies

```.ps1
python -m pip install numpy pandas python-dateutil
```


## Garmin Data Export Tools

### Usage

[garmin_data_export_tools.py](https://github.com/roboes/sports-travel-tools/blob/main/garmin_data_export_tools.py) is a script that perform a series of transformations to the [Garmin Data Export Request](https://www.garmin.com/en-US/account/datamanagement/exportdata/). The main features are:
- Change wrong activities filetype from .txt to .tcx, delete or move empty .fit activities files.
- Distribute files into multiple subfolders of up to 15 activities (to facilitate the upload of activities files to Strava).
- Combine multiple .tcx activity files into one .tcx file (to bulk upload to Strava - Strava will automatically separate/split these activities after upload).
- Simple script to check which activities from Garmin Connect are already on Strava.

### Python dependencies

```.ps1
python -m pip install pandas python-dateutil requests
```


## GPSmyCity to GPX converter

### Usage

[gpsmycity-to-gpx-converter.py](https://github.com/roboes/travel-tools/blob/main/gpsmycity-to-gpx-converter.py) is a script that downloads one or more self-guided GPSmyCity tours URLs as .gpx files. The .gpx files can be used in multiple apps (see [here](#apps)).

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
- `urls`: *str list*. List of GPSmyCity guide tours URLs to be imported and converted to .gpx.

# Utilities

## Cycling

### Cycling routes

[Komoot](https://www.komoot.com/discover): to download routes as .gpx files, see the [Komoot](#komoot) section.    
[Strava](https://www.strava.com/segments/explore): the best way to find cycling routes in Strava is to open a segment in the desired location and open the profiles of some athletes, viewing their activities. To download activities as .gpx files, use the [Strava GPX downloader Chrome extension](https://chrome.google.com/webstore/detail/strava-gpx-downloader/pnglhfabfkchkadgnkfacoakincdpeeg).


## Hiking

### Hiking routes
[Komoot](https://www.komoot.com/discover): to download routes as .gpx files, see the [Komoot](#komoot) section.  
[AllTrails](https://www.alltrails.com/explore): requires login to download routes as .gpx.  
[Outdooractive](https://www.outdooractive.com/en/routes/): requires login to download routes as .gpx.  
[Hiking Buddies](https://www.hiking-buddies.com/routes/routes_list/): does not require a login to download routes as .gpx.  

Files can be downloaded as a .gpx files and imported in apps (see [here](#apps)) or fitness devices (e.g. Garmin devices).


# Apps

## Organic Maps
([Website](https://organicmaps.app) | [GitHub](https://github.com/organicmaps/organicmaps) | [Android](https://play.google.com/store/apps/details?id=app.organicmaps) | [iOS](https://apps.apple.com/app/organic-maps/id1567437057))

### Description
Fork of Maps.me, is a simple and intuitive app for accessing the OpenStreetMap (OSM) map base offline. In some countries, OSM is better than Google Maps and HERE WeGo (e.g. the Morocco's medinas were not mapped in Google Maps/HERE WeGo maps base). It also allows to easily find Sight amenities (e.g. all Plitvice Lakes view points).


## OsmAnd
([Website](https://osmand.net) | [GitHub](https://github.com/osmandapp/OsmAnd) | [Android](https://play.google.com/store/apps/details?id=net.osmand.plus) | [iOS](https://apps.apple.com/app/apple-store/id934850257))

### Description  
Excellent app for cycling and hiking. Features include import and display .gpx routes, overlay specific POIs (e.g. Drinking Water).
Map base: OpenStreetMap (OSM).

### Add-ons  
[Online-maps sources for OsmAnd](https://anygis.ru/Web/Html/Osmand_en): contains various layers that can be added to OsmAnd, including Strava multiple heatmaps (all activity types, ride, run and water activities).


## Komoot
([Website](https://www.komoot.de) | [Android](https://play.google.com/store/apps/details?id=de.komoot.android) | [iOS](https://apps.apple.com/app/komoot-route-planner-gps/id447374873))

### Description
Excellent app for finding cycling/hiking routes and creating/editing routes (requires log-in). Premium subscription/purchases allow to download .gpx files to other apps (alternatively, some GitHub repositories bypass this restriction and allow to download .gpx files, as for example [KomootGPX](https://github.com/ThePBone/KomootGPX)).
Map base: OpenStreetMap (OSM).


## Mapy.cz
([Website](https://mapy.cz) | [Android](https://play.google.com/store/apps/details?id=cz.seznam.mapy) | [iOS](https://apps.apple.com/app/mapy-cz-navigation-maps/id411411020))

### Description
With a less confuding GUI than OsmAnd, offers an "Outdoor" layer, highlighting cycle and hiking paths. For cycling navigation, it displays the additional distance and time for altenative routes on the go.


# Useful links

[Amenities available on OSM](https://wiki.openstreetmap.org/wiki/Key:amenity)


# See also

[Nike Run Club Exporter](https://github.com/yasoob/nrc-exporter): download Nike Run Club activities and convert them to .gpx.  
[Torben's Strava Äpp](https://entorb.net/strava/): set of Strava tools, including the feature to import activities from an Excel/.csv to Strava.
