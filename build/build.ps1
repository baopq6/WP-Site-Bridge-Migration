#Requires -Version 5.1

<#
.SYNOPSIS
    Build script for WP Site Bridge Migration Plugin
    Creates a clean, production-ready ZIP file

.DESCRIPTION
    This script creates a ZIP file containing only the files needed for plugin
    installation, excluding development files, Git files, IDE files, and other
    unnecessary files.

.PARAMETER OutputName
    Custom output filename (default: wp-site-bridge-migration.zip)

.PARAMETER ShowDetails
    Show detailed output during the build process

.EXAMPLE
    .\build.ps1
    Creates wp-site-bridge-migration.zip in the dist/ folder

.EXAMPLE
    .\build.ps1 -OutputName "custom-name.zip"
    Creates a ZIP file with custom name

.EXAMPLE
    .\build.ps1 -ShowDetails
    Shows detailed output during build
#>

[CmdletBinding()]
param(
    [Parameter(Mandatory=$false)]
    [string]$OutputName = "wp-site-bridge-migration.zip",
    
    [Parameter(Mandatory=$false)]
    [switch]$ShowDetails
)

# Error handling
$ErrorActionPreference = "Stop"

# Get script and project directories
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot = Split-Path -Parent $ScriptDir
$DistDir = Join-Path $ProjectRoot "dist"
$OutputPath = Join-Path $DistDir $OutputName

# Display banner
Write-Host ""
Write-Host "================================================================================" -ForegroundColor Cyan
Write-Host "WP Site Bridge Migration - Build Script" -ForegroundColor Green
Write-Host "================================================================================" -ForegroundColor Cyan
Write-Host ""

# Ensure dist directory exists
if (-not (Test-Path $DistDir)) {
    New-Item -ItemType Directory -Path $DistDir -Force | Out-Null
    Write-Host "Created output directory: dist/" -ForegroundColor Yellow
}

# Remove old ZIP if exists
if (Test-Path $OutputPath) {
    Write-Host "Removing existing ZIP file: $OutputName" -ForegroundColor Yellow
    Remove-Item $OutputPath -Force
}

Write-Host "Building plugin package: " -NoNewline -ForegroundColor Cyan
Write-Host "$OutputName" -ForegroundColor Green
Write-Host ""

# Create temporary directory
$TempDir = Join-Path $env:TEMP "wpsbm-build-$(Get-Random)"
$PluginDir = Join-Path $TempDir "wp-site-bridge-migration"

try {
    New-Item -ItemType Directory -Path $PluginDir -Force | Out-Null
    
    if ($ShowDetails) {
        Write-Host "Temporary directory: $TempDir" -ForegroundColor Yellow
    }
    
    # Define exclusion patterns
    $ExcludePatterns = @(
        ".git",
        ".github",
        ".vscode",
        ".idea",
        ".DS_Store",
        "__MACOSX",
        "Thumbs.db",
        "node_modules",
        "vendor",
        "build",
        "dist",
        "*.zip",
        "*.log",
        "composer.json",
        "composer.lock",
        "package.json",
        "package-lock.json",
        "yarn.lock",
        "README.md",
        "IMPROVEMENTS.md",
        "SECURITY_AUDIT.md",
        "CODE_AUDIT_REPORT.md",
        "INSTALLATION_TROUBLESHOOTING.md",
        "build.sh",
        "build.ps1",
        "build.bat",
        ".gitignore",
        ".editorconfig",
        ".phpcs.xml",
        "phpunit.xml",
        "tests"
    )
    
    Write-Host "Copying files (excluding development files)..." -ForegroundColor Cyan
    
    # Copy files with exclusions
    Get-ChildItem -Path $ProjectRoot -Recurse -File | ForEach-Object {
        $RelativePath = $_.FullName.Substring($ProjectRoot.Length + 1)
        $ShouldExclude = $false
        
        # Check if file matches any exclusion pattern
        foreach ($Pattern in $ExcludePatterns) {
            if ($Pattern -like "*.*") {
                # File extension pattern
                if ($_.Name -like $Pattern) {
                    $ShouldExclude = $true
                    break
                }
            } else {
                # Directory or exact name pattern
                if ($RelativePath -like "$Pattern*" -or $_.Name -eq $Pattern) {
                    $ShouldExclude = $true
                    break
                }
            }
        }
        
        if (-not $ShouldExclude) {
            $DestPath = Join-Path $PluginDir $RelativePath
            $DestDir = Split-Path -Parent $DestPath
            
            if (-not (Test-Path $DestDir)) {
                New-Item -ItemType Directory -Path $DestDir -Force | Out-Null
            }
            
            Copy-Item $_.FullName -Destination $DestPath -Force
            
            if ($ShowDetails) {
                Write-Host "  [OK] $RelativePath" -ForegroundColor Gray
            }
        } elseif ($ShowDetails) {
            Write-Host "  [SKIP] $RelativePath (excluded)" -ForegroundColor DarkGray
        }
    }
    
    # Create ZIP file
    Write-Host "Creating ZIP archive..." -ForegroundColor Cyan
    
    # Remove existing ZIP if exists
    if (Test-Path $OutputPath) {
        Remove-Item $OutputPath -Force
    }
    
    # Compress to ZIP
    # WordPress expects files at root level, not in a subfolder
    # Use .NET ZipFile to ensure proper path separators (forward slashes)
    $OriginalLocation = Get-Location
    Set-Location $PluginDir
    
    # Use .NET ZipFile for better cross-platform compatibility
    # This ensures forward slashes in paths for Linux compatibility
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    
    # Remove existing ZIP if it exists
    if (Test-Path $OutputPath) {
        Remove-Item $OutputPath -Force
    }
    
    # Create ZIP archive
    $zip = [System.IO.Compression.ZipFile]::Open($OutputPath, [System.IO.Compression.ZipArchiveMode]::Create)
    
    try {
        # Add all files and folders recursively
        Get-ChildItem -Path "." -Recurse -File | ForEach-Object {
            # Get relative path and convert backslashes to forward slashes
            $relativePath = $_.FullName.Replace($PluginDir, '').Replace('\', '/').TrimStart('/')
            
            # Create entry in ZIP
            $entry = $zip.CreateEntry($relativePath)
            
            # Copy file content
            $entryStream = $entry.Open()
            $fileStream = [System.IO.File]::OpenRead($_.FullName)
            $fileStream.CopyTo($entryStream)
            $fileStream.Close()
            $entryStream.Close()
        }
    } finally {
        $zip.Dispose()
    }
    Set-Location $OriginalLocation
    
    # Get file size
    $FileInfo = Get-Item $OutputPath
    $FileSizeMB = [math]::Round($FileInfo.Length / 1MB, 2)
    
    # Success message
    Write-Host ""
    Write-Host "================================================================================" -ForegroundColor Green
    Write-Host "Build completed successfully!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Output file: " -NoNewline -ForegroundColor Cyan
    Write-Host "$OutputPath" -ForegroundColor Green
    Write-Host "File size: " -NoNewline -ForegroundColor Cyan
    Write-Host "$FileSizeMB MB" -ForegroundColor Green
    Write-Host ""
    Write-Host "Plugin package is ready for distribution!" -ForegroundColor Green
    Write-Host "================================================================================" -ForegroundColor Green
    Write-Host ""
    
} catch {
    Write-Host ""
    $errorMsg = $_.Exception.Message
    Write-Host "ERROR: $errorMsg" -ForegroundColor Red
    Write-Host ""
    exit 1
} finally {
    # Clean up temporary directory
    if (Test-Path $TempDir) {
        Remove-Item $TempDir -Recurse -Force -ErrorAction SilentlyContinue
    }
}

