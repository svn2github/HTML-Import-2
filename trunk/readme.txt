=== HTML Import 2 ===
Contributors: sillybean
Donate link: http://sillybean.net/code/wordpress/html-import-2/
Tags: import, pages, static files, taxonomies, taxonomy, dreamweaver, Word, FrontPage
Requires at least: 3.0
Tested up to: 3.2
Stable tag: 1.30

Imports well-formed HTML files into WordPress pages. 

== Description ==

Imports well-formed static HTML files into WordPress. Requires PHP 5.

This plugin will import a directory of files as either pages or posts. You may specify the HTML tag (e.g. `<body>`, `<div id="content">`, or `<td width="732">`) or Dreamweaver template region (e.g. 'Main Content') containing the content you want to import.

If importing pages, the directory hierarchy will be preserved. Directories containing the specified file types will be imported as empty parent pages. Directories that do not contain the specified file types will be ignored.

As files are imported, the resulting IDs, permalinks, and titles will be displayed. On completion, the importer will provide a list of Apache redirects that can be used in your .htaccess file to seamlessly transfer visitors from the old file locations to the new WordPress permalinks. As of 2.0, if you change your permalink structure after you've imported your files, you can regenerate the redirects&mdash;the file's old URL is stored as a custom field in the imported post.

Options in 2.0:

* import files into <del>pages or posts</del> any post type
* import linked image files to the media library
* select content and title by HTML tag or Dreamweaver template region
* remove a common phrase (such as the site name) from imported titles
* upload a single file or scan a directory for files to import
* specify file extensions to import (e.g. html, htm, php)
* specify directories to exclude (e.g. images, css)
* if importing pages (or any hierarchical post type), specify whether your top-level files should become top-level pages or children of an existing page
* set tags, categories, and custom taxonomies
* choose status, author, and timestamp
* use meta descriptions as excerpts
* clean up imported HTML and strip unwanted tags and attributes
* convert unencoded special characters to HTML entities

== Installation ==

1. Unzip the files and upload the plugin directory to `/wp-content/plugins/` 
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to Settings &rarr; HTML Import to begin.

== Frequently Asked Questions ==

= Does this work on Windows servers? =

Yes! Let me know if you encounter any problems.

= Will the importer duplicate the design of my old site? =

No. The importer simply extracts the relevant part of each HTML file and copies it into a WordPress post. You'll need to <a href="http://codex.wordpress.org/Theme_Development">create a custom theme</a> if you want to preserve the site's appearance.

= Will this work on large numbers of HTML files? =

Yes, it has been used to import about a thousand pages, and did so in a couple of minutes. However, you might need to adjust PHP's max_execution_time setting as described below.

= I import a few files and then the script times out. What can I do? =

The importer will attempt to work around your server's max_execution_time setting for PHP, but some servers don't allow this. You can try to increase it by adding a line to your .htaccess file:

`php_value max_execution_time 160`

If that gets you further but still doesn't finish, just increase the number (it's in seconds). However, note that your host might get irritated with you for hogging the server's resources. If you have a _lot_ of files to import, it's best to install WordPress on your desktop (XAMPP for Windows and MAMP for Macs make it pretty easy) and do the heavy lifting there.

= Known bugs =

1. The plugin will create an empty parent page for directories that contain the imported file types. However, it will not create parent pages for directories containing only other directories, even if those directories contain the right kinds of files.

For example, if your directory structure is:

    2004/
        conferences/
            index.html
            hotels.html
        workshops/
            index.html
            schedule.html
    2005/
        conferences/
            index.html
            hotels.html
        workshops/
            index.html
            schedule.html

The conferences and workshops directories will be created as parent pages, but the 2004 and 2005 directories will not.

To work around this problem, you can populate your directories with dummy index.html pages. They should contain at least the `<html>`, `<head>`, and `<title>` tags, and you can give them distinctive titles (e.g. "DUMMY") so you can easily find and delete them once all your files have been imported.

== Upgrade Notice ==

= 2.0 =
This version requires at least WP 3.0. Now handles linked images, single file uploads, and custom post types and taxonomies.

== Changelog ==

= 2.0 =
* New option to import images linked in the imported HTML files. It can handle most relative paths as well as absolute URLs. The report includes a list of the image paths that couldn't be resolved.
* Now supports all public custom post types and taxonomies (including hierarchical ones).
* Much better handling of special characters.
* The import screen now lets you upload a single file.
* New user interface. The options form is now broken up into several tabbed sections. Categories and other hierarchical taxonomies are selected with checkboxes.
* The options form is now separate from the importer. It will now check your settings before the importer runs -- for example, you'll get a warning if your beginning directory isn't readable.
* The importer itself is now based on the WordPress import class, which means it looks and works more like other importers. It is located under Tools&rarr;Import (but you should visit the settings screen first).
* Files' old URLs are now stored as custom fields in the imported posts. There's now an option to regenerate the redirects for your imported files, which is handy if you changed your permalink structure after you finished importing.
* Now makes proper use of the Settings API for better security and data validation.
* New help screen and user guide.
* Now requires at least WP 3.0.
= 1.30 =
* The '.,..' directories are no longer optional, so you can't accidentally import hundreds of empty posts/pages by removing these from the skipped directories option.
* The beginning directory default is now based on the path to your WordPress installation. There's also a hint shown below the field. This should help people locate their import directory correctly.
* There's now an option to enter your old URL. If you enter it, your .htaccess redirects should work as displayed. If you leave it blank, you'll have to doctor the paths afterward, as before.
* Character encoding IS now optional. If your special characters did not import correctly before, try again with this option unchecked (which is now the default).
* Options are now deleted on plugin uninstall instead of deactivate. (Sorry about that.)
* Code cleanup in preparation for version 2.0. (June 24, 2011)
= 1.21 =
* same as 1.2; not sure why the plugin repository can't count
= 1.2 =
* Added custom taxonomy options
* Better handling of mb encoding function and asXML
* Better security checking
* Added translation support (January 24, 2010)
= 1.13 =
* Fixed a bug in 1.11 when importing content specified by a tag (thanks, mjos)
* Added an option to assign a category or tag to all imported posts
* This is 1.12, only uncorrupted (September 13, 2009)
= 1.12 =
* Fixed a bug in 1.11 when importing content specified by a tag (thanks, mjos)
* Added an option to assign a category or tag to all imported posts (September 13, 2009)
= 1.11 =
* Left some debugging code in 1.1, oops! (August 15, 2009)
= 1.1 = 
* Added Word cleanup option (August 14, 2009)
= 1.04 =
* Better user capability check (August 3, 2009)
= 1.03 =
* Still better error handling
* minor code cleanup  (August 1, 2009)
= 1.02 =
* Better error handling for `fopen` and `file_get_contents`  (July 31, 2009)
= 1.01 =
* jQuery bug fixed
* better Windows compatibility (July 31, 2009)
= 1.0 =
* First release (July 26, 2009)

== Other Notes ==

= Roadmap =

2.1: support for custom fields

= Thanks =

Thanks to Tom Dyson's <a href="http://wordoff.org/">Wordoff.org</a> for inspiring the Word cleanup option in 1.1. 

== Screenshots ==

1. Directory to be imported
2. Resulting pages
3. Options screen
4. Results: imported pages and rewrite rules
5. New in 1.1: clean up Word HTML option