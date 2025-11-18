# Build Scripts - WP Site Bridge Migration

This directory contains build scripts to create a clean, production-ready ZIP file of the plugin.

## 📦 What These Scripts Do

The build scripts create a ZIP file containing only the files needed for plugin installation, excluding:
- Git files (`.git/`, `.gitignore`, `.github/`)
- IDE files (`.vscode/`, `.idea/`)
- OS files (`.DS_Store`, `Thumbs.db`, `__MACOSX`)
- Development files (`composer.json`, `composer.lock`, `node_modules/`)
- Documentation files (`README.md`, `IMPROVEMENTS.md`, `SECURITY_AUDIT.md`, etc.)
- Build artifacts (`*.zip`, `build/`)

## 🚀 Quick Start

### For Linux/Mac Users

```bash
cd build
chmod +x build.sh
./build.sh
```

The ZIP file will be created in the project root directory.

### For Windows Users

**Option 1: PowerShell (Recommended)**
```powershell
cd build
.\build.ps1
```

**Option 2: Batch Script**
```cmd
cd build
build.bat
```

The ZIP file will be created in the project root directory.

## 📋 Requirements

- **Linux/Mac**: Bash shell (usually pre-installed)
- **Windows**: PowerShell 5.1+ (pre-installed on Windows 10+) or Command Prompt

## 📁 Output

The build script creates a file named `wp-site-bridge-migration.zip` in the project root directory.

## 🔍 What Gets Included

✅ **Included:**
- Main plugin file (`wp-site-bridge-migration.php`)
- All PHP classes (`includes/`)
- Templates (`templates/`)
- Assets (`assets/`)
- WordPress.org readme (`readme.txt`)

❌ **Excluded:**
- Git repository files
- IDE configuration files
- OS-specific files
- Development documentation
- Composer files (unless vendor/ exists)
- Build scripts themselves
- Any existing ZIP files

## 🛠️ Advanced Usage

### Custom Output Name

**Linux/Mac:**
```bash
./build.sh -o custom-name.zip
```

**Windows PowerShell:**
```powershell
.\build.ps1 -OutputName "custom-name.zip"
```

### Verbose Mode

**Linux/Mac:**
```bash
./build.sh -v
```

**Windows PowerShell:**
```powershell
.\build.ps1 -Verbose
```

## 📝 Notes

- The scripts automatically detect the project root directory
- Existing ZIP files with the same name will be overwritten
- The scripts preserve the plugin directory structure
- All file permissions are maintained

## 🐛 Troubleshooting

### Permission Denied (Linux/Mac)
```bash
chmod +x build.sh
```

### Execution Policy Error (Windows PowerShell)
```powershell
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
```

### ZIP File Not Created
- Check that you have write permissions in the project root
- Ensure ZIP utility is installed (usually pre-installed)
- Check the script output for error messages

## 📄 License

Same as the plugin: GPL v2 or later

