<?php
/**
 * The author pages functionality of the plugin.
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
 * The author pages functionality of the plugin.
 *
 * Manages the author pages functionality.
 *
 * @since      1.0.0
 * @package    UBC\AuthorEditPages
 * @author     Rich Tape/UBC CTLT
 */
class Author_Pages {

	/**
	 * The logger instance.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Logger    $logger    The logger instance.
	 */
	protected $logger;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    Logger $logger    The logger instance.
	 */
	public function __construct( $logger ) {
		$this->logger = $logger;
	}

	/**
	 * Register the hooks for this class.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		// This class is currently a placeholder for future functionality.
		// The core functionality has been moved to the Capabilities class.
		$this->logger->log( 'Author_Pages class initialized.' );
	}
}
