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
		echo '<div id="debug-bar-php">';

		$this->render_title ( __( 'Total Warnings:', 'debug-bar' ), count( self::$warnings ) );
		$this->render_title ( __( 'Total Notices:', 'debug-bar' ), count( self::$notices ) );

		$this->render_list( self::$warnings, __( 'WARNING:', 'debug-bar' ), 'warning' );
		$this->render_list( self::$notices, __( 'NOTICE:', 'debug-bar' ), 'notice' );

		echo '</div>';
	}

	function render_title ( $title, $count ) {
		echo '<h2><span>', $title, '</span>', absint( $count ), "</h2>\n";
	}

	function render_list( $errors, $line_prefix, $class ) {
		if ( count( $errors ) ) {
			echo '<ol class="debug-bar-php-list">';
			foreach ( $errors as $location_message_stack ) {
				list( $location, $message, $stack ) = $location_message_stack;

				echo '
				<li class="debug-bar-php-', $class ,'">', $line_prefix, ' ',
				str_replace( ABSPATH, '', $location ), ' - ', strip_tags( $message ),
				'<br/>',
				$stack,
				'</li>';
			}
			echo '</ol>';
		}
	}
}
