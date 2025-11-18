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
$show_notice = isset( $show_notice ) ? $show_notice : false;
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
			
			<form method="post" action="" class="wpsbm-role-form">
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
		
		<!-- Dynamic Content Area -->
		<div class="wpsbm-card wpsbm-dynamic-content">
			<?php if ( 'destination' === $site_role ) : ?>
				<!-- Destination Website UI -->
				<div id="wpsbm-destination-ui" class="wpsbm-role-ui">
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
				</div>
			<?php else : ?>
				<!-- Source Website UI -->
				<div id="wpsbm-source-ui" class="wpsbm-role-ui">
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
							<p class="wpsbm-description">
								<?php esc_html_e( 'Click the button below to start backing up your site data. This process may take several minutes depending on your site size.', 'wp-site-bridge-migration' ); ?>
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
								<h3 style="margin-bottom: 15px; font-size: 16px; color: #1d2327;"><?php esc_html_e( 'Remote Restoration', 'wp-site-bridge-migration' ); ?></h3>
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
			<?php endif; ?>
		</div>
	</div>
</div>
