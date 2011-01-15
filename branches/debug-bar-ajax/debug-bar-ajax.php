<?php
/*
 * Plugin Name: Debug Bar AJAX
 * Plugin URI: http://wordpress.org/extend/plugins/debug-bar-ajax/
 * Description: Adds support to the debug bar for tracking DB Queries made during AJAX calls.  Requires the debug bar plugin.
 * Author: mdawaffe
 * Version: 0.1
 */

function debug_bar_ajax_admin_init() {
	if ( !is_super_admin() || !_get_admin_bar_pref( 'admin' ) ) // HACK :( is_admin_bar_showing() bails if DOING_AJAX
		return;

	if ( ! isset( $_SERVER['HTTP_X_REQUEST_DEBUG'] ) || 'true' != $_SERVER['HTTP_X_REQUEST_DEBUG'] )
		return;

	if ( ! is_callable( 'getallheaders' ) )
		return;

	$headers = getallheaders();
	if ( isset( $headers['X-Request-Debug'] ) && 'true' == $headers['X-Request-Debug'] ) {
		ob_start();
		add_action( 'shutdown', 'debug_bar_ajax_shutdown', 0 );
	}
}

function debug_bar_ajax_shutdown() {
	debug_bar_ajax_response();
	ob_end_flush();
}

function debug_bar_ajax_response() {
	if ( !isset( $GLOBALS['debug_bar']->panels ) )
		return;

	// Find the queries Panel.  Debug_Bar::$panels should be indexed by slug or class name.
	$query_panel = false;
	foreach ( $GLOBALS['debug_bar']->panels as $panel ) {
		if ( 'Debug_Bar_Queries' == get_class( $panel ) ) {
			$query_panel = $panel;
			break;
		}
	}

	if ( !$query_panel )
		return;

	ob_start();
		call_user_func( array( $query_panel, 'render' ) );
	$response = ob_get_clean();

	$response = trim( $response );
	$response = preg_replace( '/\s+/', ' ', $response ); // minimize whitespace
	$response = rawurlencode( $response );

	header( "X-Response-Debug: $response" );
}

function debug_bar_ajax_scripts() {
//	$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';
	$suffix = '.dev';

	wp_enqueue_script( 'debug-bar-ajax', plugins_url( "js/debug-bar-ajax$suffix.js", __FILE__ ), array('debug-bar'), mt_rand() );
}

if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
	add_action( 'admin_init', 'debug_bar_ajax_admin_init' );
}

add_action('debug_bar_enqueue_scripts', 'debug_bar_ajax_scripts');
