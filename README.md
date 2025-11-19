# WP Site Bridge Migration

A powerful WordPress plugin that enables seamless, direct migration of WordPress sites from one host to another without manual file transfers or database exports.

## 🚀 Features

- **Direct Site-to-Site Migration**: Migrate your WordPress site directly between two WordPress installations
- **Secure Authentication**: Token-based authentication system ensures secure connections
- **Automated Process**: Fully automated migration process with progress tracking
- **Smart Search & Replace**: Intelligently replaces URLs in database while preserving serialized data (widgets, theme options)
- **Streaming SQL Parser**: Memory-efficient database import using streaming parser (handles files 100MB+ with minimal RAM)
- **Batch Processing**: Search & replace operations split into small batches to avoid server timeouts
- **Chunked Processing**: Handles large databases and files efficiently using chunking to prevent timeouts
- **Automatic Cleanup**: Automatically cleans up temporary files after migration
- **Real-time Progress**: Visual progress indicators for each migration step with detailed status
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
├── templates/
│   └── admin-page.php            # Admin page template
├── build/                        # Build scripts for creating distribution ZIP
│   ├── build.sh                  # Linux/Mac build script
│   ├── build.ps1                 # Windows PowerShell build script
│   ├── build.bat                 # Windows batch build script
│   └── README.md                 # Build instructions
├── dist/                         # Output directory for build artifacts
├── composer.json                 # Composer configuration (PSR-4 autoloading)
└── readme.txt                    # WordPress.org plugin repository format
```

### Development Setup

The plugin uses **Composer** for dependency management and PSR-4 autoloading:

```bash
# Install Composer dependencies (if any)
composer install

# The plugin supports PSR-4 autoloading via Composer
# Namespace: WPSiteBridge\ → includes/
```

### Building Distribution Package

The plugin includes build scripts to create a clean, production-ready ZIP file:

**Windows (PowerShell - Recommended):**
```powershell
cd build
.\build.ps1
```

**Windows (Batch):**
```cmd
cd build
build.bat
```

**Linux/Mac:**
```bash
cd build
chmod +x build.sh
./build.sh
```

The build script will create `wp-site-bridge-migration.zip` in the `dist/` folder, excluding:
- Git files (`.git/`, `.github/`)
- Development files (`composer.json`, `README.md`, etc.)
- Build scripts (`build/`)
- IDE files (`.vscode/`, `.idea/`)
- OS files (`.DS_Store`, `Thumbs.db`)

See `build/README.md` for detailed build instructions.

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
  
- `POST /wp-json/wpsbm/v1/finalize_migration` - Finalize migration (Search & Replace) - Legacy endpoint
  - Parameters: `old_url`, `token`
  
- `POST /wp-json/wpsbm/v1/finalize_migration_batch` - Finalize migration with batch processing (Recommended for large sites)
  - Parameters: `old_url`, `token`, `table_name` (optional), `offset` (optional)
  - Returns: `completed`, `next_table`, `next_offset`, `progress`
  
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
- **Streaming SQL Parser**: Memory-efficient database import (1MB chunks, handles 100MB+ files)
- File extraction using WP_Filesystem
- Sequential step processing

### Phase 5: Finalize & Cleanup ✅
- **Batch Search & Replace**: Processes database in small batches (25s per batch) to avoid timeouts
- Intelligent search & replace (handles serialized data)
- URL replacement across entire database with progress tracking
- Automatic cleanup of temporary files
- Success notification with site link

## 🛠️ Technical Details

### Database Export
- Exports all tables with structure and data
- Uses chunking (1000 rows per select) to prevent memory issues
- Handles special characters and newlines correctly

### Database Import (Streaming Parser)
- **Streaming SQL Parser**: Reads file in 1MB chunks instead of loading entire file into memory
- **Memory Efficiency**: File 100MB → ~10-20MB RAM usage (vs 300-400MB with traditional method)
- **State Machine**: Handles escaped quotes, multi-line comments, and queries spanning chunks
- **Buffer Management**: Safely handles queries/strings/comments that are cut between chunks
- **Garbage Collection**: Periodic memory cleanup every 1000 queries

### File Compression
- Uses PHP `ZipArchive` for compression
- Excludes unnecessary files (node_modules, .git, cache, etc.)
- Preserves directory structure

### Search & Replace (Batch Processing)
- **Batch Processing**: Splits large operations into 25-second batches to avoid server timeouts
- **Progress Tracking**: Real-time progress updates showing current table and row range
- **Resume Capability**: Automatically resumes from last position if interrupted
- **Memory Efficient**: Processes 50 rows per chunk with column info caching
- Recursively processes strings, arrays, and objects
- Safely handles serialized data (unserialize → replace → re-serialize)
- Skips primary keys and numeric-only columns
- **No Duplicates/Skips**: Uses exact LIMIT/OFFSET to ensure data integrity

### Error Handling
- Comprehensive error messages
- Graceful degradation (continues even if cleanup fails)
- Timeout handling for long operations
- Memory limit management

## 🐛 Troubleshooting

### Migration Fails with Timeout
- **For Search & Replace**: The plugin now uses batch processing automatically. If you still experience timeouts, check your server's hard timeout limits (Nginx/Apache)
- For other operations: Increase PHP `max_execution_time` in `php.ini`
- Increase WordPress memory limit
- Consider migrating during off-peak hours

### Memory Exhaustion
- **Database Import**: The streaming parser handles large files efficiently. If you still experience issues, check PHP `memory_limit` (should be at least 128MB)
- **Search & Replace**: Batch processing minimizes memory usage. Very large databases (>500MB) may need server adjustments
- The plugin uses chunking and streaming to minimize memory usage, but very large sites may need server adjustments

### Connection Validation Fails
- Verify both sites are accessible
- Check that REST API is enabled on both sites
- Ensure migration key is copied correctly (no extra spaces)

### Search & Replace Issues
- The plugin handles serialized data automatically
- If widgets or theme options break, they may need manual reconfiguration
- Always backup before migration

## 📝 Changelog

### 1.2.0 (Current)

* ✅ **Troubleshooting Guide**: Added comprehensive troubleshooting section in Help & Guide tab
* ✅ **Author Information**: Updated plugin author information with GitHub and Facebook links
* ✅ **Donation Support**: Added donation section for USDT (BNB Smartchain)
* ✅ **Improved Documentation**: Enhanced README with common troubleshooting questions

### 1.1.0
- 🚀 **Streaming SQL Parser**: Memory-efficient database import (handles 100MB+ files with minimal RAM)
- 🚀 **Batch Search & Replace**: Processes large databases in small batches to avoid timeouts
- ✅ Real-time progress tracking for search & replace operations
- ✅ Improved error handling and recovery
- ✅ Enhanced performance for large sites
- ✅ **Composer Support**: Added PSR-4 autoloading support for future extensibility
- ✅ **Build Scripts**: Professional build system for creating distribution packages (Windows/Linux/Mac)
- ✅ **Help & Guide Tab**: Added comprehensive user guide in admin interface
- ✅ **Professional Documentation**: Updated README and added WordPress.org format readme.txt

### 1.0.0
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
- **Large Database Support** - The plugin now supports databases of any size using streaming and batch processing
- **Server Timeouts** - Batch processing automatically handles server timeouts, but ensure your server allows at least 30-second requests

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
