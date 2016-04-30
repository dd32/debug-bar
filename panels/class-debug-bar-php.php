<?php

class Debug_Bar_PHP extends Debug_Bar_Panel {
	static $warnings   = array();
	static $notices    = array();
	static $strict     = array();
	static $deprecated = array();
	static $silenced   = array();
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
		if ( ! WP_DEBUG ) {
			return false;
		}

		$this->title( __('Notices / Warnings', 'debug-bar') );
		$this->set_visible( false );
	}

	function is_visible() {
		return ( $this->get_total() > 0 );
	}

	function prerender() {
		$total = $this->get_total();

		if ( $total > 0 ) {
			$warnings = ( count( self::$warnings ) > 0 ? ' debug-bar-issue-warnings' : '' );
			$this->title( $this->title() . '<span class="debug-bar-issue-count' . $warnings . '">' . absint( $total ) . '</span>' );
		}
	}

	function get_total() {
		return count( self::$notices ) + count( self::$warnings ) + count( self::$strict ) + count( self::$deprecated ) + count( self::$silenced );
	}

	function debug_bar_classes( $classes ) {
		if ( count( self::$warnings ) ) {
			$classes[] = 'debug-bar-warning-summary';
		} elseif ( $this->get_total() ) {
			$classes[] = 'debug-bar-notice-summary';
		}
		return $classes;
	}

	static function error_handler( $type, $message, $file, $line ) {
		$_key = md5( $file . ':' . $line . ':' . $message );

		if ( 0 === error_reporting() ) {
			self::$silenced[ $_key ] = array( $file . ':' . $line, $message, wp_debug_backtrace_summary( __CLASS__ ) );
		} else {

			if ( ! defined( 'E_DEPRECATED' ) ) {
				define( 'E_DEPRECATED', 8192 );
			}
			if ( ! defined( 'E_USER_DEPRECATED' ) )	{
				define( 'E_USER_DEPRECATED', 16384 );
			}

			switch ( $type ) {
				case E_WARNING :
				case E_USER_WARNING :
					self::$warnings[ $_key ] = array( $file . ':' . $line, $message, wp_debug_backtrace_summary( __CLASS__ ) );
					break;
				case E_NOTICE :
				case E_USER_NOTICE :
					self::$notices[ $_key ] = array( $file . ':' . $line, $message, wp_debug_backtrace_summary( __CLASS__ ) );
					break;
				case E_STRICT :
					self::$strict[ $_key ] = array( $file . ':' . $line, $message, wp_debug_backtrace_summary( __CLASS__ ) );
					break;
				case E_DEPRECATED :
				case E_USER_DEPRECATED :
					self::$deprecated[ $_key ] = array( $file . ':' . $line, $message, wp_debug_backtrace_summary( __CLASS__ ) );
					break;
			}
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
		$this->render_title ( __( 'Total Strict Notices:', 'debug-bar' ), count( self::$strict ) );
		$this->render_title ( __( 'Total Deprecated:', 'debug-bar' ), count( self::$deprecated ) );
		$this->render_title ( __( 'Total Silenced:', 'debug-bar' ), count( self::$silenced ) );

		$this->render_list( self::$warnings, __( 'WARNING:', 'debug-bar' ), 'warning' );
		$this->render_list( self::$notices, __( 'NOTICE:', 'debug-bar' ), 'notice' );
		$this->render_list( self::$strict, __( 'STRICT:', 'debug-bar' ), 'notice' );
		$this->render_list( self::$deprecated, __( 'DEPRECATED:', 'debug-bar' ), 'notice' );
		$this->render_list( self::$silenced, __( 'SILENCED:', 'debug-bar' ), 'warning' );

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
