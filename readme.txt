=== WP Site Bridge Migration ===
Contributors: yourname
Tags: migration, backup, move site, transfer, cloning, site migration, wordpress migration
Requires at least: 5.6
Tested up to: 6.4
Stable tag: 1.2.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The easiest way to migrate your WordPress site directly from one host to another. No download/upload required.

== Description ==

WP Site Bridge Migration allows you to move your WordPress site from one hosting provider to another seamlessly. It connects two websites (Source and Destination) and transfers Database, Plugins, Themes, and Media files directly via a secure API connection.

**Key Features:**

* **Server-to-Server Transfer:** No need to download huge backup files to your computer. Files are transferred directly between servers.
* **Streaming SQL Parser:** Handles large databases (100MB+) even on low-resource shared hosting without memory exhaustion or timeouts.
* **Smart Batch Processing:** Automatically handles Search & Replace for domain changes in small batches to prevent server timeouts.
* **Serialized Data Safe:** Intelligently replaces URLs while preserving serialized data (widgets, theme options, custom fields).
* **Secure Handshake:** Uses token-based authentication with timing-safe comparison to ensure only authorized transfers.
* **Real-time Progress:** Visual progress indicators show exactly what's happening during migration.
* **Zero Downtime:** The source site remains live during the migration process.
* **Automatic Cleanup:** Temporary files are automatically deleted after successful migration.

**How it works:**

1. Install the plugin on both sites (Source and Destination).
2. On the Destination site, select "Destination Website" and generate a Secret Migration Key.
3. On the Source site, select "Source Website", paste the Key, and click "Connect & Validate".
4. Click "Start Migration" and watch the progress.
5. The plugin automatically handles everything: export, transfer, restore, search & replace, and cleanup.

**Perfect for:**

* Moving sites between hosting providers
* Cloning sites to staging environments
* Migrating from local development to production
* Transferring sites without FTP access
* Large sites that would timeout with traditional methods

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/wp-site-bridge-migration` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Navigate to **Tools > Site Migration** to access the plugin dashboard.

== Frequently Asked Questions ==

= Does this delete my data on the destination site? =

Yes. The migration process will overwrite the database and wp-content folder (plugins, themes, uploads) of the Destination site to match the Source site. Always backup your destination site before migrating if you need to preserve existing data.

= What happens if the migration fails? =

The plugin processes data in chunks and batches. If it fails at any point, you can simply retry. The plugin will resume from where it left off. No data on the Source site is ever deleted or modified.

= Do I need to change the domain manually? =

No. The plugin automatically runs a "Search & Replace" operation to update all URLs from your old domain to the new domain. This includes URLs in posts, pages, options, widgets, and theme settings. The process safely handles serialized data to prevent breaking widgets or theme options.

= Will this work with large databases? =

Yes! The plugin uses a streaming SQL parser that reads files in 1MB chunks, reducing memory usage from 300-400MB to just 10-20MB for a 100MB database file. Search & Replace operations are processed in small batches (25 seconds each) to avoid server timeouts.

= What if my server has a 60-second timeout? =

The batch processing feature automatically handles this. Each batch runs for 25 seconds, then the plugin makes a new request to continue. This allows the migration to complete even on servers with strict timeout limits.

= Is this secure? =

Yes. The plugin uses:
* Token-based authentication with timing-safe comparison (hash_equals)
* Path traversal protection (whitelist file mapping)
* Protected temporary directories (.htaccess)
* Input sanitization and validation
* WordPress nonce verification for AJAX requests

= What files are excluded from migration? =

The plugin excludes:
* node_modules directories
* .git directories
* Cache directories
* Temporary files (error_log, .DS_Store, Thumbs.db)
* The plugin's own temporary directory

= Can I migrate just the database or just the files? =

Currently, the plugin migrates everything (database, plugins, themes, uploads) in one process. This ensures consistency between all components.

= Does this work with multisite? =

The current version focuses on single-site migrations. Multisite support may be added in future versions.

== Screenshots ==

1. Source Site Configuration - Enter migration key and connect to destination
2. Destination Site Configuration - Generate and copy migration key
3. Migration Progress - Real-time progress tracking with detailed status
4. Success Screen - Migration completed with link to new site

== Changelog ==

= 1.2.0 =
* Added comprehensive troubleshooting guide in Help & Guide tab
* Updated plugin author information with GitHub and Facebook links
* Added donation support section
* Improved documentation

= 1.1.0 =
* Performance: Added Streaming SQL Parser for large database imports (handles 100MB+ files with minimal RAM)
* Performance: Added Batch Processing for Search & Replace to prevent server timeouts
* Feature: Real-time progress tracking showing current table and row range
* Feature: Automatic resume capability if migration is interrupted
* Security: Enhanced token validation logic
* Improvement: Better error handling and recovery mechanisms
* Improvement: Memory optimization with garbage collection

= 1.0.0 =
* Initial release
* Phase 1: Plugin skeleton and admin UI
* Phase 2: Handshake & authentication
* Phase 3: Data packing (backup)
* Phase 4: Transfer & restore
* Phase 5: Finalize & cleanup
* Complete migration workflow
* Secure authentication system
* Intelligent search & replace
* Automatic cleanup

== Upgrade Notice ==

= 1.1.0 =
Major performance update! Now supports unlimited database sizes with streaming parser and batch processing. Recommended for all users, especially those with large sites or shared hosting.

= 1.0.0 =
Initial release. Perfect for migrating WordPress sites between hosts.

== Development ==

The plugin follows WordPress coding standards and best practices:
* Object-oriented programming with namespaces
* Singleton pattern for core classes
* REST API for secure communication
* WP_Filesystem for safe file operations
* Comprehensive error handling

For developers: The plugin architecture is modular and well-documented. See the main README.md for technical details.

