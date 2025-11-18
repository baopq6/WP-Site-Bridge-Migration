# WP Site Bridge Migration

A powerful WordPress plugin that enables seamless, direct migration of WordPress sites from one host to another without manual file transfers or database exports.

## 🚀 Features

- **Direct Site-to-Site Migration**: Migrate your WordPress site directly between two WordPress installations
- **Secure Authentication**: Token-based authentication system ensures secure connections
- **Automated Process**: Fully automated migration process with progress tracking
- **Smart Search & Replace**: Intelligently replaces URLs in database while preserving serialized data (widgets, theme options)
- **Chunked Processing**: Handles large databases and files efficiently using chunking to prevent timeouts
- **Automatic Cleanup**: Automatically cleans up temporary files after migration
- **Real-time Progress**: Visual progress indicators for each migration step
- **Error Handling**: Robust error handling with detailed error messages

## 📋 Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- PHP `ZipArchive` extension (for file compression)
- Sufficient server resources (memory and execution time) for large sites

## 🔧 Installation

1. Upload the plugin files to the `/wp-content/plugins/wp-site-bridge-migration` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Navigate to **Tools > Site Migration** to access the plugin interface.

## 📖 Usage

### Step 1: Configure Destination Site

1. On the **destination site** (where you want to migrate TO):
   - Navigate to **Tools > Site Migration**
   - Select **"Destination Website"** as the site role
   - Click **"Generate Migration Key"**
   - Copy the generated migration key

### Step 2: Connect Source Site

1. On the **source site** (where you want to migrate FROM):
   - Navigate to **Tools > Site Migration**
   - Ensure **"Source Website"** is selected as the site role
   - Paste the migration key from the destination site
   - Click **"Connect & Validate"**
   - Wait for the connection confirmation

### Step 3: Start Migration

1. On the **source site**, click **"Start Migration"**
2. The plugin will automatically:
   - Export the database
   - Zip plugins, themes, and uploads
   - Transfer files to destination
   - Restore database and files on destination
   - Perform search & replace for URLs
   - Clean up temporary files
3. When complete, you'll see a success message with a link to visit your new site

## 🏗️ Architecture

The plugin is built following WordPress coding standards with a clean, modular architecture:

```
wp-site-bridge-migration/
├── wp-site-bridge-migration.php  # Main plugin file
├── includes/
│   ├── class-core.php            # Core plugin class (Singleton)
│   ├── class-admin.php           # Admin interface handler
│   ├── class-api.php             # REST API endpoints
│   └── class-migrator.php        # Migration logic
├── assets/
│   ├── css/
│   │   └── admin.css             # Admin styles
│   └── js/
│       └── admin.js              # Admin JavaScript
└── templates/
    └── admin-page.php            # Admin page template
```

## 🔐 Security Features

- **Token-based Authentication**: Secure token generation and verification using `hash_equals()` for timing-safe comparison
- **Nonce Verification**: All AJAX requests are protected with WordPress nonces
- **Capability Checks**: Only users with `manage_options` capability can access the plugin
- **Protected Temp Directory**: Temporary files are stored in a protected directory with `.htaccess` rules
- **Input Sanitization**: All user inputs are sanitized and validated

## 📡 API Endpoints

The plugin registers the following REST API endpoints:

### Source Site Endpoints

- `GET /wp-json/wpsbm/v1/download` - Secure file download proxy
  - Parameters: `file_type` (database, plugins, themes, uploads), `token`
  
### Destination Site Endpoints

- `POST /wp-json/wpsbm/v1/handshake` - Connection validation
  - Parameters: `token`
  
- `POST /wp-json/wpsbm/v1/process_step` - Process migration step
  - Parameters: `step`, `source_url`, `source_token`, `token`
  
- `POST /wp-json/wpsbm/v1/finalize_migration` - Finalize migration (Search & Replace)
  - Parameters: `old_url`, `token`
  
- `POST /wp-json/wpsbm/v1/cleanup` - Cleanup temporary files
  - Parameters: `token`

## 🔄 Migration Process

The migration process consists of 5 phases:

### Phase 1: Plugin Skeleton & Admin UI ✅
- Plugin structure following WordPress standards
- Admin interface with role switcher (Source/Destination)
- Modern UI with TailwindCSS

### Phase 2: Handshake & Authentication ✅
- Migration key generation (Base64 encoded URL + token)
- REST API handshake endpoint
- Token verification system
- Connection validation

### Phase 3: Data Packing (Source) ✅
- Database export to SQL file
- Directory zipping (plugins, themes, uploads)
- Chunked processing for large datasets
- Progress tracking

### Phase 4: Transfer & Restore (Destination) ✅
- Secure file download via REST API
- Database import with SQL parsing
- File extraction using WP_Filesystem
- Sequential step processing

### Phase 5: Finalize & Cleanup ✅
- Intelligent search & replace (handles serialized data)
- URL replacement across entire database
- Automatic cleanup of temporary files
- Success notification with site link

## 🛠️ Technical Details

### Database Export
- Exports all tables with structure and data
- Uses chunking (1000 rows per select) to prevent memory issues
- Handles special characters and newlines correctly

### File Compression
- Uses PHP `ZipArchive` for compression
- Excludes unnecessary files (node_modules, .git, cache, etc.)
- Preserves directory structure

### Search & Replace
- Recursively processes strings, arrays, and objects
- Safely handles serialized data (unserialize → replace → re-serialize)
- Skips primary keys and numeric-only columns
- Processes data in chunks (100 rows at a time)

### Error Handling
- Comprehensive error messages
- Graceful degradation (continues even if cleanup fails)
- Timeout handling for long operations
- Memory limit management

## 🐛 Troubleshooting

### Migration Fails with Timeout
- Increase PHP `max_execution_time` in `php.ini`
- Increase WordPress memory limit
- Consider migrating during off-peak hours

### Memory Exhaustion
- Increase PHP `memory_limit` in `php.ini`
- The plugin uses chunking to minimize memory usage, but very large sites may need server adjustments

### Connection Validation Fails
- Verify both sites are accessible
- Check that REST API is enabled on both sites
- Ensure migration key is copied correctly (no extra spaces)

### Search & Replace Issues
- The plugin handles serialized data automatically
- If widgets or theme options break, they may need manual reconfiguration
- Always backup before migration

## 📝 Changelog

### 1.0.0 (Current)
- ✅ Phase 1: Plugin skeleton and admin UI
- ✅ Phase 2: Handshake & authentication
- ✅ Phase 3: Data packing (backup)
- ✅ Phase 4: Transfer & restore
- ✅ Phase 5: Finalize & cleanup
- Complete migration workflow
- Secure authentication system
- Intelligent search & replace
- Automatic cleanup

## 🤝 Contributing

Contributions are welcome! Please ensure your code follows WordPress coding standards and includes proper documentation.

## 📄 License

GPL v2 or later

## 👨‍💻 Author

Developed following WordPress best practices and coding standards.

## ⚠️ Important Notes

- **Always backup your site before migration** - While the plugin is designed to be safe, backups are essential
- **Test on staging first** - Test the migration process on a staging environment before migrating production sites
- **Check server resources** - Ensure your server has sufficient resources (memory, disk space, execution time) for large migrations
- **Serialized data handling** - The plugin safely handles serialized data, but complex custom serializations may need manual review

## 🎯 Future Enhancements

Potential features for future versions:
- Incremental migration support
- Migration scheduling
- Email notifications
- Migration history/logs
- Rollback functionality
- Multi-site support

---

**Made with ❤️ for the WordPress community**
