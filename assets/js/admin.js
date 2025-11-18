/**
 * Admin JavaScript for WP Site Bridge Migration
 *
 * @package WPSiteBridge
 */

(function($) {
	'use strict';

	/**
	 * Admin functionality
	 */
	const WPSBMAdmin = {
		
		/**
		 * Initialize
		 */
		init: function() {
			this.bindEvents();
			this.initRoleSwitcher();
			this.checkConnectionStatus();
		},
		
		/**
		 * Check and restore connection status on page load
		 */
		checkConnectionStatus: function() {
			// Check if connection status exists in the form
			const $form = $('#wpsbm-connect-form');
			if ($form.length) {
				const isConnected = $form.data('connected') === true || $form.data('connected') === 'true';
				if (isConnected) {
					this.lockConnection($form);
				}
			}
		},
		
		/**
		 * Lock connection UI
		 */
		lockConnection: function($form) {
			const $keyInput = $('#wpsbm-remote-secret-key');
			const $urlInput = $('#wpsbm-destination-url');
			const $button = $('#wpsbm-connect-validate');
			
			$keyInput.prop('disabled', true).addClass('wpsbm-locked');
			$urlInput.prop('disabled', true).addClass('wpsbm-locked');
			$button.prop('disabled', true).text('Connected').addClass('wpsbm-connected');
			$form.addClass('wpsbm-connected-form');
		},
		
		/**
		 * Bind events
		 */
		bindEvents: function() {
			// Handle role radio button changes
			$(document).on('change', '.wpsbm-role-radio', this.handleRoleChange);
			
			// Handle generate secret key button
			$(document).on('click', '#wpsbm-generate-key', this.handleGenerateKey);
			
			// Handle copy key button
			$(document).on('click', '#wpsbm-copy-key', this.handleCopyKey);
			
			// Handle connect & validate form
			$(document).on('submit', '#wpsbm-connect-form', this.handleConnectValidate);
			
			// Handle start migration button
			$(document).on('click', '#wpsbm-start-migration', this.handleStartMigration);
		},
		
		/**
		 * Initialize role switcher
		 */
		initRoleSwitcher: function() {
			// Update radio label active state on change
			$('.wpsbm-role-radio').on('change', function() {
				$('.wpsbm-radio-label').removeClass('wpsbm-radio-active');
				$(this).closest('.wpsbm-radio-label').addClass('wpsbm-radio-active');
			});
		},
		
		/**
		 * Handle role change
		 */
		handleRoleChange: function(e) {
			// Visual feedback - the form will handle the actual save
			const role = $(e.target).val();
			console.log('Role changed to:', role);
		},
		
		/**
		 * Handle generate secret key
		 */
		handleGenerateKey: function(e) {
			e.preventDefault();
			
			const $button = $(this);
			const $textarea = $('#wpsbm-secret-key');
			const $copyButton = $('#wpsbm-copy-key');
			
			// Disable button during generation
			$button.prop('disabled', true).addClass('wpsbm-loading');
			
			// Make AJAX request to generate key
			$.ajax({
				url: wpsbmAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'wpsbm_generate_key',
					nonce: wpsbmAdmin.nonce
				},
				success: function(response) {
					$button.prop('disabled', false).removeClass('wpsbm-loading');
					
					if (response.success && response.data.migration_key) {
						$textarea.val(response.data.migration_key);
						$copyButton.show();
						
						WPSBMAdmin.showStatusMessage(
							response.data.message || 'Migration key generated successfully. Please copy and share it with the source website.',
							'success'
						);
					} else {
						WPSBMAdmin.showStatusMessage(
							response.data && response.data.message ? response.data.message : 'Failed to generate migration key.',
							'error'
						);
					}
				},
				error: function(xhr, status, error) {
					$button.prop('disabled', false).removeClass('wpsbm-loading');
					WPSBMAdmin.showStatusMessage(
						'An error occurred while generating the key: ' + error,
						'error'
					);
				}
			});
		},
		
		/**
		 * Handle copy key to clipboard
		 */
		handleCopyKey: function(e) {
			e.preventDefault();
			
			const $textarea = $('#wpsbm-secret-key');
			const key = $textarea.val().trim();
			
			if (!key) {
				WPSBMAdmin.showStatusMessage('No key to copy. Please generate a key first.', 'error');
				return;
			}
			
			// Copy to clipboard
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(key).then(function() {
					WPSBMAdmin.showStatusMessage('Secret key copied to clipboard!', 'success');
				}).catch(function() {
					WPSBMAdmin.fallbackCopyText(key);
				});
			} else {
				WPSBMAdmin.fallbackCopyText(key);
			}
		},
		
		/**
		 * Fallback copy method for older browsers
		 */
		fallbackCopyText: function(text) {
			const $textarea = $('#wpsbm-secret-key');
			$textarea.select();
			$textarea[0].setSelectionRange(0, 99999); // For mobile devices
			
			try {
				const successful = document.execCommand('copy');
				if (successful) {
					WPSBMAdmin.showStatusMessage('Secret key copied to clipboard!', 'success');
				} else {
					WPSBMAdmin.showStatusMessage('Failed to copy. Please select and copy manually.', 'error');
				}
			} catch (err) {
				WPSBMAdmin.showStatusMessage('Failed to copy. Please select and copy manually.', 'error');
			}
			
			// Deselect
			window.getSelection().removeAllRanges();
		},
		
		/**
		 * Handle connect & validate form submission
		 */
		handleConnectValidate: function(e) {
			e.preventDefault();
			
			const $form = $(this);
			const $button = $('#wpsbm-connect-validate');
			const $statusMessage = $('#wpsbm-connection-status');
			const $keyInput = $('#wpsbm-remote-secret-key');
			const $urlInput = $('#wpsbm-destination-url');
			const remoteKey = $keyInput.val().trim();
			const destinationUrl = $urlInput.val().trim();
			
			// Validation
			if (!remoteKey) {
				WPSBMAdmin.showStatusMessage('Please enter the migration key.', 'error', $statusMessage);
				return;
			}
			
			// URL is optional if it's in the migration key, but validate if provided
			if (destinationUrl) {
				try {
					new URL(destinationUrl);
				} catch (err) {
					WPSBMAdmin.showStatusMessage('Please enter a valid URL (e.g., https://example.com).', 'error', $statusMessage);
					return;
				}
			}
			
			// Disable inputs and button, show loading spinner
			$keyInput.prop('disabled', true);
			$urlInput.prop('disabled', true);
			$button.prop('disabled', true).addClass('wpsbm-loading');
			$statusMessage.hide();
			
			// Show spinner text
			const originalButtonText = $button.text();
			$button.text('Connecting...');
			
			// Make AJAX request to connect and validate
			$.ajax({
				url: wpsbmAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'wpsbm_connect_remote',
					nonce: wpsbmAdmin.nonce,
					migration_key: remoteKey,
					destination_url: destinationUrl
				},
				success: function(response) {
					$button.removeClass('wpsbm-loading');
					
					if (response.success) {
						// Show success message with site name
						const siteName = response.data.site_name || 'Remote Site';
						const message = 'Connected to ' + siteName + '!';
						WPSBMAdmin.showStatusMessage(message, 'success', $statusMessage);
						
						// Lock the connection - keep inputs disabled
						$keyInput.addClass('wpsbm-locked');
						$urlInput.addClass('wpsbm-locked');
						$button.text('Connected').prop('disabled', true).addClass('wpsbm-connected');
						
						// Add visual indicator
						$form.addClass('wpsbm-connected-form');
						
						// Store connection status
						$form.data('connected', true);
						
						// Show migration section
						$('#wpsbm-migration-section').fadeIn();
					} else {
						// Re-enable inputs on error
						$keyInput.prop('disabled', false);
						$urlInput.prop('disabled', false);
						$button.prop('disabled', false).text(originalButtonText);
						
						// Show error message
						const errorMsg = response.data && response.data.message ? response.data.message : 'Connection validation failed.';
						WPSBMAdmin.showStatusMessage(errorMsg, 'error', $statusMessage);
					}
				},
				error: function(xhr, status, error) {
					// Re-enable inputs on error
					$keyInput.prop('disabled', false);
					$urlInput.prop('disabled', false);
					$button.prop('disabled', false).removeClass('wpsbm-loading').text(originalButtonText);
					
					let errorMsg = 'An error occurred while connecting: ' + error;
					
					// Try to parse error response
					if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
						errorMsg = xhr.responseJSON.data.message;
					}
					
					WPSBMAdmin.showStatusMessage(errorMsg, 'error', $statusMessage);
				}
			});
		},
		
		/**
		 * Handle start migration
		 */
		handleStartMigration: function(e) {
			e.preventDefault();
			
			const $button = $('#wpsbm-start-migration');
			const $progressList = $('#wpsbm-migration-progress');
			const $statusMessage = $('#wpsbm-migration-status');
			
			// Disable button and show progress
			$button.prop('disabled', true).addClass('wpsbm-loading');
			$progressList.fadeIn();
			$statusMessage.hide();
			
			// Reset all progress items
			$('.wpsbm-progress-item').each(function() {
				const $item = $(this);
				$item.removeClass('wpsbm-progress-done wpsbm-progress-active wpsbm-progress-error');
				$item.find('.wpsbm-progress-icon').text('○');
				$item.find('.wpsbm-progress-size').text('');
			});
			
			// Start migration steps sequentially
			WPSBMAdmin.runMigrationSteps();
		},
		
		/**
		 * Run migration steps sequentially
		 */
		runMigrationSteps: function() {
			const steps = [
				{ key: 'database', action: 'wpsbm_step_backup_db', type: null, label: 'Exporting Database' },
				{ key: 'plugins', action: 'wpsbm_step_zip_files', type: 'plugins', label: 'Zipping Plugins' },
				{ key: 'themes', action: 'wpsbm_step_zip_files', type: 'themes', label: 'Zipping Themes' },
				{ key: 'uploads', action: 'wpsbm_step_zip_files', type: 'uploads', label: 'Zipping Uploads' }
			];
			
			let currentStep = 0;
			
			const executeStep = function() {
				if (currentStep >= steps.length) {
					// All steps completed
					WPSBMAdmin.completeMigration();
					return;
				}
				
				const step = steps[currentStep];
				const $item = $('.wpsbm-progress-item[data-step="' + step.key + '"]');
				
				// Mark as active
				$item.addClass('wpsbm-progress-active');
				$item.find('.wpsbm-progress-icon').text('⟳');
				
				// Prepare AJAX data
				const ajaxData = {
					action: step.action,
					nonce: wpsbmAdmin.nonce
				};
				
				if (step.type) {
					ajaxData.type = step.type;
				}
				
				// Execute step
				$.ajax({
					url: wpsbmAdmin.ajaxUrl,
					type: 'POST',
					data: ajaxData,
					success: function(response) {
						if (response.success) {
							// Mark as done
							$item.removeClass('wpsbm-progress-active').addClass('wpsbm-progress-done');
							$item.find('.wpsbm-progress-icon').text('✓');
							
							// Show file size
							if (response.data.size_formatted) {
								$item.find('.wpsbm-progress-size').text('(' + response.data.size_formatted + ')');
							}
							
							// Move to next step
							currentStep++;
							setTimeout(executeStep, 500); // Small delay between steps
						} else {
							// Mark as error
							$item.removeClass('wpsbm-progress-active').addClass('wpsbm-progress-error');
							$item.find('.wpsbm-progress-icon').text('✗');
							
							// Show error message
							const errorMsg = response.data && response.data.message ? response.data.message : 'Step failed';
							WPSBMAdmin.showStatusMessage(errorMsg, 'error', $('#wpsbm-migration-status'));
							
							// Re-enable button
							$('#wpsbm-start-migration').prop('disabled', false).removeClass('wpsbm-loading');
						}
					},
					error: function(xhr, status, error) {
						// Mark as error
						$item.removeClass('wpsbm-progress-active').addClass('wpsbm-progress-error');
						$item.find('.wpsbm-progress-icon').text('✗');
						
						// Show error message
						let errorMsg = 'An error occurred: ' + error;
						if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
							errorMsg = xhr.responseJSON.data.message;
						}
						WPSBMAdmin.showStatusMessage(errorMsg, 'error', $('#wpsbm-migration-status'));
						
						// Re-enable button
						$('#wpsbm-start-migration').prop('disabled', false).removeClass('wpsbm-loading');
					}
				});
			};
			
			// Start first step
			executeStep();
		},
		
		/**
		 * Complete migration
		 */
		completeMigration: function() {
			const $button = $('#wpsbm-start-migration');
			const $statusMessage = $('#wpsbm-migration-status');
			
			// All local steps completed, now trigger remote restoration
			WPSBMAdmin.showStatusMessage(
				'Local backup completed! Starting remote restoration...',
				'info',
				$statusMessage
			);
			
			// Trigger remote restoration
			WPSBMAdmin.triggerRemoteRestore();
		},
		
		/**
		 * Trigger remote restoration on destination site
		 */
		triggerRemoteRestore: function() {
			// Get destination connection info from stored options or form
			const destinationUrl = $('#wpsbm-destination-url').val() || '';
			const migrationKey = $('#wpsbm-remote-secret-key').val() || '';
			
			if (!destinationUrl && !migrationKey) {
				WPSBMAdmin.showStatusMessage(
					'Cannot start remote restoration: Missing destination information.',
					'error',
					$('#wpsbm-migration-status')
				);
				$('#wpsbm-start-migration').prop('disabled', false).removeClass('wpsbm-loading');
				return;
			}
			
			// Parse migration key to get destination URL and token
			let targetUrl = destinationUrl;
			let destinationToken = '';
			
			// Try to parse migration key if available
			if (migrationKey) {
				try {
					// Migration key format: base64(SITE_URL + '||' + TOKEN)
					const decoded = atob(migrationKey);
					const parts = decoded.split('||');
					if (parts.length === 2) {
						targetUrl = parts[0];
						destinationToken = parts[1];
					}
				} catch (e) {
					console.error('Failed to parse migration key:', e);
				}
			}
			
			// Validate we have required info
			if (!targetUrl || !destinationToken) {
				WPSBMAdmin.showStatusMessage(
					'Cannot start remote restoration: Invalid destination information. Please reconnect.',
					'error',
					$('#wpsbm-migration-status')
				);
				$('#wpsbm-start-migration').prop('disabled', false).removeClass('wpsbm-loading');
				return;
			}
			
			// Get source token (we need to get this from server via AJAX)
			// For now, we'll need to make an AJAX call to get source token
			// Or we can pass it from the server in the localized script
			
			// Show remote restoration progress
			const $remoteProgress = $('#wpsbm-remote-restore-progress');
			$remoteProgress.fadeIn();
			
			// Reset remote progress items
			$remoteProgress.find('.wpsbm-progress-item').each(function() {
				const $item = $(this);
				$item.removeClass('wpsbm-progress-done wpsbm-progress-active wpsbm-progress-error');
				$item.find('.wpsbm-progress-icon').text('○');
			});
			
			// Get source site URL and token
			// We need to get source token from server
			$.ajax({
				url: wpsbmAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'wpsbm_get_source_token',
					nonce: wpsbmAdmin.nonce
				},
				success: function(response) {
					if (response.success && response.data.source_token) {
						sourceToken = response.data.source_token;
						const sourceUrl = response.data.source_url || window.location.origin;
						
						// Start remote restoration steps
						WPSBMAdmin.runRemoteRestoreSteps(targetUrl, destinationToken, sourceUrl, sourceToken);
					} else {
						WPSBMAdmin.showStatusMessage(
							'Failed to get source token. Please try again.',
							'error',
							$('#wpsbm-migration-status')
						);
						$('#wpsbm-start-migration').prop('disabled', false).removeClass('wpsbm-loading');
					}
				},
				error: function() {
					// Fallback: try without source token (destination will need to handle this)
					WPSBMAdmin.showStatusMessage(
						'Warning: Source token not available. Remote restoration may fail.',
						'warning',
						$('#wpsbm-migration-status')
					);
					// Still try to proceed
					const sourceUrl = window.location.origin;
					WPSBMAdmin.runRemoteRestoreSteps(targetUrl, destinationToken, sourceUrl, '');
				}
			});
		},
		
		/**
		 * Run remote restoration steps sequentially
		 */
		runRemoteRestoreSteps: function(destinationUrl, destinationToken, sourceUrl, sourceToken) {
			const steps = [
				{ key: 'remote-database', step: 'database', label: 'Restoring Database on remote site' },
				{ key: 'remote-plugins', step: 'plugins', label: 'Restoring Plugins' },
				{ key: 'remote-themes', step: 'themes', label: 'Restoring Themes' },
				{ key: 'remote-uploads', step: 'uploads', label: 'Restoring Uploads' }
			];
			
			let currentStep = 0;
			
			const executeRemoteStep = function() {
				if (currentStep >= steps.length) {
					// All remote steps completed
					WPSBMAdmin.completeRemoteRestore();
					return;
				}
				
				const step = steps[currentStep];
				const $item = $('.wpsbm-progress-item[data-step="' + step.key + '"]');
				
				// Mark as active
				$item.addClass('wpsbm-progress-active');
				$item.find('.wpsbm-progress-icon').text('⟳');
				
				// Build destination API URL
				const apiUrl = destinationUrl.replace(/\/$/, '') + '/wp-json/wpsbm/v1/process_step';
				
				// Prepare request data
				const requestData = {
					step: step.step,
					source_url: sourceUrl,
					source_token: sourceToken,
					token: destinationToken
				};
				
				// Make AJAX call to destination API
				$.ajax({
					url: apiUrl,
					type: 'POST',
					data: requestData,
					timeout: 600000, // 10 minutes timeout
					success: function(response) {
						if (response.success) {
							// Mark as done
							$item.removeClass('wpsbm-progress-active').addClass('wpsbm-progress-done');
							$item.find('.wpsbm-progress-icon').text('✓');
							
							// Move to next step
							currentStep++;
							setTimeout(executeRemoteStep, 1000); // 1 second delay between steps
						} else {
							// Mark as error
							$item.removeClass('wpsbm-progress-active').addClass('wpsbm-progress-error');
							$item.find('.wpsbm-progress-icon').text('✗');
							
							const errorMsg = response.message || 'Step failed';
							WPSBMAdmin.showStatusMessage(
								'Remote restoration failed: ' + errorMsg,
								'error',
								$('#wpsbm-migration-status')
							);
							
							$('#wpsbm-start-migration').prop('disabled', false).removeClass('wpsbm-loading');
						}
					},
					error: function(xhr, status, error) {
						// Mark as error
						$item.removeClass('wpsbm-progress-active').addClass('wpsbm-progress-error');
						$item.find('.wpsbm-progress-icon').text('✗');
						
						let errorMsg = 'An error occurred: ' + error;
						if (xhr.responseJSON && xhr.responseJSON.message) {
							errorMsg = xhr.responseJSON.message;
						} else if (status === 'timeout') {
							errorMsg = 'Request timed out. The remote site may be processing a large file.';
						}
						
						WPSBMAdmin.showStatusMessage(
							'Remote restoration failed: ' + errorMsg,
							'error',
							$('#wpsbm-migration-status')
						);
						
						$('#wpsbm-start-migration').prop('disabled', false).removeClass('wpsbm-loading');
					}
				});
			};
			
			// Start first remote step
			executeRemoteStep();
		},
		
		/**
		 * Complete remote restoration
		 */
		completeRemoteRestore: function() {
			// After uploads restore, proceed to finalize and cleanup
			WPSBMAdmin.finalizeAndCleanup();
		},
		
		/**
		 * Finalize migration and cleanup
		 */
		finalizeAndCleanup: function() {
			const destinationUrl = $('#wpsbm-destination-url').val() || '';
			const migrationKey = $('#wpsbm-remote-secret-key').val() || '';
			
			// Parse migration key to get destination URL and token
			let targetUrl = destinationUrl;
			let destinationToken = '';
			
			if (migrationKey) {
				try {
					const decoded = atob(migrationKey);
					const parts = decoded.split('||');
					if (parts.length === 2) {
						targetUrl = parts[0];
						destinationToken = parts[1];
					}
				} catch (e) {
					console.error('Failed to parse migration key:', e);
				}
			}
			
			if (!targetUrl || !destinationToken) {
				WPSBMAdmin.showStatusMessage(
					'Cannot finalize: Missing destination information.',
					'error',
					$('#wpsbm-migration-status')
				);
				return;
			}
			
			// Get source URL (old URL)
			const sourceUrl = window.location.origin;
			
			// Step 1: Finalize migration (Search & Replace)
			const $finalizeItem = $('.wpsbm-progress-item[data-step="finalize"]');
			if ($finalizeItem.length === 0) {
				// Create finalize progress item if it doesn't exist
				const $progressList = $('#wpsbm-migration-progress');
				$progressList.append(
					'<div class="wpsbm-progress-item" data-step="finalize">' +
					'<span class="wpsbm-progress-icon">○</span> ' +
					'<span class="wpsbm-progress-text">Finalizing Migration (Search & Replace)</span>' +
					'</div>'
				);
			}
			
			const $finalize = $('.wpsbm-progress-item[data-step="finalize"]');
			$finalize.addClass('wpsbm-progress-active');
			$finalize.find('.wpsbm-progress-icon').text('⟳');
			
			const finalizeApiUrl = targetUrl.replace(/\/$/, '') + '/wp-json/wpsbm/v1/finalize_migration';
			
			$.ajax({
				url: finalizeApiUrl,
				type: 'POST',
				data: {
					old_url: sourceUrl,
					token: destinationToken
				},
				timeout: 600000, // 10 minutes
				success: function(response) {
					if (response.success) {
						$finalize.removeClass('wpsbm-progress-active').addClass('wpsbm-progress-done');
						$finalize.find('.wpsbm-progress-icon').text('✓');
						
						// Step 2: Remote cleanup
						WPSBMAdmin.cleanupRemote(targetUrl, destinationToken, function() {
							// Step 3: Local cleanup
							WPSBMAdmin.cleanupLocal(function() {
								// All done!
								WPSBMAdmin.showMigrationSuccess(targetUrl);
							});
						});
					} else {
						$finalize.removeClass('wpsbm-progress-active').addClass('wpsbm-progress-error');
						$finalize.find('.wpsbm-progress-icon').text('✗');
						
						WPSBMAdmin.showStatusMessage(
							'Finalization failed: ' + (response.message || 'Unknown error'),
							'error',
							$('#wpsbm-migration-status')
						);
					}
				},
				error: function(xhr, status, error) {
					$finalize.removeClass('wpsbm-progress-active').addClass('wpsbm-progress-error');
					$finalize.find('.wpsbm-progress-icon').text('✗');
					
					let errorMsg = 'Finalization failed: ' + error;
					if (xhr.responseJSON && xhr.responseJSON.message) {
						errorMsg = 'Finalization failed: ' + xhr.responseJSON.message;
					} else if (status === 'timeout') {
						errorMsg = 'Finalization timed out. The operation may still be processing.';
					}
					
					WPSBMAdmin.showStatusMessage(errorMsg, 'error', $('#wpsbm-migration-status'));
				}
			});
		},
		
		/**
		 * Cleanup remote site
		 */
		cleanupRemote: function(destinationUrl, destinationToken, callback) {
			const $cleanupItem = $('.wpsbm-progress-item[data-step="cleanup-remote"]');
			if ($cleanupItem.length === 0) {
				const $progressList = $('#wpsbm-migration-progress');
				$progressList.append(
					'<div class="wpsbm-progress-item" data-step="cleanup-remote">' +
					'<span class="wpsbm-progress-icon">○</span> ' +
					'<span class="wpsbm-progress-text">Cleaning up remote site</span>' +
					'</div>'
				);
			}
			
			const $cleanup = $('.wpsbm-progress-item[data-step="cleanup-remote"]');
			$cleanup.addClass('wpsbm-progress-active');
			$cleanup.find('.wpsbm-progress-icon').text('⟳');
			
			const cleanupApiUrl = destinationUrl.replace(/\/$/, '') + '/wp-json/wpsbm/v1/cleanup';
			
			$.ajax({
				url: cleanupApiUrl,
				type: 'POST',
				data: {
					token: destinationToken
				},
				timeout: 60000, // 1 minute
				success: function(response) {
					if (response.success) {
						$cleanup.removeClass('wpsbm-progress-active').addClass('wpsbm-progress-done');
						$cleanup.find('.wpsbm-progress-icon').text('✓');
						
						if (callback) callback();
					} else {
						$cleanup.removeClass('wpsbm-progress-active').addClass('wpsbm-progress-error');
						$cleanup.find('.wpsbm-progress-icon').text('✗');
						
						WPSBMAdmin.showStatusMessage(
							'Remote cleanup failed: ' + (response.message || 'Unknown error'),
							'warning',
							$('#wpsbm-migration-status')
						);
						
						// Continue with local cleanup even if remote cleanup failed
						if (callback) callback();
					}
				},
				error: function(xhr, status, error) {
					$cleanup.removeClass('wpsbm-progress-active').addClass('wpsbm-progress-error');
					$cleanup.find('.wpsbm-progress-icon').text('✗');
					
					WPSBMAdmin.showStatusMessage(
						'Remote cleanup failed: ' + error + '. Continuing with local cleanup...',
						'warning',
						$('#wpsbm-migration-status')
					);
					
					// Continue with local cleanup even if remote cleanup failed
					if (callback) callback();
				}
			});
		},
		
		/**
		 * Cleanup local site
		 */
		cleanupLocal: function(callback) {
			const $cleanupItem = $('.wpsbm-progress-item[data-step="cleanup-local"]');
			if ($cleanupItem.length === 0) {
				const $progressList = $('#wpsbm-migration-progress');
				$progressList.append(
					'<div class="wpsbm-progress-item" data-step="cleanup-local">' +
					'<span class="wpsbm-progress-icon">○</span> ' +
					'<span class="wpsbm-progress-text">Cleaning up local site</span>' +
					'</div>'
				);
			}
			
			const $cleanup = $('.wpsbm-progress-item[data-step="cleanup-local"]');
			$cleanup.addClass('wpsbm-progress-active');
			$cleanup.find('.wpsbm-progress-icon').text('⟳');
			
			$.ajax({
				url: wpsbmAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'wpsbm_cleanup',
					nonce: wpsbmAdmin.nonce
				},
				timeout: 60000, // 1 minute
				success: function(response) {
					if (response.success) {
						$cleanup.removeClass('wpsbm-progress-active').addClass('wpsbm-progress-done');
						$cleanup.find('.wpsbm-progress-icon').text('✓');
						
						if (callback) callback();
					} else {
						$cleanup.removeClass('wpsbm-progress-active').addClass('wpsbm-progress-error');
						$cleanup.find('.wpsbm-progress-icon').text('✗');
						
						WPSBMAdmin.showStatusMessage(
							'Local cleanup failed: ' + (response.data && response.data.message ? response.data.message : 'Unknown error'),
							'warning',
							$('#wpsbm-migration-status')
						);
						
						// Still show success even if cleanup failed
						if (callback) callback();
					}
				},
				error: function(xhr, status, error) {
					$cleanup.removeClass('wpsbm-progress-active').addClass('wpsbm-progress-error');
					$cleanup.find('.wpsbm-progress-icon').text('✗');
					
					WPSBMAdmin.showStatusMessage(
						'Local cleanup failed: ' + error + '. You may need to manually delete the temp folder.',
						'warning',
						$('#wpsbm-migration-status')
					);
					
					// Still show success even if cleanup failed
					if (callback) callback();
				}
			});
		},
		
		/**
		 * Show migration success
		 */
		showMigrationSuccess: function(destinationUrl) {
			const $button = $('#wpsbm-start-migration');
			const $progressList = $('#wpsbm-migration-progress');
			const $statusMessage = $('#wpsbm-migration-status');
			const $migrationSection = $('#wpsbm-migration-section');
			
			// Hide progress list
			$progressList.fadeOut();
			
			// Re-enable button (though it won't be visible)
			$button.prop('disabled', false).removeClass('wpsbm-loading');
			
			// Create success message HTML
			const successHtml = 
				'<div class="wpsbm-migration-success" style="text-align: center; padding: 40px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; color: white; margin: 20px 0;">' +
				'<div style="font-size: 64px; margin-bottom: 20px;">🎉</div>' +
				'<h2 style="font-size: 32px; font-weight: bold; margin-bottom: 15px; color: white;">Migration Successful!</h2>' +
				'<p style="font-size: 18px; margin-bottom: 30px; opacity: 0.95;">Your WordPress site has been successfully migrated to the new location.</p>' +
				'<a href="' + destinationUrl + '" target="_blank" class="button button-primary button-hero" style="font-size: 18px; padding: 15px 40px; text-decoration: none; display: inline-block; background: white; color: #667eea; border: none; border-radius: 6px; font-weight: bold; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">' +
				'Visit New Site →' +
				'</a>' +
				'</div>';
			
			// Insert success message
			$migrationSection.prepend(successHtml);
			
			// Show success status
			WPSBMAdmin.showStatusMessage(
				'Migration completed successfully! All files have been transferred, restored, and cleaned up.',
				'success',
				$statusMessage
			);
			
			// Scroll to top to show success message
			$('html, body').animate({
				scrollTop: $migrationSection.offset().top - 100
			}, 500);
		},
		
		/**
		 * Show status message
		 */
		showStatusMessage: function(message, type, $container) {
			type = type || 'info';
			$container = $container || $('#wpsbm-connection-status');
			
			$container
				.removeClass('wpsbm-status-success wpsbm-status-error wpsbm-status-warning wpsbm-status-info')
				.addClass('wpsbm-status-' + type)
				.text(message)
				.fadeIn();
			
			// Auto-hide after 5 seconds for success/info messages
			if (type === 'success' || type === 'info') {
				setTimeout(function() {
					$container.fadeOut();
				}, 5000);
			}
		}
	};
	
	// Initialize when document is ready
	$(document).ready(function() {
		WPSBMAdmin.init();
	});
	
})(jQuery);
