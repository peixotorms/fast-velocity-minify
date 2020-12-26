=== Fast Velocity Minify ===
Contributors: Alignak
Tags: PHP Minify, Lighthouse, GTmetrix, Pingdom, Pagespeed, Merging, Minification, Optimization, Speed, Performance, FVM
Requires at least: 4.7
Requires PHP: 5.6
Stable tag: 3.0.0
Tested up to: 5.6
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Improve your speed score on GTmetrix, Pingdom Tools and Google PageSpeed Insights by merging and minifying CSS and JavaScript, compressing HTML and a few more speed optimization options. 
 

== Description ==
Speed optimization plugin for developers and advanced users. This plugin reduces HTTP requests by merging CSS & JavaScript files. It minifies CSS and JS files with PHP Minify, the same library used on most cache plugins.

Minification is done on the frontend during the first uncached request. Once the first request is processed, any other pages that require the same set of CSS and JavaScript files, will reuse the same generated file.

The plugin includes options for developers and advanced users, however the default settings should work just fine for most sites.
Kindly read the HELP section after installing the plugin, about possible issues and how to solve them.

= Aditional Optimization =

I can offer you aditional `custom made` optimization on top of this plugin. If you would like to hire me, please visit my profile links for further information.


= Features =

*	Merge JS and CSS files into groups to reduce the number of HTTP requests
*	Google Fonts merging, inlining and optimization
*	Handles scripts loaded both in the header & footer separately
*	Keeps the order of the scripts even if you exclude some files from minification
*	Supports localized scripts (https://codex.wordpress.org/Function_Reference/wp_localize_script)
*	Minifies CSS and JS with PHP Minify only, no third party software or libraries needed.
*	Option to defer JavaScript and CSS files, either globally or pagespeed insights only.
*	Creates static cache files in the uploads directory.
*	Preserves your original files, by duplicating and copying those files to the uploads directory 
*	View the status and detailed logs on the WordPress admin page.
*	Option to Minify HTML, remove extra info from the header and other optimizations.
*	Ability to turn off minification for JS, CSS or HTML (purge the cache to see it)
*	Ability to turn off CSS or JS merging completely (so you can debug which section causes conflicts and exclude the offending files)
*	Ability to manually ignore JavaScript or CSS files that conflict when merged together (please report if you find some)
*	Support for conditional scripts and styles, as well as inlined code that depends on the handles
*	Support for multisite installations (each site has its own settings)
*	Support for gzip_static on Nginx
*	Support for preconnect and preload headers
*	CDN option, to rewrite all static assets inside the JS or CSS files
*	WP CLI support to check stats and purge the cache
*	Auto purging of cache files for W3 Total Cache, WP Supercache, WP Rocket, Cachify, Comet Cache, Zen Cache, LiteSpeed Cache, Nginx Cache (by Till Krüss ), SG Optimizer, HyperCache, Cache Enabler, Breeze (Cloudways), Godaddy Managed WordPress Hosting and WP Engine (read the FAQs)
*	and some more...


= WP-CLI Commands =
*	Purge all caches: `wp fvm purge`
*	Purge all caches on a network site: `wp --url=blog.example.com fvm purge`
*	Purge all caches on the entire network (linux): `wp site list --field=url | xargs -n1 -I % wp --url=% fvm purge`
*	Get cache size: `wp fvm stats`
*	Get cache size on a network site: `wp --url=blog.example.com fvm stats`
*	Get cache size on each site (linux): `wp site list --field=url | xargs -n1 -I % wp --url=% fvm stats`


= Notes =
*	The JavaScript minification is by [PHP Minify](https://github.com/matthiasmullie/minify)
*	Compatible with Nginx, HHVM and PHP 7
*	Minimum requirements are PHP 5.5 and WP 4.4, from version 1.4.0 onwards


== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory or upload the zip within WordPress
2. Activate the plugin through the `Plugins` menu in WordPress
3. Configure the options under: `Settings > Fast Velocity Minify` and that's it.


== Screenshots ==

1. The Settings page.


== Upgrade Notice ==

= 3.0.0 =
Please backup your site before updating. Version 3.0 is a major code rewrite to improve JS and CSS merging, but it requires JS settings to be readjusted after the update. 


== Changelog ==

= 3.0.0 [2020.12.26] =
* New version has been remade from scratch
* JS Optimization is disabled by default and requires manual configuration
* Third party scripts can now be delayed until user interaction, to improve the initial loading time

= 2.8.9 [2020.06.23] =
* new filter for wp hide compatibility

= 2.8.8 [2020.05.01] =
* bug fixes for woocommerce, which could result in 403 errors when adding to cart under certain cases

= 2.8.7 [2020.04.30] =
* fixed the sourceMappingURL removal regex introduced on 2.8.3 for js files and css files

= 2.8.6 [2020.04.30] =
* fixed an error notice on php

= 2.8.5 [2020.04.30] =
* bug fixes and some more minification default exclusions

= 2.8.4 [2020.04.24] =
* added frontend-builder-global-functions.js to the list of minification exclusions, but allowing merging (Divi Compatibility)

= 2.8.3 [2020.04.17] =
* Removed some options out of the autoload wp_option to avoid getting cached on the alloptions when using OPCache 
* Removed the CDN purge option for WP Engine (not needed since FVM automatically does cache busting)
* Added support for Kinsta, Pagely, Pressidum, Savvii and Pantheon
* Better sourcemaps regex removal from minified css and js files

= 2.8.2 [2020.04.13] =
* Skip changing clip-path: url(#some-svg); to absolute urls during css minification
* Added a better cronjob duplicate cleanup task, when uninstalling the plugin

= 2.8.1 [2020.03.15] =
* added filter for the fvm_get_url function

= 2.8.0 [2020.03.10] =
* improved compatibility with Thrive Architect editor
* improved compatibility with Divi theme

= 2.7.9 [2020.02.18] =
* changed cache file names hash to longer names to avoid colisions on elementor plugin

= 2.7.8 [2020.02.06] =
* updated PHP Minify with full support for PHP 7.4
* added try, catch wrappers for merged javacript files with console log errors (instead of letting the browser stop execution on error)
* improved compatibility with windows servers
* improved compatibility for font paths with some themes

= 2.7.7 [2019.10.15] =
* added a capability check on the status page ajax request, which could show the cache file path when debug mode is enabled to subscribers

= 2.7.6 [2019.10.10] =
* bug fix release

= 2.7.5 [2019.10.09] =
* added support to "after" scripts added via wp_add_inline_script 

= 2.7.4 [2019.08.18] =
* change to open JS/CSS files suspected of having PHP code via HTTP request, instead of reading the file directly from disk

= 2.7.3 [2019.07.29] =
* Beaver Builder compatibility fix

= 2.7.2 [2019.07.29] =
* fixed a PHP notice when WP_DEBUG mode is enabled on wordpress
* small improvements on google fonts merging

= 2.7.1 [2019.07.27] =
* fixed an AMP validation javascript error

= 2.7.0 [2019.07.23] =
* some score fixes when deferring to pagespeed is enabled

= 2.6.9 [2019.07.15] =
* custom cache path permissions fix (thanks to @fariazz)

= 2.6.8 [2019.07.06] =
* header preload fixes (thanks to @vandreev)

= 2.6.7 [2019.07.04] =
* added cache purging support for the swift cache plugin
* changed cache directory to the uploads directory for compatibility reasons
* better cache purging checks

= 2.6.6 [2019.06.20] =
* cache purging bug fixes
* php notice fixes

= 2.6.5 [2019.05.04] =
* fixed cache purging on Hyper Cache plugin
* removed support for WPFC (plugin author implemented a notice stating that FVM is incompatible with WPFC)
* improved the filtering engine for pagespeed insights on desktop

= 2.6.4 [2019.03.31] =
* fixed subdirectories permissions

= 2.6.3 [2019.03.30] =
* fixed another minor PHP notice

= 2.6.2 [2019.03.27] =
* fixed a PHP notice on urls with query strings that include arrays on keys or values

= 2.6.1 [2019.03.26] =
* fixed compatibility with the latest elementor plugin
* fixed adding duplicate cron jobs + existing duplicate cronjobs cleanup
* fixed duplicate "cache/cache" directory path
* changed the minimum PHP requirements to PHP 5.5

= 2.6.0 [2019.03.02] =
* fixed cache purging with the hypercache plugin
* fixed a bug with inline scripts and styles not showing up if there is no url for the enqueued handle
* changed the cache directory from the wp-content/uploads to wp-content/cache
* improved compatibility with page cache plugins and servers (purging FVM without purging the page cache should be fine now)
* added a daily cronjob, to delete public invalid cache files that are older than 3 months (your page cache should expire before this)

= 2.0.0 [2017.05.11] =
* version 2.x branch release

= 1.0 [2016.06.19] =
* Initial Release
