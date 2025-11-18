# Installation Fix Guide

## Problem: "Required file not found" Error

If you see this error:
```
WP Site Bridge Migration: Required file not found: includes/class-core.php in /var/www/html/wp-content/plugins/wp-site-bridge-migration/
```

But your plugin folder is named `wp-site-bridge-migration-1/`, this means WordPress is loading the plugin from the OLD folder.

## Solution: Complete Clean Installation

### Step 1: Delete ALL Plugin Folders

**IMPORTANT:** Delete BOTH folders if they exist:

```bash
# Delete old folder (without -1)
rm -rf /var/www/html/wp-content/plugins/wp-site-bridge-migration/

# Delete new folder (with -1)
rm -rf /var/www/html/wp-content/plugins/wp-site-bridge-migration-1/
```

Or via File Manager:
- Delete `wp-content/plugins/wp-site-bridge-migration/` (if exists)
- Delete `wp-content/plugins/wp-site-bridge-migration-1/` (if exists)

### Step 2: Verify Folders Are Deleted

```bash
ls -la /var/www/html/wp-content/plugins/ | grep wp-site-bridge
```

Should return nothing (no wp-site-bridge folders).

### Step 3: Install Fresh

**Option A: Via WordPress Admin (Recommended)**
1. Go to WordPress Admin → Plugins → Add New
2. Click "Upload Plugin"
3. Choose the ZIP file: `wp-site-bridge-migration.zip`
4. Click "Install Now"
5. WordPress will create folder `wp-site-bridge-migration/` automatically

**Option B: Manual Upload**
1. Upload ZIP file to server
2. Extract to: `/var/www/html/wp-content/plugins/wp-site-bridge-migration/`
3. **Important:** Extract directly to `wp-site-bridge-migration/`, NOT `wp-site-bridge-migration-1/`

### Step 4: Verify Structure

After installation, verify the structure:

```bash
ls -la /var/www/html/wp-content/plugins/wp-site-bridge-migration/
```

Should show:
```
wp-site-bridge-migration.php  ← Main file
includes/
  ├── class-core.php
  ├── class-admin.php
  ├── class-api.php
  └── class-migrator.php
templates/
assets/
readme.txt
```

### Step 5: Check File Permissions

```bash
# Set correct permissions
chmod -R 755 /var/www/html/wp-content/plugins/wp-site-bridge-migration/
chmod -R 644 /var/www/html/wp-content/plugins/wp-site-bridge-migration/*.php
chmod -R 644 /var/www/html/wp-content/plugins/wp-site-bridge-migration/includes/*.php
```

### Step 6: Activate Plugin

Go to WordPress Admin → Plugins and activate "WP Site Bridge Migration"

## Why This Happens

WordPress detects plugins by:
1. Scanning `wp-content/plugins/` folder
2. Looking for PHP files with plugin headers
3. Using the **folder name** where the main PHP file is located

If you have:
- `wp-site-bridge-migration/` (old, incomplete)
- `wp-site-bridge-migration-1/` (new, complete)

WordPress will load from `wp-site-bridge-migration/` first (alphabetically), causing the error.

## Prevention

Always delete the old plugin folder completely before installing a new version.

