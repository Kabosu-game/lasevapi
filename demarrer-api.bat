@echo off
title LASEV API
cd /d "%~dp0"

REM Chercher PHP : d'abord les versions connues, puis n'importe quel php.exe dans WAMP
set PHP_EXE=php
if exist "c:\wamp64\bin\php\php8.5.0\php.exe" set PHP_EXE=c:\wamp64\bin\php\php8.5.0\php.exe
if "%PHP_EXE%"=="php" if exist "c:\wamp64\bin\php\php8.4.0\php.exe" set PHP_EXE=c:\wamp64\bin\php\php8.4.0\php.exe
if "%PHP_EXE%"=="php" if exist "c:\wamp64\bin\php\php8.3.0\php.exe" set PHP_EXE=c:\wamp64\bin\php\php8.3.0\php.exe
if "%PHP_EXE%"=="php" if exist "c:\wamp64\bin\php\php8.2.0\php.exe" set PHP_EXE=c:\wamp64\bin\php\php8.2.0\php.exe
if "%PHP_EXE%"=="php" if exist "c:\wamp64\bin\php\php8.1.0\php.exe" set PHP_EXE=c:\wamp64\bin\php\php8.1.0\php.exe
if "%PHP_EXE%"=="php" for /d %%d in ("c:\wamp64\bin\php\php*") do if exist "%%d\php.exe" (set "PHP_EXE=%%d\php.exe" & goto :php_ok)
:php_ok

echo ============================================
echo   LASEV API - Demarrage
echo ============================================
echo.
echo Repertoire : %CD%
echo PHP        : %PHP_EXE%
echo.

if "%PHP_EXE%"=="php" (
    echo ATTENTION: PHP WAMP non trouve. Utilisation de "php" du PATH...
    php -v 2>nul || (
        echo ERREUR: "php" introuvable. Solutions:
        echo   1. Demarrez WAMP et relancez ce script
        echo   2. Ou ajoutez PHP au PATH Windows
        echo   3. Lancez verifier-env.bat pour le diagnostic
        pause
        exit /b 1
    )
) else (
    "%PHP_EXE%" -v 2>&1
)

echo.
if not exist "vendor\autoload.php" (
    echo ERREUR: Dossier vendor manquant. Lancez: composer install
    pause
    exit /b 1
)
if not exist ".env" (
    echo ERREUR: Fichier .env manquant. Copiez .env.example en .env
    pause
    exit /b 1
)

echo API disponible sur : http://127.0.0.1:8000
echo Admin            : http://127.0.0.1:8000/admin/login
echo.
echo Arret : Ctrl+C puis fermer la fenetre.
echo ============================================
echo.

"%PHP_EXE%" artisan serve --host=0.0.0.0

echo.
echo Le serveur s est arrete.
pause
