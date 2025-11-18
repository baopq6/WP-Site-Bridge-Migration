@echo off
REM Build Script for WP Site Bridge Migration Plugin (Windows Batch)
REM Creates a clean, production-ready ZIP file

setlocal enabledelayedexpansion

REM Default values
set "PLUGIN_SLUG=wp-site-bridge-migration"
set "ZIP_NAME=%PLUGIN_SLUG%.zip"
set "SCRIPT_DIR=%~dp0"
set "PROJECT_ROOT=%SCRIPT_DIR%.."
set "DIST_DIR=%PROJECT_ROOT%\dist"

REM Parse command line arguments
if "%1"=="-o" (
    set "ZIP_NAME=%2"
    shift
    shift
)
if "%1"=="--output" (
    set "ZIP_NAME=%2"
    shift
    shift
)

cd /d "%PROJECT_ROOT%"

REM Ensure dist directory exists
if not exist "%DIST_DIR%" (
    mkdir "%DIST_DIR%"
    echo 📁 Created output directory: dist/
)

REM Display banner
echo.
echo ════════════════════════════════════════════════════════════════════════════
echo 🚀 WP Site Bridge Migration - Build Script
echo ════════════════════════════════════════════════════════════════════════════
echo.

REM Remove old ZIP if exists
if exist "%DIST_DIR%\%ZIP_NAME%" (
    echo ⚠️  Removing existing ZIP file: %ZIP_NAME%
    del /f /q "%DIST_DIR%\%ZIP_NAME%"
)

echo 📦 Building plugin package: %ZIP_NAME%
echo.

REM Check if PowerShell is available (preferred method)
where powershell >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    echo 📋 Using PowerShell for better file handling...
    powershell -ExecutionPolicy Bypass -File "%SCRIPT_DIR%build.ps1" -OutputName "%ZIP_NAME%"
    goto :end
)

REM Fallback: Check if 7-Zip is available
where 7z >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    echo 📋 Using 7-Zip...
    
    REM Create temporary directory
    set "TEMP_DIR=%TEMP%\wpsbm-build-%RANDOM%"
    set "PLUGIN_DIR=%TEMP_DIR%\%PLUGIN_SLUG%"
    mkdir "%PLUGIN_DIR%"
    
    echo 📋 Copying files (excluding development files)...
    
    REM Copy files manually (basic exclusion)
    xcopy /E /I /Y /EXCLUDE:"%SCRIPT_DIR%exclude.txt" "%PROJECT_ROOT%\*" "%PLUGIN_DIR%\" >nul 2>&1
    
    REM Create exclude list if it doesn't exist
    if not exist "%SCRIPT_DIR%exclude.txt" (
        (
            echo .git
            echo .github
            echo .vscode
            echo .idea
            echo .DS_Store
            echo __MACOSX
            echo Thumbs.db
            echo node_modules
            echo vendor
            echo build
            echo *.zip
            echo *.log
            echo composer.json
            echo composer.lock
            echo README.md
            echo IMPROVEMENTS.md
            echo SECURITY_AUDIT.md
            echo CODE_AUDIT_REPORT.md
        ) > "%SCRIPT_DIR%exclude.txt"
    )
    
    echo 🗜️  Creating ZIP archive...
    cd /d "%TEMP_DIR%"
    7z a -tzip "%DIST_DIR%\%ZIP_NAME%" "%PLUGIN_SLUG%\*" -mx=9 >nul
    
    REM Clean up
    rmdir /s /q "%TEMP_DIR%"
    cd /d "%PROJECT_ROOT%"
    
    goto :success
)

REM Final fallback: Use built-in PowerShell Compress-Archive
echo 📋 Using PowerShell Compress-Archive...
powershell -Command "$ErrorActionPreference='Stop'; $ProjectRoot='%PROJECT_ROOT%'; $DistDir='%DIST_DIR%'; $OutputName='%ZIP_NAME%'; if (-not (Test-Path $DistDir)) { New-Item -ItemType Directory -Path $DistDir -Force | Out-Null }; $TempDir=$env:TEMP + '\wpsbm-build-' + (Get-Random); $PluginDir=$TempDir + '\wp-site-bridge-migration'; New-Item -ItemType Directory -Path $PluginDir -Force | Out-Null; Get-ChildItem -Path $ProjectRoot -Recurse -File | Where-Object { $_.FullName -notmatch '\.git|\.github|\.vscode|\.idea|\.DS_Store|__MACOSX|Thumbs\.db|node_modules|vendor|build|\.zip$|\.log$|composer\.(json|lock)|package.*\.json|yarn\.lock|README\.md|IMPROVEMENTS\.md|SECURITY_AUDIT\.md|CODE_AUDIT_REPORT\.md|build\.(sh|ps1|bat)|\.gitignore' } | ForEach-Object { $DestPath = $PluginDir + $_.FullName.Substring($ProjectRoot.Length); $DestDir = Split-Path -Parent $DestPath; if (-not (Test-Path $DestDir)) { New-Item -ItemType Directory -Path $DestDir -Force | Out-Null }; Copy-Item $_.FullName -Destination $DestPath -Force }; Compress-Archive -Path ($PluginDir + '\*') -DestinationPath ($DistDir + '\' + $OutputName) -CompressionLevel Optimal -Force; Remove-Item $TempDir -Recurse -Force"

if %ERRORLEVEL% EQU 0 (
    goto :success
)

echo.
echo ❌ Error: Could not find a suitable compression tool.
echo.
echo Please install one of the following:
echo   - PowerShell 5.1+ (usually pre-installed on Windows 10+)
echo   - 7-Zip (download from https://www.7-zip.org/)
echo.
echo Alternatively, use the PowerShell script directly:
echo   powershell -ExecutionPolicy Bypass -File build\build.ps1
echo.
exit /b 1

:success
echo.
echo ════════════════════════════════════════════════════════════════════════════
echo ✅ Build completed successfully!
echo.
echo 📦 Output file: %DIST_DIR%\%ZIP_NAME%
echo.
echo ✨ Plugin package is ready for distribution!
echo ════════════════════════════════════════════════════════════════════════════
echo.

:end
endlocal

