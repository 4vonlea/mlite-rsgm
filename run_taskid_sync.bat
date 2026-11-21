@echo off
title mLITE - Auto-Sync Task ID BPJS Antrean
cd /d "%~dp0"
echo Menjalankan Sinkronisasi Task ID BPJS Antrean...
"D:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe" cron_taskid.php %*

