@echo off
chcp 65001 >nul
title Diagnostic LASEV API
echo ============================================
echo   DIAGNOSTIC - Pourquoi l'API ne demarre pas
echo ============================================
echo.

cd /d "%~dp0"

echo [1] Repertoire actuel :
echo     %CD%
echo.

echo [2] Recherche de PHP (WAMP)...
set PHP_EXE=
for %%d in (8.5.0 8.4.0 8.3.0 8.2.0 8.1.0) do (
    if exist "c:\wamp64\bin\php\php%%d\php.exe" (
        set PHP_EXE=c:\wamp64\bin\php\php%%d\php.exe
        goto :found
    )
)
:found
if "%PHP_EXE%"=="" (
    echo     ERREUR: Aucun PHP trouve dans c:\wamp64\bin\php\
    echo     Verifiez que WAMP est installe et que le dossier bin\php existe.
    echo.
    where php 2>nul && echo     "php" trouve dans le PATH : && where php
) else (
    echo     OK: %PHP_EXE%
    "%PHP_EXE%" -v 2>&1
)
echo.

echo [3] Fichier artisan present ?
if exist "artisan" (echo     OK: artisan existe) else (echo     ERREUR: artisan absent)
echo.

echo [4] Dossier vendor (Composer) ?
if exist "vendor\autoload.php" (echo     OK: vendor existe) else (echo     ERREUR: lancez composer install)
echo.

echo [5] Fichier .env ?
if exist ".env" (echo     OK: .env existe) else (echo     ERREUR: copiez .env.example vers .env)
echo.

echo [6] Tentative: php artisan --version
if not "%PHP_EXE%"=="" (
    "%PHP_EXE%" artisan --version 2>&1
    if errorlevel 1 (
        echo     ERREUR lors de l execution. Ci-dessus le message Laravel/PHP.
    )
) else (
    php artisan --version 2>&1
    if errorlevel 1 echo     Si "php" inconnu, utilisez le script avec WAMP installe.
)
echo.

echo [7] Port 8000 utilise ?
netstat -ano | findstr ":8000" 2>nul && echo     Attention: le port 8000 est peut-etre deja utilise. || echo     Le port 8000 est libre.
echo.

echo ============================================
echo   Pour demarrer l API apres correction:
echo   Double-clic sur demarrer-api.bat
echo   Ou en console: "%PHP_EXE%" artisan serve
echo ============================================
pause
