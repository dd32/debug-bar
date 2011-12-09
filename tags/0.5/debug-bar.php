<?php
/*
 Plugin Name: Debug Bar
 Plugin URI: http://wordpress.org/extend/plugins/debug-bar/
 Description: Adds a debug menu to the admin bar that shows query, cache, and other helpful debugging information.
 Author: wordpressdotorg
 Version: 0.5
 Author URI: http://wordpress.org/
 */

/***
 * Debug Functions
 *
 * When logged in as a super admin, these functions will run to provide
 * debugging information when specific super admin menu items are selected.
 *
 * They are not used when a regular user is logged in.
 */

class Debug_Bar {
	var $panels = array();

	function Debug_Bar() {
		if ( defined('DOING_AJAX') && DOING_AJAX )
			add_action( 'admin_init', array( &$this, 'init_ajax' ) );
		add_action( 'admin_bar_init', array( &$this, 'init' ) );
	}

	function init() {
		if ( ! is_super_admin() || ! is_admin_bar_showing() )
			return;

		add_action( 'admin_bar_menu',               array( &$this, 'admin_bar_menu' ), 1000 );
		add_action( 'wp_after_admin_bar_render',    array( &$this, 'render' ) );
		add_action( 'wp_head',                      array( &$this, 'ensure_ajaxurl' ), 1 );

		$this->requirements();
		$this->enqueue();
		$this->init_panels();
	}

	function init_ajax() {
		if ( ! is_super_admin() )
			return;

		$this->requirements();
		$this->init_panels();
	}

	function requirements() {
		$recs = array( 'panel', 'php', 'queries', 'request', 'wp-query', 'object-cache', 'deprecated' );
		foreach ( $recs as $rec )
			require_once "panels/class-debug-bar-$rec.php";
	}

	function enqueue() {
		$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';

		$url = plugin_dir_url( __FILE__ );

		wp_enqueue_style( 'debug-bar', "{$url}css/debug-bar$suffix.css", array(), '20110114' );
		wp_enqueue_script( 'debug-bar-ui-dockable', "{$url}js/ui-dockable$suffix.js", array('jquery-ui-mouse'), '20110113' );
		wp_enqueue_script( 'debug-bar', "{$url}js/debug-bar$suffix.js", array('jquery', 'debug-bar-ui-dockable'), '20110114' );

		do_action('debug_bar_enqueue_scripts');
	}

	function init_panels() {
		$classes = array(
			'Debug_Bar_PHP',
			'Debug_Bar_WP_Query',
			'Debug_Bar_Queries',
			'Debug_Bar_Deprecated',
			'Debug_Bar_Request',
			'Debug_Bar_Object_Cache',
		);

		foreach ( $classes as $class ) {
			$this->panels[] = new $class;
		}

		$this->panels = apply_filters( 'debug_bar_panels', $this->panels );

		foreach ( $this->panels as $panel_key => $panel ) {
			if ( ! $panel->is_visible() )
				unset( $this->panels[ $panel_key ] );
		}
	}

	function ensure_ajaxurl() {
		if ( is_admin() )
			return;
		?>
		<script type="text/javascript">
		//<![CDATA[
		var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
		//]]>
		</script>
		<?php
	}

	function admin_bar_menu() {
		global $wp_admin_bar;

		$classes = apply_filters( 'debug_bar_classes', array() );
		$classes = implode( " ", $classes );

		/* Add the main siteadmin menu item */
		$wp_admin_bar->add_menu( array(
			'id'    => 'debug-bar',
			'title' => __('Debug'),
			'meta'  => array( 'class' => $classes )
		) );
	}

	function render() {
		global $wpdb;

		if ( empty( $this->panels ) )
			return;

		foreach ( $this->panels as $panel_key => $panel ) {
			$panel->prerender();
			if ( ! $panel->is_visible() )
				unset( $this->panels[ $panel_key ] );
		}

		?>
	<div id='querylist'>

	<div id='debug-bar-handle'></div>
	<div id='debug-bar-menu'>
		<div id="debug-status">
			<?php //@todo: Add a links to information about WP_DEBUG, PHP version, MySQL version, and Peak Memory.
			$statuses = array();
			if ( ! WP_DEBUG )
				$statuses[] = array( 'warning', __('WP_DEBUG OFF'), '' );
			$statuses[] = array( 'site', sprintf( __('Site #%d on %s'), $GLOBALS['blog_id'], php_uname( 'n' ) ), '' );
			$statuses[] = array( 'php', __('PHP'), phpversion() );
			$statuses[] = array( 'db', __('DB'), $wpdb->db_version() );
			$statuses[] = array( 'memory', __('Mem.'), sprintf( __('%s bytes'), number_format( memory_get_peak_usage() ) ) );

			$statuses = apply_filters( 'debug_bar_statuses', $statuses );

			$status_html = array();
			foreach ( $statuses as $status ) {
				list( $slug, $title, $data ) = $status;

				$html = "<span id='debug-status-$slug' class='debug-status'>";
				$html .= "<span class='debug-status-title'>$title</span>";
				if ( ! empty( $data ) )
					$html .= " <span class='debug-status-data'>$data</span>";
				$html .= '</span>';
				$status_html[] = $html;
			}

			echo implode( ' | ', $status_html );

			?>
		</div>
		<ul id="debug-menu-links">

	<?php
		$current = ' current';
		foreach ( $this->panels as $panel ) :
			$class = get_class( $panel );
			?>
			<li><a
				id="debug-menu-link-<?php echo esc_attr( $class ); ?>"
				class="debug-menu-link<?php echo $current; ?>"
				href="#debug-menu-target-<?php echo esc_attr( $class ); ?>">
				<?php
				// Not escaping html here, so panels can use html in the title.
				echo $panel->title();
				?>
			</a></li>
			<?php
			$current = '';
		endforeach; ?>

		</ul>
	</div>

	<div id="debug-menu-targets"><?php
	$current = ' style="display: block"';
	foreach ( $this->panels as $panel ) :
		$class = get_class( $panel ); ?>

		<div id="debug-menu-target-<?php echo $class; ?>" class="debug-menu-target" <?php echo $current; ?>>
			<?php $panel->render(); ?>
			<?php // echo str_replace( '&nbsp;', '', $panel->run() ); ?>
		</div>

		<?php
		$current = '';
	endforeach;
	?>
	</div>

	<?php do_action( 'debug_bar' ); ?>
	</div>
	<?php
	}
}

$GLOBALS['debug_bar'] = new Debug_Bar();

?>