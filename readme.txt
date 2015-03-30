=== Heroic Gallery Manager ===
Contributors: herothemes
Tags: images, gallery, manager, management, photos, photography, photo, tool
Requires at least: 3.5
Tested up to: 4.1
Stable tag: 1.21
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Heroic Gallery Manager is an enhanced drag and drop gallery manager for WordPress

== Description ==

The **Ultimate** gallery manager that combines the best features of the WordPress media manager with a simplified and flexible gallery management tool. 

Create and manage your galleries with Hero Themes Gallery Manager. Drag and drop re-ordering, uploads, editing and simple, powerful gallery views. This gallery manager has been designed specifically for the requirements of photography professionals and creatives. From one screen you can upload all your images, sort and rename images, adding captions and gallery description. This makes the process of getting your images online faster and simpler, with more control and flexibility than other gallery solutions.

Note this is a backend management tool, it uses the inbuilt gallery shortcode which will be styled by your theme and/or a separate gallery display plugin.

All of these features, combined with the simplicity of use, makes Hero Gallery Manager one of the best gallery creation and management tools. Better still you are in complete control as it can use it right from your existing WordPress powered site without the expense of 3rd party offerings.


**Video Demonstration**

A short video demonstrating the improved workflow benefits using Heroic Gallery Manager:

[youtube http://www.youtube.com/watch?v=0kIxPJLa5vk]

> <strong>Supercharge with Hero Themes</strong><br>
> Hero Gallery Manager is designed to work seamlessly with our [Focal and Moda premium photography themes](http://www.herothemes.com). This plugin will work as a gallery manager for any other theme but note the theme still controls how galleries are displayed on your site.

> <strong>About Hero Themes</strong><br>
> Hero Themes develop some of the best WordPress plugins, tools and themes, with over 10,000 customers and counting. If you like this plugin and want more news, themes and plugins, you can do the following:
> 
> * Try our [Heroic Favicon Generator for WordPress](https://wordpress.org/plugins/favhero-favicon-generator/).
> * Check out the [best WordPress Knowledge Base plugin](http://herothemes.com/plugins/heroic-wordpress-knowledge-base/).
> * Add Social Icons to you site with [Heroic Social Widget](https://wordpress.org/plugins/heroic-social-widget/).
> * Follow Hero Themes on [Twitter](https://twitter.com/herothemes) & [Facebook](https://www.facebook.com/herothemes).





== Installation ==

It's easy to get started

1. Upload `ht-gallery-manager` to the `/wp-content/plugins/` directory or goto Plugins>Add New and search for Heroic Gallery Manager.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Under the new Heroic Galleries menu option select New Heroic Gallery.
4. Be sure to click publish/update to save your Heroic Gallery, then copy the shortcode into your post or page where you want the Heroic Gallery to appear or select `Add Heroic Gallery` from the target post or page. 
5. If you are using a compatible theme, you do not need to use the shortcode, simply view or link the Heroic Gallery Post directly for enchanced features and functionality.
6. You can star an image, which works in a similar way to featured images. These will appear as a cover image when using a supporting theme.

== Frequently Asked Questions ==

= Q. My Galleries aren't saving =

A. Be sure to hit publish or update on the right when you've finished editing.

= Q. My images don't appear when adding or editing a Heroic Gallery? =

A. Simply hit the refresh button and the images will be re-loaded.

== Screenshots ==

1. Drag and drop media to create galleries.
2. Intuitive layout and presentation.
3. Edit titles, captions, alts and descriptions in sections with autosave function.
4. Re-order your gallery.
5. Easily include your Hero Galleries in any post or page.


== Changelog ==

= 1.21 =

Updated to support WP4.1
Fixed bug with unescaped html in manager

= 1.20 =

Updated to support WP3.9

= 1.19 =

Added gallery display widget
Bug fix for gallery count

= 1.18 =

Added support for youtu.be urls in video link
Bug fix for empty related galleries

= 1.17 =

Added support for YouTube and Vimeo videos for supporting themes
Added several bug fixes and UI improvements

= 1.16 =

Shortcode now links to file by default

= 1.15 =

Adding support for hidden/private galleries

= 1.14 =

Additional menu fixes

= 1.13 =

Added language support, menu fixes

= 1.12 =

Version update, bug fixes 

= 1.11 = 

Renamed to Heroic Gallery Manager from Hero Themes Gallery Manager

= 1.10 = 

Added static methods to obtain galleries and starred images

= 1.10 = 

Added columns to shortcode

= 1.9 =

Move password protection to Hero Themes Gallery Proofing plugin

= 1.8 =

Password protect CPT meta.

= 1.7 =

New sprites and tweaked UI. Can now add Hero Gallery Archives as top-level custom menu item.

= 1.6 =

Autosave of image order and meta, editing image opens in new tab

= 1.5 =

Custom slugs

= 1.4 =

Reorder Hero Galleries with Menu Order, functional improvements.

= 1.3 =

Gallery category, editor and excerpt.

= 1.2 =

New features, inline upload and more.

= 1.1 =

Improved functionality.

= 1.0 =

Initial release.

== Developer Notes ==

Get galleries as array by using 
ht_gallery_get_gallery($gallery_id=null)

Get related galleries as array by using
ht_gallery_get_related($gallery_id = null, $no_of_related = 4, $src_size = null)

Declare theme support for videos with 
add_theme_support('ht_gallery_video_url_features')

Mute comments (for themes that do not need to implement comments)
add_theme_support('mute_ht_gallery_comments')