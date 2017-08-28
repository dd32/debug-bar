<?php
/*
 Plugin Name: Debug Bar
 Plugin URI: https://wordpress.org/plugins/debug-bar/
 Description: Adds a debug menu to the admin bar that shows query, cache, and other helpful debugging information.
 Author: wordpressdotorg
 Version: 0.9.1-alpha
 Author URI: https://wordpress.org/
 Text Domain: debug-bar
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

	/**
	 * Debug Bar version nr for use by add-on plugins.
	 *
	 * @var string
	 */
	const VERSION = '0.9.1-alpha';

	/**
	 * Path to the directory for this plugin.
	 *
	 * @since 0.10.0
	 *
	 * @var string
	 */
	public $path;

	public $panels = array();

	public function __construct() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			add_action( 'admin_init', array( $this, 'init_ajax' ) );
		}
		add_action( 'admin_bar_init', array( $this, 'init' ) );

		$this->path = plugin_dir_path( __FILE__ );

		$this->early_requirements();
		Debug_Bar_PHP::start_logging();
		Debug_Bar_Deprecated::start_logging();
		Debug_Bar_Doing_It_Wrong::start_logging();
	}

	public function Debug_Bar() {
		if ( function_exists( '_deprecated_constructor' ) ) {
			_deprecated_constructor( __METHOD__, '0.8.3', __CLASS__ );
		}
		self::__construct();
	}

	public function init() {
		if ( ! $this->enable_debug_bar() ) {
			$this->stop_logging();
			return;
		}

		load_plugin_textdomain( 'debug-bar' );

		add_action( 'admin_bar_menu',   array( $this, 'admin_bar_menu' ), 1000 );
		add_action( 'admin_footer',     array( $this, 'render' ), 1000 );
		add_action( 'wp_footer',        array( $this, 'render' ), 1000 );
		add_action( 'wp_head',          array( $this, 'ensure_ajaxurl' ), 1 );
		add_filter( 'body_class',       array( $this, 'body_class' ) );
		add_filter( 'admin_body_class', array( $this, 'body_class' ) );

		$this->requirements();
		$this->enqueue();
		$this->init_panels();
	}

	/**
	 * Are we on the wp-login.php page?
	 *
	 * We can get here while logged in and break the page as the admin bar
	 * is not shown and other things the js relies on are not available.
	 *
	 * @return bool
	 */
	public function is_wp_login() {
		return 'wp-login.php' === basename( $_SERVER['SCRIPT_NAME'] );
	}

	/**
	 * Should the debug bar functionality be enabled?
	 *
	 * @since 0.9
	 *
	 * @param bool $ajax Whether this is an ajax call or not. Defaults to false.
	 * @return bool
	 */
	protected function enable_debug_bar( $ajax = false ) {
		$enable = false;

		if ( $ajax && is_super_admin() ) {
			$enable = true;
		} elseif ( ! $ajax && ( is_super_admin() && is_admin_bar_showing() && ! $this->is_wp_login() ) ) {
			$enable = true;
		}

		/**
		 * Allows for overruling of whether the debug bar functionality will be enabled.
		 *
		 * @since 0.9
		 *
		 * @param bool $enable Whether the debug bar will be enabled or not.
		 */
		return apply_filters( 'debug_bar_enable', $enable );
	}

	public function init_ajax() {
		if ( ! $this->enable_debug_bar( true ) ) {
			$this->stop_logging();
			return;
		}

		$this->requirements();
		$this->init_panels();
	}

	/**
	 * Stop logging of notices.
	 *
	 * @since 0.10.0
	 */
	protected function stop_logging() {
		Debug_Bar_PHP::stop_logging();
		Debug_Bar_Deprecated::stop_logging();
		Debug_Bar_Doing_It_Wrong::stop_logging();
	}

	/**
	 * Include a number of files early to allow for error logging to start.
	 *
	 * @since 0.10.0
	 */
	protected function early_requirements() {
		$recs = array( 'panel', 'php', 'deprecated', 'doing-it-wrong' );
		$this->include_files( $recs );
	}

	/**
	 * Include the rest of the files.
	 *
	 * @since 0.10.0
	 */
	protected function requirements() {
		$recs = array( 'queries', 'request', 'wp-query', 'object-cache', 'js' );
		$this->include_files( $recs );
	}

	/**
	 * Include plugin files.
	 *
	 * @since 0.10.0
	 *
	 * @param array $recs List of partial file names of the files to include.
	 */
	private function include_files( $recs ) {
		foreach ( $recs as $rec ) {
			require_once $this->path . "/panels/class-debug-bar-$rec.php";
		}
	}

	protected function enqueue() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '.dev' : '';

		wp_enqueue_style( 'debug-bar', plugins_url( "css/debug-bar$suffix.css", __FILE__ ), array(), '20170828' );

		wp_enqueue_script( 'debug-bar', plugins_url( "js/debug-bar$suffix.js", __FILE__ ), array( 'jquery' ), '20170623', true );

		do_action( 'debug_bar_enqueue_scripts' );
	}

	protected function init_panels() {
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
			$this->panels[] = new $class();
		}

		$this->panels = apply_filters( 'debug_bar_panels', $this->panels );
	}

	public function ensure_ajaxurl() {
		?>
		<script type="text/javascript">
			//<![CDATA[
			var ajaxurl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
			//]]>
		</script>
		<?php
	}

	public function admin_bar_menu() {
		global $wp_admin_bar;

		$classes = apply_filters( 'debug_bar_classes', array() );
		$classes = implode( ' ', $classes );

		/* Add the main siteadmin menu item */
		$wp_admin_bar->add_menu( array(
			'id'     => 'debug-bar',
			'parent' => 'top-secondary',
			'title'  => apply_filters( 'debug_bar_title', __( 'Debug', 'debug-bar' ) ),
			'meta'   => array(
				'class' => $classes,
			),
		) );

		foreach ( $this->panels as $panel_key => $panel ) {
			$panel->prerender();
			if ( ! $panel->is_visible() ) {
				continue;
			}

			$panel_class = get_class( $panel );

			$wp_admin_bar->add_menu( array(
				'parent' => 'debug-bar',
				'id'     => "debug-bar-$panel_class",
				'title'  => $panel->title(),
				'href'   => '#debug-menu-target-' . esc_attr( $panel_class ),
				'meta'   => array(
					'rel' => '#debug-menu-link-' . esc_attr( $panel_class ),
				),
			) );
		}
	}

	public function body_class( $classes ) {
		if ( is_array( $classes ) ) {
			$classes[] = 'debug-bar-maximized';
		} else {
			$classes .= ' debug-bar-maximized ';
		}

		if ( isset( $_GET['debug-bar'] ) ) {
			if ( is_array( $classes ) ) {
				$classes[] = 'debug-bar-visible';
			} else {
				$classes .= ' debug-bar-visible ';
			}
		}

		return $classes;
	}

	public function render() {
		global $wpdb;

		if ( empty( $this->panels ) ) {
			return;
		}

		foreach ( $this->panels as $panel_key => $panel ) {
			$panel->prerender();
			if ( ! $panel->is_visible() ) {
				unset( $this->panels[ $panel_key ] );
			}
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
					<?php
					//@todo: Add a links to information about WP_DEBUG, PHP version, MySQL version, and Peak Memory.
					$statuses   = array();

					$host_name = __( 'Site', 'debug-bar' );
					if ( function_exists( 'php_uname' ) ) {
						$host_name = php_uname( 'n' );
					} elseif ( ! empty( $_SERVER['SERVER_NAME'] ) ) {
						$host_name = $_SERVER['SERVER_NAME'];
					}
					$statuses[] = array(
						'site',
						$host_name,
						/* translators: %d is the site id number in a multi-site setting. */
						sprintf( __( '#%d', 'debug-bar' ), get_current_blog_id() ),
					);
					$statuses[] = array(
						'php',
						__( 'PHP', 'debug-bar' ),
						phpversion(),
					);
					$db_title   = empty( $wpdb->is_mysql ) ? __( 'DB', 'debug-bar' ) : __( 'MySQL', 'debug-bar' );
					$statuses[] = array(
						'db',
						$db_title,
						$wpdb->db_version(),
					);
					$statuses[] = array(
						'memory',
						__( 'Memory Usage', 'debug-bar' ),
						/* translators: %s is a formatted number representing the memory usage. */
						sprintf( __( '%s bytes', 'debug-bar' ), number_format_i18n( memory_get_peak_usage() ) ),
					);

					if ( ! WP_DEBUG ) {
						$statuses[] = array(
							'warning',
							__( 'Please Enable', 'debug-bar' ),
							'WP_DEBUG',
						);
					}

					$statuses = apply_filters( 'debug_bar_statuses', $statuses );

					foreach ( $statuses as $status ) :
						list( $slug, $title, $data ) = $status;
						?>
						<div id='debug-status-<?php echo esc_attr( $slug ); ?>' class='debug-status'>
						<div class='debug-status-title'><?php echo $title; ?></div>
						<?php
						if ( ! empty( $data ) ) :
						?>
						<div class='debug-status-data'><?php echo $data; ?></div>
						<?php
						endif;
						?>
						</div>
						<?php
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
					endforeach;
					?>
				</ul>
			</div>

			<div id="debug-menu-targets">
				<?php
				$current = ' style="display: block"';
				foreach ( $this->panels as $panel ) :
					$class = get_class( $panel );
					?>
					<div id="debug-menu-target-<?php echo $class; ?>" class="debug-menu-target" <?php echo $current; ?>>
						<?php
						$panel->render();
						?>
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
