<?php
/**
 * Admin class
 *
 * @package WPSiteBridge
 */

namespace WPSiteBridge;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin class
 */
class Admin {
	
	/**
	 * Plugin instance
	 *
	 * @var Admin
	 */
	private static $instance = null;
	
	/**
	 * Current site role
	 *
	 * @var string
	 */
	private $site_role = 'source';
	
	/**
	 * Get plugin instance
	 *
	 * @return Admin
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
		$this->init_hooks();
	}
	
	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		
		// AJAX handlers
		add_action( 'wp_ajax_wpsbm_generate_key', array( $this, 'ajax_generate_key' ) );
		add_action( 'wp_ajax_wpsbm_connect_validate', array( $this, 'ajax_connect_validate' ) );
		add_action( 'wp_ajax_wpsbm_connect_remote', array( $this, 'ajax_connect_validate' ) ); // Alias for consistency
		add_action( 'wp_ajax_wpsbm_step_backup_db', array( $this, 'ajax_step_backup_db' ) );
		add_action( 'wp_ajax_wpsbm_step_zip_files', array( $this, 'ajax_step_zip_files' ) );
		add_action( 'wp_ajax_wpsbm_get_source_token', array( $this, 'ajax_get_source_token' ) );
		add_action( 'wp_ajax_wpsbm_cleanup', array( $this, 'ajax_cleanup' ) );
	}
	
	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		add_management_page(
			__( 'WP Site Bridge Migration', 'wp-site-bridge-migration' ),
			__( 'Site Migration', 'wp-site-bridge-migration' ),
			'manage_options',
			'wp-site-bridge-migration',
			array( $this, 'render_admin_page' )
		);
	}
	
	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Current admin page hook
	 */
	public function enqueue_assets( $hook ) {
		// Only load on our admin page
		if ( 'tools_page_wp-site-bridge-migration' !== $hook ) {
			return;
		}
		
		// Enqueue TailwindCSS from CDN
		wp_enqueue_style(
			'wpsbm-tailwind',
			'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css',
			array(),
			null
		);
		
		// Enqueue CSS
		wp_enqueue_style(
			'wpsbm-admin',
			WPSBM_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			WPSBM_VERSION
		);
		
		// Enqueue JS
		wp_enqueue_script(
			'wpsbm-admin',
			WPSBM_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			WPSBM_VERSION,
			true
		);
		
		// Localize script
		wp_localize_script(
			'wpsbm-admin',
			'wpsbmAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'wpsbm_admin_nonce' ),
			)
		);
	}
	
	/**
	 * Render admin page
	 */
	public function render_admin_page() {
		// Get saved site role from options, default to 'source'
		$this->site_role = get_option( 'wpsbm_site_role', 'source' );
		
		// Handle form submission to save site role
		if ( isset( $_POST['wpsbm_save_site_role'] ) ) {
			// Verify nonce - must be called before any output
			check_admin_referer( 'wpsbm_save_site_role', 'wpsbm_site_role_nonce' );
			
			// Check user capabilities
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Insufficient permissions.', 'wp-site-bridge-migration' ) );
			}
			
			$new_role = isset( $_POST['wpsbm_site_role'] ) ? sanitize_text_field( $_POST['wpsbm_site_role'] ) : 'source';
			if ( in_array( $new_role, array( 'source', 'destination' ), true ) ) {
				$this->site_role = $new_role;
				$updated = update_option( 'wpsbm_site_role', $this->site_role );
				
				// If set as source, ensure source token exists
				if ( 'source' === $this->site_role ) {
					$source_token = get_option( 'wpsbm_secret_token' );
					if ( empty( $source_token ) ) {
						$migrator = Migrator::get_instance();
						$source_token = $migrator->generate_secure_token( 32 );
						update_option( 'wpsbm_secret_token', $source_token );
					}
				}
				
				// Clear any existing destination migration key when switching roles
				if ( 'source' === $this->site_role ) {
					delete_option( 'wpsbm_secret_key' );
				}
				
				// Redirect to show success message - use absolute URL
				$redirect_url = add_query_arg(
					array( 'wpsbm_updated' => '1' ),
					admin_url( 'tools.php?page=wp-site-bridge-migration' )
				);
				wp_safe_redirect( $redirect_url );
				exit;
			} else {
				// Invalid role value
				wp_die( esc_html__( 'Invalid site role selected.', 'wp-site-bridge-migration' ) );
			}
		}
		
		// Check if we should show success notice
		$show_notice = isset( $_GET['wpsbm_updated'] ) && '1' === $_GET['wpsbm_updated'];
		
		// Load template
		$template_path = WPSBM_PLUGIN_DIR . 'templates/admin-page.php';
		if ( file_exists( $template_path ) ) {
			// Make variables available to template
			$site_role = $this->site_role;
			include $template_path;
		} else {
			$this->render_default_admin_page();
		}
	}
	
	/**
	 * Show success notice
	 */
	public function show_success_notice() {
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Site role saved successfully.', 'wp-site-bridge-migration' ); ?></p>
		</div>
		<?php
	}
	
	/**
	 * AJAX handler: Generate migration key
	 */
	public function ajax_generate_key() {
		// Check nonce
		if ( ! check_ajax_referer( 'wpsbm_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'wp-site-bridge-migration' ) ) );
		}
		
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'wp-site-bridge-migration' ) ) );
		}
		
		// Check if site role is destination
		$site_role = get_option( 'wpsbm_site_role', 'source' );
		if ( 'destination' !== $site_role ) {
			wp_send_json_error( array( 
				'message' => sprintf(
					/* translators: %s: Current site role */
					__( 'This action is only available for destination sites. Current role: %s. Please go to "This Site Is" section above and save the role as "Destination Website" first.', 'wp-site-bridge-migration' ),
					esc_html( $site_role )
				)
			) );
		}
		
		// Generate migration key
		$migrator = Migrator::get_instance();
		$migration_key = $migrator->generate_migration_key();
		
		if ( empty( $migration_key ) ) {
			wp_send_json_error( array( 'message' => __( 'Failed to generate migration key.', 'wp-site-bridge-migration' ) ) );
		}
		
		// Save migration key to options for display
		update_option( 'wpsbm_secret_key', $migration_key );
		
		wp_send_json_success( array(
			'migration_key' => $migration_key,
			'message'        => __( 'Migration key generated successfully.', 'wp-site-bridge-migration' ),
		) );
	}
	
	/**
	 * AJAX handler: Connect and validate
	 */
	public function ajax_connect_validate() {
		// Check nonce
		if ( ! check_ajax_referer( 'wpsbm_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'wp-site-bridge-migration' ) ) );
		}
		
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'wp-site-bridge-migration' ) ) );
		}
		
		// Check if site role is source
		$site_role = get_option( 'wpsbm_site_role', 'source' );
		if ( 'source' !== $site_role ) {
			wp_send_json_error( array( 'message' => __( 'This action is only available for source sites.', 'wp-site-bridge-migration' ) ) );
		}
		
		// Get parameters
		$migration_key = isset( $_POST['migration_key'] ) ? sanitize_text_field( $_POST['migration_key'] ) : '';
		$destination_url = isset( $_POST['destination_url'] ) ? esc_url_raw( $_POST['destination_url'] ) : '';
		
		if ( empty( $migration_key ) ) {
			wp_send_json_error( array( 'message' => __( 'Migration key is required.', 'wp-site-bridge-migration' ) ) );
		}
		
		// Parse migration key
		$migrator = Migrator::get_instance();
		$key_data = $migrator->parse_migration_key( $migration_key );
		
		if ( false === $key_data ) {
			wp_send_json_error( array( 'message' => __( 'Invalid migration key format.', 'wp-site-bridge-migration' ) ) );
		}
		
		// Use URL from key if destination_url is not provided, otherwise use provided URL
		$target_url = ! empty( $destination_url ) ? $destination_url : $key_data['url'];
		$target_url = trailingslashit( $target_url );
		
		// Prepare REST API endpoint
		$rest_url = $target_url . 'wp-json/wpsbm/v1/handshake';
		
		// Make handshake request
		$response = wp_remote_post(
			$rest_url,
			array(
				'timeout' => 30,
				'body'    => array(
					'token' => $key_data['token'],
				),
				'sslverify' => true,
			)
		);
		
		// Check for errors
		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array(
				'message' => sprintf(
					/* translators: %s: Error message */
					__( 'Connection failed: %s', 'wp-site-bridge-migration' ),
					$response->get_error_message()
				),
			) );
		}
		
		// Get response code
		$response_code = wp_remote_retrieve_response_code( $response );
		
		if ( 200 !== $response_code ) {
			$response_body = wp_remote_retrieve_body( $response );
			$error_data = json_decode( $response_body, true );
			
			$error_message = __( 'Connection validation failed.', 'wp-site-bridge-migration' );
			if ( isset( $error_data['message'] ) ) {
				$error_message = $error_data['message'];
			} elseif ( 403 === $response_code ) {
				$error_message = __( 'Invalid token. Please check your migration key.', 'wp-site-bridge-migration' );
			} elseif ( 404 === $response_code ) {
				$error_message = __( 'Destination site not found or plugin not activated.', 'wp-site-bridge-migration' );
			}
			
			wp_send_json_error( array( 'message' => $error_message ) );
		}
		
		// Parse response
		$response_body = wp_remote_retrieve_body( $response );
		$data = json_decode( $response_body, true );
		
		if ( ! isset( $data['success'] ) || ! $data['success'] ) {
			wp_send_json_error( array(
				'message' => __( 'Handshake failed. Invalid response from destination site.', 'wp-site-bridge-migration' ),
			) );
		}
		
		// Save connection data
		update_option( 'wpsbm_remote_secret_key', $migration_key );
		update_option( 'wpsbm_destination_url', $target_url );
		update_option( 'wpsbm_connection_status', 'connected' );
		update_option( 'wpsbm_destination_info', $data );
		
		// Success response
		$site_name = isset( $data['site_name'] ) ? $data['site_name'] : $target_url;
		
		wp_send_json_success( array(
			'message'   => sprintf(
				/* translators: %s: Site name or URL */
				__( 'Connected to %s', 'wp-site-bridge-migration' ),
				esc_html( $site_name )
			),
			'site_name' => $site_name,
			'site_url'  => $target_url,
			'version'   => isset( $data['version'] ) ? $data['version'] : '',
		) );
	}
	
	/**
	 * AJAX handler: Backup database step
	 */
	public function ajax_step_backup_db() {
		// Check nonce
		if ( ! check_ajax_referer( 'wpsbm_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'wp-site-bridge-migration' ) ) );
		}
		
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'wp-site-bridge-migration' ) ) );
		}
		
		// Check if site role is source
		$site_role = get_option( 'wpsbm_site_role', 'source' );
		if ( 'source' !== $site_role ) {
			wp_send_json_error( array( 'message' => __( 'This action is only available for source sites.', 'wp-site-bridge-migration' ) ) );
		}
		
		// Check connection status
		$connection_status = get_option( 'wpsbm_connection_status', '' );
		if ( 'connected' !== $connection_status ) {
			wp_send_json_error( array( 'message' => __( 'Please connect to destination site first.', 'wp-site-bridge-migration' ) ) );
		}
		
		// Increase time limit and memory limit for large databases
		@set_time_limit( 300 ); // 5 minutes
		@ini_set( 'memory_limit', '256M' );
		
		// Export database
		$migrator = Migrator::get_instance();
		$result = $migrator->export_database();
		
		if ( false === $result ) {
			wp_send_json_error( array(
				'message' => __( 'Failed to export database. Please check file permissions and try again.', 'wp-site-bridge-migration' ),
			) );
		}
		
		// Success response
		wp_send_json_success( array(
			'file'          => $result['file'],
			'size'          => $result['size'],
			'size_formatted' => $result['size_formatted'],
			'message'       => sprintf(
				/* translators: %s: File size */
				__( 'Database exported successfully. File size: %s', 'wp-site-bridge-migration' ),
				$result['size_formatted']
			),
		) );
	}
	
	/**
	 * AJAX handler: Zip files step
	 */
	public function ajax_step_zip_files() {
		// Check nonce
		if ( ! check_ajax_referer( 'wpsbm_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'wp-site-bridge-migration' ) ) );
		}
		
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'wp-site-bridge-migration' ) ) );
		}
		
		// Check if site role is source
		$site_role = get_option( 'wpsbm_site_role', 'source' );
		if ( 'source' !== $site_role ) {
			wp_send_json_error( array( 'message' => __( 'This action is only available for source sites.', 'wp-site-bridge-migration' ) ) );
		}
		
		// Get type parameter
		$type = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : '';
		
		if ( empty( $type ) ) {
			wp_send_json_error( array( 'message' => __( 'Type parameter is required.', 'wp-site-bridge-migration' ) ) );
		}
		
		// Check if ZipArchive is available
		if ( ! class_exists( 'ZipArchive' ) ) {
			wp_send_json_error( array(
				'message' => __( 'ZipArchive class is not available. Please contact your hosting provider to enable the zip extension.', 'wp-site-bridge-migration' ),
			) );
		}
		
		// Increase time limit and memory limit for large files
		@set_time_limit( 600 ); // 10 minutes
		@ini_set( 'memory_limit', '512M' );
		
		// Export based on type
		$migrator = Migrator::get_instance();
		$result = false;
		
		switch ( $type ) {
			case 'plugins':
				$result = $migrator->zip_plugins();
				break;
			case 'themes':
				$result = $migrator->zip_themes();
				break;
			case 'uploads':
				$result = $migrator->zip_uploads();
				break;
			default:
				wp_send_json_error( array(
					'message' => sprintf(
						/* translators: %s: Type value */
						__( 'Invalid type: %s', 'wp-site-bridge-migration' ),
						esc_html( $type )
					),
				) );
		}
		
		if ( false === $result ) {
			wp_send_json_error( array(
				'message' => sprintf(
					/* translators: %s: Type */
					__( 'Failed to create %s zip file. Please check file permissions and try again.', 'wp-site-bridge-migration' ),
					esc_html( $type )
				),
			) );
		}
		
		// Success response
		wp_send_json_success( array(
			'file'          => $result['file'],
			'size'          => $result['size'],
			'size_formatted' => $result['size_formatted'],
			'type'          => $type,
			'message'       => sprintf(
				/* translators: %1$s: Type, %2$s: File size */
				__( '%1$s zipped successfully. File size: %2$s', 'wp-site-bridge-migration' ),
				ucfirst( $type ),
				$result['size_formatted']
			),
		) );
	}
	
	/**
	 * AJAX handler: Get source token
	 */
	public function ajax_get_source_token() {
		// Check nonce
		if ( ! check_ajax_referer( 'wpsbm_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'wp-site-bridge-migration' ) ) );
		}
		
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'wp-site-bridge-migration' ) ) );
		}
		
		// Check if site role is source
		$site_role = get_option( 'wpsbm_site_role', 'source' );
		if ( 'source' !== $site_role ) {
			wp_send_json_error( array( 'message' => __( 'This action is only available for source sites.', 'wp-site-bridge-migration' ) ) );
		}
		
		// Get source token (this is the token that destination will use to download from source)
		$source_token = get_option( 'wpsbm_secret_token' );
		
		if ( empty( $source_token ) ) {
			// Generate a new token if none exists
			$migrator = Migrator::get_instance();
			$source_token = $migrator->generate_secure_token( 32 );
			update_option( 'wpsbm_secret_token', $source_token );
		}
		
		// Get source URL
		$source_url = get_site_url();
		
		wp_send_json_success( array(
			'source_token' => $source_token,
			'source_url'   => $source_url,
		) );
	}
	
	/**
	 * AJAX handler for cleanup
	 *
	 * Deletes temporary files on the local site
	 */
	public function ajax_cleanup() {
		// Check nonce
		if ( ! check_ajax_referer( 'wpsbm_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'wp-site-bridge-migration' ) ) );
		}
		
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'wp-site-bridge-migration' ) ) );
		}
		
		// Get migrator instance
		$migrator = Migrator::get_instance();
		
		// Run cleanup
		$result = $migrator->cleanup_temp_files();
		
		if ( false === $result ) {
			wp_send_json_error( array(
				'message' => __( 'Cleanup failed. Some files may still exist.', 'wp-site-bridge-migration' ),
			) );
		}
		
		wp_send_json_success( array(
			'message' => __( 'Temporary files cleaned up successfully.', 'wp-site-bridge-migration' ),
		) );
	}

}

