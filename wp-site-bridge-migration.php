<?php
/**
 * Plugin Name: WP Site Bridge Migration
 * Plugin URI: https://example.com/wp-site-bridge-migration
 * Description: Migrate WordPress sites from one host to another directly.
 * Version: 1.1.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-site-bridge-migration
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'WPSBM_VERSION', '1.1.0' );
define( 'WPSBM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPSBM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPSBM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'WPSBM_PLUGIN_FILE', __FILE__ );

/**
 * Main plugin class
 */
final class WPSiteBridge_Migration {
	
	/**
	 * Plugin instance
	 *
	 * @var WPSiteBridge_Migration
	 */
	private static $instance = null;
	
	/**
	 * Get plugin instance
	 *
	 * @return WPSiteBridge_Migration
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * Constructor
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}
	
	/**
	 * Load required files
	 */
	private function load_dependencies() {
		require_once WPSBM_PLUGIN_DIR . 'includes/class-core.php';
		require_once WPSBM_PLUGIN_DIR . 'includes/class-admin.php';
		require_once WPSBM_PLUGIN_DIR . 'includes/class-api.php';
		require_once WPSBM_PLUGIN_DIR . 'includes/class-migrator.php';
	}
	
	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
		
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}
	
	/**
	 * Initialize plugin
	 */
	public function init() {
		// Load text domain for translations
		load_plugin_textdomain(
			'wp-site-bridge-migration',
			false,
			dirname( WPSBM_PLUGIN_BASENAME ) . '/languages'
		);
		
		// Initialize core class
		WPSiteBridge\Core::get_instance();
	}
	
	/**
	 * Plugin activation
	 */
	public function activate() {
		// Check PHP version
		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			deactivate_plugins( WPSBM_PLUGIN_BASENAME );
			wp_die( 
				esc_html__( 'WP Site Bridge Migration requires PHP version 7.4 or higher.', 'wp-site-bridge-migration' ),
				esc_html__( 'Plugin Activation Error', 'wp-site-bridge-migration' ),
				array( 'back_link' => true )
			);
		}
		
		// Check WordPress version
		global $wp_version;
		if ( version_compare( $wp_version, '5.0', '<' ) ) {
			deactivate_plugins( WPSBM_PLUGIN_BASENAME );
			wp_die( 
				esc_html__( 'WP Site Bridge Migration requires WordPress version 5.0 or higher.', 'wp-site-bridge-migration' ),
				esc_html__( 'Plugin Activation Error', 'wp-site-bridge-migration' ),
				array( 'back_link' => true )
			);
		}
		
		// Set default options if needed
		// This will be implemented in Phase 2
	}
	
	/**
	 * Plugin deactivation
	 */
	public function deactivate() {
		// Cleanup if needed
		// This will be implemented in later phases
	}
}

/**
 * Initialize the plugin
 */
function wpsbm_init() {
	return WPSiteBridge_Migration::get_instance();
}

// Start the plugin
wpsbm_init();

