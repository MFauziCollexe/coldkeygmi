@echo off
setlocal

set "SCRIPT_DIR=%~dp0"
for %%I in ("%SCRIPT_DIR%..\..") do set "PROJECT_PATH=%%~fI"

if not "%PHP_PATH%"=="" goto php_defined
if exist "%PROJECT_PATH%\php\php.exe" set "PHP_PATH=%PROJECT_PATH%\php\php.exe"
if exist "%PROJECT_PATH%\..\php\php.exe" set "PHP_PATH=%PROJECT_PATH%\..\php\php.exe"
if not "%PHP_PATH%"=="" goto php_defined
set "PHP_PATH=php"

:php_defined
set "LOG_PATH=%PROJECT_PATH%\storage\logs\scheduler.log"

if /I "%PHP_PATH%"=="php" (
  where php >nul 2>nul
  if errorlevel 1 (
    echo PHP executable not found in PATH. Set PHP_PATH before running this script.
    exit /b 1
  )
) else (
  if not exist "%PHP_PATH%" (
    echo PHP executable not found at "%PHP_PATH%".
    exit /b 1
  )
)

if not exist "%PROJECT_PATH%\artisan" (
  echo Laravel artisan file not found at "%PROJECT_PATH%\artisan".
  exit /b 1
)

if not exist "%PROJECT_PATH%\storage\logs" (
  mkdir "%PROJECT_PATH%\storage\logs"
)

pushd "%PROJECT_PATH%"
"%PHP_PATH%" artisan schedule:run >> "%LOG_PATH%" 2>&1
set "EXIT_CODE=%ERRORLEVEL%"
popd

exit /b %EXIT_CODE%
