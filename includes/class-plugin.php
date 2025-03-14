<?php
/**
 * The main plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @since      1.0.0
 * @package    UBC\AuthorEditPages
 */

namespace UBC\AuthorEditPages;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The main plugin class.
 *
 * @since      1.0.0
 * @package    UBC\AuthorEditPages
 * @author     Rich Tape/UBC CTLT
 */
class Plugin {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Capabilities    $capabilities    Manages the capabilities for authors.
	 */
	protected $capabilities;

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Admin_UI    $admin_ui    Manages the admin UI modifications.
	 */
	protected $admin_ui;

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Author_Pages    $author_pages    Manages the author pages functionality.
	 */
	protected $author_pages;

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Logger    $logger    Manages the logging functionality.
	 */
	protected $logger;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->define_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Capabilities class. Manages the capabilities for authors.
	 * - Admin_UI class. Manages the admin UI modifications.
	 * - Author_Pages class. Manages the author pages functionality.
	 * - Logger class. Manages the logging functionality.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		// Load the dependencies.
		require_once UBC_AEP_PLUGIN_DIR . 'includes/class-capabilities.php';
		require_once UBC_AEP_PLUGIN_DIR . 'includes/class-admin-ui.php';
		require_once UBC_AEP_PLUGIN_DIR . 'includes/class-author-pages.php';
		require_once UBC_AEP_PLUGIN_DIR . 'includes/class-logger.php';

		// Initialize the classes.
		$this->logger       = new Logger();
		$this->capabilities = new Capabilities( $this->logger );
		$this->admin_ui     = new Admin_UI( $this->logger );
		$this->author_pages = new Author_Pages( $this->logger );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_hooks() {
		// Register activation and deactivation hooks.
		register_activation_hook( UBC_AEP_PLUGIN_BASENAME, array( $this->capabilities, 'activate' ) );
		register_deactivation_hook( UBC_AEP_PLUGIN_BASENAME, array( $this->capabilities, 'deactivate' ) );
	}

	/**
	 * Run the plugin.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->capabilities->run();
		$this->admin_ui->run();
		$this->author_pages->run();
	}
}
