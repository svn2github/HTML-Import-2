=== Import HTML Pages ===
Contributors: sillybean
Tags: import, pages
Requires at least: 2.8
Tested up to: 2.8.2
Stable tag: 1.01

Imports well-formed HTML files into WordPress pages. 

== Description ==

Imports well-formed static HTML files into WordPress pages. Requires PHP 5.

This plugin will import a directory of files as either pages or posts. You may specify the HTML tag (e.g. `body`, `<div id="content">`, or `<td width="732">`) or Dreamweaver template region (e.g. 'Main Content') containing the content you want to import.

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

== Changelog ==

= 1.01 =
* jQuery bug fixed
* better Windows compatibility (July 31, 2009)
= 1.0 =
* First release (July 26, 2009)

== Screenshots ==

1. Directory to be imported
2. Resulting pages
3. Options screen
4. Results: imported pages and rewrite rules