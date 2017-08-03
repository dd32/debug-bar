<?php
/**
 * Debug Bar Panel
 *
 * @package \DebugBar
 */

/**
 * Panel to register "Doing it Wrong" messages.
 *
 * @since 0.10.0
 */
class Debug_Bar_Doing_It_Wrong extends Debug_Bar_Panel {

	/**
	 * Storage to log "doing it wrong" notices.
	 *
	 * @since 0.10.0
	 *
	 * @var array
	 */
	public static $doing_it_wrong = array();

	/**
	 * Start logging "doing it wrong" notices thrown by WP.
	 *
	 * @since 0.10.0
	 */
	public static function start_logging() {
		add_action( 'doing_it_wrong_run', array( __CLASS__, 'doing_it_wrong_run' ), 10, 3 );
		add_filter( 'doing_it_wrong_trigger_error', '__return_false' );
	}

	/**
	 * Stop logging "doing it wrong" notices thrown by WP.
	 *
	 * @since 0.10.0
	 */
	public static function stop_logging() {
		remove_action( 'doing_it_wrong_run', array( __CLASS__, 'doing_it_wrong_run' ), 10 );
		remove_filter( 'doing_it_wrong_trigger_error', '__return_false' );
	}

	/**
	 * Initialize the panel.
	 *
	 * @since 0.10.0
	 */
	protected function init() {
		$this->title( __( 'Doing It Wrong', 'debug-bar' ) );
	}

	/**
	 * Set whether or not to display the panel.
	 *
	 * @since 0.10.0
	 */
	public function prerender() {
		$this->set_visible( count( self::$doing_it_wrong ) );
	}

	/**
	 * Render the panel content.
	 *
	 * @since 0.10.0
	 */
	public function render() {
		echo '<div id="debug-bar-doingitwrong">';

		$this->render_panel_info_block( __( 'Total Calls:', 'debug-bar' ), count( self::$doing_it_wrong ) );

		$this->render_error_list(
			self::$doing_it_wrong,
			__( 'DOING IT WRONG:', 'debug-bar' ),
			'doingitwrong'
		);

		echo '</div>';
	}

	/**
	 * Log "doing it wrong" notices being thrown.
	 *
	 * @since 0.10.0
	 *
	 * @param string $function The function that was called.
	 * @param string $message  A message explaining what has been done incorrectly.
	 * @param string $version  The version of WordPress where the message was added.
	 */
	public static function doing_it_wrong_run( $function, $message, $version ) {
		$backtrace = debug_backtrace( false );
		$bt        = 4;
		if ( ! isset( $backtrace[4]['file'] ) && 'call_user_func_array' === $backtrace[5]['function'] ) {
			$bt = 6;
		}

		$file     = ( isset( $backtrace[ $bt ]['file'] ) ? $backtrace[ $bt ]['file'] : 0 );
		$line     = ( isset( $backtrace[ $bt ]['line'] ) ? $backtrace[ $bt ]['line'] : 0 );
		$location = $file . ':' . $line;

		if ( is_null( $version ) ) {
			$version = '';
		} else {
			/* translators: 1: version number. */
			$version = sprintf( __( '(This message was added in version %1$s.)', 'debug-bar' ), $version );
		}

		$message .= ' ' . sprintf(
			/* translators: 1: Codex URL. */
			__( 'Please see <a href="%1$s">Debugging in WordPress</a> for more information.', 'debug-bar' ),
			__( 'https://codex.wordpress.org/Debugging_in_WordPress', 'debug-bar' )
		);
		$notice = sprintf(
			/* translators: Developer debugging message. 1: PHP function name, 2: Explanatory message, 3: Version information message */
			__( '%1$s was called <strong>incorrectly</strong>. %2$s %3$s', 'debug-bar' ),
			$function,
			$message,
			$version
		);

		$key = md5( $location . ':' . $message );
		self::$doing_it_wrong[ $key ] = array( $location, $notice, wp_debug_backtrace_summary( null, $bt ) );

		error_log( 'Doing it wrong Notice: ' . strip_tags( $notice ) . '  in ' . $location );
	}
}
