<?php

if ( !defined('WP_LOAD_IMPORTERS') )
	return;

// Load Importer API
require_once ABSPATH . 'wp-admin/includes/import.php';

if ( !class_exists( 'WP_Importer' ) ) {
	$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
	if ( file_exists( $class_wp_importer ) )
		require_once $class_wp_importer;
}

if ( class_exists( 'WP_Importer' ) ) {
class HTML_Import extends WP_Importer {

	var $posts = array ();
	var $file;

	function header() {
		echo '<div class="wrap">';
		screen_icon();
		echo '<h2>'.__('Import HTML Files', 'import-html-pages').'</h2>';
	}

	function footer() {
		echo '</div>';
	}

	function unhtmlentities($string) { // From php.net for < 4.3 compat
		$trans_tbl = get_html_translation_table(HTML_ENTITIES);
		$trans_tbl = array_flip($trans_tbl);
		return strtr($string, $trans_tbl);
	}
	
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

	function html_import_clean_html( $string, $allowtags = NULL, $allowattributes = NULL ) {
	    $string = strip_tags( $string, $allowtags );
	    if ( !is_null( $allowattributes ) ) {
	        if ( !is_array( $allowattributes ) )
	            $allowattributes = explode( ",", $allowattributes );
	        if ( is_array( $allowattributes ) )
	            $allowattributes = implode( ")(?<!", $allowattributes );
	        if ( strlen( $allowattributes ) > 0)
	            $allowattributes = "(?<!".$allowattributes.")";
	        $string = preg_replace_callback( "/<[^>]*>/i", create_function(
	            '$matches',
	            'return preg_replace("/ [^ =]*'.$allowattributes.'=(\"[^\"]*\"|\'[^\']*\')/i", "", $matches[0]);'   
	        ), $string );
	    }
		$string = str_replace( '\n', ' ', $string ); // reduce line breaks
		$string = preg_replace( "/<[^\/>]*>([\s]?)*<\/[^>]*>/", ' ', $string ); // remove empty tags
		return $string;
	}

	function greet() {
		?>
		<div class="narrow">
		<p><?php _e('This importer allows you to import HTML files as posts or pages. ', 'import-html-pages');
		printf(__('If you have not yet visited the <a href="%s">HTML Import options page</a>, please do so now. You need to specify which portions of your HTML files should be imported.', 'import-html-pages'), 'options-general.php?page=html-import.php'); ?></p>
		
		<h4><?php _e('What are you importing today?'); ?></h4>
		<p>
		<label><input name="import_files" id="import_files" type="radio" value="directory" checked="checked"
		onclick="javascript: jQuery('#single').hide('fast'); jQuery('#directory').show('fast');"  />	
			<?php _e('a directory of files', 'import-html-pages'); ?></label> &nbsp; &nbsp;	
		<label><input name="import_files" id="import_files" type="radio" value="file" 
		onclick="javascript: jQuery('#directory').hide('fast'); jQuery('#single').show('fast');" />
			<?php _e('a single file', 'import-html-pages'); ?></label>
		</p>
		
		<form enctype="multipart/form-data" method="post" action="admin.php?import=html&amp;step=1"><p>
		
		<p id="single" style="display: none;">
		<label for="upload"><?php _e('Choose an HTML file from your computer:', 'import-html-pages'); ?></label>
		<input type="file" id="upload" name="import" size="25" />
		</p>
		
		<p id="directory">
			<?php $options = get_option('html_import');
			printf(__('Your files will be imported from <kbd>%s</kbd>. <a href="%s">Change directories</a>.', 'import-html-pages'),
			esc_html($options['root_directory']), 'options-general.php?page=html-import.php'); ?>
		</p>
		
		<input type="hidden" name="action" value="save" />
		
		<p class="submit">
			<input type="submit" name="submit" class="button" value="<?php echo esc_attr(__('Submit', 'import-html-pages')); ?>" />
		</p>
		<?php wp_nonce_field('html-import'); ?>
		</form>
		</div>
	<?php
	}

	function get_posts() {   // REDO ALL THIS
		/* The idea here is to get all posts in one big file, then process their contents into a post array.
		This is not what we need to do. We need to either:
		a) put the contents of one post (file) into the post array
		b) recurse through all the directories and dump the files' contents into the post array
		
		Perhaps we need a get_files() function that figures that out and passes the contents here for array insertion. 
		See the Gallery2 import() function for comparison.
		*/
		global $wpdb;
		set_magic_quotes_runtime(0);
		$datalines = file($this->file); // Read the file into an array
		$importdata = implode('', $datalines); // squish it
		$importdata = str_replace(array ("\r\n", "\r"), "\n", $importdata);
		$xml = simplexml_load_string($importdata);
		$index = 0;
		foreach($xml->post as $post) {
			$post_title = $post['description'];
			$post_date_gmt = $post['time'];
			$post_date_gmt = strtotime($post_date_gmt);
			$post_date_gmt = gmdate('Y-m-d H:i:s', $post_date_gmt);
			$post_date = get_date_from_gmt( $post_date_gmt );
			$category = $post['tag'];
			$categories = explode(" ", $category);
			$cat_index = 0;
			foreach ($categories as $category) {
				$categories[$cat_index] = $wpdb->escape($this->unhtmlentities($category));
				$cat_index++;
			}
			$post_content = $post['extended'];
			$post_content = $wpdb->escape($this->unhtmlentities(trim($post_content)));

			$post_link = $post['href'];
			$post_link = $wpdb->escape($this->unhtmlentities(trim($post_link)));
			$post_link = '<p class="delicious_post_link"><a href="'.$post_link.'">'.$post_title.'</a></p>';
			$post_content = $post_content.$post_link;
		
			// Clean up content
			$post_content = preg_replace_callback('|<(/?[A-Z]+)|', create_function('$match', 'return "<" . strtolower($match[1]);'), $post_content);
			$post_content = str_replace('<br>', '<br />', $post_content);
			$post_content = str_replace('<hr>', '<hr />', $post_content);

			$post_author = 1;
			if ($post['shared'] == 'no') $post_status = 'private';
			else $post_status = 'publish';
			$this->posts[$index] = compact('post_author', 'post_date', 'post_date_gmt', 'post_content', 'post_title', 'post_status', 'categories');
			$index++;
		}
	}
	
	function import_posts() {  // REDO ALL THIS
		echo '<ol>';
		$categoriestags = $_POST['categoriestags'];
		$cat_id = $_POST['cat_id'];
		foreach ($this->posts as $post) {
			echo "<li>".__('Importing post...', 'import-html-pages');

			extract($post);

			if ($post_id = post_exists($post_title, $post_content, $post_date)) {
				_e('Post already imported', 'import-html-pages');
			} else {
				$post_id = wp_insert_post($post);
				if ( is_wp_error( $post_id ) )
					return $post_id;
				if (!$post_id) {
					_e('Couldn&#8217;t get post ID', 'import-html-pages');
					return;
				}
				if (0 != count($categories)) 
					if ($categoriestags == 'Tags') {
					wp_add_post_tags($post_id, $categories);
					}
					else {
					wp_create_categories($categories, $post_id);
					} 
				_e('Done !', 'import-html-pages');
			}
			echo '</li>';
		}

		echo '</ol>';

	}
	
	function import() {
		
		if ($_POST['import_file'] == 'file') {
			$file = wp_import_handle_upload();
			if ( isset($file['error']) ) {
				echo $file['error'];
				return;
			}

			$this->file = $file['file'];
		}
		else {
			// need to get the directory option here, open files, and start recursing
		}
		$this->get_posts();
		$result = $this->import_posts();
		
		if ( is_wp_error( $result ) )
			return $result;
		wp_import_cleanup($file['id']);
		do_action('import_done', 'html');

		echo '<h3>';
		printf(__('All done. <a href="%s">Have fun!</a>', 'import-html-pages'), 'edit.php');
		echo '</h3>';
	}

	function dispatch() {
		if (empty ($_GET['step']))
			$step = 0;
		else
			$step = (int) $_GET['step'];

		$this->header();

		switch ($step) {
			case 0 :
				$this->greet();
				break;
			case 1 :
				check_admin_referer('html-import');
				$result = $this->import();
				if ( is_wp_error( $result ) )
					echo $result->get_error_message();
				break;
			// case 2 : import images?
		}

		$this->footer();
	}

	function HTML_Import() {
		// Nothing.
	}
}

} // class_exists( 'WP_Importer' )

$html_import = new HTML_Import();

register_importer('html', __('HTML', 'import-html-pages'), __('Import HTML files.', 'import-html-pages'), array ($html_import, 'dispatch'));
?>