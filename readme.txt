=== RSSMedia ===
Contributors: anatoly.kazantsev
Donate link: 
Tags: feed, rss, atom, media
Requires at least: 3.5
Tested up to: 3.5.1
Stable tag: 0.2.1.1
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl.html

Import and display media from feeds in your blog in various ways.

== Description ==

Use following code with a PHP-Plugin or in a template, example `sidebar.php`
or `single.php`, for WordPress:

Example (shows last 10 items from the specified feed with Galleria plugin):
`<php RSSMedia('galleria', 10, 'http://example.com/feed/'); ?>`

Example for shortcode:
`[rssmedia template="sidebar" limit="10" url="http://example.com/feed/"]`

Parameters:

1. `template` - Name of the template which will be used to display content of
the feed
1. `limit` - How many items, Default is `5`
1. `url` - Address of feed, default is `http://example.com/feed/`

= Available templates =

* Galleria [`galleria`] - Shows product thumbs and images with Galleria JS
library.
* Galleriffic [`galleriffic`] - Shows product thumbs and images with
Galleriffic JS library.
* jCarousel [`jcarousel`] - Show carousel with product thumbs
(uses jCarousel JS library).
* Featured products slideshow [`featured-slideshow`] - Slideshow with full
product's image and description (uses jquery.tools JS library).
* Sidebar [`sidebar`] - Simple template for sidebar widget. Shows product
thumbs in several columns.
* Sidebar (images and text) [`sidebar-list`] - Template for sidebar widget.
Shows list of items with product thumb, title and product details when mouse
is over thumb.

== Installation ==

1. Unpack the downloaded package
1. Upload all files to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Create a new site in WordPress or edit your template
1. Copy the code to posts/pages or edit templates

== Frequently asked questions ==



== Screenshots ==



== Changelog ==

= 0.2.1.1 =
* Fix bug in sidebar widget which inserts rssmedia tag

= 0.2.1 =
* Allow inserting rssmedia tag in visual mode

= 0.2 =
* Add sidebar widget to post/page editor for easely inserting of rssmedia tag

= 0.1.1 =
* Add an option to ping remote service on post publish

= 0.1.0.1 =
* Fix examples in documentation

= 0.1 =
* Initial release

== Upgrade notice ==



== Other Notes ==

= Adding templates =

Every template consistes from four files: before.php, content.php and
after.php.

* setup.php - used to configure template and add scripts and styles
* before.php - used before repeating part of feed (feed items)
* content.php - used to display feed's items
* after.php - used after repeating part of feed

Template should be placed in its own directory in 'templates' directory of
plugin's root.

Example of directory structure:

wp-content/plugins/rss-media/templates/<template_name>/{setup.php, before.php, content.php, after.php}

Also JS scripts and CSS files which are used in the template should be placed in
its directory.
