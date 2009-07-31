=== Import HTML Pages ===
Contributors: sillybean
Tags: import, pages
Requires at least: 2.8
Tested up to: 2.8.2
Stable tag: 1.0

Imports well-formed HTML files into WordPress pages. 

== Description ==

Imports well-formed static HTML pages into WordPress pages. Requires PHP 5.

This script will import a directory of files as either pages or posts. You must specify the HTML tag containing the content you want to import (e.g. `<div id="content">` or `<td width="732">`). (Future versions will allow you to specify a Dreamweaver template region instead.) 

If importing pages, the directory hierarchy will be preserved. Directories containing the specified file types will be imported as empty parent pages. Directories that do not contain the specified file types will be ignored.

Options:

* specify file extensions to import (e.g. html, htm, php)
* specify directories to exclude (e.g. images, css)
* if importing pages, specify whether your top-level files should become top-level pages or children of an existing page

== Installation ==

1. Unzip the files and upload the html-import directory to `/wp-content/plugins/` 
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to Settings &rarr; HTML Import to begin.

== Frequently Asked Questions ==

= Does this work on Windows servers? =

It has not been tested on Windows. Give it a try and let me know how it goes!

== Changelog ==

= 1.0 =
* First release (July 26, 2009)

== Screenshots ==

1. Directory to be imported
2. Resulting pages
3. Options screen