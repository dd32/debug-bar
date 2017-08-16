<?php

class Debug_Bar_Panel {
	public $_title = '';
	public $_visible = true;

	public function __construct( $title = '' ) {
		$this->title( $title );

		if ( $this->init() === false ) {
			$this->set_visible( false );

			return;
		}

		add_filter( 'debug_bar_classes', array( $this, 'debug_bar_classes' ) );
	}

	public function Debug_Bar_Panel( $title = '' ) {
		if ( function_exists( '_deprecated_constructor' ) ) {
			_deprecated_constructor( __METHOD__, '0.8.3', __CLASS__ );
		}
		self::__construct( $title );
	}

	/**
	 * Initializes the panel.
	 */
	protected function init() {}

	public function prerender() {}

	/**
	 * Renders the panel.
	 */
	public function render() {}

	public function is_visible() {
		return $this->_visible;
	}

	protected function set_visible( $visible ) {
		$this->_visible = $visible;
	}

	/**
	 * Get/set title.
	 *
	 * @param null $title
	 * @return string|void
	 */
	public function title( $title = null ) {
		if ( ! isset( $title ) ) {
			return $this->_title;
		}

		$this->_title = $title;
	}

	public function debug_bar_classes( $classes ) {
		return $classes;
	}

	/**
	 * Render a (floating) block with a title and content.
	 *
	 * Generally these blocks are used at the top of a panel for a quick info overview.
	 *
	 * {@internal The list of allowed HTML tags is based on the most common HTML tags used
	 *            by this plugin and its add-ons.}}
	 *
	 * @since 0.10.0
	 *
	 * @param string $title   The title of the block.
	 * @param mixed  $content The value/other content for the block.
	 *
	 * @return string|void
	 */
	protected function render_panel_info_block( $title, $content ) {
		if ( is_numeric( $content ) ) {
			$escaped_content = number_format_i18n( $content );
		} else {
			$escaped_content = wp_kses(
				$content,
				array(
					'br'    => array(),
					'small' => array(
						'id'    => true,
						'class' => true,
					),
					'span'  => array(
						'id'    => true,
						'class' => true,
					),
				)
			);
		}

		echo '<h2><span>' . esc_html( $title ) . '</span>' . $escaped_content . "</h2>\n"; // WPCS: XSS ok.
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
