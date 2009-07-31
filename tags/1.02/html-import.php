<?php
/*
Plugin Name: Import HTML Pages
Plugin URI: http://sillybean.net/code/wordpress/html-import/
Description: Imports well-formed static HTML pages into WordPress pages. Requires PHP5. Now with Dreamweaver template support.
Version: 1.01
Author: Stephanie Leary
Author URI: http://sillybean.net/

== Changelog ==

= 1.01 =
* jQuery bug fixed
* better Windows compatibility (July 31, 2009)
= 1.0 =
* First release (July 26, 2009)

Copyright 2009  Stephanie Leary  (email : steph@sillybean.net)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
* plugin_activation_check() by Otto
*
* Replace "plugin" with the name of your plugin
*/
function html_import_activation_check(){
	if (version_compare(PHP_VERSION, '5.0.0', '<')) {
		deactivate_plugins(basename(__FILE__)); // Deactivate myself
		wp_die("Sorry, but you can't run this plugin, it requires PHP 5 or higher.");
	}
}
register_activation_hook(__FILE__, 'html_import_activation_check');

// Hook for adding admin menus
add_action('admin_menu', 'html_import_add_pages');

function html_import_css() {
	$options = get_option('html_import');
		echo "<style type=\"text/css\">\n";
	 	echo ".clear, #html_import p.submit, #html_import .wrap p, #html_import .wrap h3, p.htmlimportfloat.clear { clear: both; } \n";	
		echo "p.htmlimportfloat { float: left; width: 15em; margin-right: 2em; clear: none; } \n";
		echo "p.widefloat { width: 31.4em; } \n";	
		echo "input.widefloat { width: 48.5em; } \n";	
		echo "div#tips { width: 18em; float: right; } \n";
		echo "div#optionsform { float: left; width: 48em; } \n";	
		echo "#importing th { width: 32% } \n";
		echo "#importing th#id { width: 4% }\n";
		echo "textarea#import-result { height: 12em; width: 100%; }\n";
		echo "#content-region, #title-region { width: 100%; height: 8em; background: #f9f9f9; z-index: 10; }";
		echo "#content-switch, #title-switch { position: relative; height: 8em; }";
		echo "#content-region, #content-tag, #title-region, #title-tag { position: absolute; }";
	if( $_POST[ $hidden_field_name ] == 'Y' ) {
		if ($_POST['import_content'] == 'tag') echo "#content-region { display: none }";
		if ($_POST['import_title'] == 'tag') echo "#title-region { display: none }";
	}
	else {
		if ($options['import_content'] == 'tag') echo "#content-region { display: none }";
		if ($options['import_title'] == 'tag') echo "#title-region { display: none }";
	}
		echo "#tips h3 { margin-bottom: 0; }";
		echo "#tips { -moz-border-radius-bottomleft:4px;
			-moz-border-radius-bottomright:4px;
			-moz-border-radius-topleft:4px;
			-moz-border-radius-topright:4px;
			border-style:solid;
			border-width:1px; 
			border-color: #DFDFDF;
			background: #fff; 
			padding: 0 2em 1em; }";
		echo "</style>";
}

function html_import_add_pages() {
    // Add a new submenu under Options:
	$css = add_options_page('HTML Import', 'HTML Import', 8, basename(__FILE__), 'html_import_options');
	add_action("admin_head-$css", 'html_import_css');
	
	// set defaults
	$options = array(
		'root_directory' => '/path/to/files',
		'file_extensions' => 'html,htm,shtml',
		'skipdirs' => '.,..,images',
		'status' => 'publish',
		'root_parent' => 0,
		'type' => 'page',
		'timestamp' => 'filemtime',
		'import_content' => 'tag',
		'content_region' => '',
		'content_tag' => 'div',
		'content_tagatt' => 'id',
		'content_attval' => 'content',
		'import_title' => 'tag',
		'title_region' => '',
		'title_tag' => 'title',
		'title_tagatt' => '',
		'title_attval' => '',
		'remove_from_title' => '',
		'meta_desc' => 1,
		'user' => 0
	);
	
	add_option('html_import', $options, '', yes);
}

// displays the options page content
function html_import_options() {
	if ( current_user_can('manage_options') ) {  
	
	// variables for the field and option names 
		$hidden_field_name = 'html_import_submit_hidden';
	
		// See if the user has posted us some information
		// If they did, this hidden field will be set to 'Y'
		if( $_POST[ $hidden_field_name ] == 'Y' ) {
				
			?> <div class="wrap"><h2><?php _e( 'Importing...'); ?></h2>
            <table class="widefat page fixed" id="importing" cellspacing="0"><thead><tr><th id="id">ID</th><th>Old path</th><th>New path</th><th>Title</th></tr></thead><tbody> <?php	
  			// Save the posted value in the database
			$options = array();
			$options['root_directory'] = $_POST['root_directory'];
			$options['file_extensions'] = $_POST['file_extensions'];
			$options['skipdirs'] = $_POST['skipdirs'];
			$options['status'] = $_POST['status'];
			$options['root_parent'] = $_POST['root_parent'];
			$options['type'] = $_POST['type'];
			$options['timestamp'] = $_POST['timestamp'];
			$options['import_content'] = $_POST['import_content'];
			$options['content_region'] = $_POST['content_region'];
			$options['content_tag'] = $_POST['content_tag'];
			$options['content_tagatt'] = $_POST['content_tagatt'];
			$options['content_attval'] = $_POST['content_attval'];
			$options['import_title'] = $_POST['import_title'];
			$options['title_region'] = $_POST['title_region'];
			$options['title_tag'] = $_POST['title_tag'];
			$options['title_tagatt'] = $_POST['title_tagatt'];
			$options['title_attval'] = $_POST['title_attval'];
			$options['remove_from_title'] = $_POST['remove_from_title'];
			$options['meta_desc'] = $_POST['meta_desc'];
			$options['root_parent'] = $_POST['root_parent'];
			$options['user'] = $_POST['user'];
			
			update_option('html_import', $options);
			
			// make the magic happen
			$result = import_html_files($options['root_directory']);
					
			// Put an options updated message on the screen 
			?>
            </tbody></table>
            <h3>.htaccess Redirects</h3>
            <p><small>if you need to redirect visitors from the old file locations to your new WordPress pages, copy these redirects into your .htaccess file above the WordPress rules. <strong>Note:</strong> You might need to search &amp; replace first if your import root directory was not the same as your web root. Also, if you imported many files, the complete list of redirects might slow your web server's performance. Consider copying only essential ones, or if there's a pattern to your file or directory names, create a <a href="http://www.workingwith.me.uk/articles/scripting/mod_rewrite">RewriteRule</a> instead.</small></p>
            <textarea id="import-result"><?php
			foreach ($result as $id => $old) {
				echo "Redirect\t".$old."\t".get_permalink($id)."\t[R=301,NC,L]\n";
			}
			?></textarea>
            <div class="updated"><p><strong><?php _e( " Imported "); echo count($result); _e(" files in "); echo timer_stop(0,5); _e(" seconds. See above for any pages that did not automatically import and need your attention."); ?></strong></p></div>
            </div> <!-- wrap -->
            <?php
	} // Now display the options editing screen  ?>
	
    <div class="wrap" id="html_import">
	<form method="post" id="html_import_form">
    <?php wp_nonce_field('update-options'); ?>
    <?php $options = get_option('html_import'); ?>

    <h2><?php _e( 'HTML Page Import Options '); ?></h2>
	<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">
        
    <div id="optionsform">
    <p><label><?php _e("Beginning directory or URL: "); ?><br />
    <input type="text" name="root_directory" id="root_directory" value="<?php echo stripslashes(htmlentities($options['root_directory'])); ?>" class="widefloat" />  </label></p>
    
    <p><label><?php _e("Process files with these extensions: "); ?><br />
    <input type="text" name="file_extensions" id="file_extensions" value="<?php echo stripslashes(htmlentities($options['file_extensions'])); ?>" class="widefloat" />  </label><br />
<small><?php _e("Enter file extensions, without periods, separated by commas. All other file types will be ignored."); ?></small></p>

    <p><label><?php _e("Skip directories with these names: "); ?><br />
        <input type="text" name="skipdirs" id="skipdirs" value="<?php echo stripslashes(htmlentities($options['skipdirs'])); ?>" class="widefloat" />  </label><br />
    <small><?php _e("Enter directory names, without slashes, separated by commas. All files in these directories will be ignored."); ?></small></p>
    
    <h3><?php _e("Content"); ?></h3>
    <p><?php _e("Select content by:"); ?></p>
	
    <p><label><input name="import_content" id="import_content"  type="radio" value="tag" 
		<?php if ($options['import_content'] == "tag") { ?> checked="checked" <?php } ?> onclick="javascript: jQuery('#content-region').hide('fast');" /> HTML tag</label>&nbsp;&nbsp;
    <label><input name="import_content" id="import_content"  type="radio" value="region" 
		<?php if ($options['import_content'] == "region") { ?> checked="checked" <?php  } ?> onclick="javascript: jQuery('#content-region').show('fast');" /> Dreamweaver template region</label> </p>
    
    <div id="content-switch">
    <div id="content-tag">
    <p class="htmlimportfloat clear"><label><?php _e("Tag"); ?><br />
    <input type="text" name="content_tag" id="content_tag" value="<?php echo stripslashes(htmlentities($options['content_tag'])); ?>" />  </label><br />
	<small><?php _e("The HTML tag, without brackets"); ?></small></p>
    <p class="htmlimportfloat"><label><?php _e("Attribute"); ?><br />
    <input type="text" name="content_tagatt" id="content_tagatt" value="<?php echo stripslashes(htmlentities($options['content_tagatt'])); ?>" />  </label><br />
	<small><?php _e("Leave blank to use a tag without an attribute, or when the attributes don't matter, such as &lt;body&gt;"); ?></small></p>
    <p class="htmlimportfloat"><label><?php _e("= Value"); ?><br />
    <input type="text" name="content_attval" id="content_attval" value="<?php echo stripslashes(htmlentities($options['content_attval'])); ?>" />  </label><br />
	<small><?php _e("Enter the attribute's value (such as width, ID, or class name) without quotes"); ?></small></p>
    </div>
    <p id="content-region"><label><?php _e("Dreamweaver template region"); ?><br />
    <input type="text" name="content_region" id="content_region" value="<?php echo stripslashes(htmlentities($options['content_region'])); ?>" />  </label><br />
	<small><?php _e("The name of the editable region (e.g. 'Main Content')"); ?></small></p> 
    </div>
    
    <h3><?php _e("Title"); ?></h3>
    
    <p><?php _e("Select title by:"); ?><br />
	<label><input name="import_title" id="import_title"  type="radio" value="tag" 
		<?php if ($options['import_title'] == "tag") { ?> checked="checked" <?php } ?> onclick="javascript: jQuery('#title-region').hide('fast');" /> HTML tag</label>&nbsp;&nbsp;  
    <label><input name="import_title" id="import_title"  type="radio" value="region" 
		<?php if ($options['import_title'] == "region") { ?> checked="checked" <?php } ?>  onclick="javascript: jQuery('#title-region').show('fast');" /> Dreamweaver template region</label></p>
    
    <div id="title-switch">
    <div id="title-tag">
    <p class="htmlimportfloat clear"><label><?php _e("Tag containing page title: "); ?><br />
    <input type="text" name="title_tag" id="title_tag" value="<?php echo stripslashes(htmlentities($options['title_tag'])); ?>" />  </label><br />
	<small><?php _e("The HTML tag, without brackets"); ?></small></p>
    <p class="htmlimportfloat"><label><?php _e("Attribute"); ?><br />
    <input type="text" name="title_tagatt" id="title_tagatt" value="<?php echo stripslashes(htmlentities($options['title_tagatt'])); ?>" />  </label><br />
	<small><?php _e("Leave blank to use a tag without an attribute, or when the attributes don't matter, such as &lt;title&gt;"); ?></small></p>
    <p class="htmlimportfloat"><label><?php _e("= Value"); ?><br />
    <input type="text" name="title_attval" id="title_attval" value="<?php echo stripslashes(htmlentities($options['title_attval'])); ?>" />  </label><br />
	<small><?php _e("Enter the attribute's value (such as width, ID, or class name) without quotes"); ?></small></p>
    </div>
    <p id="title-region"><label><?php _e("Dreamweaver template region containing page title: "); ?><br />
    <input type="text" name="title_region" id="title_region" value="<?php echo stripslashes(htmlentities($options['title_region'])); ?>" />  </label><br />
	<small><?php _e("The name of the editable region (e.g. 'Page Title')"); ?></small></p>
   	</div>
   
    <p class="clear"><label><?php _e("Phrase to remove from page title: "); ?><br />
    <input type="text" name="remove_from_title" id="remove_from_title" value="<?php echo stripslashes(htmlentities($options['remove_from_title'])); ?>" class="widefloat" />  </label><br />
	<small><?php _e("Any common title phrase (such as the site name, which WordPress will duplicate)"); ?></small></p>
    
    <h3><?php _e("Metadata"); ?></h3>
    
    <p class="htmlimportfloat clear"><label><?php _e("Import files as: "); ?>
    <select name="type" id="type">
    	<option value="page" <?php if ($options['type'] == 'page') echo 'selected="selected"'; ?>><?php _e("pages"); ?></option>
        <option value="post" <?php if ($options['type'] == 'post') echo 'selected="selected"'; ?>><?php _e("posts"); ?></option>
    </select></label></p>
    
    <p class="htmlimportfloat widefloat"><label><?php _e("Set timestamps to: "); ?>
    <select name="timestamp" id="timestamp">
    	<option value="now" <?php if ($options['timestamp'] == 'now') echo 'selected="selected"'; ?>><?php _e("now"); ?></option>
        <option value="filemtime" <?php if ($options['timestamp'] == 'filemtime') echo 'selected="selected"'; ?>><?php _e("last time the file was modified"); ?></option>
    </select></label></p>
 
    <p class="htmlimportfloat clear"><label><?php _e("Set status to: "); ?>
    <select name="status" id="status">
    	<option value="publish" <?php if ($options['status'] == 'publish') echo 'selected="selected"'; ?>><?php _e("publish"); ?></option>
        <option value="draft" <?php if ($options['status'] == 'draft') echo 'selected="selected"'; ?>><?php _e("draft"); ?></option>
        <option value="private" <?php if ($options['status'] == 'private') echo 'selected="selected"'; ?>><?php _e("private"); ?></option>
        <option value="pending" <?php if ($options['status'] == 'pending') echo 'selected="selected"'; ?>><?php _e("pending"); ?></option>
    </select></label></p>
    
    <p class="htmlimportfloat widefloat"><label><?php _e("Set author to: "); ?>
    <?php wp_dropdown_users(array('selected' => $options['user'])); ?></label></p>
    
    <p class="clear"><label><?php _e("Import pages as children of: "); ?>
    <?php 
		$pages = wp_dropdown_pages(array('echo' => 0, 'selected' => $options['root_parent'], 'name' => 'root_parent', 'show_option_none' => __('None (top level)'), 'sort_column'=> 'menu_order, post_title'));
		if (empty($pages)) $pages = "<select name=\"root_parent\"><option value=\"0\">None (top level)</option></select>";
		echo $pages;
	?>
    </label><br />
	<small>Your directory hierarchy will be maintained, but your top level files will be children of the page selected here.</small></p>
    
    <p><label><input name="meta_desc" id="meta_desc" value="1" type="checkbox" <?php if (!empty($options['meta_desc'])) { ?> checked="checked" <?php } ?> /> <?php _e("Use meta description as excerpt"); ?> </label><br />
	<small><?php _e("Excerpts will be stored for both posts and pages. However, to edit and/or display excerpts for pages, you will need to install a plugin such as <a href=\"http://blog.ftwr.co.uk/wordpress/page-excerpt/\">PJW Page Excerpt</a>
					or <a href=\"http://www.laptoptips.ca/projects/wordpress-excerpt-editor/\">Excerpt Editor</a>."); ?></small></p>
                    
    <input type="hidden" name="action" value="update" />
	<input type="hidden" name="page_options" value="html_import" />
  
	<p class="submit">
	<input type="submit" name="submit" class="button-primary" value="<?php _e('Import using these options'); ?>" />
	</p>
	</form>
    </div> <!-- #optionsform -->
    
    <div id="tips">
    <h3><?php _e("Tips"); ?></h3>
    <p><small><?php _e("(of the technical sort)"); ?></small></p>
    <ol>
    	<li><?php _e("You should see the options again once the import has finished. If you don't, the importer encountered a serious problem with one of your files and could not continue."); ?></li>
        <li><?php _e("If things didn't work out the way you intended and you need to delete all the posts or pages you just imported, make a note of the first and last IDs imported and use the <a href='http://www.wesg.ca/2008/07/wordpress-plugin-mass-page-remover/'>Mass Page Remover plugin</a> to remove them all at once."); ?></li>
      	<li><?php _e("Need to import both posts and pages? Run the importer on a subdirectory (e.g. 'news'), then move those files somewhere else temporarily while you run the importer again."); ?></li>
    </ol>
    <h3><?php _e("Tips"); ?></h3>
    <p><small><?php _e("(of the monetary sort)"); ?></small></p>
    <p>Did this plugin save you hours and hours of copying? Buy me a cookie, if you don't mind!</p>
    <!-- Donation link -->
    <form action="https://www.paypal.com/cgi-bin/webscr" method="post">
        <input type="hidden" name="cmd" value="_s-xclick">
        <input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----MIIHTwYJKoZIhvcNAQcEoIIHQDCCBzwCAQExggEwMIIBLAIBADCBlDCBjjELMAkGA1UEBhMCVVMxCzAJBgNVBAgTAkNBMRYwFAYDVQQHEw1Nb3VudGFpbiBWaWV3MRQwEgYDVQQKEwtQYXlQYWwgSW5jLjETMBEGA1UECxQKbGl2ZV9jZXJ0czERMA8GA1UEAxQIbGl2ZV9hcGkxHDAaBgkqhkiG9w0BCQEWDXJlQHBheXBhbC5jb20CAQAwDQYJKoZIhvcNAQEBBQAEgYA4iknbgihMRQaBIrIo5UjA6/cMQjq9XiW24YXO2M6hFffgzbCeqnyJZYCl6/O3OMwqFcgKC8zMBXmYcp5F2sZLZYjjPE5yob5LlIerBwDGsh/fdsteejEUugy1I8WBQKln/E49Nr385RdAmeQmOhd/BBAcpS0guUyeDogke7rFQjELMAkGBSsOAwIaBQAwgcwGCSqGSIb3DQEHATAUBggqhkiG9w0DBwQIE4CGOzowRiKAgaie4pBu+jDXxZk1aYygLsTaB7j/Kpold7aeFjb5k0TFuQrA2A4ydqQC+OSzgYO9o85zJgdk9KMmnvwc8RZ/mu3IfYYqsph/C1XTxOTbZR8Yg2RDuHiNWdvZmLbcJLKad20gbDif64XBMikDaZppPLTi8F6c/JMQXsT7mghWEFVwpW7NCK45Z6wuoqfU0b2Fqu1d/nj0gNPPFo7c0TK6GVjLOvCYhhEgxGCgggOHMIIDgzCCAuygAwIBAgIBADANBgkqhkiG9w0BAQUFADCBjjELMAkGA1UEBhMCVVMxCzAJBgNVBAgTAkNBMRYwFAYDVQQHEw1Nb3VudGFpbiBWaWV3MRQwEgYDVQQKEwtQYXlQYWwgSW5jLjETMBEGA1UECxQKbGl2ZV9jZXJ0czERMA8GA1UEAxQIbGl2ZV9hcGkxHDAaBgkqhkiG9w0BCQEWDXJlQHBheXBhbC5jb20wHhcNMDQwMjEzMTAxMzE1WhcNMzUwMjEzMTAxMzE1WjCBjjELMAkGA1UEBhMCVVMxCzAJBgNVBAgTAkNBMRYwFAYDVQQHEw1Nb3VudGFpbiBWaWV3MRQwEgYDVQQKEwtQYXlQYWwgSW5jLjETMBEGA1UECxQKbGl2ZV9jZXJ0czERMA8GA1UEAxQIbGl2ZV9hcGkxHDAaBgkqhkiG9w0BCQEWDXJlQHBheXBhbC5jb20wgZ8wDQYJKoZIhvcNAQEBBQADgY0AMIGJAoGBAMFHTt38RMxLXJyO2SmS+Ndl72T7oKJ4u4uw+6awntALWh03PewmIJuzbALScsTS4sZoS1fKciBGoh11gIfHzylvkdNe/hJl66/RGqrj5rFb08sAABNTzDTiqqNpJeBsYs/c2aiGozptX2RlnBktH+SUNpAajW724Nv2Wvhif6sFAgMBAAGjge4wgeswHQYDVR0OBBYEFJaffLvGbxe9WT9S1wob7BDWZJRrMIG7BgNVHSMEgbMwgbCAFJaffLvGbxe9WT9S1wob7BDWZJRroYGUpIGRMIGOMQswCQYDVQQGEwJVUzELMAkGA1UECBMCQ0ExFjAUBgNVBAcTDU1vdW50YWluIFZpZXcxFDASBgNVBAoTC1BheVBhbCBJbmMuMRMwEQYDVQQLFApsaXZlX2NlcnRzMREwDwYDVQQDFAhsaXZlX2FwaTEcMBoGCSqGSIb3DQEJARYNcmVAcGF5cGFsLmNvbYIBADAMBgNVHRMEBTADAQH/MA0GCSqGSIb3DQEBBQUAA4GBAIFfOlaagFrl71+jq6OKidbWFSE+Q4FqROvdgIONth+8kSK//Y/4ihuE4Ymvzn5ceE3S/iBSQQMjyvb+s2TWbQYDwcp129OPIbD9epdr4tJOUNiSojw7BHwYRiPh58S1xGlFgHFXwrEBb3dgNbMUa+u4qectsMAXpVHnD9wIyfmHMYIBmjCCAZYCAQEwgZQwgY4xCzAJBgNVBAYTAlVTMQswCQYDVQQIEwJDQTEWMBQGA1UEBxMNTW91bnRhaW4gVmlldzEUMBIGA1UEChMLUGF5UGFsIEluYy4xEzARBgNVBAsUCmxpdmVfY2VydHMxETAPBgNVBAMUCGxpdmVfYXBpMRwwGgYJKoZIhvcNAQkBFg1yZUBwYXlwYWwuY29tAgEAMAkGBSsOAwIaBQCgXTAYBgkqhkiG9w0BCQMxCwYJKoZIhvcNAQcBMBwGCSqGSIb3DQEJBTEPFw0wOTA3MzExODE5MDRaMCMGCSqGSIb3DQEJBDEWBBQybUcFfiIQKmfoXMItwECAkH6XiTANBgkqhkiG9w0BAQEFAASBgEm9ehwb0Zzk5OHruQl6SoDGSjgNS+oLadAqCgR6WQ1sdTTG84T/kY/wmaQ7Cd4uRv/qSi+eKjdV+RHfC/29FR8XYzocZtzUgxLB8FD+c9BoTWlkWOhQTXe5Van+UrWVlGqIGsDOM123h4G7rCew7Xh24nPHtdDgvxVk0h/L+SKc-----END PKCS7-----">
        <input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
        <img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
        </form>
    </div> <!-- #tips -->
    
	</div> <!-- .wrap -->
<?php  } // if current_user_can
 } // end function html_import_options() 



function import_html_files($rootdir, $filearr=array())   {
	global $wpdb;
	$options = get_option('html_import');
	
    $allowed = explode(",", $options['file_extensions']);
	$skipdirs = explode(",", $options['skipdirs']);
    $dir_content = scandir($rootdir);
    foreach($dir_content as $key => $val) {
      set_time_limit(30);
      $path = $rootdir.'/'.$val;
      if(is_file($path) && is_readable($path)) {
		$filename_parts = explode(".",$val);
		$ext = $filename_parts[count($filename_parts) - 1];
		// allowed extensions only, please
		if (in_array($ext, $allowed)) {
						
			// process the HTML file
			$doc = new DOMDocument();
			$doc->strictErrorChecking = FALSE; // ignore invalid HTML, we hope
			$doc->preserveWhiteSpace = FALSE;  
			$dom->formatOutput = false;  // speed this up
			$contents = @fopen($path);  // read entire file
			if (empty($contents)) $contents = @file_get_contents($path);  // read entire file
			if (empty($contents)) wp_die("fopen and file_get_contents have both failed. We can't import any files without these functions.");
			$encoded = mb_convert_encoding($contents, 'HTML-ENTITIES', "UTF-8"); 
			$doc->loadHTML($encoded);
			$xml = simplexml_import_dom($doc);
			
			// start building the WP post object to insert
			$my_post = array();	
			
			if ($options['import_title'] == "region") {
				// appending strings unnecessarily so this plugin can be edited in Dreamweaver if needed
				$titlematch = '/<'.'!-- InstanceBeginEditable name="'.$options['title_region'].'" --'.'>(.*)<'.'!-- InstanceEndEditable --'.'>/isU';
				preg_match($titlematch, $encoded, $titlematches);
				$my_post['post_title'] = strip_tags($titlematches[1]);
			}
			else { // it's a tag
				$titletag = $options['title_tag'];
				$titletagatt = $options['title_tagatt'];
				$titleattval = $options['title_attval'];
				$titlequery = '//'.$titletag;
				if (!empty($titletagatt))
					$titlequery .= '[@'.$titletagatt.'="'.$titleattval.'"]';
				$my_post['post_title'] = $xml->xpath($titlequery);
				$my_post['post_title'] = strip_tags($my_post['post_title'][0]);
			}
			
			//$my_post['post_title'] = (string)$xml->head->title;
			
			$remove = $options['remove_from_title'];
			if (!empty($remove))
				$my_post['post_title'] = str_replace($remove, '', $my_post['post_title']);
			
			$my_post['post_type'] = $options['type'];
			
			if ($my_post['post_type'] == 'page') {
				$parentdir = rtrim(parent_directory($path), '/');
				if (in_array($parentdir, $filearr))
					$my_post['post_parent'] = array_search($parentdir, $filearr);
				else $my_post['post_parent'] = $options['root_parent'];
			}
			
			if ($options['timestamp'] == 'filemtime')
				$date = filemtime($path);
			else $date = time();
			$my_post['post_date'] = date("Y-m-d H:i:s", $date);
			$my_post['post_date_gmt'] = date("Y-m-d H:i:s", $date);
			
			if ($options['import_content'] == "region") {
				// appending strings unnecessarily so this plugin can be edited in Dreamweaver if needed
				$contentmatch = '/<'.'!-- InstanceBeginEditable name="'.$options['content_region'].'" --'.'>(.*)<'.'!-- InstanceEndEditable --'.'>/isU';
				preg_match($contentmatch, $encoded, $contentmatches);
				$my_post['post_content'] = $contentmatches[1];
			}
			else { // it's a tag
				$tag = $options['content_tag'];
				$tagatt = $options['content_tagatt'];
				$attval = $options['content_attval'];
				$xquery = '//'.$tag;
				if (!empty($tagatt))
					$xquery .= '[@'.$tagatt.'="'.$attval.'"]';
				$my_post['post_content'] = $xml->xpath($xquery);
				$my_post['post_content'] = $my_post['post_content'][0]->asXML(); // asXML() preserves HTML in content
			}
			// get rid of remaining newlines
			if (!empty($my_post['post_content'])) {
				$my_post['post_content'] = str_replace('&#13;', ' ', $my_post['post_content']); 
				$my_post['post_content'] = ereg_replace("[\n\r]", " ", $my_post['post_content']); 
			}
			
			$excerpt = $options['meta_desc'];
			if (!empty($excerpt)) {
				 $my_post['post_excerpt'] = $xml->xpath('//meta[@name="description"]');
				 $my_post['post_excerpt'] = (string)$my_post['post_excerpt'][0]['content'];
			}
			
			$my_post['post_status'] = $options['status'];
			$my_post['post_author'] = $options['user'];
			
			// Insert the post into the database
			$newid = wp_insert_post( $my_post );
			if (!empty($newid)) {
			  if ($newid & 1) /*even or odd*/ $class = ' class="alternate"'; else $class = '';
			  _e( " <tr".$class."><th>".$newid."</th><td>".$path."</td><td>".get_permalink($newid)."</td><td>".$my_post['post_title']."</td></tr>");
			}
			else _e( "<tr><td colspan='4' class='error'> Could not import ".$val.". You should copy its contents manually.</td></tr>");
			usleep(5000);
			
			// store old and new paths
			$filearr[$newid] = $path;
			flush();
		}
      }
      elseif(is_dir($path) && is_readable($path)) { 
        if(!in_array($val, $skipdirs)) {
		  $createpage = array();
		  // get list of files in this directory only (checking children)
				$files = scandir($path);
				foreach ($files as $file) {
					$ext = strrchr($file,'.');
					$ext = trim($ext,'.'); // dratted double dots
					if (!empty($ext)) $exts[] = $ext;
				}
				
				// allowed extensions only, please
				$createpage = array_intersect($exts, $allowed);
				
				// if the directory contains the right kind of files, create an empty parent page
				if (!empty($createpage)) { 
				// start building the WP post object to insert
					$my_post = array();		  
					
					$title = trim(strrchr($path,'/'),'/');
					$my_post['post_title'] = pretty_title($title);
					
					if ($options['timestamp'] == 'filemtime')
						$date = filemtime($path);
					else $date = time();
					$my_post['post_date'] = date("Y-m-d H:i:s", $date);
					$my_post['post_date_gmt'] = date("Y-m-d H:i:s", $date);
					
					$my_post['post_type'] = 'page';
					
					$parentdir = rtrim(parent_directory($path), '/');
					if (in_array($parentdir, $filearr))
						$my_post['post_parent'] = array_search($parentdir, $filearr);
					else $my_post['post_parent'] = $options['root_parent'];
					
					$my_post['post_content'] = '<!-- placeholder -->';
					$my_post['post_status'] = $options['status'];
					$my_post['post_author'] = $options['user'];
					
					// Insert the post into the database
					$newid = wp_insert_post( $my_post );
					if (!empty($newid)) {
						if ($newid & 1) /*even or odd*/ $class = ' class="alternate"'; else $class = '';
						_e( "<tr".$class."><th>".$newid."</th><td>".$path."</td><td>".get_permalink($newid)."</td><td>".$title."</td></tr>");
					}
					// store old and new paths
	                $filearr[$newid] = $path;
					flush();
				} // if $createpage
				usleep(5000);
			 $filearr = import_html_files($path, $filearr); // recurse!
        }
      }
    } // end foreach
    return $filearr;
} // end function
  
function pretty_title($title) {
	$title = str_replace('_', ' ', $title);
	$title = str_replace('-', ' ', $title);
	$title = ucwords($title);
	return $title;
}

function parent_directory($path) {
	if (strpos($path, '\\') !== FALSE) {
		$win = true;
    	$path = str_replace('\\', '/', $path);
	}
    if (substr($path, strlen($path) - 1) != '/') $path .= '/'; 
    $path = substr($path, 0, strlen($path) - 1);
    $path = substr($path, 0, strrpos($path, '/')) . '/';
    if ($win) $path = str_replace('/', '\\', $path);
    return $path;
}
?>