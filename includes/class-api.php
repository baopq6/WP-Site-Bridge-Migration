<?php
/**
 * API class
 *
 * @package WPSiteBridge
 */

namespace WPSiteBridge;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * API class
 */
class API {
	
	/**
	 * Plugin instance
	 *
	 * @var API
	 */
	private static $instance = null;
	
	/**
	 * Get plugin instance
	 *
	 * @return API
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
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}
	
	/**
	 * Register REST API routes
	 */
	public function register_routes() {
		register_rest_route(
			'wpsbm/v1',
			'/handshake',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_handshake' ),
				'permission_callback' => array( $this, 'handshake_permission_check' ),
				'args'                => array(
					'token' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => function( $param ) {
							return ! empty( $param );
						},
					),
				),
			)
		);
		
		register_rest_route(
			'wpsbm/v1',
			'/download',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'handle_download' ),
				'permission_callback' => array( $this, 'download_permission_check' ),
				'args'                => array(
					'file_type' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => function( $param ) {
							return in_array( $param, array( 'database', 'plugins', 'themes', 'uploads' ), true );
						},
					),
					'token'     => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => function( $param ) {
							return ! empty( $param );
						},
					),
				),
			)
		);
		
		register_rest_route(
			'wpsbm/v1',
			'/process_step',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_process_step' ),
				'permission_callback' => array( $this, 'process_step_permission_check' ),
				'args'                => array(
					'step'      => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => function( $param ) {
							return in_array( $param, array( 'database', 'plugins', 'themes', 'uploads' ), true );
						},
					),
					'source_url' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'esc_url_raw',
						'validate_callback' => function( $param ) {
							return ! empty( $param ) && filter_var( $param, FILTER_VALIDATE_URL );
						},
					),
					'source_token' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => function( $param ) {
							return ! empty( $param );
						},
					),
					'token'     => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => function( $param ) {
							return ! empty( $param );
						},
					),
				),
			)
		);
		
		register_rest_route(
			'wpsbm/v1',
			'/finalize_migration',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_finalize_migration' ),
				'permission_callback' => array( $this, 'finalize_migration_permission_check' ),
				'args'                => array(
					'old_url' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'esc_url_raw',
						'validate_callback' => function( $param ) {
							return ! empty( $param ) && filter_var( $param, FILTER_VALIDATE_URL );
						},
					),
					'token'   => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => function( $param ) {
							return ! empty( $param );
						},
					),
				),
			)
		);
		
		register_rest_route(
			'wpsbm/v1',
			'/finalize_migration_batch',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_finalize_migration_batch' ),
				'permission_callback' => array( $this, 'finalize_migration_permission_check' ),
				'args'                => array(
					'old_url' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'esc_url_raw',
						'validate_callback' => function( $param ) {
							return ! empty( $param ) && filter_var( $param, FILTER_VALIDATE_URL );
						},
					),
					'table_name' => array(
						'required' => false,
						'type'     => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'offset' => array(
						'required' => false,
						'type'     => 'integer',
						'default'  => 0,
						'validate_callback' => function( $param ) {
							return is_numeric( $param ) && $param >= 0;
						},
					),
					'token'   => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => function( $param ) {
							return ! empty( $param );
						},
					),
				),
			)
		);
		
		register_rest_route(
			'wpsbm/v1',
			'/cleanup',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_cleanup' ),
				'permission_callback' => array( $this, 'cleanup_permission_check' ),
				'args'                => array(
					'token' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => function( $param ) {
							return ! empty( $param );
						},
					),
				),
			)
		);
		
		register_rest_route(
			'wpsbm/v1',
			'/migration_status',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'handle_migration_status' ),
				'permission_callback' => '__return_true', // Public endpoint for status check
			)
		);
	}
	
	/**
	 * Permission callback for handshake endpoint
	 *
	 * Allows public access but validates the token strictly
	 *
	 * @param WP_REST_Request $request Request object
	 * @return bool|WP_Error True if token is valid, WP_Error otherwise
	 */
	public function handshake_permission_check( $request ) {
		// Get token from request
		$token = $request->get_param( 'token' );
		
		if ( empty( $token ) ) {
			return new \WP_Error(
				'missing_token',
				__( 'Token is required.', 'wp-site-bridge-migration' ),
				array( 'status' => 400 )
			);
		}
		
		// Verify token using Migrator class
		$migrator = Migrator::get_instance();
		$is_valid = $migrator->verify_token( $token );
		
		if ( ! $is_valid ) {
			return new \WP_Error(
				'invalid_token',
				__( 'Invalid token.', 'wp-site-bridge-migration' ),
				array( 'status' => 403 )
			);
		}
		
		// Token is valid, allow access
		return true;
	}
	
	/**
	 * Handle handshake request
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response|WP_Error Response object
	 */
	public function handle_handshake( $request ) {
		// If we reach here, the token has been validated by permission_callback
		
		// Get site information
		$site_name = get_bloginfo( 'name' );
		if ( empty( $site_name ) ) {
			$site_name = get_bloginfo( 'url' );
		}
		
		// Prepare response
		$response_data = array(
			'success'   => true,
			'version'   => WPSBM_VERSION,
			'site_name' => $site_name,
			'site_url'  => get_site_url(),
		);
		
		return new \WP_REST_Response( $response_data, 200 );
	}
	
	/**
	 * Permission callback for download endpoint
	 *
	 * Validates token before allowing file download
	 *
	 * @param WP_REST_Request $request Request object
	 * @return bool|WP_Error True if token is valid, WP_Error otherwise
	 */
	public function download_permission_check( $request ) {
		// Get token from request
		$token = $request->get_param( 'token' );
		
		if ( empty( $token ) ) {
			return new \WP_Error(
				'missing_token',
				__( 'Token is required.', 'wp-site-bridge-migration' ),
				array( 'status' => 400 )
			);
		}
		
		// Verify token using Migrator class
		$migrator = Migrator::get_instance();
		$is_valid = $migrator->verify_token( $token );
		
		if ( ! $is_valid ) {
			return new \WP_Error(
				'invalid_token',
				__( 'Invalid token.', 'wp-site-bridge-migration' ),
				array( 'status' => 403 )
			);
		}
		
		// Token is valid, allow access
		return true;
	}
	
	/**
	 * Handle download request
	 *
	 * Streams file content to the client
	 *
	 * @param WP_REST_Request $request Request object
	 * @return void Exits after streaming file
	 */
	public function handle_download( $request ) {
		// If we reach here, the token has been validated by permission_callback
		
		// Get file type
		$file_type = $request->get_param( 'file_type' );
		
		// Map file type to filename
		$file_map = array(
			'database' => 'database.sql',
			'plugins'  => 'plugins.zip',
			'themes'   => 'themes.zip',
			'uploads'  => 'uploads.zip',
		);
		
		if ( ! isset( $file_map[ $file_type ] ) ) {
			status_header( 400 );
			wp_die( esc_html__( 'Invalid file type.', 'wp-site-bridge-migration' ) );
		}
		
		$filename = $file_map[ $file_type ];
		
		// Get temp directory
		$migrator = Migrator::get_instance();
		$temp_dir = $migrator->get_temp_dir();
		
		if ( false === $temp_dir ) {
			status_header( 500 );
			wp_die( esc_html__( 'Temporary directory not found.', 'wp-site-bridge-migration' ) );
		}
		
		// Build file path
		$file_path = trailingslashit( $temp_dir ) . $filename;
		
		// Check if file exists
		if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
			status_header( 404 );
			wp_die( esc_html__( 'File not found.', 'wp-site-bridge-migration' ) );
		}
		
		// Get file size
		$file_size = filesize( $file_path );
		
		// Determine content type based on file extension
		$content_type = 'application/octet-stream';
		if ( '.zip' === substr( $filename, -4 ) ) {
			$content_type = 'application/zip';
		} elseif ( '.sql' === substr( $filename, -4 ) ) {
			$content_type = 'application/sql';
		}
		
		// Set headers for file download
		header( 'Content-Type: ' . $content_type );
		header( 'Content-Length: ' . $file_size );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Cache-Control: no-cache, must-revalidate' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
		
		// Disable output buffering
		if ( ob_get_level() ) {
			ob_end_clean();
		}
		
		// Register shutdown function to delete file after streaming completes
		// This ensures the file is deleted only after successful transfer
		register_shutdown_function( function() use ( $file_path ) {
			// Only delete if file still exists (might have been deleted already)
			if ( file_exists( $file_path ) ) {
				@unlink( $file_path );
			}
		} );
		
		// Stream file content
		readfile( $file_path );
		
		// Exit immediately after streaming
		exit;
	}
	
	/**
	 * Permission callback for process_step endpoint
	 *
	 * Validates token before allowing restore process
	 *
	 * @param WP_REST_Request $request Request object
	 * @return bool|WP_Error True if token is valid, WP_Error otherwise
	 */
	public function process_step_permission_check( $request ) {
		// Get token from request
		$token = $request->get_param( 'token' );
		
		if ( empty( $token ) ) {
			return new \WP_Error(
				'missing_token',
				__( 'Token is required.', 'wp-site-bridge-migration' ),
				array( 'status' => 400 )
			);
		}
		
		// Verify token using Migrator class
		$migrator = Migrator::get_instance();
		$is_valid = $migrator->verify_token( $token );
		
		if ( ! $is_valid ) {
			return new \WP_Error(
				'invalid_token',
				__( 'Invalid token.', 'wp-site-bridge-migration' ),
				array( 'status' => 403 )
			);
		}
		
		// Token is valid, allow access
		return true;
	}
	
	/**
	 * Handle process_step request
	 *
	 * Processes a restoration step (download, import, extract)
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response|WP_Error Response object
	 */
	public function handle_process_step( $request ) {
		// If we reach here, the token has been validated by permission_callback
		
		// Get parameters
		$step = $request->get_param( 'step' );
		$source_url = $request->get_param( 'source_url' );
		$source_token = $request->get_param( 'source_token' ); // Token to download from source
		
		// Update migration status
		$status = get_option( 'wpsbm_migration_status', array() );
		$status['current_step'] = $step;
		$status['status'] = 'processing';
		$status['last_update'] = time();
		$status['source_url'] = $source_url;
		update_option( 'wpsbm_migration_status', $status );
		
		// Increase time limit and memory limit
		@set_time_limit( 600 ); // 10 minutes
		@ini_set( 'memory_limit', '512M' );
		
		// Get migrator instance
		$migrator = Migrator::get_instance();
		
		// Process based on step type
		switch ( $step ) {
			case 'database':
				$result = $migrator->restore_database( $source_url, $source_token );
				break;
			case 'plugins':
				$result = $migrator->restore_plugins( $source_url, $source_token );
				break;
			case 'themes':
				$result = $migrator->restore_themes( $source_url, $source_token );
				break;
			case 'uploads':
				$result = $migrator->restore_uploads( $source_url, $source_token );
				break;
			default:
				return new \WP_Error(
					'invalid_step',
					__( 'Invalid step type.', 'wp-site-bridge-migration' ),
					array( 'status' => 400 )
				);
		}
		
		if ( false === $result ) {
			// Update status to error
			$status['status'] = 'error';
			$status['error'] = sprintf(
				/* translators: %s: Step name */
				__( 'Failed to process %s step.', 'wp-site-bridge-migration' ),
				$step
			);
			update_option( 'wpsbm_migration_status', $status );
			
			return new \WP_Error(
				'process_failed',
				$status['error'],
				array( 'status' => 500 )
			);
		}
		
		// Update status to completed for this step
		$status['completed_steps'][] = $step;
		$status['status'] = 'completed';
		$status['last_update'] = time();
		update_option( 'wpsbm_migration_status', $status );
		
		// Success response
		return new \WP_REST_Response(
			array(
				'success' => true,
				'step'    => $step,
				'message' => sprintf(
					/* translators: %s: Step name */
					__( '%s restored successfully.', 'wp-site-bridge-migration' ),
					ucfirst( $step )
				),
			),
			200
		);
	}
	
	/**
	 * Handle migration status request
	 *
	 * Returns current migration status for destination site monitoring
	 * This endpoint is lightweight - only reads from wp_options (cached)
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response Response object
	 */
	public function handle_migration_status( $request ) {
		// Use get_option with autoload=true (cached in memory, no DB query after first load)
		$status = get_option( 'wpsbm_migration_status', array() );
		
		// Default status if not set
		if ( empty( $status ) ) {
			$status = array(
				'status' => 'idle',
				'current_step' => null,
				'completed_steps' => array(),
				'last_update' => null,
				'source_url' => null,
			);
		}
		
		// Return minimal response (lightweight)
		return new \WP_REST_Response( $status, 200 );
	}
	
	/**
	 * Permission callback for finalize_migration endpoint
	 *
	 * Validates token before allowing finalization
	 *
	 * @param WP_REST_Request $request Request object
	 * @return bool|WP_Error True if token is valid, WP_Error otherwise
	 */
	public function finalize_migration_permission_check( $request ) {
		// Get token from request
		$token = $request->get_param( 'token' );
		
		if ( empty( $token ) ) {
			return new \WP_Error(
				'missing_token',
				__( 'Token is required.', 'wp-site-bridge-migration' ),
				array( 'status' => 400 )
			);
		}
		
		// Verify token using Migrator class
		$migrator = Migrator::get_instance();
		$is_valid = $migrator->verify_token( $token );
		
		if ( ! $is_valid ) {
			return new \WP_Error(
				'invalid_token',
				__( 'Invalid token.', 'wp-site-bridge-migration' ),
				array( 'status' => 403 )
			);
		}
		
		// Token is valid, allow access
		return true;
	}
	
	/**
	 * Handle finalize_migration request
	 *
	 * Performs search & replace and flushes rewrite rules
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response|WP_Error Response object
	 */
	public function handle_finalize_migration( $request ) {
		// If we reach here, the token has been validated by permission_callback
		
		// Get parameters
		$old_url = $request->get_param( 'old_url' );
		$new_url = get_site_url();
		
		// Increase time limit and memory limit
		@set_time_limit( 600 ); // 10 minutes
		@ini_set( 'memory_limit', '512M' );
		
		// Get migrator instance
		$migrator = Migrator::get_instance();
		
		// Run search and replace
		$result = $migrator->run_search_replace( $old_url, $new_url );
		
		if ( false === $result ) {
			return new \WP_Error(
				'search_replace_failed',
				__( 'Search and replace operation failed.', 'wp-site-bridge-migration' ),
				array( 'status' => 500 )
			);
		}
		
		// Flush rewrite rules to fix permalinks
		flush_rewrite_rules( false );
		
		// Update site URL in options (if not already updated)
		update_option( 'siteurl', $new_url );
		update_option( 'home', $new_url );
		
		// Success response
		return new \WP_REST_Response(
			array(
				'success' => true,
				'old_url' => $old_url,
				'new_url' => $new_url,
				'message' => sprintf(
					/* translators: %1$s: Old URL, %2$s: New URL */
					__( 'Migration finalized successfully. Replaced %1$s with %2$s.', 'wp-site-bridge-migration' ),
					$old_url,
					$new_url
				),
			),
			200
		);
	}
	
	/**
	 * Handle finalize_migration_batch request
	 *
	 * Performs search & replace in batches to avoid timeout
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response|WP_Error Response object
	 */
	public function handle_finalize_migration_batch( $request ) {
		// If we reach here, the token has been validated by permission_callback
		
		// Get parameters
		$old_url = $request->get_param( 'old_url' );
		$new_url = get_site_url();
		$table_name = $request->get_param( 'table_name' );
		$offset = (int) $request->get_param( 'offset' );
		
		// Increase time limit and memory limit for this batch
		@set_time_limit( 30 ); // 30 seconds per batch
		@ini_set( 'memory_limit', '256M' );
		
		// Get migrator instance
		$migrator = Migrator::get_instance();
		
		// Run batch search and replace
		$result = $migrator->run_search_replace_batch( $old_url, $new_url, $table_name, $offset );
		
		if ( isset( $result['error'] ) ) {
			return new \WP_Error(
				'search_replace_failed',
				$result['error'],
				array( 'status' => 500 )
			);
		}
		
		// If completed, flush rewrite rules and update options
		if ( isset( $result['completed'] ) && $result['completed'] ) {
			// Flush rewrite rules to fix permalinks
			flush_rewrite_rules( false );
			
			// Update site URL in options
			update_option( 'siteurl', $new_url );
			update_option( 'home', $new_url );
		}
		
		// Return status for next batch or completion
		return new \WP_REST_Response( $result, 200 );
	}
	
	/**
	 * Permission callback for cleanup endpoint
	 *
	 * Validates token before allowing cleanup
	 *
	 * @param WP_REST_Request $request Request object
	 * @return bool|WP_Error True if token is valid, WP_Error otherwise
	 */
	public function cleanup_permission_check( $request ) {
		// Get token from request
		$token = $request->get_param( 'token' );
		
		if ( empty( $token ) ) {
			return new \WP_Error(
				'missing_token',
				__( 'Token is required.', 'wp-site-bridge-migration' ),
				array( 'status' => 400 )
			);
		}
		
		// Verify token using Migrator class
		$migrator = Migrator::get_instance();
		$is_valid = $migrator->verify_token( $token );
		
		if ( ! $is_valid ) {
			return new \WP_Error(
				'invalid_token',
				__( 'Invalid token.', 'wp-site-bridge-migration' ),
				array( 'status' => 403 )
			);
		}
		
		// Token is valid, allow access
		return true;
	}
	
	/**
	 * Handle cleanup request
	 *
	 * Deletes temporary files and directories
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response|WP_Error Response object
	 */
	public function handle_cleanup( $request ) {
		// If we reach here, the token has been validated by permission_callback
		
		// Get migrator instance
		$migrator = Migrator::get_instance();
		
		// Run cleanup
		$result = $migrator->cleanup_temp_files();
		
		if ( false === $result ) {
			return new \WP_Error(
				'cleanup_failed',
				__( 'Cleanup operation failed. Some files may still exist.', 'wp-site-bridge-migration' ),
				array( 'status' => 500 )
			);
		}
		
		// Success response
		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Temporary files cleaned up successfully.', 'wp-site-bridge-migration' ),
			),
			200
		);
	}
}

