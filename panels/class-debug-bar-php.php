<?php

class Debug_Bar_PHP extends Debug_Bar_Panel {

	/**
	 * Storage to log PHP warnings encountered.
	 *
	 * @since 0.5
	 * @since 0.10.0 Changed to static to allow for error logging to start early.
	 *
	 * @var array
	 */
	public static $warnings = array();

	/**
	 * Storage to log PHP errors encountered.
	 *
	 * @since 0.5
	 * @since 0.10.0 Changed to static to allow for error logging to start early.
	 *
	 * @var array
	 */
	public static $notices = array();

	/**
	 * Store the callback for the previous error handler.
	 *
	 * @since 0.5
	 * @since 0.10.0 Changed to static to allow for error logging to start early.
	 *
	 * @var array
	 */
	public static $real_error_handler;

	/**
	 * Start logging PHP errors.
	 *
	 * @since 0.10.0
	 */
	public static function start_logging() {
		if ( ! WP_DEBUG ) {
			return false;
		}

		error_reporting( -1 );
		self::$real_error_handler = set_error_handler( array( __CLASS__, 'error_handler' ) );
	}

	/**
	 * Stop logging PHP errors.
	 *
	 * @since 0.10.0
	 */
	public static function stop_logging() {
		restore_error_handler();
	}

	protected function init() {
		if ( ! WP_DEBUG ) {
			return false;
		}

		$this->title( __( 'Notices / Warnings', 'debug-bar' ) );
	}

	public function is_visible() {
		return count( self::$notices ) || count( self::$warnings );
	}

	public function debug_bar_classes( $classes ) {
		if ( count( self::$warnings ) ) {
			$classes[] = 'debug-bar-php-warning-summary';
		} elseif ( count( self::$notices ) ) {
			$classes[] = 'debug-bar-php-notice-summary';
		}

		return $classes;
	}

	/**
	 * Log PHP errors when they are encountered.
	 *
	 * @since 0.5
	 * @since 0.10.0 Changed to static to allow for error logging to start early.
	 *
	 * @param int    $type    The level of the error raised.
	 * @param string $message The error message.
	 * @param string $file    The filename that the error was raised in.
	 * @param int    $line    The line number the error was raised at.
	 *
	 * @return false To allow for the normal error handler to continue.
	 */
	public static function error_handler( $type, $message, $file, $line ) {
		if ( ! ( error_reporting() & $type ) ) {
			return false;
		}

		$_key = md5( $file . ':' . $line . ':' . $message );

		if ( ! defined( 'E_DEPRECATED' ) ) {
			define( 'E_DEPRECATED', 8192 );
		}
		if ( ! defined( 'E_USER_DEPRECATED' ) ) {
			define( 'E_USER_DEPRECATED', 16384 );
		}

		switch ( $type ) {
			case E_WARNING:
			case E_USER_WARNING:
				self::$warnings[ $_key ] = array(
					$file . ':' . $line,
					$message,
					wp_debug_backtrace_summary( __CLASS__ ),
				);
				break;
			case E_NOTICE:
			case E_USER_NOTICE:
				self::$notices[ $_key ] = array(
					$file . ':' . $line,
					$message,
					wp_debug_backtrace_summary( __CLASS__ ),
				);
				break;
			case E_STRICT:
				// TODO
				break;
			case E_DEPRECATED:
				// TODO
				break;
			case 0:
				// TODO
				break;
		}

		if ( isset( self::$real_error_handler ) ) {
			return call_user_func( self::$real_error_handler, $type, $message, $file, $line );
		} else {
			return false;
		}
	}

	public function render() {
		echo '<div id="debug-bar-php">';

		$this->render_panel_info_block( __( 'Total Warnings:', 'debug-bar' ), count( self::$warnings ) );
		$this->render_panel_info_block( __( 'Total Notices:', 'debug-bar' ), count( self::$notices ) );

		$this->render_error_list( self::$warnings, __( 'WARNING:', 'debug-bar' ), 'php-warning' );
		$this->render_error_list( self::$notices, __( 'NOTICE:', 'debug-bar' ), 'php-notice' );

		echo '</div>';
	}
}
