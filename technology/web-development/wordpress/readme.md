# WordPress Tools

## Content

[Skelementor](https://skelementor.com): Elementor component library.

## Tools

[Move your WordPress site to another domain](https://help.one.com/hc/en-us/articles/115005585969-Move-your-WordPress-site-to-another-domain)\
[Xenu's Link Sleuth](https://home.snafu.de/tilman/xenulink.html): Checks website for broken links.\
[WinSCP](https://winscp.net): Search for automatically generated WordPress media sizes using the following command:

```.sh
*-???x???.jpg
```

After removing all unused media sizes, regenerate thumbnails using [Force Regenerate Thumbnails](https://wordpress.org/plugins/force-regenerate-thumbnails/) and clean up the orphaned media entries.

```.php
# See defined functions
get_defined_functions()['user'];
```

```.php
# List all registered hooks
global $wp_filter;
print_r(array_keys($wp_filter));

// List all functions currently attached to a specific hook
global $wp_filter;
$hook_name = 'woocommerce_payment_complete';

if (isset($wp_filter[$hook_name])) {
    echo "# Callbacks for {$hook_name}\n";
    foreach ($wp_filter[$hook_name]->callbacks as $priority => $callbacks) {
        foreach ($callbacks as $id => $details) {
            echo "Priority {$priority}: " . $id . "\n";
        }
    }
} else {
    echo "Hook not found.";
}
```

## Plugins

### Content

[Connect Polylang for Elementor](https://wordpress.org/plugins/connect-polylang-elementor/): For translating Elementor's "Theme Builder" pages (e.g. 404 Page, Header, Footer).

#### Polylang

##### Cache

Disable the Polylang language cookie by adding the following line to `wp-config.php`:

```.php
define('PLL_COOKIE', false);
```

##### Flags

[World Flags](https://gitlab.com/catamphetamine/country-flag-icons/-/tree/master/flags/3x2).

```.sh
for file in ./*.svg; do
    magick convert -monitor "$file" -resize 22x13 "${file%.svg}.png"
done
```

#### wp_options

```sql
SELECT *, LENGTH(option_value) AS size
FROM wp_options
WHERE autoload = 'yes'
ORDER BY size DESC
```

```sql
SELECT *, LENGTH(option_value) AS size
FROM wp_options
WHERE (option_name LIKE '%colibri%' OR option_value LIKE '%colibri%') ORDER BY size DESC
```

### Tools

[Media File Renamer](https://wordpress.org/plugins/media-file-renamer/):

- Move media files within the WordPress uploads directory and automatically update the links in the database (requires pro).

[WP-Optimize](https://wordpress.org/plugins/wp-optimize/): Delete unused images (requires premium).

#### Brazil

[Gerador de Termos de Uso](https://www.nuvemshop.com.br/ferramentas/gerador-termos-de-uso)\
[Gerador de Política de Privacidade](https://www.nuvemshop.com.br/ferramentas/gerador-politica-de-privacidade)
