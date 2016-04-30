<?php
/*
 Plugin Name: Debug Bar
 Plugin URI: https://wordpress.org/plugins/debug-bar/
 Description: Adds a debug menu to the admin bar that shows query, cache, and other helpful debugging information.
 Author: wordpressdotorg
 Version: 0.8.4
 Author URI: https://wordpress.org/
 Text Domain: debug-bar
 Domain Path: /languages
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
	var $path;
	var $panels = array();

	function __construct() {
		if ( defined('DOING_AJAX') && DOING_AJAX ) {
			add_action( 'admin_init', array( $this, 'init_ajax' ) );
		}
		add_action( 'admin_bar_init', array( $this, 'init' ) );

		$this->path = plugin_dir_path( __FILE__ );

		$this->early_requirements();
		Debug_Bar_PHP::start_logging();
		Debug_Bar_Deprecated::start_logging();
		Debug_Bar_Doing_It_Wrong::start_logging();
	}

	function Debug_Bar() {
		_deprecated_constructor( __METHOD__, '0.8.5', __CLASS__ );
		self::__construct();
	}

	function init() {
		if ( ! $this->enable_debug_bar() ) {
			Debug_Bar_PHP::stop_logging();
			Debug_Bar_Deprecated::stop_logging();
			Debug_Bar_Doing_It_Wrong::stop_logging();
			return;
		}

		self::load_textdomain( 'debug-bar' );

		add_action( 'admin_bar_menu',               array( $this, 'admin_bar_menu' ), 1000 );
		add_action( 'admin_footer',                 array( $this, 'render' ), 1000 );
		add_action( 'wp_footer',                    array( $this, 'render' ), 1000 );
		add_action( 'wp_head',                      array( $this, 'ensure_ajaxurl' ), 1 );
		add_filter( 'body_class',                   array( $this, 'body_class' ) );
		add_filter( 'admin_body_class',             array( $this, 'body_class' ) );

		$this->requirements();
		$this->enqueue();
		$this->init_panels();
	}

	/* Are we on the wp-login.php page?
	 * We can get here while logged in and break the page as the admin bar isn't shown and otherthings the js relies on aren't available.
	 */
	function is_wp_login() {
		return 'wp-login.php' == basename( $_SERVER['SCRIPT_NAME'] );
	}

	/**
	 * Should the debug bar functionality be enabled ?
	 *
	 * @param bool $ajax Whether this is an ajax call or not. Defaults to false.
	 * @return bool
	 */
	function enable_debug_bar( $ajax = false ) {
		$enable = false;
		if ( $ajax && is_super_admin() ) {
			$enable = true;
		} elseif ( ! $ajax && ( is_super_admin() && is_admin_bar_showing() && ! $this->is_wp_login() ) ) {
			$enable = true;
		}

		/**
		 * Allows for overruling of whether the debug bar functionality will be enabled.
		 *
		 * @since 0.8.5
		 *
		 * @param bool $enable Whether the debug bar will be enabled or not.
		 */
		return apply_filters( 'debug_bar_enable', $enable );
	}

	/**
	 * Load the plugin text strings.
	 *
	 * Compatible with use of the plugin in the must-use plugins directory.
	 *
	 * @param string $domain Text domain to load.
	 */
	static function load_textdomain( $domain ) {
		if ( is_textdomain_loaded( $domain ) ) {
			return;
		}

		$lang_path = dirname( plugin_basename( __FILE__ ) ) . '/languages';
		if ( false === strpos( __FILE__, basename( WPMU_PLUGIN_DIR ) ) ) {
			load_plugin_textdomain( $domain, false, $lang_path );
		} else {
			load_muplugin_textdomain( $domain, $lang_path );
		}
	}

	function init_ajax() {
		if ( ! $this->enable_debug_bar( true ) ) {
			Debug_Bar_PHP::stop_logging();
			Debug_Bar_Deprecated::stop_logging();
			Debug_Bar_Doing_It_Wrong::stop_logging();
			return;
		}

		$this->requirements();
		$this->init_panels();
	}

	function early_requirements() {
		require_once( $this->path . '/compat.php' );
		$recs = array( 'panel', 'php', 'deprecated', 'doingitwrong' );
		$this->include_files( $recs );
	}

	function requirements() {
		$recs = array( 'queries', 'request', 'wp-query', 'object-cache', 'js' );
		$this->include_files( $recs );
	}

	function include_files( $recs ) {
		foreach ( $recs as $rec ) {
			require_once $this->path . "/panels/class-debug-bar-$rec.php";
		}
	}

	function enqueue() {
		$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';

		wp_enqueue_style( 'debug-bar', plugins_url( "css/debug-bar$suffix.css", __FILE__ ), array(), '20160430' );

		wp_enqueue_script( 'debug-bar', plugins_url( "js/debug-bar$suffix.js", __FILE__ ), array( 'jquery' ), '20160430', true );

		do_action('debug_bar_enqueue_scripts');
	}

	function init_panels() {
		$classes = array(
			'Debug_Bar_PHP',
			'Debug_Bar_Queries',
			'Debug_Bar_WP_Query',
			'Debug_Bar_Deprecated',
			'Debug_Bar_Doing_It_Wrong',
			'Debug_Bar_Request',
			'Debug_Bar_Object_Cache',
			'Debug_Bar_JS',
		);

		foreach ( $classes as $class ) {
			$this->panels[] = new $class;
		}

		$this->panels = apply_filters( 'debug_bar_panels', $this->panels );
	}

	function ensure_ajaxurl() { ?>
		<script type="text/javascript">
		//<![CDATA[
		var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
		//]]>
		</script>
		<?php
	}

	// memory_get_peak_usage is PHP >= 5.2.0 only
	function safe_memory_get_peak_usage() {
		if ( function_exists( 'memory_get_peak_usage' ) ) {
			$usage = memory_get_peak_usage();
		} else {
			$usage = memory_get_usage();
		}
		return $usage;
	}

	function admin_bar_menu() {
		global $wp_admin_bar;

		$classes = apply_filters( 'debug_bar_classes', array() );
		$classes = implode( " ", array_unique( $classes ) );

		/* Add the main siteadmin menu item */
		$wp_admin_bar->add_menu( array(
			'id'     => 'debug-bar',
			'parent' => 'top-secondary',
			'title'  => apply_filters( 'debug_bar_title', __('Debug', 'debug-bar') ),
			'meta'   => array( 'class' => $classes ),
		) );

		// @todo: Uncomment and finish me!
		// foreach ( $this->panels as $panel_key => $panel ) {
		// 	if ( ! $panel->is_visible() )
		// 		continue;
		//
		// 	$panel_class = get_class( $panel );
		//
		// 	$wp_admin_bar->add_menu( array(
		// 		'parent' => 'debug-bar',
		// 		'id'     => "debug-bar-$panel_class",
		// 		'title'  => $panel->title(),
		// 	) );
		// }
	}

	function body_class( $classes ) {
		if ( is_array( $classes ) )
			$classes[] = 'debug-bar-maximized';
		else
			$classes .= ' debug-bar-maximized ';

		if ( isset( $_GET['debug-bar'] ) ) {
			if ( is_array( $classes ) )
				$classes[] = 'debug-bar-visible';
			else
				$classes .= ' debug-bar-visible ';
		}

		return $classes;
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

	<div id="debug-bar-actions">
		<span class="maximize">+</span>
		<span class="restore">&ndash;</span>
		<span class="close">&times;</span>
	</div>

	<div id='debug-bar-info'>
		<div id="debug-status">
			<?php //@todo: Add a links to information about WP_DEBUG, PHP version, MySQL version, and Peak Memory.
			$statuses = array();
			$statuses[] = array(
				'site',
				php_uname( 'n' ),
				/* translators: %d is the site id number in a multi-site setting. */
				sprintf( __( '#%d', 'debug-bar' ), get_current_blog_id() ),
			);
			$statuses[] = array(
				'php',
				__( 'PHP', 'debug-bar' ),
				phpversion(),
			);
			$db_title = empty( $wpdb->is_mysql ) ? __( 'DB', 'debug-bar' ) : __( 'MySQL', 'debug-bar' );
			$statuses[] = array(
				'db',
				$db_title,
				$wpdb->db_version(),
			);
			$statuses[] = array(
				'memory',
				__( 'Memory Usage', 'debug-bar' ),
				/* translators: %s is a formatted number representing the memory usage. */
				sprintf( __( '%s bytes', 'debug-bar' ), number_format_i18n( $this->safe_memory_get_peak_usage() ) ),
			);

			if ( ! WP_DEBUG ) {
				$statuses[] = array(
					'warning',
					__( 'Please Enable', 'debug-bar' ),
					'WP_DEBUG',
				);
			}

			$statuses = apply_filters( 'debug_bar_statuses', $statuses );

			foreach ( $statuses as $status ):
				list( $slug, $title, $data ) = $status;

				?><div id='debug-status-<?php echo esc_attr( $slug ); ?>' class='debug-status'>
					<div class='debug-status-title'><?php echo $title; ?></div>
					<?php if ( ! empty( $data ) ): ?>
						<div class='debug-status-data'><?php echo $data; ?></div>
					<?php endif; ?>
				</div><?php
			endforeach;
			?>
		</div>
	</div>

	<div id='debug-bar-menu'>
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
