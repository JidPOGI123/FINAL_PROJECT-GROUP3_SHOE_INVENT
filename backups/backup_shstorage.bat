@echo off

:: ─────────────────────────────────────────
::   ShStorage - Auto Database Backup
:: ─────────────────────────────────────────

set DB_USER=root
set DB_PASS=
set DB_NAME=shstorage
set BACKUP_DIR=C:\xampp\htdocs\ShStorage\backups\files

if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"

:: ─── Timestamp (Windows 11 compatible) ───
set YEAR=%date:~10,4%
set MONTH=%date:~4,2%
set DAY=%date:~7,2%
set HOUR=%time:~0,2%
set MIN=%time:~3,2%

:: Fix hour if single digit (e.g. " 9" → "09")
if "%HOUR:~0,1%"==" " set HOUR=0%HOUR:~1,1%

set TIMESTAMP=%YEAR%-%MONTH%-%DAY%_%HOUR%-%MIN%

:: ─── Run Backup ───────────────────────────
set FILENAME=%BACKUP_DIR%\shstorage_%TIMESTAMP%.sql

"C:\xampp\mysql\bin\mysqldump.exe" -u %DB_USER% %DB_NAME% > "%FILENAME%"

echo [OK] Backup saved: %FILENAME%
pause