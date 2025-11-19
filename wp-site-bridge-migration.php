<?php
/**
 * Plugin Name: WP Site Bridge Migration
 * Plugin URI: https://github.com/baopq6/WP-Site-Bridge-Migration
 * Description: Migrate WordPress sites from one host to another directly.
 * Version: 1.2.0
 * Author: @pqbao1987
 * Author URI: https://www.facebook.com/pqbao1987
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

// Load Composer autoloader if available
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

// Define plugin constants
define( 'WPSBM_VERSION', '1.2.0' );
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
	 *
	 * Note: If Composer autoloader is available, classes will be autoloaded via PSR-4.
	 * These require_once statements ensure backward compatibility when Composer is not used.
	 */
	private function load_dependencies() {
		$required_files = array(
			'includes/class-core.php',
			'includes/class-admin.php',
			'includes/class-api.php',
			'includes/class-migrator.php',
		);
		
		// Check if all required files exist
		foreach ( $required_files as $file ) {
			$file_path = WPSBM_PLUGIN_DIR . $file;
			if ( ! file_exists( $file_path ) ) {
				wp_die(
					sprintf(
						/* translators: %1$s: File path, %2$s: Plugin directory */
						esc_html__( 'WP Site Bridge Migration: Required file not found: %1$s in %2$s. Please reinstall the plugin and ensure all files are extracted correctly.', 'wp-site-bridge-migration' ),
						esc_html( $file ),
						esc_html( WPSBM_PLUGIN_DIR )
					),
					esc_html__( 'Plugin Activation Error', 'wp-site-bridge-migration' ),
					array( 'back_link' => true )
				);
			}
		}
		
		// Load classes manually if Composer autoloader is not available
		if ( ! class_exists( 'WPSiteBridge\Core' ) ) {
			require_once WPSBM_PLUGIN_DIR . 'includes/class-core.php';
		}
		if ( ! class_exists( 'WPSiteBridge\Admin' ) ) {
			require_once WPSBM_PLUGIN_DIR . 'includes/class-admin.php';
		}
		if ( ! class_exists( 'WPSiteBridge\API' ) ) {
			require_once WPSBM_PLUGIN_DIR . 'includes/class-api.php';
		}
		if ( ! class_exists( 'WPSiteBridge\Migrator' ) ) {
			require_once WPSBM_PLUGIN_DIR . 'includes/class-migrator.php';
		}
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

