<?php

class Debug_Bar_Panel {
	public $_title = '';
	public $_visible = true;

	function __construct( $title = '' ) {
		$this->title( $title );

		if ( $this->init() === false ) {
			$this->set_visible( false );

			return;
		}

		add_filter( 'debug_bar_classes', array( $this, 'debug_bar_classes' ) );
	}

	function Debug_Bar_Panel( $title = '' ) {
		if ( function_exists( '_deprecated_constructor' ) ) {
			_deprecated_constructor( __METHOD__, '0.8.3', __CLASS__ );
		}
		self::__construct( $title );
	}

	/**
	 * Initializes the panel.
	 */
	function init() {}

	function prerender() {}

	/**
	 * Renders the panel.
	 */
	function render() {}

	function is_visible() {
		return $this->_visible;
	}

	function set_visible( $visible ) {
		$this->_visible = $visible;
	}

	/**
	 * Get/set title.
	 *
	 * @param null $title
	 * @return string|void
	 */
	function title( $title = null ) {
		if ( ! isset( $title ) ) {
			return $this->_title;
		}

		$this->_title = $title;
	}

	function debug_bar_classes( $classes ) {
		return $classes;
	}

	/**
	 * Render a list of logged error notices.
	 *
	 * Used by the Debug_Bar_PHP panel and the Debug_Bar_Deprecated panel to
	 * display lists of PHP Warnings, PHP Notices and WP Deprecated calls.
	 *
	 * @since 0.10.0
	 *
	 * @param array  $errors      Array of recorded errors/notices/calls.
	 * @param string $line_prefix Text string to prefix the recorded message with.
	 * @param string $class       CSS class suffix for the list item.
	 */
	protected function render_error_list( $errors, $line_prefix, $class ) {
		if ( count( $errors ) ) {
			echo '
			<ol class="debug-bar-error-notices-list">';

			foreach ( $errors as $location_message_stack ) {
				list( $location, $message, $stack ) = $location_message_stack;

				echo '
				<li class="debug-bar-', esc_attr( $class ), '">
					<strong>', esc_html( $line_prefix ), '</strong> ',
					esc_html( str_replace( ABSPATH, '', $location ) ), ' - ',
					esc_html( wp_strip_all_tags( $message ) ),
					'<br/>', esc_html( $stack ), '
				</li>';
			}

			echo '
			</ol>';
		}
	}
}
