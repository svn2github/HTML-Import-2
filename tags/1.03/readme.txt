=== Import HTML Pages ===
Contributors: sillybean
Donate link: http://sillybean.net/code/wordpress/html-import/
Tags: import, pages, static files
Requires at least: 2.8
Tested up to: 2.8.3
Stable tag: 1.03

Imports well-formed HTML files into WordPress pages. 

== Description ==

Imports well-formed static HTML files into WordPress pages. Requires PHP 5.

This plugin will import a directory of files as either pages or posts. You may specify the HTML tag (e.g. `<body>`, `<div id="content">`, or `<td width="732">`) or Dreamweaver template region (e.g. 'Main Content') containing the content you want to import.

If importing pages, the directory hierarchy will be preserved. Directories containing the specified file types will be imported as empty parent pages. Directories that do not contain the specified file types will be ignored.

As files are imported, the resulting IDs, permalinks, and titles will be displayed. On completion, the importer will provide a list of Apache redirects that can be used in your .htaccess file to seamlessly transfer visitors from the old file locations to the new WordPress posts or pages.

Options:

* import pages or posts
* specify content and title as HTML tags or Dreamweaver template regions
* remove a common phrase (such as the site name) from imported titles
* specify file extensions to import (e.g. html, htm, php)
* specify directories to exclude (e.g. images, css)
* if importing pages, specify whether your top-level files should become top-level pages or children of an existing page
* choose status, author, and timestamp
* use meta descriptions as excerpts

== Installation ==

1. Unzip the files and upload the html-import directory to `/wp-content/plugins/` 
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to Settings &rarr; HTML Import to begin.

== Frequently Asked Questions ==

= Does this work on Windows servers? =

It has not been tested on Windows. Give it a try and let me know how it goes!

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

== Changelog ==

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

= Roadmap = 
1.1: Set tags and/or categories for imported posts (in the meantime, <a href="http://wordpress.org/extend/plugins/simple-tags/">Simple Tags</a> does a nice job on tags)

== Screenshots ==

1. Directory to be imported
2. Resulting pages
3. Options screen
4. Results: imported pages and rewrite rules