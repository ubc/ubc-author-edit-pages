<?php
/**
 * UBC Author Edit Pages
 *
 * This plugin allows users with the author role to edit pages where they have been
 * assigned as the author, mimicking the behavior that authors already have for posts.
 *
 * @package           UBC\AuthorEditPages
 * @author            Rich Tape/UBC CTLT
 * @copyright         2024 UBC CTLT
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       UBC Author Edit Pages
 * Plugin URI:        https://github.com/ubc/ubc-author-edit-pages
 * Description:       Allows users with the author role to edit pages where they have been assigned as the author.
 * Version:           1.0.0
 * Requires at least: 5.0
 * Requires PHP:      8.0
 * Author:            Rich Tape/UBC CTLT
 * Author URI:        https://ctlt.ubc.ca/
 * Text Domain:       ubc-author-edit-pages
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define plugin constants.
define( 'UBC_AEP_VERSION', '1.0.0' );
define( 'UBC_AEP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'UBC_AEP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'UBC_AEP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Include the main plugin class.
require_once UBC_AEP_PLUGIN_DIR . 'includes/class-plugin.php';

/**
 * Begins execution of the plugin.
 *
 * @since 1.0.0
 */
function run_ubc_author_edit_pages() {
	// Initialize the plugin.
	$plugin = new UBC\AuthorEditPages\Plugin();
	$plugin->run();
}

// Start the plugin.
run_ubc_author_edit_pages();
