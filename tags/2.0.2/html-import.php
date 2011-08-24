<?php
/*
Plugin Name: HTML Import 2
Plugin URI: http://sillybean.net/code/wordpress/html-import/
Description: Imports well-formed static HTML files into WordPress posts or pages. Supports Dreamweaver templates and Word HTML cleanup. Visit the <a href="options-general.php?page=html-import.php">options page</a> to get started. See the <a href="http://sillybean.net/code/wordpress/html-import-2/user-guide/">User Guide</a> for details.
Version: 2.0.2
Author: Stephanie Leary
Author URI: http://sillybean.net/
License: GPL 2
*/

require_once ('html-importer.php');
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
	add_option( 'html_import', $options, '', 'no' );
}
register_activation_hook(__FILE__, 'html_import_set_defaults');

//register our settings
function register_html_import_settings() {
	register_setting( 'html_import', 'html_import', 'html_import_validate_options');
}

// when uninstalled, remove option
function html_import_remove_options() {
	delete_option('html_import');
}
register_uninstall_hook( __FILE__, 'html_import_remove_options' );
// for testing only
// register_deactivation_hook( __FILE__, 'html_import_remove_options' );

function html_import_add_pages() {
// Add option page to admin menu
	$pg = add_options_page(__('HTML Import', 'import-html-pages'), __('HTML Import', 'import-html-pages'), 'manage_options', basename(__FILE__), 'html_import_options_page');
	
// Add styles and scripts
	add_action( 'admin_print_styles-'.$pg, 'add_html_import_styles' );
	add_action( 'admin_print_scripts-'.$pg, 'html_import_scripts' );

// register setting
	add_action( 'admin_init', 'register_html_import_settings' );
		
// Help screen 
	$text = '<p>'.sprintf(__('This is a complicated importer with lots of options. If you have never used this importer before, you should take a look at the <a href="%s">User Guide</a>.', 'import-html-pages' ), 'http://sillybean.net/code/wordpress/html-import-2/user-guide/').'</p>';
	$text .= '<p>'.__("You need to look through the first five tabs and save your settings before you run the importer. The sixth (Tools) contains links to some tools that are helpful after you've imported.", 'import-html-pages' ).'</p>';
	
	$text .= '<h3>'.__('Tips', 'html-import-pages')."</h3>
    <ol>
		<li>" . __("If there is already some content in this site, you should back up your database before you import.", 'import-html-pages' )."</li>        
		<li>" . __("Before you import, deactivate any crosspost or notification plugins.", 'import-html-pages' )."</li>
		<li>" . __("Try uploading a single file before you run the importer on the whole directory. Check the imported page and see whether you need to adjust your content and/or title settings.", 'import-html-pages' )."</li>
		<li>" . __("Need to import both posts and pages? Run the importer on a subdirectory (e.g. 'news'), then add the subdirectory name to the list of skipped directories and run the importer again on the parent directory.", 'import-html-pages' )."</li>
    </ol>";
	$text .= '<h3>' . __( 'More Help', 'import-html-pages' ) . '</h3>';

	$text .= '<ul>';
	$text .= '<li><a href="http://sillybean.net/code/wordpress/html-import-2/user-guide/">' . __( 'User Guide', 'import-html-pages' ) . '</a></li>';
	$text .= '<li><a href="http://sillybean.net/code/wordpress/html-import-2/">' . __( 'Plugin Home Page', 'import-html-pages' ) . '</a></li>';
	$text .= '<li><a href="http://forum.sillybean.net/forums/forum/html-import-2/">' . __( 'Support Forum', 'import-html-pages' ) . '</a></li>';
	$text .= '</ul>';
	
	add_contextual_help( $pg, $text );
}
add_action('admin_menu', 'html_import_add_pages');
?>