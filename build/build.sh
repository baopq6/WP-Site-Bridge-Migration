#!/bin/bash

###############################################################################
# Build Script for WP Site Bridge Migration Plugin
# Creates a clean, production-ready ZIP file
#
# Usage: ./build.sh [options]
# Options:
#   -o, --output NAME    Custom output filename (default: wp-site-bridge-migration.zip)
#   -v, --verbose        Show detailed output
#   -h, --help           Show this help message
###############################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default values
PLUGIN_SLUG="wp-site-bridge-migration"
ZIP_NAME="${PLUGIN_SLUG}.zip"
VERBOSE=false
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
DIST_DIR="$PROJECT_ROOT/dist"

# Parse command line arguments
while [[ $# -gt 0 ]]; do
	case $1 in
		-o|--output)
			ZIP_NAME="$2"
			shift 2
			;;
		-v|--verbose)
			VERBOSE=true
			shift
			;;
		-h|--help)
			echo "Usage: $0 [options]"
			echo "Options:"
			echo "  -o, --output NAME    Custom output filename"
			echo "  -v, --verbose        Show detailed output"
			echo "  -h, --help           Show this help message"
			exit 0
			;;
		*)
			echo -e "${RED}Unknown option: $1${NC}"
			exit 1
			;;
	esac
done

# Change to project root
cd "$PROJECT_ROOT"

# Check if zip command is available
if ! command -v zip &> /dev/null; then
	echo -e "${RED}Error: 'zip' command not found. Please install it first.${NC}"
	echo "On Ubuntu/Debian: sudo apt-get install zip"
	echo "On macOS: zip is usually pre-installed"
	exit 1
fi

# Display banner
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}🚀 WP Site Bridge Migration - Build Script${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# Ensure dist directory exists
if [ ! -d "$DIST_DIR" ]; then
	mkdir -p "$DIST_DIR"
	echo -e "${YELLOW}📁 Created output directory: dist/${NC}"
fi

# Remove old ZIP if exists
if [ -f "$DIST_DIR/$ZIP_NAME" ]; then
	echo -e "${YELLOW}⚠️  Removing existing ZIP file: $ZIP_NAME${NC}"
	rm -f "$DIST_DIR/$ZIP_NAME"
fi

echo -e "${BLUE}📦 Building plugin package: ${GREEN}$ZIP_NAME${NC}"
echo ""

# Create temporary directory for building
TEMP_DIR=$(mktemp -d)
PLUGIN_DIR="$TEMP_DIR/$PLUGIN_SLUG"

if [ "$VERBOSE" = true ]; then
	echo -e "${YELLOW}📂 Temporary directory: $TEMP_DIR${NC}"
fi

# Create plugin directory structure
mkdir -p "$PLUGIN_DIR"

# Copy files with exclusions
echo -e "${BLUE}📋 Copying files (excluding development files)...${NC}"

# Use rsync if available (better for exclusions), otherwise use find + cp
if command -v rsync &> /dev/null; then
	rsync -av \
		--exclude='.git' \
		--exclude='.github' \
		--exclude='.vscode' \
		--exclude='.idea' \
		--exclude='.DS_Store' \
		--exclude='__MACOSX' \
		--exclude='Thumbs.db' \
		--exclude='node_modules' \
		--exclude='vendor' \
		--exclude='build' \
		--exclude='*.zip' \
		--exclude='*.log' \
		--exclude='composer.json' \
		--exclude='composer.lock' \
		--exclude='package.json' \
		--exclude='package-lock.json' \
		--exclude='yarn.lock' \
		--exclude='README.md' \
		--exclude='IMPROVEMENTS.md' \
		--exclude='SECURITY_AUDIT.md' \
		--exclude='CODE_AUDIT_REPORT.md' \
		--exclude='build.sh' \
		--exclude='build.ps1' \
		--exclude='build.bat' \
		--exclude='.gitignore' \
		--exclude='.editorconfig' \
		--exclude='.phpcs.xml' \
		--exclude='phpunit.xml' \
		--exclude='tests' \
		"$PROJECT_ROOT/" "$PLUGIN_DIR/" \
		--exclude='build/'
else
	# Fallback: use find
	find "$PROJECT_ROOT" -type f \
		! -path "*/\.git/*" \
		! -path "*/.github/*" \
		! -path "*/.vscode/*" \
		! -path "*/.idea/*" \
		! -name ".DS_Store" \
		! -name "Thumbs.db" \
		! -name "__MACOSX" \
		! -path "*/node_modules/*" \
		! -path "*/vendor/*" \
		! -path "*/build/*" \
		! -name "*.zip" \
		! -name "*.log" \
		! -name "composer.json" \
		! -name "composer.lock" \
		! -name "package.json" \
		! -name "package-lock.json" \
		! -name "yarn.lock" \
		! -name "README.md" \
		! -name "IMPROVEMENTS.md" \
		! -name "SECURITY_AUDIT.md" \
		! -name "CODE_AUDIT_REPORT.md" \
		! -name "build.sh" \
		! -name "build.ps1" \
		! -name "build.bat" \
		! -name ".gitignore" \
		! -name ".editorconfig" \
		! -name ".phpcs.xml" \
		! -name "phpunit.xml" \
		! -path "*/tests/*" \
		-exec cp --parents {} "$TEMP_DIR/" \;
fi

# Create ZIP file
echo -e "${BLUE}🗜️  Creating ZIP archive...${NC}"
cd "$TEMP_DIR"
zip -r -q "$DIST_DIR/$ZIP_NAME" "$PLUGIN_SLUG"

# Clean up temporary directory
rm -rf "$TEMP_DIR"

# Get file size
if command -v stat &> /dev/null; then
	if [[ "$OSTYPE" == "darwin"* ]]; then
		# macOS
		FILE_SIZE=$(stat -f%z "$DIST_DIR/$ZIP_NAME" 2>/dev/null || echo "0")
	else
		# Linux
		FILE_SIZE=$(stat -c%s "$DIST_DIR/$ZIP_NAME" 2>/dev/null || echo "0")
	fi
	FILE_SIZE_MB=$(echo "scale=2; $FILE_SIZE / 1024 / 1024" | bc 2>/dev/null || echo "0")
else
	FILE_SIZE_MB="N/A"
fi

# Success message
echo ""
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}✅ Build completed successfully!${NC}"
echo ""
echo -e "${BLUE}📦 Output file: ${GREEN}$DIST_DIR/$ZIP_NAME${NC}"
if [ "$FILE_SIZE_MB" != "0" ] && [ "$FILE_SIZE_MB" != "N/A" ]; then
	echo -e "${BLUE}📊 File size: ${GREEN}${FILE_SIZE_MB} MB${NC}"
fi
echo ""
echo -e "${GREEN}✨ Plugin package is ready for distribution!${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

