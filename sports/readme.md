# Sports Tools

## Tracking

[Strava](https://www.strava.com)

Strava add-ons:\
[StatsHunters](https://www.statshunters.com): Heatmaps and statistics of Strava activities.\
[Project Kodos](https://labs.strava.com/kodos/): Kudos statistics.\
[The Roster](https://labs.strava.com/roster/): Visually analyze your athletic social habits, total group activities and preferred training partners.

## Utilities

### Cycling

#### Routes

[Komoot](https://www.komoot.com/discover): To download routes as .gpx files, see the [Komoot](#komoot) section.\
[Strava](https://www.strava.com/segments/explore): The best way to find cycling routes in Strava is to open a segment in the desired location and open the profiles of some athletes, viewing their activities. It is possible to download activities as .gpx files.\
[Wikiloc](https://wikiloc.com): Best app for finding routes in Brazil.

##### Munich, Germany

[Great Rides München Nord](https://ridewithgps.com/clubs/4756-great-rides-munchen-nord/routes)

### Hiking

#### Routes

[Komoot](https://www.komoot.com/discover): To download routes as .gpx files, see the [Komoot](#komoot) section.\
[Wikiloc](https://wikiloc.com): Best app for finding routes in Brazil.\
[AllTrails](https://www.alltrails.com/explore): Requires login to download routes as .gpx.\
[Outdooractive](https://www.outdooractive.com/en/routes/): Requires login to download routes as .gpx.\
[Hiking Buddies](https://www.hiking-buddies.com/routes/routes_list/): Does not require a login to download routes as .gpx.

Files can be downloaded as a .gpx files and imported in apps (see [here](#apps)) or fitness devices (e.g. Garmin devices).

## Apps

### Komoot

([Website](https://www.komoot.de) | [Android](https://play.google.com/store/apps/details?id=de.komoot.android) | [iOS](https://apps.apple.com/app/komoot-route-planner-gps/id447374873))

#### Description

Excellent app for finding cycling/hiking routes and creating/editing routes (requires log-in). Premium subscription/purchases allow to download .gpx files to other apps (alternatively, some GitHub repositories bypass this restriction and allow to download .gpx files, as for example [KomootGPX](https://github.com/ThePBone/KomootGPX). To run it: `python -m komootgpx --mail="mail_address" --pass="password" --filter="planned" --output="./"`).\
Map base: OpenStreetMap (OSM).

### Mapy.cz

([Website](https://mapy.cz) | [Android](https://play.google.com/store/apps/details?id=cz.seznam.mapy) | [iOS](https://apps.apple.com/app/mapy-cz-navigation-maps/id411411020))

#### Description

With a less confuding GUI than OsmAnd, offers an "Outdoor" layer, highlighting cycle and hiking paths. For cycling navigation, it displays the additional distance and time for alternative routes on the go.

### OsmAnd

([Website](https://osmand.net) | [GitHub](https://github.com/osmandapp/OsmAnd) | [Android](https://play.google.com/store/apps/details?id=net.osmand.plus) | [iOS](https://apps.apple.com/app/apple-store/id934850257))

#### Description

Excellent app for cycling and hiking. Features include import and display .gpx routes, overlay specific POIs (e.g. Drinking Water).\
Map base: OpenStreetMap (OSM).

#### Add-ons

[Online-maps sources for OsmAnd](https://anygis.ru/Web/Html/Osmand_en): Contains various layers that can be added to OsmAnd, including Strava multiple heatmaps (all activity types, ride, run and water activities).

### Apple Health Export Tools

#### Usage

[apple_health_export_tools.py](apple_health_export_tools.py) is a script that performs a series of transformations to the [Apple Health .xml Export](https://support.apple.com/guide/iphone/share-your-health-data-iph5ede58c3d/ios). The main features are:

- Import Apple Health workouts/activities to a DataFrame and convert/save them as .tcx files (to upload to Strava).

#### Python dependencies

```.ps1
python -m pip install numpy pandas python-dateutil
```

### Garmin Data Export Tools

#### Usage

[garmin_data_export_tools.py](garmin_data_export_tools.py) is a script that performs a series of transformations to the [Garmin Data Export Request](https://www.garmin.com/en-US/account/datamanagement/exportdata/). The main features are:

- Change wrong activities filetype from .txt to .tcx, delete or move empty .fit activities files.
- Distribute files into multiple subfolders of up to 15 activities (to facilitate the upload of activities files to Strava).
- Simple script to check which activities from Garmin Connect are already on Strava.

#### Python dependencies

```.ps1
python -m pip install openpyxl pandas python-dateutil requests
```

### .tcx Tools

#### Usage

[tcx_tools.py](tcx_tools.py) is a script that performs a series of transformations to the Training Center XML (.tcx) workout data file. The main features are:

- Remove leading first line blank spaces of .tcx activities files for properly importing it.
- Combine multiple .tcx activity files into one .tcx file (to bulk upload to Strava - Strava will automatically separate/split these activities after upload).

This script intends to be used to migrate activities from [Garmin Data Export Request](https://www.garmin.com/en-US/account/datamanagement/exportdata/) and from [Nike Run Club Export](https://www.nike.com/help/privacy) to Strava.

#### Python dependencies

None.

## Useful links

[Amenities available in OSM](https://wiki.openstreetmap.org/wiki/Key:amenity)\
[Online-maps sources for OsmAnd](https://anygis.ru/Web/Html/Osmand_en): Contains various layers that can be added to OsmAnd, including Strava multiple heatmaps (all activity types, ride, run and water activities).\
[dérive - Generate a heatmap from GPS tracks](https://erik.github.io/derive/): Generate heatmap by drag and dropping one or more .gpx/.tcx/.fit/.igc/.skiz file(s).\
[BBBike](https://garmin.bbbike.org): Extracts OpenStreetMap maps for Garmin devices.

## See also

[Workout Transferer](https://github.com/andre0707/WorkoutTransferer): Read and export Apple Health activities.\
[Nike Run Club Exporter](https://github.com/yasoob/nrc-exporter): Download Nike Run Club activities and convert them to .gpx.\
[Torben's Strava Äpp](https://entorb.net/strava/): Set of Strava tools, including the feature to import activities from an Excel/.csv to Strava.
