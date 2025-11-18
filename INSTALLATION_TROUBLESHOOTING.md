# Installation Troubleshooting Guide

## Error: "Failed to open stream: No such file or directory"

If you encounter this error when activating the plugin:

```
Warning: require_once(.../includes/class-core.php): Failed to open stream: No such file or directory
```

### Solution 1: Verify ZIP File Contents

1. **Download the latest ZIP file** from the repository or rebuild it using the build scripts
2. **Extract the ZIP file** and verify the structure:

```
wp-site-bridge-migration/
├── wp-site-bridge-migration.php
├── includes/
│   ├── class-core.php          ← REQUIRED
│   ├── class-admin.php         ← REQUIRED
│   ├── class-api.php           ← REQUIRED
│   └── class-migrator.php      ← REQUIRED
├── templates/
│   └── admin-page.php
├── assets/
│   ├── css/
│   │   └── admin.css
│   └── js/
│       └── admin.js
└── readme.txt
```

3. **Ensure all 4 files exist in `includes/` folder**:
   - `class-core.php`
   - `class-admin.php`
   - `class-api.php`
   - `class-migrator.php`

### Solution 2: Rebuild ZIP File

If you have the source code, rebuild the ZIP file:

**Windows:**
```powershell
cd build
.\build.ps1
```

**Linux/Mac:**
```bash
cd build
chmod +x build.sh
./build.sh
```

The new ZIP file will be created in `dist/wp-site-bridge-migration.zip`

### Solution 3: Manual Installation

1. **Delete the old plugin folder** (if exists):
   ```
   wp-content/plugins/wp-site-bridge-migration/
   ```

2. **Extract the ZIP file** to:
   ```
   wp-content/plugins/wp-site-bridge-migration/
   ```

3. **Verify file permissions**:
   - Files should be readable (644)
   - Directories should be executable (755)

4. **Check file ownership** (if on Linux):
   ```bash
   chown -R www-data:www-data wp-content/plugins/wp-site-bridge-migration/
   ```

### Solution 4: Check Server Logs

Check your server error logs for more details:
- Apache: `/var/log/apache2/error.log`
- Nginx: `/var/log/nginx/error.log`
- PHP: Check `php.ini` for `error_log` location

### Common Issues

1. **Incomplete ZIP extraction**: Some FTP clients or hosting control panels may not extract ZIP files completely. Try extracting manually.

2. **File permissions**: Ensure files are readable by the web server user.

3. **Old ZIP file**: Make sure you're using the latest ZIP file from the repository.

4. **WordPress auto-renaming**: If WordPress adds `-1` to the folder name, ensure all files are still in the correct structure.

### Verification

After installation, verify the plugin structure:

```bash
# Check if all required files exist
ls -la wp-content/plugins/wp-site-bridge-migration/includes/

# Should show:
# class-core.php
# class-admin.php
# class-api.php
# class-migrator.php
```

If any file is missing, reinstall the plugin using a fresh ZIP file.

