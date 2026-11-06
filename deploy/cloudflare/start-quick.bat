@echo off
REM Tunnel cepat tanpa akun/domain. URL acak *.trycloudflare.com tampil di jendela cloudflared.
REM Pastikan MySQL (XAMPP) sudah menyala.
cd /d "%~dp0\..\.."
start "Laravel" cmd /k php artisan serve --host=127.0.0.1 --port=8000
timeout /t 3 >nul
cloudflared tunnel --url http://localhost:8000
