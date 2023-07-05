# Sports Tools

## Apple Health Export Tools

### Usage

[apple_health_export_tools.py](apple_health_export_tools.py) is a script that perform a series of transformations to the [Apple Health .xml Export](https://support.apple.com/guide/iphone/share-your-health-data-iph5ede58c3d/ios). The main features are:
- Import Apple Health workouts/activities to a DataFrame and convert/save them as .tcx files (to upload to Strava).

### Python dependencies

```.ps1
python -m pip install numpy pandas python-dateutil
```


## Garmin Data Export Tools

### Usage

[garmin_data_export_tools.py](garmin_data_export_tools.py) is a script that perform a series of transformations to the [Garmin Data Export Request](https://www.garmin.com/en-US/account/datamanagement/exportdata/). The main features are:
- Change wrong activities filetype from .txt to .tcx, delete or move empty .fit activities files.
- Distribute files into multiple subfolders of up to 15 activities (to facilitate the upload of activities files to Strava).
- Combine multiple .tcx activity files into one .tcx file (to bulk upload to Strava - Strava will automatically separate/split these activities after upload).
- Simple script to check which activities from Garmin Connect are already on Strava.

### Python dependencies

```.ps1
python -m pip install pandas python-dateutil requests
```


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

[Amenities available in OSM](https://wiki.openstreetmap.org/wiki/Key:amenity)


# See also

[Nike Run Club Exporter](https://github.com/yasoob/nrc-exporter): Download Nike Run Club activities and convert them to .gpx.
[Torben's Strava Äpp](https://entorb.net/strava/): Set of Strava tools, including the feature to import activities from an Excel/.csv to Strava.
