<?php
/*
Plugin Name: Import HTML Pages
Plugin URI: http://sillybean.net/code/wordpress/html-import/
Description: Imports well-formed static HTML files into WordPress posts or pages. Supports Dreamweaver templates and Word HTML cleanup.
Version: 1.30
Author: Stephanie Leary
Author URI: http://sillybean.net/

== TODO in 2.0 ==

* import images
* handle single file uploads
* jQuery directory picker?
* change over to the usual importer class?
* progress meter?
* move most of the hints into the Help screen

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
function html_import_activation_check() {
	if (version_compare(PHP_VERSION, '5.0.0', '<')) {
		deactivate_plugins(basename(__FILE__)); // Deactivate myself
		wp_die("Sorry, but you can't run this plugin, it requires PHP 5 or higher.");
	}
	$options = html_import_get_options(); 
	add_option('html_import', $options, '', 'yes');
}
register_activation_hook(__FILE__, 'html_import_activation_check');

// when uninstalled, remove option
register_uninstall_hook( __FILE__, 'html_import_remove_options' );

function html_import_remove_options() {
	delete_option('html_import');
}

// i18n
$plugin_dir = basename(dirname(__FILE__)). '/languages';
load_plugin_textdomain( 'html_import', 'wp-content/plugins/' . $plugin_dir, $plugin_dir );

function html_import_plugin_actions($links, $file) {
 	if ($file == 'html-import/html-import.php' && function_exists("admin_url")) {
		$settings_link = '<a href="' . admin_url('options-general.php?page=html-import') . '">' . __('Import Files', 'import-html-pages') . '</a>';
		array_unshift($links, $settings_link); 
	}
	return $links;
}
add_filter('plugin_action_links', 'html_import_plugin_actions', 10, 2);

// Hook for adding admin menus
add_action('admin_menu', 'html_import_add_pages');

function html_import_css() {
	$options = get_option('html_import'); ?>
		<style type="text/css">
	 	.clear, #html_import p.submit, #html_import .wrap p, #html_import .wrap h3, p.htmlimportfloat.clear, div#tips { clear: both; } 	
		.wrap h4 { margin: 1em 0 0; }
		kbd { display: inline; } 
		p.htmlimportfloat { float: left; width: 15em; margin-right: 2em; clear: none; } 
		p.widefloat { width: 31.4em; } 	
		input.widefloat { width: 48.5em; } 	
		small { color: #666; }
		#importing th { width: 32% } 
		#importing th#id { width: 4% }
		textarea#import-result { height: 12em; width: 100%; }
		#content-region, #title-region, #taxonomy { width: 100%; height: 8em; z-index: 10; }
		#content-switch, #title-switch, #type-switch { position: relative; height: 8em; }
		#content-region, #content-tag, #title-region, #title-tag, #taxonomy, #hierarchy { position: absolute; }
		#content-region, #title-region, #clean-region, #taxonomy { display: none; }
		#tips h3 { margin-bottom: 0; }
		#tips { -moz-border-radius: 4px; -webkit-border-radius: 4px; border-radius: 4px; border: 1px solid #dfdfdf; background: #fff; padding: 0 2em 1em; }
		</style>
	<?php
}

function html_import_add_pages() {
    // Add a new submenu under Options:
	$css = add_options_page(__('HTML Import', 'import-html-pages'), __('HTML Import', 'import-html-pages'), 'manage_options', basename(__FILE__), 'html_import_options_page');
	add_action("admin_head-$css", 'html_import_css');
}

function html_import_get_options() {
	// set defaults
	$defaults = array(
		'root_directory' => __(ABSPATH.'html-files-to-import', 'import-html-pages'),
		'file_extensions' => 'html,htm,shtml',
		'skipdirs' => __('images', 'import-html-pages'),
		'status' => 'publish',
		'root_parent' => 0,
		'type' => 'page',
		'timestamp' => 'filemtime',
		'import_content' => 'tag',
		'content_region' => '',
		'content_tag' => __('div', 'import-html-pages'),
		'content_tagatt' => __('id', 'import-html-pages'),
		'content_attval' => __('content', 'import-html-pages'),
		'clean_html' => 0,
		'allow_tags' => '<p><br><img><a><ul><ol><li><blockquote><cite><em><i><strong><b><h2><h3><h4><h5><h6><hr>',
		'allow_attributes' => 'href,alt,title,src',
		'encode' => 0,
		'import_title' => 'tag',
		'title_region' => '',
		'title_tag' => __('title', 'import-html-pages'),
		'title_tagatt' => '',
		'title_attval' => '',
		'remove_from_title' => '',
		'meta_desc' => 1,
		'user' => 0,
		'tagwith' => '',
		'taxwith' => '',
		'categorize' => get_option('default_category')
	);
	$options = get_option('html_import');
	if (!is_array($options)) $options = array();
	return array_merge( $defaults, $options );
}

add_action('admin_init', 'register_html_import_options' );
function register_html_import_options(){
	register_setting( 'html_import', 'html_import' );
}

// displays the options page content
function html_import_options_page() {
	if ( current_user_can('import') ) {  
	
	// variables for the field and option names 
		$hidden_field_name = 'html_import_submit_hidden';
	
		// See if the user has posted us some information
		// If they did, this hidden field will be set to 'Y'
		if ( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {
			if (! wp_verify_nonce($_POST['_wpnonce'], 'html_import') ) die("Failed security check!");
		 ?> 
			<div class="wrap">
			<h2><?php _e( 'Importing...', 'import-html-pages'); ?></h2>
			
			<table class="widefat page fixed" id="importing" cellspacing="0">
			<thead><tr>
			<th id="id"><?php _e('ID', 'import-html-pages'); ?></th>
			<th><?php _e('Old path', 'import-html-pages'); ?></th>
			<th><?php _e('New path', 'import-html-pages'); ?></th>
			<th><?php _e('Title', 'import-html-pages'); ?></th>
			</tr></thead><tbody> 
				<?php	
	  			// Save the posted value in the database
				$options = array();
				$options['root_directory'] = $_POST['root_directory'];
				$options['old_url'] = $_POST['old_url'];
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
				$options['encode'] = $_POST['encode'];
				$options['clean_html'] = $_POST['clean_html'];
				$options['allow_tags'] = $_POST['allow_tags'];
				$options['allow_attributes'] = $_POST['allow_attributes'];
				$options['import_title'] = $_POST['import_title'];
				$options['title_region'] = $_POST['title_region'];
				$options['title_tag'] = $_POST['title_tag'];
				$options['title_tagatt'] = $_POST['title_tagatt'];
				$options['title_attval'] = $_POST['title_attval'];
				$options['remove_from_title'] = $_POST['remove_from_title'];
				$options['meta_desc'] = $_POST['meta_desc'];
				$options['root_parent'] = $_POST['root_parent'];
				$options['user'] = $_POST['user'];
				$options['tagwith'] = $_POST['tagwith'];
				$options['taxwith'] = $_POST['taxwith'];
				$options['categorize'] = $_POST['categorize'];

				update_option('html_import', $options);

				// make the magic happen
				$result = import_html_files($options['root_directory']);

				// Put an options updated message on the screen 
				?>
			</tbody></table>
			<h3><?php _e('.htaccess Redirects', 'import-html-pages'); ?></h3>
			<p><small><?php _e('if you need to redirect visitors from the old file locations to your new WordPress pages, copy these redirects 
							   into your .htaccess file above the WordPress rules. <strong>Note:</strong> You might need to search &amp; replace 
							   first if your import root directory was not the same as your web root. Also, if you imported many files, the complete 
							   list of redirects might slow your web server\'s performance. Consider copying only essential ones, or if there\'s a 
							   pattern to your file or directory names, create a 
							   <a href="http://www.workingwith.me.uk/articles/scripting/mod_rewrite">RewriteRule</a> instead.', 'import-html-pages'); ?></small></p>
				<textarea id="import-result"><?php
					foreach ($result as $id => $old) {
						$url = esc_url($options['old_url']);
						$url = rtrim($url, '/');
						if (!empty($url)) $old = str_replace($options['root_directory'], $url, $old);
						echo "Redirect\t".$old."\t".get_permalink($id)."\t[R=301,NC,L]\n";
					} ?>
				</textarea>
				<div class="updated"><p><strong>
					<?php _e("Imported ", 'import-html-pages'); 
					echo count($result); 
					_e(" files in ", 'import-html-pages'); 
					echo timer_stop(0,5); 
					_e(" seconds. See above for any pages that did not automatically import and need your attention.", 'import-html-pages'); ?></strong></p>
				</div>
			</div> <!-- wrap -->
		<?php 
	} // if form submitted
	// Now display the options editing screen  ?>
	
    <div class="wrap" id="html_import">
	<form method="post" id="html_import_form">
    <?php $options = get_option('html_import'); ?>
	<h2><?php _e( 'HTML Page Import Options ', 'import-html-pages'); ?></h2> 
	<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">  
	<?php 
	$nonce= wp_create_nonce  ('html_import');
	wp_nonce_field('html_import'); 
	?>
    <div id="optionsform">
    <p><label><?php _e("Beginning directory: ", 'import-html-pages'); ?><br />
    <input type="text" name="root_directory" id="root_directory" value="<?php esc_attr_e($options['root_directory']); ?>" 
    	class="widefloat" />  </label><br />
	<small><?php _e('This should be a full path from the server root, on the same server where WordPress is running now.', 'import-html-pages'); ?><br />
		<?php _e('Hint: the full path to this WordPress installation is: '); ?><kbd><?php echo ABSPATH; ?></kbd></small></p>
	
	 <p><label><?php _e("Old URL: ", 'import-html-pages'); ?><br />
	    <input type="text" name="old_url" id="old_url" value="<?php esc_html_e($options['old_url']); ?>" class="widefloat" />  </label><br />
		<small><?php _e('Enter the old URL matching your beginning directory if you would like .htaccess redirects to be generated for you.', 'import-html-pages'); ?></small></p>
    
    <p><label><?php _e("Process files with these extensions: ", 'import-html-pages'); ?><br />
    <input type="text" name="file_extensions" id="file_extensions" value="<?php esc_attr_e($options['file_extensions']); ?>" 
    	class="widefloat" />  </label><br />
	<small><?php _e("Enter file extensions, without periods, separated by commas. All other file types will be ignored.", 'import-html-pages'); ?></small></p>

    <p><label><?php _e("Skip directories with these names: ", 'import-html-pages'); ?><br />
        <input type="text" name="skipdirs" id="skipdirs" value="<?php esc_attr_e($options['skipdirs']); ?>" 
        	class="widefloat" />  </label><br />
    <small><?php _e("Enter directory names, without slashes, separated by commas. All files in these directories will be ignored.", 'import-html-pages'); ?></small></p>
    
    <h3><?php _e("Content", 'import-html-pages'); ?></h3>
    <p><?php _e("Select content by:", 'import-html-pages'); ?></p>
	
    <p><label><input name="import_content" id="import_content"  type="radio" value="tag" 
	<?php if ($options['import_content'] == "tag") { ?> checked="checked" <?php } ?> onclick="javascript: jQuery('#content-region').hide('fast'); jQuery('#content-tag').show('fast');" />
		<?php _e('HTML tag', 'import-html-pages'); ?></label>&nbsp;&nbsp;
    <label><input name="import_content" id="import_content"  type="radio" value="region" 
	<?php if ($options['import_content'] == "region") { ?> checked="checked" <?php  } ?> onclick="javascript: jQuery('#content-tag').hide('fast'); jQuery('#content-region').show('fast');" />	
		<?php _e('Dreamweaver template region', 'import-html-pages'); ?></label> </p>
    
    <div id="content-switch">
        <div id="content-tag" <?php if ($options['import_content'] == 'region') echo "style=display:none;"; ?>>
            <p class="htmlimportfloat clear"><label><?php _e("Tag", 'import-html-pages'); ?><br />
            <input type="text" name="content_tag" id="content_tag" value="<?php echo esc_attr_e($options['content_tag']); ?>" />
            </label>
            <br />
            <small><?php _e("The HTML tag, without brackets", 'import-html-pages'); ?></small></p>
            
            <p class="htmlimportfloat"><label><?php _e("Attribute", 'import-html-pages'); ?><br />
            <input type="text" name="content_tagatt" id="content_tagatt" value="<?php esc_attr_e($options['content_tagatt']); ?>" />
            </label>
            <br />
            <small><?php _e("Leave blank to use a tag without an attribute, or when the attributes don't matter, such as &lt;body&gt;", 'import-html-pages'); ?></small>
            </p>
            
            <p class="htmlimportfloat"><label><?php _e("= Value", 'import-html-pages'); ?><br />
            <input type="text" name="content_attval" id="content_attval" value="<?php esc_attr_e($options['content_attval']); ?>" />
            </label>
            <br />
            <small><?php _e("Enter the attribute's value (such as width, ID, or class name) without quotes", 'import-html-pages'); ?></small>
            </p>
        </div>
        
        <p id="content-region" <?php if ($options['import_content'] == 'region') echo "style=display:block;"; ?>>
		<label><?php _e("Dreamweaver template region", 'import-html-pages'); ?><br />
        <input type="text" name="content_region" id="content_region" value="<?php esc_attr_e($options['content_region']); ?>" />  
        </label><br />
        <small><?php _e("The name of the editable region (e.g. 'Main Content')", 'import-html-pages'); ?></small>
        </p> 
    </div>
	<p><label><input name="encode" id="encode" value="1" type="checkbox" <?php checked($options['encode']); ?> /> 
		<?php _e("Convert character set to UTF-8", 'import-html-pages'); ?> </label><br />
	<small><?php _e("Check this option if your special characters are not imported correctly.", 'import-html-pages'); ?></small></p>
   
	<p><label><input name="meta_desc" id="meta_desc" value="1" type="checkbox" <?php checked($options['meta_desc']); ?> /> 
		<?php _e("Use meta description as excerpt", 'import-html-pages'); ?> </label><br />
	<small><?php _e("Excerpts will be stored for both posts and pages. However, to edit and/or display excerpts for pages, you will need to install 
					a plugin such as <a href=\"http://blog.ftwr.co.uk/wordpress/page-excerpt/\">PJW Page Excerpt</a>
					or <a href=\"http://www.laptoptips.ca/projects/wordpress-excerpt-editor/\">Excerpt Editor</a>.", 'import-html-pages'); ?></small></p>
    
    <p><label><input name="clean_html" id="clean_html"  type="checkbox" value="1" 
		<?php checked($options['clean_html'], '1'); ?> onclick="jQuery(this).is(':checked') && jQuery('#clean-region').show('fast') || jQuery('#clean-region').hide('fast');" />
		<?php _e("Clean up bad (Word, Frontpage) HTML?", 'import-html-pages'); ?> </label> 
    </p>
    
    <div id="clean-switch">
        <div  id="clean-region" <?php if ($options['clean_html'] == '1') echo "style=display:block;"; ?>>
        	<p>
                <label><?php _e("Allowed HTML", 'import-html-pages'); ?><br />
                <input type="text" name="allow_tags" id="allow_tags" value="<?php esc_attr_e($options['allow_tags']); ?>" 
                    class="widefloat" />  </label><br />
                <small><?php _e("Enter tags (with brackets) to be preserved. <br />Suggested: ", 'import-html-pages'); ?> 
                &lt;p&gt;
                &lt;br&gt;
                &lt;img&gt;
                &lt;a&gt;
                &lt;ul&gt;
                &lt;ol&gt;
                &lt;li&gt;
                &lt;blockquote&gt;
                &lt;cite&gt;
                &lt;em&gt;
                &lt;i&gt;
                &lt;strong&gt;
                &lt;b&gt;
                &lt;h2&gt;
                &lt;h3&gt;
                &lt;h4&gt;
                &lt;h5&gt;
                &lt;h6&gt;
                &lt;hr&gt;
                <br />
                
                <em><?php _e("If you have data tables, also include:", 'import-html-pages'); ?></em> 
                &lt;table&gt;
                &lt;tbody&gt;
                &lt;thead&gt;
                &lt;tfoot&gt;
                &lt;tr&gt;
                &lt;td&gt;
                &lt;th&gt;
                &lt;caption&gt;
                &lt;colgroup&gt;
                </small>
            </p> 
            
            <p><label><?php _e("Allowed attributes", 'import-html-pages'); ?><br />
            <input type="text" name="allow_attributes" id="allow_attributes" value="<?php esc_attr_e($options['allow_attributes']); ?>" 
            	class="widefloat" />  </label><br />
            <small><?php _e("Enter attributes separated by commas. <br />Suggested: href,src,alt,title<br />
    			<em>If you have data tables, also include:</em> summary,rowspan,colspan,span", 'import-html-pages'); ?></small>
            </p> 
        </div>
    </div>
    
    
    <h3><?php _e("Title", 'import-html-pages'); ?></h3>
    
    <p><?php _e("Select title by:", 'import-html-pages'); ?><br />
	<label><input name="import_title" id="import_title"  type="radio" value="tag" 
		<?php if ($options['import_title'] == "tag") { ?> checked="checked" <?php } ?> onclick="javascript: jQuery('#title-region').hide('fast'); jQuery('#title-tag').show('fast');" /> 
		 <?php _e("HTML tag", 'import-html-pages'); ?></label>&nbsp;&nbsp;  
    <label><input name="import_title" id="import_title"  type="radio" value="region" 
		<?php if ($options['import_title'] == "region") { ?> checked="checked" <?php } ?>  onclick="javascript: jQuery('#title-region').show('fast'); jQuery('#title-tag').hide('fast');" />
         <?php _e("Dreamweaver template region", 'import-html-pages'); ?></label></p>
    
    <div id="title-switch">
        <div id="title-tag" <?php if ($options['import_title'] == 'region') echo "style=display:none;"; ?>>
            <p class="htmlimportfloat clear"><label><?php _e("Tag containing page title: ", 'import-html-pages'); ?><br />
            <input type="text" name="title_tag" id="title_tag" value="<?php echo esc_attr_e($options['title_tag']); ?>" /></label><br />
            <small><?php _e("The HTML tag, without brackets", 'import-html-pages'); ?></small></p>
            
            <p class="htmlimportfloat"><label><?php _e("Attribute", 'import-html-pages'); ?><br />
            <input type="text" name="title_tagatt" id="title_tagatt" value="<?php echo esc_attr_e($options['title_tagatt']); ?>" /></label>
            <br />
            <small><?php _e("Leave blank to use a tag without an attribute, or when the attributes don't matter, such as &lt;title&gt;", 'import-html-pages'); ?></small></p>
            
            <p class="htmlimportfloat"><label><?php _e("= Value", 'import-html-pages'); ?><br />
            <input type="text" name="title_attval" id="title_attval" value="<?php esc_attr_e($options['title_attval']); ?>" /></label>
            <br />
            <small><?php _e("Enter the attribute's value (such as width, ID, or class name) without quotes", 'import-html-pages'); ?></small></p>
        </div>
        <p id="title-region"  <?php if ($options['import_title'] == 'region') echo "style=display:block;"; ?>>
			<label><?php _e("Dreamweaver template region containing page title: ", 'import-html-pages'); ?><br />
        <input type="text" name="title_region" id="title_region" value="<?php esc_attr_e($options['title_region']); ?>" /></label>
        <br />
        <small><?php _e("The name of the editable region (e.g. 'Page Title')", 'import-html-pages'); ?></small></p>
   	</div>
   
    <p class="clear"><label><?php _e("Phrase to remove from page title: ", 'import-html-pages'); ?><br />
    <input type="text" name="remove_from_title" id="remove_from_title" value="<?php esc_attr_e($options['remove_from_title']); ?>" 
    	class="widefloat" />  </label><br />
	<small><?php _e("Any common title phrase (such as the site name, which WordPress will duplicate)", 'import-html-pages'); ?></small></p>
    
    <div id="metadata">
    <h3><?php _e("Metadata", 'import-html-pages'); ?></h3>
    
    <p class="htmlimportfloat clear"><?php _e("Import files as: ", 'import-html-pages'); ?><br />
    <label><input name="type" type="radio" value="page" 
	<?php if ($options['type'] == 'page') { ?> checked="checked" <?php } ?> onclick="javascript: jQuery('#taxonomy').hide('fast'); jQuery('#hierarchy').show('fast');" /> 
		<?php _e("pages", 'import-html-pages'); ?></label>&nbsp;&nbsp;
    <label><input name="type" type="radio" value="post" 
	<?php if ($options['type'] == "post") { ?> checked="checked" <?php  } ?> onclick="javascript: jQuery('#hierarchy').hide('fast'); jQuery('#taxonomy').show('fast');" /> 
		<?php _e("posts", 'import-html-pages'); ?></label> </p>
    
    <p class="htmlimportfloat widefloat"><label><?php _e("Set timestamps to: ", 'import-html-pages'); ?>
    <select name="timestamp" id="timestamp">
    	<option value="now" <?php if ($options['timestamp'] == 'now') echo 'selected="selected"'; ?>><?php _e("now", 'import-html-pages'); ?></option>
        <option value="filemtime" <?php if ($options['timestamp'] == 'filemtime') echo 'selected="selected"'; ?>>
			<?php _e("last time the file was modified", 'import-html-pages'); ?></option>
    </select></label></p>
 
    <p class="htmlimportfloat clear"><label><?php _e("Set status to: ", 'import-html-pages'); ?>
    <select name="status" id="status">
    	<option value="publish" <?php selected('publish', $options['status']); ?>><?php _e("publish", 'import-html-pages'); ?></option>
        <option value="draft" <?php selected('draft', $options['status']); ?>><?php _e("draft", 'import-html-pages'); ?></option>
        <option value="private" <?php selected('private', $options['status']); ?>><?php _e("private", 'import-html-pages'); ?></option>
        <option value="pending" <?php selected('pending', $options['status']); ?>><?php _e("pending", 'import-html-pages'); ?></option>
    </select></label></p>
    
    <p class="htmlimportfloat widefloat"><label><?php _e("Set author to: ", 'import-html-pages'); ?>
    <?php wp_dropdown_users(array('selected' => $options['user'])); ?></label></p>
    
    <div id="type-switch" class="clear">
        <p class="clear" id="hierarchy" <?php if ($options['type'] == 'post') echo "style=display:none;"; ?>>
			<label><?php _e("Import pages as children of: ", 'import-html-pages'); ?>
        <?php 
            $pages = wp_dropdown_pages(array('echo' => 0, 'selected' => $options['root_parent'], 'name' => 'root_parent', 
				'show_option_none' => __('None (top level)', 'import-html-pages'), 'sort_column'=> 'menu_order, post_title'));
            if (empty($pages)) $pages = "<select name=\"root_parent\"><option value=\"0\">"._e('None (top level)', 'import-html-pages')."</option></select>";
            echo $pages;
        ?>
        </label><br />
        <small><?php _e('Your directory hierarchy will be maintained, but your top level files will be children of the page selected here.', 'import-html-pages'); ?></small>
        </p>
    
        <div id="taxonomy" <?php if ($options['type'] == 'post') echo "style=display:block;"; ?>>
            <p class="clear"><label><?php _e("Categorize imported posts as: ", 'import-html-pages'); ?>
            <?php wp_dropdown_categories(array('name' => 'categorize', 'hide_empty'=>0, 'hierarchical'=>1, 'selected'=>$options['categorize'])); ?>
            </p>
            
            <p class="clear"><label><?php _e("Tag imported posts as: ", 'import-html-pages'); ?>
            <input type="text" name="tagwith" id="tagwith" value="<?php esc_attr_e($options['tagwith']); ?>" 
            	class="widefloat" /></label>
            <br />
            <small><?php _e('Enter tags separated by commas.', 'import-html-pages'); ?></small></p>
        </div>
    </div>

	<?php global $wp_taxonomies; ?>
	<?php if ( is_array( $wp_taxonomies ) ) :
		$standardtaxes = array('category','link_category','post_tag','nav_menu','post_format');
		$taxwith = $options['taxwith'];
		$taxoutput = ''; ?>
	<div id="custom-taxonomy" class="clear">
			<?php foreach ( $wp_taxonomies as $tax ) : ?>
				<?php if (!in_array($tax->name, $standardtaxes)) : 
					if (empty($taxwith)) $values = '';
					elseif (is_array($taxwith[$tax->name])) $values = implode(',', $taxwith[$tax->name]);
					else $values = $taxwith[$tax->name]; 
					$taxoutput .= '<p class="clear"><label>'.$tax->label.'<br />
					<input type="text" name="taxwith['.$tax->name.']" class="widefloat" 
							value="'.$values.'"  /></label>';
				 endif; ?>
			<?php endforeach; 
	if (!empty($taxoutput)) { ?>
	<h3><?php _e('Custom Taxonomies', 'import-html-pages'); ?></h3>
	<?php echo $taxoutput; } ?>
	</div>
	<?php endif; ?>

    </div>                
    
    <input type="hidden" name="action" value="update" />
	<input type="hidden" name="page_options" value="html_import" />
	
    <p class="submit">
	<input type="submit" name="submit" class="button-primary" value="<?php _e('Import using these options', 'import-html-pages'); ?>" />
	</p>
	</form>
    </div> <!-- #optionsform -->
    
    <div id="tips">
    <h3><?php _e("Tips", 'import-html-pages'); ?></h3>
    <p><small><?php _e("(of the technical sort)", 'import-html-pages'); ?></small></p>
    <ol>
    	<li><?php _e("You should see the options again once the import has finished. If you don't, the importer encountered a serious problem 
					 with one of your files and could not continue.", 'import-html-pages'); ?></li>
        <li><?php _e("If things didn't work out the way you intended and you need to delete all the posts or pages you just imported, make a 
					 note of the first and last IDs imported and use the <a href='http://www.wesg.ca/2008/07/wordpress-plugin-mass-page-remover/'>
					 Mass Page Remover plugin</a> to remove them all at once.", 'import-html-pages'); ?></li>
      	<li><?php _e("Need to import both posts and pages? Run the importer on a subdirectory (e.g. 'news'), then move those files somewhere else 
					temporarily while you run the importer again.", 'import-html-pages'); ?></li>
    </ol>
    <h3><?php _e("Tips", 'import-html-pages'); ?></h3>
    <p><small><?php _e("(of the monetary sort)", 'import-html-pages'); ?></small></p>
    <p><?php _e("Did this plugin save you hours and hours of copying? Buy me a cookie, if you don't mind!", 'import-html-pages'); ?></p>
    <!-- Donation link -->
    <form action="https://www.paypal.com/cgi-bin/webscr" method="post">
        <input type="hidden" name="cmd" value="_s-xclick">
        <input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----">
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
	$skipdirs = $options['skipdirs'];
	$skipdirs = explode(",", $skipdirs);
	$skipdirs = array_merge($skipdirs, array('.','..'));
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
			$contents = @fopen($path);  // read entire file
			if (empty($contents)) $contents = @file_get_contents($path);  // read entire file
			if (empty($contents)) wp_die("The PHP functions fopen and file_get_contents have both failed. We can't import any files without these functions. Please ask your server administrator if they are enabled.");
			if (function_exists('mb_convert_encoding') && ($options['encode'] == 1)) $encoded = mb_convert_encoding($contents, 'HTML-ENTITIES', "UTF-8"); 
			else $encoded = $contents;
			$doc = new DOMDocument();
			$doc->strictErrorChecking = FALSE; // ignore invalid HTML, we hope
			$doc->preserveWhiteSpace = FALSE;  
			$doc->formatOutput = false;  // speed this up
			@$doc->loadHTML($encoded);
			$xml = @simplexml_import_dom($doc);
			
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
			
			$remove = $options['remove_from_title'];
			if (!empty($remove))
				$my_post['post_title'] = str_replace($remove, '', $my_post['post_title']);
			
			$my_post['post_type'] = $options['type'];
			
			if ($my_post['post_type'] == 'page') {
				$parentdir = rtrim(html_import_parent_directory($path), '/');
				if (in_array($parentdir, $filearr))
					$my_post['post_parent'] = array_search($parentdir, $filearr);
				else $my_post['post_parent'] = $options['root_parent'];
			}
			else {
				$my_post['post_category'] = array($options['categorize']); // even one category must be passed as an array
				if (!empty($options['tagwith'])) {
					$my_post['tags_input'] = strip_tags($options['tagwith']); // no HTML, please
				}
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
				$content = $xml->xpath($xquery);
				if (is_array($content) && isset($content[0]) && is_object($content[0]))
					$my_post['post_content'] = $content[0]->asXML(); // asXML() preserves HTML in content
				else $my_post['post_content'] = '';
			}
			if (!empty($options['clean_html']))
				$my_post['post_content'] = html_import_clean_html($my_post['post_content'], $options['allow_tags'], $options['allow_attributes']);
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
			  html_import_set_custom_taxonomy($newid, $options['taxwith']);
			  // echo the table row
			  if ($newid & 1) /*even or odd*/ $class = ' class="alternate"'; else $class = '';
			  echo " <tr".$class."><th>".$newid."</th><td>".$path."</td><td>".get_permalink($newid).'</td><td>
				<a href="post.php?action=edit&post='.$newid.'">'.$my_post['post_title']."</td></tr>";
			}
			else echo "<tr><td colspan='4' class='error'> Could not import ".$val.". You should copy its contents manually.</td></tr>";
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
					if (!empty($ext)) $exts[] .= $ext;
				}
				
				// allowed extensions only, please
				$createpage = @array_intersect($exts, $allowed); // suppress warnings about not being an array
				
				// if the directory contains the right kind of files, create an empty parent page
				if (!empty($createpage)) { 
				// start building the WP post object to insert
					$my_post = array();		  
					
					$title = trim(strrchr($path,'/'),'/');
					$title = str_replace('_', ' ', $title);
					$title = str_replace('-', ' ', $title);
					$my_post['post_title'] = ucwords($title);
					
					if ($options['timestamp'] == 'filemtime')
						$date = filemtime($path);
					else $date = time();
					$my_post['post_date'] = date("Y-m-d H:i:s", $date);
					$my_post['post_date_gmt'] = date("Y-m-d H:i:s", $date);
					
					$my_post['post_type'] = 'page';
					
					$parentdir = rtrim(html_import_parent_directory($path), '/');
					if (in_array($parentdir, $filearr))
						$my_post['post_parent'] = array_search($parentdir, $filearr);
					else $my_post['post_parent'] = $options['root_parent'];
					
					$my_post['post_content'] = '<!-- placeholder -->';
					$my_post['post_status'] = $options['status'];
					$my_post['post_author'] = $options['user'];
					
					// Insert the post into the database
					$newid = wp_insert_post( $my_post );
					if (!empty($newid)) {
						html_import_set_custom_taxonomy($newid, $options['taxwith']);
						// output table row
						if ($newid & 1) /*even or odd*/ $class = ' class="alternate"'; else $class = '';
						echo "<tr".$class."><th>".$newid."</th><td>".$path."</td><td>".get_permalink($newid).'</td>
							<td><a href="post.php?action=edit&post='.$newid.'">'.$title."</td></tr>";
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

// set the custom taxonomies (ex. input field: taxinputs[people] = jane, tim )  
function html_import_set_custom_taxonomy($postid, $taxinputs) {
	  if (is_array($taxinputs)) {
		// we might have more than one taxonomy; loop through them
	  	foreach ($taxinputs as $name => $terms) {
			// each taxonomy might have multiple values stored as a string (separated by commas, possibly with spaces)
			$terms = is_array($terms) ? $terms : explode( ',', trim($terms, " \n\t\r\0\x0B,") );
			
			// we have names; we need slugs or IDs
			$termslugs = array();
			foreach ($terms as $term) {
				$termslugs[] .= sanitize_title($term);
			}
			wp_set_object_terms( $postid, $termslugs, $name );
		}
      }
}

function html_import_parent_directory($path) {
	$win = false;
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
	
function html_import_clean_html($string,$allowtags=NULL,$allowattributes=NULL){
    $string = strip_tags($string,$allowtags);
    if (!is_null($allowattributes)) {
        if(!is_array($allowattributes))
            $allowattributes = explode(",",$allowattributes);
        if(is_array($allowattributes))
            $allowattributes = implode(")(?<!",$allowattributes);
        if (strlen($allowattributes) > 0)
            $allowattributes = "(?<!".$allowattributes.")";
        $string = preg_replace_callback("/<[^>]*>/i",create_function(
            '$matches',
            'return preg_replace("/ [^ =]*'.$allowattributes.'=(\"[^\"]*\"|\'[^\']*\')/i", "", $matches[0]);'   
        ),$string);
    }
	$string = str_replace('\n', ' ', $string); // reduce line breaks
	$string = preg_replace("/<[^\/>]*>([\s]?)*<\/[^>]*>/", ' ', $string); // remove empty tags
	return $string;
}
?>