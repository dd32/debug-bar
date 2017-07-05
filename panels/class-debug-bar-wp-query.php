<?php

class Debug_Bar_WP_Query extends Debug_Bar_Panel {
	function init() {
		$this->title( __( 'WP Query', 'debug-bar' ) );
	}

	function prerender() {
		$this->set_visible( defined( 'SAVEQUERIES' ) && SAVEQUERIES );
	}

	function render() {
		global $template, $wp_query;

		$queried_object = get_queried_object();
		if ( $queried_object && isset( $queried_object->post_type ) ) {
			$post_type_object = get_post_type_object( $queried_object->post_type );
		}

		echo '<div id="debug-bar-wp-query">';
		$this->render_panel_info_block( __( 'Queried Object ID:', 'debug-bar' ), (int) get_queried_object_id() );

		// Determine the query type. Follows the template loader order.
		$type = '';
		if ( is_404() ) {
			$type = __( '404', 'debug-bar' );
		} elseif ( is_search() ) {
			$type = __( 'Search', 'debug-bar' );
		} elseif ( is_tax() ) {
			$type = __( 'Taxonomy', 'debug-bar' );
		} elseif ( is_front_page() ) {
			$type = __( 'Front Page', 'debug-bar' );
		} elseif ( is_home() ) {
			$type = __( 'Home', 'debug-bar' );
		} elseif ( is_attachment() ) {
			$type = __( 'Attachment', 'debug-bar' );
		} elseif ( is_single() ) {
			$type = __( 'Single', 'debug-bar' );
		} elseif ( is_page() ) {
			$type = __( 'Page', 'debug-bar' );
		} elseif ( is_category() ) {
			$type = __( 'Category', 'debug-bar' );
		} elseif ( is_tag() ) {
			$type = __( 'Tag', 'debug-bar' );
		} elseif ( is_author() ) {
			$type = __( 'Author', 'debug-bar' );
		} elseif ( is_date() ) {
			$type = __( 'Date', 'debug-bar' );
		} elseif ( is_archive() ) {
			$type = __( 'Archive', 'debug-bar' );
		} elseif ( is_paged() ) {
			$type = __( 'Paged', 'debug-bar' );
		}

		if ( ! empty( $type ) ) {
			$this->render_panel_info_block( __( 'Query Type:', 'debug-bar' ), $type );
		}

		if ( ! empty( $template ) ) {
			$this->render_panel_info_block( __( 'Query Template:', 'debug-bar' ), basename( $template ) );
		}

		$show_on_front  = get_option( 'show_on_front' );
		$page_on_front  = get_option( 'page_on_front' );
		$page_for_posts = get_option( 'page_for_posts' );

		$this->render_panel_info_block( __( 'Show on Front:', 'debug-bar' ), $show_on_front );

		if ( 'page' === $show_on_front ) {
			$this->render_panel_info_block( __( 'Page for Posts:', 'debug-bar' ), $page_for_posts );
			$this->render_panel_info_block( __( 'Page on Front:', 'debug-bar' ), $page_on_front );
		}

		if ( isset( $post_type_object ) ) {
			$this->render_panel_info_block( __( 'Post Type:', 'debug-bar' ), $post_type_object->labels->singular_name );
		}

		echo '<div class="clear"></div>';

		if ( empty( $wp_query->query ) ) {
			$query = __( 'None', 'debug-bar' );
		} else {
			$query = http_build_query( $wp_query->query );
		}

		echo '<h3>', __( 'Query Arguments:', 'debug-bar' ), '</h3>';
		echo '<p>' . esc_html( $query ) . '</p>';

		if ( ! empty( $wp_query->request ) ) {
			echo '<h3>', __( 'Query SQL:', 'debug-bar' ), '</h3>';
			echo '<p>' . esc_html( $wp_query->request ) . '</p>';
		}

		if ( ! is_null( $queried_object ) ) {
			echo '<h3>', __( 'Queried Object:', 'debug-bar' ), '</h3>';
			echo '<table class="debug-bar-wp-query-list"><tbody>';
			$this->_recursive_print_kv( $queried_object );
			echo '</tbody></table>';
		}
		echo '</div>';
	}

	protected function _recursive_print_kv( $kv_array ) {
		foreach ( $kv_array as $key => $value ) {
			if ( is_object( $value ) || is_array( $value ) ) {
				echo '<tr><th>', esc_html( $key ), '</th> <td>&rArr;</td> <td>';
				$this->_recursive_print_kv( $value );
				echo '</td></tr>';
			} else {
				echo '<tr><th>', esc_html( $key ), '</th> <td>&rArr;</td> <td>', esc_html( $value ), '</td></tr>';
			}
		}
	}
}
