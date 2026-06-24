@echo off
echo ===================================================
echo   Basic Bidall - Quick Setup Script
echo ===================================================
echo.

REM Check if composer is installed
where composer >nul 2>nul
if %errorlevel% neq 0 (
    echo [ERROR] Composer not found!
    echo Please install Composer or use Laragon
    echo Download: https://getcomposer.org or https://laragon.org
    pause
    exit /b 1
)

REM Check if npm is installed
where npm >nul 2>nul
if %errorlevel% neq 0 (
    echo [ERROR] NPM not found!
    echo Please install Node.js from https://nodejs.org
    pause
    exit /b 1
)

echo [1/6] Installing PHP dependencies...
call composer install
if %errorlevel% neq 0 (
    echo [ERROR] Composer install failed
    pause
    exit /b 1
)
echo.

echo [2/6] Installing Node dependencies...
call npm install
if %errorlevel% neq 0 (
    echo [ERROR] NPM install failed
    pause
    exit /b 1
)
echo.

echo [3/6] Setting up environment file...
if not exist .env (
    copy .env.example .env
    echo .env file created from .env.example
) else (
    echo .env file already exists, skipping...
)
echo.

echo [4/6] Generating application key...
php artisan key:generate
echo.

echo [5/6] Creating storage link...
php artisan storage:link
echo.

echo [6/6] Optimizing...
php artisan config:clear
php artisan route:clear
php artisan cache:clear
echo.

echo ===================================================
echo   Setup Complete!
echo ===================================================
echo.
echo NEXT STEPS:
echo.
echo 1. Edit .env file with your database credentials
echo    - Set DB_DATABASE, DB_USERNAME, DB_PASSWORD
echo.
echo 2. Create database 'basic_bidall' in MySQL
echo.
echo 3. Run migrations:
echo    php artisan migrate
echo.
echo 4. Seed test data (optional):
echo    php artisan db:seed
echo.
echo 5. Start development servers:
echo    Terminal 1: php artisan serve
echo    Terminal 2: npm run dev
echo.
echo 6. Open http://localhost:8000 in your browser
echo.
echo See TESTING_GUIDE.md for complete testing instructions!
echo ===================================================
pause
