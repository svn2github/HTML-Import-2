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
?>