=== Ultimate Posts Widget ===
Contributors: bostondv
Donate link: http://www.pomelodesign.com/donate
Tags: widget, recent posts, custom post types, sticky posts, featured image, post thumbnail, excerpts, category
Requires at least: 3.0
Tested up to: 3.6
Stable tag: 1.7
License: GPL2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The ultimate widget for displaying posts, custom post types or sticky posts with an array of options.

== Description ==

The ultimate widget for displaying posts, custom post types or sticky posts with an array of options.

Options:

* Filter by categories
* Filter by current category
* Filter by custom post types
* Filter by sticky posts
* Select number of posts to display
* Display title
* Display publish date
* Display publish time
* Display post author
* Display excerpt
* Display read more link
* Display featured image
* Display more button link
* Display post categories
* Display post tags
* Custom widget title link
* Custom read more link text
* Custom excerpt length (in words)
* Custom featured image size
* Feature image crop mode
* Order by date, title, number of comments or random

For support please use [wordpress.org](http://wordpress.org/support/plugin/ultimate-posts-widget). Visit [our website](http://pomelodesign.com), follow [@bostondv](http://twitter.com/bostondv/) for updates.

Fork or contribute on [Github](https://github.com/bostondv/ultimate-posts-widget)

== Frequently Asked Questions ==

= Thumbnail images are not displaying =

This plugin uses the [TimThumb library](http://www.binarymoon.co.uk/projects/timthumb/) to resize post thumbnails. Please review these requirements and troubleshooting tips if you are having problems displaying thumbnails.

* Right click > view image - If an image isn't loading then this is the first thing you should do. 9 times out of 10 it will tell you what the problem is in an easy to read error message.
* JetPack plugin - There is a known conflict between JetPack's "Photon" component, please disable it for compatibility with TimThumb.
* Server requirements - PHP and the GD image library must be installed on your web server. Normally most web servers include them by default.
* Cache permissions - The cache directory `wp-content/plugins/ultimate-posts-widget/cache` should be set to 777 or if that doesn't work, you may find 755 is ok.
* Image sizes - TimThumb is configured to only work for images smaller than 1500 x 1500. The plugin and automatically selects the "Large" size from Settings > Media, if it is greater than 1500 x 1500 you will need to reduce the size or modify the configuration in `thumb.php` to support larger image sizes.
* Tilde(~) in url - Timthumb has a known issue with this, please use a url without a tilde until a fix is available. [Bug report](https://code.google.com/p/timthumb/issues/detail?id=263)

Still stuck? See [additional troubleshooting tips](http://www.binarymoon.co.uk/2010/11/timthumb-hints-tips/) from the TimThumb author.

== Screenshots ==

1. Widget options

== Changelog ==

= 1.7 =

* Added show author option
* Added class to highlight current post
* Fixed PHP error notices

= 1.6 =

* Added localization support
* Added show post categories option
* Added show post tags option
* Added custom widget title URL option
* Improved filter controls for sticky posts
* Bug fixes

= 1.5.1 =

* Upgrade timthumb to 2.8.11
* Load "Large" size post thumbnails which should eliminate large image errors
* Improved FAQ for thumbnail troubleshooting

= 1.5 =

* Adds crop mode option
* Add show published time option
* Now uses date / time format settings
* Bug fixes

= 1.4.5 =

* Updates screenshot

= 1.4.4 =

* Fixes show_readmore function
* Adds more button link option

= 1.4 =

* Cleaner widget options
* Code refactoring and fixes
* Adds order by option

= 1.3 =

* Sticky posts only now optional

= 1.2 =

* Added post type filter option.
* Code cleanup.
* Better selection mechanism for categories.

= 1.1 =

* Added category filter option.

= 1.0 =

* First release.

== Installation ==

1. Download and extract the zip archive
2. Upload `ultimate-posts-widget` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Add the widget to a sidebar and configure the options as desired