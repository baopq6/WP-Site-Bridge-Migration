<?php
/**
 * Core plugin class
 *
 * @package WPSiteBridge
 */

namespace WPSiteBridge;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core class
 */
class Core {
	
	/**
	 * Plugin instance
	 *
	 * @var Core
	 */
	private static $instance = null;
	
	/**
	 * Admin instance
	 *
	 * @var Admin
	 */
	public $admin;
	
	/**
	 * API instance
	 *
	 * @var API
	 */
	public $api;
	
	/**
	 * Migrator instance
	 *
	 * @var Migrator
	 */
	public $migrator;
	
	/**
	 * Get plugin instance
	 *
	 * @return Core
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
		$this->init();
	}
	
	/**
	 * Initialize plugin components
	 */
	private function init() {
		// Initialize admin interface
		if ( is_admin() ) {
			$this->admin = Admin::get_instance();
		}
		
		// Initialize API endpoints
		$this->api = API::get_instance();
		
		// Initialize migrator
		$this->migrator = Migrator::get_instance();
	}
}

