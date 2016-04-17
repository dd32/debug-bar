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
	 * Storage to log PHP notices encountered.
	 *
	 * @since 0.5
	 * @since 0.10.0 Changed to static to allow for error logging to start early.
	 *
	 * @var array
	 */
	public static $notices = array();

	/**
	 * Storage to log PHP strict errors encountered.
	 *
	 * @since 0.10.0
	 *
	 * @var array
	 */
	public static $strict = array();

	/**
	 * Storage to log PHP deprecated errors encountered.
	 *
	 * @since 0.10.0
	 *
	 * @var array
	 */
	public static $deprecated = array();

	/**
	 * Storage to log silenced PHP errors encountered.
	 *
	 * @since 0.10.0
	 *
	 * @var array
	 */
	public static $silenced = array();

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
		return count( self::$notices )
			|| count( self::$warnings )
			|| count( self::$strict )
			|| count( self::$deprecated )
			|| count( self::$silenced );
	}

	public function debug_bar_classes( $classes ) {
		if ( count( self::$warnings ) ) {
			$classes[] = 'debug-bar-php-warning-summary';
		} elseif ( count( self::$notices ) || count( self::$strict )
			|| count( self::$deprecated ) || count( self::$silenced )
		) {
			$classes[] = 'debug-bar-php-notice-summary';
		}

		return $classes;
	}

	/**
	 * Log PHP errors when they are encountered.
	 *
	 * @since 0.5
	 * @since 0.10.0 Changed to static to allow for error logging to start early.
	 * @since 0.10.0 Now also reports strict, deprecated and silenced errors.
	 *
	 * @param int    $type    The level of the error raised.
	 * @param string $message The error message.
	 * @param string $file    The filename that the error was raised in.
	 * @param int    $line    The line number the error was raised at.
	 *
	 * @return false To allow for the normal error handler to continue.
	 */
	public static function error_handler( $type, $message, $file, $line ) {
		$location = $file . ':' . $line;
		$_key     = md5( $location . ':' . $message );

		if ( 0 === error_reporting() ) {
			self::$silenced[ $_key ] = array( $location, $message, wp_debug_backtrace_summary( __CLASS__ ) );
		} else {

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
						$location,
						$message,
						wp_debug_backtrace_summary( __CLASS__ ),
					);
					break;

				case E_NOTICE:
				case E_USER_NOTICE:
					self::$notices[ $_key ] = array(
						$location,
						$message,
						wp_debug_backtrace_summary( __CLASS__ ),
					);
					break;

				case E_STRICT:
					self::$strict[ $_key ] = array(
						$location,
						$message,
						wp_debug_backtrace_summary( __CLASS__ ),
					);
					break;

				case E_DEPRECATED:
				case E_USER_DEPRECATED:
					self::$deprecated[ $_key ] = array(
						$location,
						$message,
						wp_debug_backtrace_summary( __CLASS__ ),
					);
					break;
			}
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
		$this->render_panel_info_block( __( 'Total Strict Notices:', 'debug-bar' ), count( self::$strict ) );
		$this->render_panel_info_block( __( 'Total Deprecated:', 'debug-bar' ), count( self::$deprecated ) );
		$this->render_panel_info_block( __( 'Total Silenced:', 'debug-bar' ), count( self::$silenced ) );

		$this->render_error_list( self::$warnings, __( 'WARNING:', 'debug-bar' ), 'php-warning' );
		$this->render_error_list( self::$notices, __( 'NOTICE:', 'debug-bar' ), 'php-notice' );
		$this->render_error_list( self::$strict, __( 'STRICT:', 'debug-bar' ), 'php-notice' );
		$this->render_error_list( self::$deprecated, __( 'DEPRECATED:', 'debug-bar' ), 'php-notice' );
		$this->render_error_list( self::$silenced, __( 'SILENCED:', 'debug-bar' ), 'php-warning' );

		echo '</div>';
	}
}
