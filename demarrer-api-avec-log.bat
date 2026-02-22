@echo off
title LASEV API (avec log)
cd /d "%~dp0"

set PHP_EXE=php
if exist "c:\wamp64\bin\php\php8.5.0\php.exe" set PHP_EXE=c:\wamp64\bin\php\php8.5.0\php.exe
if "%PHP_EXE%"=="php" if exist "c:\wamp64\bin\php\php8.4.0\php.exe" set PHP_EXE=c:\wamp64\bin\php\php8.4.0\php.exe
if "%PHP_EXE%"=="php" if exist "c:\wamp64\bin\php\php8.3.0\php.exe" set PHP_EXE=c:\wamp64\bin\php\php8.3.0\php.exe
if "%PHP_EXE%"=="php" if exist "c:\wamp64\bin\php\php8.2.0\php.exe" set PHP_EXE=c:\wamp64\bin\php\php8.2.0\php.exe
if "%PHP_EXE%"=="php" for /d %%d in ("c:\wamp64\bin\php\php*") do if exist "%%d\php.exe" (set "PHP_EXE=%%d\php.exe" & goto :php_ok)
:php_ok

echo Demarrage API... Log : api-demarrage-log.txt
echo Repertoire: %CD% > api-demarrage-log.txt
echo PHP: %PHP_EXE% >> api-demarrage-log.txt
echo. >> api-demarrage-log.txt

"%PHP_EXE%" artisan serve --host=0.0.0.0 >> api-demarrage-log.txt 2>&1

echo.
type api-demarrage-log.txt
echo.
echo Log complet enregistre dans : %CD%\api-demarrage-log.txt
pause
