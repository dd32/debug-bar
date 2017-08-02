<?php

class Debug_Bar_Object_Cache extends Debug_Bar_Panel {
	function init() {
		$this->title( __( 'Object Cache', 'debug-bar' ) );
	}

	function prerender() {
		global $wp_object_cache;
		$this->set_visible( is_object( $wp_object_cache ) && method_exists( $wp_object_cache, 'stats' ) );
	}

	function render() {
		global $wp_object_cache;

		$this->render_panel_info_block( __( 'Cache Hits:', 'debug-bar' ), $wp_object_cache->cache_hits );
		$this->render_panel_info_block( __( 'Cache Misses:', 'debug-bar' ), $wp_object_cache->cache_misses );

		ob_start();
		echo '<div id="object-cache-stats">';
		$wp_object_cache->stats();
		echo '</div>';
		$out = ob_get_contents();
		ob_end_clean();

		echo $out;
	}
}
