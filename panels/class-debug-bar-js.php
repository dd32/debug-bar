<?php

class Debug_Bar_JS extends Debug_Bar_Panel {
	public $real_error_handler = array();

	protected function init() {
		$this->title( __( 'JavaScript', 'debug-bar' ) );

		/*
		 * attach here instead of debug_bar_enqueue_scripts
		 * because we want to be as early as possible!
		 */
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '.dev' : '';
		wp_enqueue_script( 'debug-bar-js', plugins_url( "js/debug-bar-js$suffix.js", dirname( __FILE__ ) ), array( 'jquery' ), '20170623' );
	}

	public function render() {
		echo '<div id="debug-bar-js">';
		$this->render_panel_info_block( __( 'Total Errors:', 'debug-bar' ), '<span id="debug-bar-js-error-count">0</span>' );
		echo '<ol class="debug-bar-js-list" id="debug-bar-js-errors"></ol>' . "\n";
		echo '</div>';
	}
}
