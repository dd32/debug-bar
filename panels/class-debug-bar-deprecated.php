<?php

// Alot of this code is massaged from Andrew Nacin's log-deprecated-notices plugin

class Debug_Bar_Deprecated extends Debug_Bar_Panel {

	/**
	 * Storage to log notices about deprecated WP functions encountered.
	 *
	 * @since 0.5
	 * @since 0.10.0 Changed to public static to allow for logging to start early.
	 *
	 * @var array
	 */
	public static $deprecated_functions = array();

	/**
	 * Storage to log notices about deprecated WP files being included.
	 *
	 * @since 0.5
	 * @since 0.10.0 Changed to public static to allow for logging to start early.
	 *
	 * @var array
	 */
	public static $deprecated_files = array();

	/**
	 * Storage to log notices about deprecated WP function arguments being passed.
	 *
	 * @since 0.5
	 * @since 0.10.0 Changed to public static to allow for logging to start early.
	 *
	 * @var array
	 */
	public static $deprecated_arguments = array();

	/**
	 * Start logging deprecation notices thrown by WP.
	 *
	 * @since 0.10.0
	 */
	public static function start_logging() {
		add_action( 'deprecated_function_run', array( __CLASS__, 'deprecated_function_run' ), 10, 3 );
		add_action( 'deprecated_file_included', array( __CLASS__, 'deprecated_file_included' ), 10, 4 );
		add_action( 'deprecated_argument_run',  array( __CLASS__, 'deprecated_argument_run' ),  10, 3 );

		// Silence E_NOTICE for deprecated usage.
		foreach ( array( 'function', 'file', 'argument' ) as $item ) {
			add_filter( "deprecated_{$item}_trigger_error", '__return_false' );
		}
	}

	/**
	 * Stop logging deprecation notices thrown by WP.
	 *
	 * @since 0.10.0
	 */
	public static function stop_logging() {
		remove_action( 'deprecated_function_run', array( __CLASS__, 'deprecated_function_run' ), 10 );
		remove_action( 'deprecated_file_included', array( __CLASS__, 'deprecated_file_included' ), 10 );
		remove_action( 'deprecated_argument_run',  array( __CLASS__, 'deprecated_argument_run' ),  10 );

		// Don't silence E_NOTICE for deprecated usage.
		foreach ( array( 'function', 'file', 'argument' ) as $item ) {
			remove_filter( "deprecated_{$item}_trigger_error", '__return_false' );
		}
	}

	protected function init() {
		$this->title( __( 'Deprecated', 'debug-bar' ) );
	}

	public function prerender() {
		$this->set_visible(
			count( self::$deprecated_functions )
			|| count( self::$deprecated_files )
			|| count( self::$deprecated_arguments )
		);
	}

	public function render() {
		echo '<div id="debug-bar-deprecated">';

		$this->render_panel_info_block( __( 'Total Functions:', 'debug-bar' ), count( self::$deprecated_functions ) );
		$this->render_panel_info_block( __( 'Total Arguments:', 'debug-bar' ), count( self::$deprecated_arguments ) );
		$this->render_panel_info_block( __( 'Total Files:', 'debug-bar' ), count( self::$deprecated_files ) );

		$this->render_error_list(
			self::$deprecated_functions,
			__( 'DEPRECATED FUNCTION:', 'debug-bar' ),
			'deprecated-function'
		);
		$this->render_error_list(
			self::$deprecated_arguments,
			__( 'DEPRECATED ARGUMENT:', 'debug-bar' ),
			'deprecated-argument'
		);
		$this->render_error_list(
			self::$deprecated_files,
			__( 'DEPRECATED FILE:', 'debug-bar' ),
			'deprecated-file'
		);

		echo '</div>';
	}

	/**
	 * Log notices about deprecated functions being called.
	 *
	 * @since 0.5
	 * @since 0.10.0 Changed to static to allow for deprecation logging to start early.
	 *
	 * @param string $function    The function that was called.
	 * @param string $replacement The function that should have been called.
	 * @param string $version     The version of WordPress that deprecated the function.
	 */
	public static function deprecated_function_run( $function, $replacement, $version ) {
		$backtrace = debug_backtrace( false );
		$bt        = 4;

		// Check if we're a hook callback.
		if ( ! isset( $backtrace[4]['file'] ) && 'call_user_func_array' === $backtrace[5]['function'] ) {
			$bt = 6;
		}

		$location = $backtrace[ $bt ]['file'] . ':' . $backtrace[ $bt ]['line'];

		if ( ! is_null( $replacement ) ) {
			/* translators: %1$s is a function or file name, %2$s a version number, %3$s an alternative function or file to use. */
			$message = sprintf( __( '%1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.', 'debug-bar' ), $function, $version, $replacement );
		} else {
			/* translators: %1$s is a function or file name, %2$s a version number. */
			$message = sprintf( __( '%1$s is <strong>deprecated</strong> since version %2$s with no alternative available.', 'debug-bar' ), $function, $version );
		}

		$key = md5( $location . ':' . $message );
		self::$deprecated_functions[ $key ] = array( $location, $message, wp_debug_backtrace_summary( null, $bt ) );

		error_log( 'Deprecation Notice: ' . strip_tags( $message ) . '  in ' . $location );
	}

	/**
	 * Log notices about deprecated files being included.
	 *
	 * @since 0.5
	 * @since 0.10.0 Changed to static to allow for deprecation logging to start early.
	 *
	 * @param string $old_file    The file that was called.
	 * @param string $replacement The file that should have been included based on ABSPATH.
	 * @param string $version     The version of WordPress that deprecated the file.
	 * @param string $message     A message regarding the change.
	 */
	public static function deprecated_file_included( $old_file, $replacement, $version, $message ) {
		$backtrace = debug_backtrace( false );
		$file      = $backtrace[4]['file'];
		$file_abs  = str_replace( ABSPATH, '', $file );
		$location  = $file . ':' . $backtrace[4]['line'];
		$message   = empty( $message ) ? '' : ' ' . $message;

		if ( ! is_null( $replacement ) ) {
			/* translators: %1$s is a function or file name, %2$s a version number, %3$s an alternative function or file to use. */
			$message = sprintf( __( '%1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.', 'debug-bar' ), $file_abs, $version, $replacement ) . $message;
		} else {
			/* translators: %1$s is a function or file name, %2$s a version number. */
			$message = sprintf( __( '%1$s is <strong>deprecated</strong> since version %2$s with no alternative available.', 'debug-bar' ), $file_abs, $version ) . $message;
		}

		$key = md5( $location . ':' . $message );
		self::$deprecated_files[ $key ] = array( $location, $message, wp_debug_backtrace_summary( null, 4 ) );

		error_log( 'Deprecation Notice: ' . strip_tags( $message ) . '  in ' . $location );
	}

	/**
	 * Log notices about deprecated function parameters being passed.
	 *
	 * @since 0.5
	 * @since 0.10.0 Changed to static to allow for deprecation logging to start early.
	 *
	 * @param string $function    The function that was called.
	 * @param string $message     A message regarding the change.
	 * @param string $version     The version of WordPress that deprecated the argument used.
	 */
	public static function deprecated_argument_run( $function, $message, $version ) {
		$backtrace = debug_backtrace( false );

		$bt = 4;
		if ( ! isset( $backtrace[4]['file'] ) && 'call_user_func_array' === $backtrace[5]['function'] ) {
			$bt = 6;
		}

		$location = $backtrace[ $bt ]['file'] . ':' . $backtrace[ $bt ]['line'];
		$key      = md5( $location . ':' . $function . ':' . $message );

		if ( 'define()' === $function ) {
			self::$deprecated_arguments[ $key ] = array( $location, $message, '' );
			return;
		}

		if ( ! is_null( $message ) ) {
			/* translators: %1$s is a function name, %2$s a version number, %3$s a message regarding the change. */
			$message = sprintf( __( '%1$s was called with an argument that is <strong>deprecated</strong> since version %2$s! %3$s', 'debug-bar' ), $function, $version, $message );
		} else {
			/* translators: %1$s is a function name, %2$s a version number. */
			$message = sprintf( __( '%1$s was called with an argument that is <strong>deprecated</strong> since version %2$s with no alternative available.', 'debug-bar' ), $function, $version );
		}

		self::$deprecated_arguments[ $key ] = array( $location, $message, wp_debug_backtrace_summary( null, $bt ) );

		error_log( 'Deprecation Notice: ' . strip_tags( $message ) . '  in ' . $location );
	}
}
