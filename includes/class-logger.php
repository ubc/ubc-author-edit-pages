<?php
/**
 * The logger functionality of the plugin.
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
 * The logger functionality of the plugin.
 *
 * Manages the logging functionality.
 *
 * @since      1.0.0
 * @package    UBC\AuthorEditPages
 * @author     Rich Tape/UBC CTLT
 */
class Logger {

	/**
	 * Whether to log verbose debug information.
	 *
	 * @since    1.0.0
	 * @var      boolean
	 */
	private $verbose = false;

	/**
	 * Log a message to the debug.log file.
	 *
	 * @since    1.0.0
	 * @param    string  $message    The message to log.
	 * @param    string  $level      The log level (info, warning, error).
	 * @param    boolean $force      Whether to force logging even if not in verbose mode.
	 */
	public function log( $message, $level = 'info', $force = false ) {
		// Only log if WP_DEBUG is enabled.
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		// Skip non-forced logs if not in verbose mode.
		if ( ! $this->verbose && ! $force && 'error' !== $level ) {
			return;
		}

		// Format the log message.
		$timestamp         = current_time( 'mysql' );
		$formatted_message = sprintf(
			'[%s] [%s] [UBC Author Edit Pages] %s' . PHP_EOL,
			$timestamp,
			strtoupper( $level ),
			$message
		);

		// Try multiple logging methods to ensure it works.
		$this->log_to_file( $formatted_message );

		// As a fallback, also use WordPress's built-in error logging if enabled.
		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			error_log( $formatted_message );
		}
	}

	/**
	 * Write log message to the debug.log file.
	 *
	 * @since    1.0.0
	 * @param    string $formatted_message    The formatted message to log.
	 */
	private function log_to_file( $formatted_message ) {
		try {
			// Get the debug log path.
			$debug_log_path = WP_CONTENT_DIR . '/debug.log';

			// Check if the file exists and is writable, or if we can create it.
			if ( ( file_exists( $debug_log_path ) && is_writable( $debug_log_path ) ) ||
				is_writable( dirname( $debug_log_path ) ) ) {

				// Write to the debug.log file.
				file_put_contents( $debug_log_path, $formatted_message, FILE_APPEND );
			} else {
				// Try an alternative location in the plugin directory.
				$alt_log_path = plugin_dir_path( __DIR__ ) . 'debug.log';
				file_put_contents( $alt_log_path, $formatted_message, FILE_APPEND );
			}
		} catch ( \Exception $e ) {
			// If all else fails, write to PHP error log.
			error_log( 'UBC Author Edit Pages - Logger Error: ' . $e->getMessage() );
			error_log( $formatted_message );
		}
	}

	/**
	 * Log an info message.
	 *
	 * @since    1.0.0
	 * @param    string  $message    The message to log.
	 * @param    boolean $force      Whether to force logging even if not in verbose mode.
	 */
	public function info( $message, $force = false ) {
		$this->log( $message, 'info', $force );
	}

	/**
	 * Log a warning message.
	 *
	 * @since    1.0.0
	 * @param    string  $message    The message to log.
	 * @param    boolean $force      Whether to force logging even if not in verbose mode.
	 */
	public function warning( $message, $force = false ) {
		$this->log( $message, 'warning', $force );
	}

	/**
	 * Log an error message.
	 *
	 * @since    1.0.0
	 * @param    string  $message    The message to log.
	 * @param    boolean $force      Whether to force logging even if not in verbose mode.
	 */
	public function error( $message, $force = true ) {
		$this->log( $message, 'error', $force );
	}
}
