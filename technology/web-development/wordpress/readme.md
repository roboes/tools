# WordPress Tools

## Tools

[Move your WordPress site to another domain](https://help.one.com/hc/en-us/articles/115005585969-Move-your-WordPress-site-to-another-domain)\
[Xenu's Link Sleuth](https://home.snafu.de/tilman/xenulink.html): Checks website for broken links.\
[WinSCP](https://winscp.net): Search for automatically generated WordPress media sizes using the following command:

```.sh
*-???x???.jpg
```

After removing all unused media sizes, regenerate thumbnails using [Force Regenerate Thumbnails](https://wordpress.org/plugins/force-regenerate-thumbnails/) and clean up the orphaned media entries.


## Plugins

### Content

[Connect Polylang for Elementor](https://wordpress.org/plugins/connect-polylang-elementor/): For translating Elementor's "Theme Builder" pages (e.g. 404 Page, Header, Footer).


### Optimization

[W3 Total Cache](https://wordpress.org/plugins/w3-total-cache/):
- [How to Configure W3 Total Cache & CloudFlare](https://www.thewebmaster.com/guide-to-w3-total-cache-settings-with-cloudflare/)

[WP-Optimize](https://wordpress.org/plugins/wp-optimize/):
- Delete tables left behind by old plugins (WP-Optimize > Database > Tables) - look for tables with the "not installed" and "inactive" tags.


### Tools

[Media File Renamer](https://wordpress.org/plugins/media-file-renamer/):
- Move media files within the WordPress uploads directory and automatically update the links in the database (requires pro).

[WP-Optimize](https://wordpress.org/plugins/wp-optimize/): Delete unused images (requires premium).
