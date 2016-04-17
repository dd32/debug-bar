<?php

class Debug_Bar_PHP extends Debug_Bar_Panel {
	static $warnings = array();
	static $notices = array();
	static $real_error_handler;

	static function start_logging() {
		if ( ! WP_DEBUG ) {
			return false;
		}

		self::$real_error_handler = set_error_handler( array( __CLASS__, 'error_handler' ) );
	}

	static function stop_logging() {
		restore_error_handler();
	}

	function init() {
		if ( ! WP_DEBUG )
			return false;

		$this->title( __('Notices / Warnings', 'debug-bar') );
	}

	function is_visible() {
		return count( self::$notices ) || count( self::$warnings );
	}

	function debug_bar_classes( $classes ) {
		if ( count( self::$warnings ) ) {
			$classes[] = 'debug-bar-php-warning-summary';
		} elseif ( count( self::$notices ) ) {
			$classes[] = 'debug-bar-php-notice-summary';
		}
		return $classes;
	}

	static function error_handler( $type, $message, $file, $line ) {
		if( ! ( error_reporting() & $type ) ) {
			return false;
		}

		$_key = md5( $file . ':' . $line . ':' . $message );

		if ( ! defined( 'E_DEPRECATED' ) ) {
			define( 'E_DEPRECATED', 8192 );
		}
		if ( ! defined( 'E_USER_DEPRECATED' ) )	{
			define( 'E_USER_DEPRECATED', 16384 );
		}

		switch ( $type ) {
			case E_WARNING :
			case E_USER_WARNING :
				self::$warnings[$_key] = array( $file.':'.$line, $message, wp_debug_backtrace_summary( __CLASS__ ) );
				break;
			case E_NOTICE :
			case E_USER_NOTICE :
				self::$notices[$_key] = array( $file.':'.$line, $message, wp_debug_backtrace_summary( __CLASS__ ) );
				break;
			case E_STRICT :
				// TODO
				break;
			case E_DEPRECATED :
			case E_USER_DEPRECATED :
				// TODO
				break;
			case 0 :
				// TODO
				break;
		}

		if ( isset( self::$real_error_handler ) ) {
			return call_user_func( self::$real_error_handler, $type, $message, $file, $line );
		} else {
			return false;
		}
	}

	function render() {
		echo "<div id='debug-bar-php'>";
		echo '<h2><span>', __( 'Total Warnings:', 'debug-bar' ), '</span>', number_format_i18n( count( self::$warnings ) ), "</h2>\n";
		echo '<h2><span>', __( 'Total Notices:', 'debug-bar' ), '</span>', number_format_i18n( count( self::$notices ) ), "</h2>\n";
		if ( count( self::$warnings ) ) {
			echo '<ol class="debug-bar-php-list">';
			foreach ( self::$warnings as $location_message_stack ) {
				list( $location, $message, $stack) = $location_message_stack;
				echo '<li class="debug-bar-php-warning">', __( 'WARNING:', 'debug-bar' ), ' ';
				echo str_replace(ABSPATH, '', $location) . ' - ' . strip_tags($message);
				echo '<br/>';
				echo $stack;
				echo '</li>';
			}
			echo '</ol>';
		}
		if ( count( self::$notices ) ) {
			echo '<ol class="debug-bar-php-list">';
			foreach ( self::$notices as $location_message_stack) {
				list( $location, $message, $stack) = $location_message_stack;
				echo '<li class="debug-bar-php-notice">', __( 'NOTICE:', 'debug-bar' ), ' ';
				echo str_replace(ABSPATH, '', $location) . ' - ' . strip_tags($message);
				echo '<br/>';
				echo $stack;
				echo '</li>';
			}
			echo '</ol>';
		}
		echo "</div>";

	}
}

