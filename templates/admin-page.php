<?php
/**
 * Admin page template
 *
 * @package WPSiteBridge
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @var string $site_role Current site role (source or destination)
 * @var bool   $show_notice Whether to show success notice
 */
// Check if we should show success notice (from redirect after save)
$show_notice = isset( $_GET['wpsbm_updated'] ) && '1' === $_GET['wpsbm_updated'];
?>
<div class="wrap wpsbm-wrap">
	<h1 class="wp-heading-inline"><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<hr class="wp-header-end">
	
	<?php if ( $show_notice ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Site role saved successfully.', 'wp-site-bridge-migration' ); ?></p>
		</div>
	<?php endif; ?>
	
	<div class="wpsbm-admin-container">
		<!-- Site Role Switcher -->
		<div class="wpsbm-card">
			<h2 class="wpsbm-card-title"><?php esc_html_e( 'This Site Is', 'wp-site-bridge-migration' ); ?></h2>
			<p class="wpsbm-description">
				<?php esc_html_e( 'Select the role of this WordPress site in the migration process.', 'wp-site-bridge-migration' ); ?>
			</p>
			
			<form method="post" action="<?php echo esc_url( admin_url( 'tools.php?page=wp-site-bridge-migration' ) ); ?>" class="wpsbm-role-form">
				<?php wp_nonce_field( 'wpsbm_save_site_role', 'wpsbm_site_role_nonce' ); ?>
				
				<div class="wpsbm-radio-group">
					<label class="wpsbm-radio-label <?php echo 'source' === $site_role ? 'wpsbm-radio-active' : ''; ?>">
						<input 
							type="radio" 
							name="wpsbm_site_role" 
							value="source" 
							<?php checked( $site_role, 'source' ); ?>
							class="wpsbm-role-radio"
						>
						<span class="wpsbm-radio-content">
							<strong><?php esc_html_e( 'Source Website', 'wp-site-bridge-migration' ); ?></strong>
							<span class="wpsbm-radio-desc"><?php esc_html_e( 'The site sending data (where you are migrating FROM)', 'wp-site-bridge-migration' ); ?></span>
						</span>
					</label>
					
					<label class="wpsbm-radio-label <?php echo 'destination' === $site_role ? 'wpsbm-radio-active' : ''; ?>">
						<input 
							type="radio" 
							name="wpsbm_site_role" 
							value="destination" 
							<?php checked( $site_role, 'destination' ); ?>
							class="wpsbm-role-radio"
						>
						<span class="wpsbm-radio-content">
							<strong><?php esc_html_e( 'Destination Website', 'wp-site-bridge-migration' ); ?></strong>
							<span class="wpsbm-radio-desc"><?php esc_html_e( 'The fresh site receiving data (where you are migrating TO)', 'wp-site-bridge-migration' ); ?></span>
						</span>
					</label>
				</div>
				
				<button type="submit" name="wpsbm_save_site_role" class="wpsbm-button wpsbm-button-primary">
					<?php esc_html_e( 'Save Role', 'wp-site-bridge-migration' ); ?>
				</button>
			</form>
		</div>
		
		<!-- Tab Navigation -->
		<div class="wpsbm-card wpsbm-tabs-container">
			<nav class="wpsbm-tabs-nav">
				<button type="button" class="wpsbm-tab-button <?php echo 'source' === $site_role ? 'wpsbm-tab-active' : ''; ?>" data-tab="source">
					<?php esc_html_e( 'Source Site', 'wp-site-bridge-migration' ); ?>
				</button>
				<button type="button" class="wpsbm-tab-button <?php echo 'destination' === $site_role ? 'wpsbm-tab-active' : ''; ?>" data-tab="destination">
					<?php esc_html_e( 'Destination Site', 'wp-site-bridge-migration' ); ?>
				</button>
				<button type="button" class="wpsbm-tab-button" data-tab="help">
					<?php esc_html_e( 'Help & Guide', 'wp-site-bridge-migration' ); ?>
				</button>
			</nav>
		</div>
		
		<!-- Dynamic Content Area -->
		<div class="wpsbm-card wpsbm-dynamic-content">
			<!-- Destination Website UI -->
			<div id="wpsbm-destination-ui" class="wpsbm-role-ui wpsbm-tab-content <?php echo 'destination' === $site_role ? 'wpsbm-tab-content-active' : ''; ?>" data-tab="destination">
				<h2 class="wpsbm-card-title"><?php esc_html_e( 'Destination Site Configuration', 'wp-site-bridge-migration' ); ?></h2>
				<p class="wpsbm-description">
					<?php esc_html_e( 'Generate a migration key to share with the source website. This key contains the site URL and a secure token for authentication.', 'wp-site-bridge-migration' ); ?>
				</p>
				
				<div class="wpsbm-form-group">
					<label for="wpsbm-secret-key" class="wpsbm-label">
						<?php esc_html_e( 'Migration Key', 'wp-site-bridge-migration' ); ?>
					</label>
					<textarea 
						id="wpsbm-secret-key" 
						name="wpsbm_secret_key" 
						class="wpsbm-textarea wpsbm-secret-key-display" 
						readonly 
						rows="4"
						placeholder="<?php esc_attr_e( 'Click "Generate Migration Key" to create a new key', 'wp-site-bridge-migration' ); ?>"
					><?php echo esc_textarea( get_option( 'wpsbm_secret_key', '' ) ); ?></textarea>
					<p class="description">
						<?php esc_html_e( 'Copy this migration key and share it with the source website administrator. The key contains both the destination URL and authentication token.', 'wp-site-bridge-migration' ); ?>
					</p>
				</div>
				
				<div class="wpsbm-button-group">
					<button type="button" id="wpsbm-generate-key" class="wpsbm-button wpsbm-button-primary">
						<?php esc_html_e( 'Generate Migration Key', 'wp-site-bridge-migration' ); ?>
					</button>
					<button type="button" id="wpsbm-copy-key" class="wpsbm-button wpsbm-button-secondary" style="display: none;">
						<?php esc_html_e( 'Copy Key', 'wp-site-bridge-migration' ); ?>
					</button>
				</div>
				
				<!-- Status message container for destination -->
				<div id="wpsbm-destination-status" class="wpsbm-status-message" style="display: none; margin-top: 15px;"></div>
				
				<!-- Migration Status Section (Destination Site) -->
				<div id="wpsbm-destination-migration-status" class="wpsbm-migration-status-section" style="margin-top: 30px;">
					<h2 class="wpsbm-card-title"><?php esc_html_e( 'Migration Status', 'wp-site-bridge-migration' ); ?></h2>
					<p class="wpsbm-description">
						<?php esc_html_e( 'Monitor the migration progress in real-time. This section will automatically update as data is being restored.', 'wp-site-bridge-migration' ); ?>
					</p>
					
					<div id="wpsbm-destination-progress" class="wpsbm-progress-list" style="margin-top: 20px;">
						<div class="wpsbm-progress-item" data-step="destination-database">
							<span class="wpsbm-progress-icon">○</span>
							<span class="wpsbm-progress-text"><?php esc_html_e( 'Waiting for database restoration...', 'wp-site-bridge-migration' ); ?></span>
						</div>
						<div class="wpsbm-progress-item" data-step="destination-plugins">
							<span class="wpsbm-progress-icon">○</span>
							<span class="wpsbm-progress-text"><?php esc_html_e( 'Waiting for plugins restoration...', 'wp-site-bridge-migration' ); ?></span>
						</div>
						<div class="wpsbm-progress-item" data-step="destination-themes">
							<span class="wpsbm-progress-icon">○</span>
							<span class="wpsbm-progress-text"><?php esc_html_e( 'Waiting for themes restoration...', 'wp-site-bridge-migration' ); ?></span>
						</div>
						<div class="wpsbm-progress-item" data-step="destination-uploads">
							<span class="wpsbm-progress-icon">○</span>
							<span class="wpsbm-progress-text"><?php esc_html_e( 'Waiting for uploads restoration...', 'wp-site-bridge-migration' ); ?></span>
						</div>
						<div class="wpsbm-progress-item" data-step="destination-finalize">
							<span class="wpsbm-progress-icon">○</span>
							<span class="wpsbm-progress-text"><?php esc_html_e( 'Waiting for search & replace...', 'wp-site-bridge-migration' ); ?></span>
						</div>
					</div>
					
					<div id="wpsbm-destination-status-message" class="wpsbm-status-message" style="display: none; margin-top: 15px;"></div>
					
					<div class="wpsbm-button-group" style="margin-top: 15px;">
						<button type="button" id="wpsbm-refresh-status" class="wpsbm-button wpsbm-button-secondary">
							<?php esc_html_e( 'Refresh Status', 'wp-site-bridge-migration' ); ?>
						</button>
					</div>
				</div>
			</div>
			
			<!-- Source Website UI -->
			<div id="wpsbm-source-ui" class="wpsbm-role-ui wpsbm-tab-content <?php echo 'source' === $site_role ? 'wpsbm-tab-content-active' : ''; ?>" data-tab="source">
				<h2 class="wpsbm-card-title"><?php esc_html_e( 'Source Site Configuration', 'wp-site-bridge-migration' ); ?></h2>
				<p class="wpsbm-description">
					<?php esc_html_e( 'Enter the migration key provided by the destination website to establish a secure connection. The key contains the destination URL and authentication token.', 'wp-site-bridge-migration' ); ?>
				</p>
				
				<?php
				$connection_status = get_option( 'wpsbm_connection_status', '' );
				$is_connected = ( 'connected' === $connection_status );
				$destination_info = get_option( 'wpsbm_destination_info', array() );
				$connected_site_name = isset( $destination_info['site_name'] ) ? $destination_info['site_name'] : '';
				?>
				<form id="wpsbm-connect-form" class="wpsbm-form <?php echo $is_connected ? 'wpsbm-connected-form' : ''; ?>" data-connected="<?php echo $is_connected ? 'true' : 'false'; ?>">
					<div class="wpsbm-form-group">
						<label for="wpsbm-remote-secret-key" class="wpsbm-label">
							<?php esc_html_e( 'Migration Key', 'wp-site-bridge-migration' ); ?>
						</label>
						<textarea 
							id="wpsbm-remote-secret-key" 
							name="wpsbm_remote_secret_key" 
							class="wpsbm-textarea wpsbm-input-large <?php echo $is_connected ? 'wpsbm-locked' : ''; ?>" 
							rows="3"
							placeholder="<?php esc_attr_e( 'Paste the migration key from the destination website', 'wp-site-bridge-migration' ); ?>"
							<?php echo $is_connected ? 'disabled' : ''; ?>
						><?php echo esc_textarea( get_option( 'wpsbm_remote_secret_key', '' ) ); ?></textarea>
						<p class="description">
							<?php esc_html_e( 'Paste the migration key you received from the destination website. The key contains both the URL and authentication token.', 'wp-site-bridge-migration' ); ?>
						</p>
					</div>
					
					<div class="wpsbm-form-group">
						<label for="wpsbm-destination-url" class="wpsbm-label">
							<?php esc_html_e( 'Destination Website URL (Optional)', 'wp-site-bridge-migration' ); ?>
						</label>
						<input 
							type="url" 
							id="wpsbm-destination-url" 
							name="wpsbm_destination_url" 
							class="wpsbm-input wpsbm-input-large <?php echo $is_connected ? 'wpsbm-locked' : ''; ?>" 
							placeholder="<?php esc_attr_e( 'https://destination-site.com (optional - URL is in the migration key)', 'wp-site-bridge-migration' ); ?>"
							value="<?php echo esc_attr( get_option( 'wpsbm_destination_url', '' ) ); ?>"
							<?php echo $is_connected ? 'disabled' : ''; ?>
						>
						<p class="description">
							<?php esc_html_e( 'Optional: Override the destination URL. If not provided, the URL from the migration key will be used.', 'wp-site-bridge-migration' ); ?>
							<br>
							<strong><?php esc_html_e( 'Docker Users:', 'wp-site-bridge-migration' ); ?></strong>
							<?php esc_html_e( 'If both sites are in Docker containers on the same network, use the container name (e.g., http://wp-wordpress-blank - recommended, works from inside containers). Alternatively, use http://host.docker.internal:8094/ (Windows/Mac) or http://172.17.0.1:8094/ (Linux).', 'wp-site-bridge-migration' ); ?>
							<br>
							<small style="color: #666;">
								<?php esc_html_e( 'Note: Container names only work from inside Docker containers, not from your browser on the host machine. To test from browser, use http://localhost:8094/', 'wp-site-bridge-migration' ); ?>
							</small>
						</p>
					</div>
					
					<div class="wpsbm-button-group">
						<button type="submit" id="wpsbm-connect-validate" class="wpsbm-button wpsbm-button-primary <?php echo $is_connected ? 'wpsbm-connected' : ''; ?>" <?php echo $is_connected ? 'disabled' : ''; ?>>
							<?php if ( $is_connected ) : ?>
								<?php esc_html_e( 'Connected', 'wp-site-bridge-migration' ); ?>
							<?php else : ?>
								<?php esc_html_e( 'Connect & Validate', 'wp-site-bridge-migration' ); ?>
							<?php endif; ?>
						</button>
					</div>
				</form>
				
				<div id="wpsbm-connection-status" class="wpsbm-status-message <?php echo $is_connected ? 'wpsbm-status-success' : ''; ?>" style="<?php echo $is_connected ? '' : 'display: none;'; ?>">
					<?php if ( $is_connected && $connected_site_name ) : ?>
						<?php
						printf(
							/* translators: %s: Site name */
							esc_html__( 'Connected to %s!', 'wp-site-bridge-migration' ),
							esc_html( $connected_site_name )
						);
						?>
					<?php endif; ?>
				</div>
				
				<?php if ( $is_connected ) : ?>
					<!-- Migration Section -->
					<div id="wpsbm-migration-section" class="wpsbm-migration-section" style="margin-top: 30px;">
						<h2 class="wpsbm-card-title"><?php esc_html_e( 'Start Migration', 'wp-site-bridge-migration' ); ?></h2>
						
						<?php
						$destination_url = get_option( 'wpsbm_destination_url', '' );
						$destination_info = get_option( 'wpsbm_destination_info', array() );
						$destination_site_name = isset( $destination_info['site_name'] ) ? $destination_info['site_name'] : $destination_url;
						?>
						
						<div class="wpsbm-alert wpsbm-alert-info" style="background: #f0f6fc; border-left: 4px solid #2271b1; padding: 16px; margin-bottom: 20px; border-radius: 4px;">
							<strong style="display: block; margin-bottom: 8px; color: #1d2327;"><?php esc_html_e( 'Migration Target:', 'wp-site-bridge-migration' ); ?></strong>
							<p style="margin: 0; color: #1d2327; line-height: 1.6;">
								<?php
								printf(
									/* translators: %1$s: Site name, %2$s: Destination URL */
									esc_html__( 'Migrating to: %1$s (%2$s)', 'wp-site-bridge-migration' ),
									'<strong>' . esc_html( $destination_site_name ) . '</strong>',
									'<code style="background: #fff; padding: 2px 6px; border-radius: 3px;">' . esc_html( $destination_url ) . '</code>'
								);
								?>
							</p>
						</div>
						
						<p class="wpsbm-description">
							<?php esc_html_e( 'Click the button below to start backing up your site data and migrating it to the destination site. This process may take several minutes depending on your site size.', 'wp-site-bridge-migration' ); ?>
						</p>
						
						<div class="wpsbm-button-group" style="margin-bottom: 20px;">
							<button type="button" id="wpsbm-start-migration" class="wpsbm-button wpsbm-button-primary">
								<?php esc_html_e( 'Start Migration', 'wp-site-bridge-migration' ); ?>
							</button>
						</div>
						
						<!-- Progress List -->
						<div id="wpsbm-migration-progress" class="wpsbm-progress-list" style="display: none;">
							<div class="wpsbm-progress-item" data-step="database">
								<span class="wpsbm-progress-icon">○</span>
								<span class="wpsbm-progress-text"><?php esc_html_e( 'Exporting Database...', 'wp-site-bridge-migration' ); ?></span>
								<span class="wpsbm-progress-size"></span>
							</div>
							<div class="wpsbm-progress-item" data-step="plugins">
								<span class="wpsbm-progress-icon">○</span>
								<span class="wpsbm-progress-text"><?php esc_html_e( 'Zipping Plugins...', 'wp-site-bridge-migration' ); ?></span>
								<span class="wpsbm-progress-size"></span>
							</div>
							<div class="wpsbm-progress-item" data-step="themes">
								<span class="wpsbm-progress-icon">○</span>
								<span class="wpsbm-progress-text"><?php esc_html_e( 'Zipping Themes...', 'wp-site-bridge-migration' ); ?></span>
								<span class="wpsbm-progress-size"></span>
							</div>
							<div class="wpsbm-progress-item" data-step="uploads">
								<span class="wpsbm-progress-icon">○</span>
								<span class="wpsbm-progress-text"><?php esc_html_e( 'Zipping Uploads...', 'wp-site-bridge-migration' ); ?></span>
								<span class="wpsbm-progress-size"></span>
							</div>
						</div>
						
						<!-- Remote Restoration Progress -->
						<div id="wpsbm-remote-restore-progress" class="wpsbm-progress-list" style="display: none; margin-top: 30px;">
							<h3 style="margin-bottom: 15px; font-size: 16px; color: #1d2327;">
								<?php esc_html_e( 'Remote Restoration', 'wp-site-bridge-migration' ); ?>
								<span style="font-size: 13px; font-weight: normal; color: #646970; margin-left: 10px;">
									→ <?php echo esc_html( $destination_url ); ?>
								</span>
							</h3>
							<div class="wpsbm-progress-item" data-step="remote-database">
								<span class="wpsbm-progress-icon">○</span>
								<span class="wpsbm-progress-text"><?php esc_html_e( 'Restoring Database on remote site...', 'wp-site-bridge-migration' ); ?></span>
							</div>
							<div class="wpsbm-progress-item" data-step="remote-plugins">
								<span class="wpsbm-progress-icon">○</span>
								<span class="wpsbm-progress-text"><?php esc_html_e( 'Restoring Plugins...', 'wp-site-bridge-migration' ); ?></span>
							</div>
							<div class="wpsbm-progress-item" data-step="remote-themes">
								<span class="wpsbm-progress-icon">○</span>
								<span class="wpsbm-progress-text"><?php esc_html_e( 'Restoring Themes...', 'wp-site-bridge-migration' ); ?></span>
							</div>
							<div class="wpsbm-progress-item" data-step="remote-uploads">
								<span class="wpsbm-progress-icon">○</span>
								<span class="wpsbm-progress-text"><?php esc_html_e( 'Restoring Uploads...', 'wp-site-bridge-migration' ); ?></span>
							</div>
						</div>
						
						<div id="wpsbm-migration-status" class="wpsbm-status-message" style="display: none; margin-top: 20px;"></div>
					</div>
				<?php endif; ?>
			</div>
			
			<!-- Help & Guide UI -->
			<div id="wpsbm-help-ui" class="wpsbm-help-container wpsbm-tab-content" data-tab="help">
				<h3 style="font-size: 24px; margin-bottom: 20px; color: #1d2327;">🚀 <?php esc_html_e( 'User Guide: How to Migrate', 'wp-site-bridge-migration' ); ?></h3>
				
				<div class="wpsbm-alert wpsbm-alert-warning" style="background: #fcf9e8; border-left: 4px solid #dba617; padding: 16px; margin-bottom: 24px; border-radius: 4px;">
					<strong style="display: block; margin-bottom: 12px; color: #1d2327; font-size: 16px;"><?php esc_html_e( 'Important:', 'wp-site-bridge-migration' ); ?></strong>
					<ul style="list-style-type: disc; margin-left: 24px; margin-top: 8px; color: #1d2327; line-height: 1.8;">
						<li style="margin-bottom: 8px;">
							<strong><?php esc_html_e( 'Destination Site (Receiver):', 'wp-site-bridge-migration' ); ?></strong> 
							<?php esc_html_e( 'All existing data will be overwritten. Ensure this is a fresh install or you have a backup.', 'wp-site-bridge-migration' ); ?>
						</li>
						<li>
							<strong><?php esc_html_e( 'Source Site (Sender):', 'wp-site-bridge-migration' ); ?></strong> 
							<?php esc_html_e( 'Safe. The plugin only reads data, it does not delete anything here.', 'wp-site-bridge-migration' ); ?>
						</li>
					</ul>
				</div>
				
				<div class="wpsbm-guide-section" style="margin-bottom: 32px;">
					<h4 style="font-size: 18px; font-weight: 600; margin-bottom: 12px; color: #1d2327; padding-bottom: 8px; border-bottom: 2px solid #dcdcde;">
						<?php esc_html_e( 'Step 1: Preparation', 'wp-site-bridge-migration' ); ?>
					</h4>
					<p style="color: #646970; line-height: 1.7; font-size: 14px;">
						<?php esc_html_e( 'Install and activate', 'wp-site-bridge-migration' ); ?> <strong><?php esc_html_e( 'WP Site Bridge Migration', 'wp-site-bridge-migration' ); ?></strong> <?php esc_html_e( 'on BOTH websites.', 'wp-site-bridge-migration' ); ?>
					</p>
				</div>
				
				<div class="wpsbm-guide-section" style="margin-bottom: 32px;">
					<h4 style="font-size: 18px; font-weight: 600; margin-bottom: 12px; color: #1d2327; padding-bottom: 8px; border-bottom: 2px solid #dcdcde;">
						<?php esc_html_e( 'Step 2: Get the "Secret Key" (On Destination Site)', 'wp-site-bridge-migration' ); ?>
					</h4>
					<ol style="margin-left: 20px; color: #646970; line-height: 1.8; font-size: 14px;">
						<li style="margin-bottom: 8px;"><?php esc_html_e( 'Go to the', 'wp-site-bridge-migration' ); ?> <strong><?php esc_html_e( 'Destination Site', 'wp-site-bridge-migration' ); ?></strong> <?php esc_html_e( 'dashboard.', 'wp-site-bridge-migration' ); ?></li>
						<li style="margin-bottom: 8px;"><?php esc_html_e( 'Open', 'wp-site-bridge-migration' ); ?> <strong><?php esc_html_e( '"Site Migration"', 'wp-site-bridge-migration' ); ?></strong> <?php esc_html_e( 'menu.', 'wp-site-bridge-migration' ); ?></li>
						<li style="margin-bottom: 8px;"><?php esc_html_e( 'Select the', 'wp-site-bridge-migration' ); ?> <strong><?php esc_html_e( 'Destination Site', 'wp-site-bridge-migration' ); ?></strong> <?php esc_html_e( 'tab.', 'wp-site-bridge-migration' ); ?></li>
						<li style="margin-bottom: 8px;"><?php esc_html_e( 'Click', 'wp-site-bridge-migration' ); ?> <strong><?php esc_html_e( 'Generate Migration Key', 'wp-site-bridge-migration' ); ?></strong>.</li>
						<li><?php esc_html_e( 'Copy the long code that appears.', 'wp-site-bridge-migration' ); ?></li>
					</ol>
				</div>
				
				<div class="wpsbm-guide-section" style="margin-bottom: 32px;">
					<h4 style="font-size: 18px; font-weight: 600; margin-bottom: 12px; color: #1d2327; padding-bottom: 8px; border-bottom: 2px solid #dcdcde;">
						<?php esc_html_e( 'Step 3: Connect & Start (On Source Site)', 'wp-site-bridge-migration' ); ?>
					</h4>
					<ol style="margin-left: 20px; color: #646970; line-height: 1.8; font-size: 14px;">
						<li style="margin-bottom: 8px;"><?php esc_html_e( 'Go to the', 'wp-site-bridge-migration' ); ?> <strong><?php esc_html_e( 'Source Site', 'wp-site-bridge-migration' ); ?></strong> <?php esc_html_e( 'dashboard.', 'wp-site-bridge-migration' ); ?></li>
						<li style="margin-bottom: 8px;"><?php esc_html_e( 'Open', 'wp-site-bridge-migration' ); ?> <strong><?php esc_html_e( '"Site Migration"', 'wp-site-bridge-migration' ); ?></strong> <?php esc_html_e( 'menu.', 'wp-site-bridge-migration' ); ?></li>
						<li style="margin-bottom: 8px;"><?php esc_html_e( 'Select the', 'wp-site-bridge-migration' ); ?> <strong><?php esc_html_e( 'Source Site', 'wp-site-bridge-migration' ); ?></strong> <?php esc_html_e( 'tab.', 'wp-site-bridge-migration' ); ?></li>
						<li style="margin-bottom: 8px;"><?php esc_html_e( 'Paste the key into the', 'wp-site-bridge-migration' ); ?> <strong><?php esc_html_e( '"Enter Remote Secret Key"', 'wp-site-bridge-migration' ); ?></strong> <?php esc_html_e( 'box.', 'wp-site-bridge-migration' ); ?></li>
						<li style="margin-bottom: 8px;"><?php esc_html_e( 'Click', 'wp-site-bridge-migration' ); ?> <strong><?php esc_html_e( 'Connect & Validate', 'wp-site-bridge-migration' ); ?></strong>.</li>
						<li><?php esc_html_e( 'Once connected (Green message), click', 'wp-site-bridge-migration' ); ?> <strong><?php esc_html_e( 'Start Migration', 'wp-site-bridge-migration' ); ?></strong>.</li>
					</ol>
				</div>
				
				<div class="wpsbm-guide-section" style="margin-bottom: 32px;">
					<h4 style="font-size: 18px; font-weight: 600; margin-bottom: 12px; color: #1d2327; padding-bottom: 8px; border-bottom: 2px solid #dcdcde;">
						<?php esc_html_e( 'Step 4: Sit Back & Relax', 'wp-site-bridge-migration' ); ?>
					</h4>
					<p style="color: #646970; line-height: 1.7; font-size: 14px;">
						<?php esc_html_e( 'The plugin will automatically Backup -> Transfer -> Restore -> Search & Replace Database. Do not close the tab until you see the "Migration Successful" message.', 'wp-site-bridge-migration' ); ?>
					</p>
				</div>
				
				<hr style="border: none; border-top: 1px solid #dcdcde; margin: 32px 0;">
				
				<div class="wpsbm-guide-section" style="margin-bottom: 32px;">
					<h4 style="font-size: 18px; font-weight: 600; margin-bottom: 12px; color: #1d2327; padding-bottom: 8px; border-bottom: 2px solid #dcdcde;">
						✅ <?php esc_html_e( 'Final Steps', 'wp-site-bridge-migration' ); ?>
					</h4>
					<p style="color: #646970; line-height: 1.7; font-size: 14px;">
						<?php esc_html_e( 'After migration, log in to the Destination site using the', 'wp-site-bridge-migration' ); ?> <strong><?php esc_html_e( 'Admin Username & Password from the SOURCE site', 'wp-site-bridge-migration' ); ?></strong>. 
						<?php esc_html_e( 'Go to Settings > Permalinks and click "Save Changes" to flush rewrite rules.', 'wp-site-bridge-migration' ); ?>
					</p>
				</div>
				
				<div class="wpsbm-guide-section" style="margin-bottom: 32px;">
					<h4 style="font-size: 18px; font-weight: 600; margin-bottom: 12px; color: #1d2327; padding-bottom: 8px; border-bottom: 2px solid #dcdcde;">
						❓ <?php esc_html_e( 'Troubleshooting', 'wp-site-bridge-migration' ); ?>
					</h4>
					
					<div style="color: #646970; line-height: 1.8; font-size: 14px;">
						<p style="margin-bottom: 16px;">
							<strong style="color: #1d2327;">Q: <?php esc_html_e( 'The process stopped or shows a red error?', 'wp-site-bridge-migration' ); ?></strong><br>
							<?php esc_html_e( 'A:', 'wp-site-bridge-migration' ); ?> <?php esc_html_e( 'Don\'t worry. Simply refresh the page (F5) on Site A and try again. The plugin will automatically clean up temporary files and restart.', 'wp-site-bridge-migration' ); ?>
						</p>
						
						<p style="margin-bottom: 16px;">
							<strong style="color: #1d2327;">Q: <?php esc_html_e( 'After migration, I get 404 errors when accessing posts?', 'wp-site-bridge-migration' ); ?></strong><br>
							<?php esc_html_e( 'A:', 'wp-site-bridge-migration' ); ?> <?php esc_html_e( 'Go to Dashboard → Settings → Permalinks → Click "Save Changes". This will reset the permalink structure.', 'wp-site-bridge-migration' ); ?>
						</p>
						
						<p style="margin-bottom: 16px;">
							<strong style="color: #1d2327;">Q: <?php esc_html_e( 'The new site has broken layout/design?', 'wp-site-bridge-migration' ); ?></strong><br>
							<?php esc_html_e( 'A:', 'wp-site-bridge-migration' ); ?> <?php esc_html_e( 'The "Search & Replace" process may not have completed. Try running the migration again or contact technical support.', 'wp-site-bridge-migration' ); ?>
						</p>
					</div>
				</div>
				
				<hr style="border: none; border-top: 1px solid #dcdcde; margin: 32px 0;">
				
				<div class="wpsbm-guide-section" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 24px; border-radius: 8px; color: white; margin-bottom: 32px;">
					<h4 style="font-size: 18px; font-weight: 600; margin-bottom: 12px; color: white; padding-bottom: 8px; border-bottom: 2px solid rgba(255,255,255,0.3);">
						💝 <?php esc_html_e( 'Support & Donate', 'wp-site-bridge-migration' ); ?>
					</h4>
					<p style="color: rgba(255,255,255,0.95); line-height: 1.8; font-size: 14px; margin-bottom: 16px;">
						<?php esc_html_e( 'This plugin is developed with ❤️ for the WordPress community. If you find it useful, please consider supporting the development:', 'wp-site-bridge-migration' ); ?>
					</p>
					<div style="background: rgba(255,255,255,0.15); padding: 16px; border-radius: 6px; margin-bottom: 16px;">
						<p style="margin: 0 0 8px 0; font-weight: 600; color: white;">
							<?php esc_html_e( 'USDT Donation (BNB Smartchain):', 'wp-site-bridge-migration' ); ?>
						</p>
						<code style="background: rgba(0,0,0,0.2); padding: 8px 12px; border-radius: 4px; display: block; word-break: break-all; color: #fff; font-size: 13px;">
							0xeC6CfB0eE72d8104F6c17eB0163b84c3b6E9ad33
						</code>
					</div>
					<p style="color: rgba(255,255,255,0.9); line-height: 1.7; font-size: 13px; margin: 0;">
						<?php esc_html_e( 'Your support helps improve the plugin and add new features. You can also request features or provide feedback when donating!', 'wp-site-bridge-migration' ); ?>
					</p>
				</div>
				
				<div class="wpsbm-guide-section" style="text-align: center; padding: 20px; background: #f6f7f7; border-radius: 8px; margin-bottom: 0;">
					<p style="margin: 0; color: #646970; font-size: 14px;">
						<?php esc_html_e( 'Plugin by', 'wp-site-bridge-migration' ); ?> 
						<a href="https://www.facebook.com/pqbao1987" target="_blank" style="color: #2271b1; text-decoration: none; font-weight: 600;">@pqbao1987</a> 
						| 
						<a href="https://github.com/baopq6/WP-Site-Bridge-Migration" target="_blank" style="color: #2271b1; text-decoration: none; font-weight: 600;"><?php esc_html_e( 'Visit Plugin GitHub', 'wp-site-bridge-migration' ); ?></a>
					</p>
				</div>
			</div>
		</div>
	</div>
</div>
