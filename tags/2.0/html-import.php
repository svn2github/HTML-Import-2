<?php
/*
Plugin Name: Import HTML Pages
Plugin URI: http://sillybean.net/code/wordpress/html-import/
Description: Imports well-formed static HTML files into WordPress posts or pages. Supports Dreamweaver templates and Word HTML cleanup.
Version: 2.0-alpha
Author: Stephanie Leary
Author URI: http://sillybean.net/
License: GPL 2
*/

require_once ('importer.php');
require_once ('html-import-options.php');

// plugin_activation_check() by Otto
function html_import_activation_check() {
	if (version_compare(PHP_VERSION, '5.0.0', '<')) {
		deactivate_plugins(basename(__FILE__)); // Deactivate myself
		wp_die("Sorry, but you can't run this plugin, it requires PHP 5 or higher.");
	}
}
register_activation_hook(__FILE__, 'html_import_activation_check');

// i18n
$plugin_dir = basename(dirname(__FILE__)). '/languages';
load_plugin_textdomain( 'html_import', 'wp-content/plugins/' . $plugin_dir, $plugin_dir );

// Option page styles
function html_import_css() {
    wp_register_style( 'html-import-css', WP_PLUGIN_URL . '/import-html-pages/html-import-styles.css' );
}
function add_html_import_styles() {
    wp_enqueue_style( 'html-import-css' );
}
add_action( 'admin_init', 'html_import_css' );

// Option page scripts
function html_import_scripts() {
	wp_print_scripts( 'jquery-ui-tabs' );
}

// set default options 
function html_import_set_defaults() {
	$options = html_import_get_options();
	add_option( 'html_import', $options, '', 'yes' );
}
register_activation_hook(__FILE__, 'html_import_set_defaults');

//register our settings
function register_html_import_settings() {
	register_setting( 'html_import', 'html_import', 'html_import_sanitize_options');
}

// when uninstalled, remove option
function html_import_remove_options() {
	delete_option('html_import');
}
//register_uninstall_hook( __FILE__, 'html_import_remove_options' );
// for testing only
register_deactivation_hook( __FILE__, 'html_import_remove_options' );

// Add option page to admin menu
function html_import_add_pages() {
	$pg = add_options_page(__('HTML Import', 'import-html-pages'), __('HTML Import', 'import-html-pages'), 'manage_options', basename(__FILE__), 'html_import_options_page');
	add_action( 'admin_print_styles-'.$pg, 'add_html_import_styles' );
	add_action( 'admin_print_scripts-'.$pg, 'html_import_scripts' );

// register setting
	add_action( 'admin_init', 'register_html_import_settings' );
		
// Help screen 
	$text = "<h3>Tips</h3>
    
    <ol>
    	<li>" . __("You should see the options again once the import has finished. If you don't, the importer encountered a serious problem 
					 with one of your files and could not continue.", 'import-html-pages' )."</li>
        <li>" . __("If things didn't work out the way you intended and you need to delete all the posts or pages you just imported, make a 
					 note of the first and last IDs imported and use the <a href='http://www.wesg.ca/2008/07/wordpress-plugin-mass-page-remover/'>
					 Mass Page Remover plugin</a> to remove them all at once.", 'import-html-pages' )."</li>
      	<li>" . __("Need to import both posts and pages? Run the importer on a subdirectory (e.g. 'news'), then skip that directory when you run the importer again on the parent directory.", 'import-html-pages' )."</li>
    </ol>";
	$text .= '<p><strong>' . __( 'For more information:', 'import-html-pages' ) . '</strong></p>';

	$text .= '<ul>';
	$text .= '<li><a href="http://yoursite.com/theme-documentation">' . __( 'Documentation', 'import-html-pages' ) . '</a></li>';
	$text .= '<li><a href="http://yoursite.com/support">' . __( 'Support Forums', 'import-html-pages' ) . '</a></li>';
	$text .= '</ul>';

	$text .= "Did this plugin save you hours and hours of copying? Buy me a cookie, if you don't mind!</p>";
	$text .= '<!-- Donation link -->
    <form action="https://www.paypal.com/cgi-bin/webscr" method="post">
        <input type="hidden" name="cmd" value="_s-xclick">
        <input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----">
        <input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
        <img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
        </form>';

	add_contextual_help( $pg, $text );
}
add_action('admin_menu', 'html_import_add_pages');

/* -------- PROCESSING ----------- */
//*
function import_html_files($rootdir, $filearr=array())   {
/*
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
			if (empty($contents)) $contents = @file_get_contents($path); 
			if (empty($contents)) wp_die("The PHP functions fopen() and file_get_contents() have both failed. We can't import any files without these functions. Please ask your server administrator if they are enabled.");
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
			  if ($newid & 1) $class = ' class="alternate"'; else $class = '';
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
						if ($newid & 1) $class = ' class="alternate"'; else $class = '';
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
/**/
} // end function
/**/
?>