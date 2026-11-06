# UKK SCP sebagai Aplikasi Mobile (PWA) + Cloudflare Tunnel

Aplikasi Laravel ini sudah punya tampilan mobile-first. Perubahan yang ditambahkan:
- **PWA**: `manifest.webmanifest`, `sw.js`, `offline.html`, ikon, dan partial `partials/pwa.blade.php`
  sehingga bisa di-install di layar utama HP (Android/iOS) seperti aplikasi.
- **Trust proxy + HTTPS** (`bootstrap/app.php`, `AppServiceProvider`) agar Laravel benar di belakang Cloudflare.
- **Skrip & konfigurasi tunnel** di folder `deploy/cloudflare/`.

## A. Tunnel cepat (tanpa domain, cocok untuk demo/UKK)
1. Install cloudflared: https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/downloads/
2. Nyalakan MySQL (XAMPP), pastikan `.env` benar, lalu jalankan `composer install` dan `php artisan migrate`.
3. Jalankan `deploy\cloudflare\start-quick.bat` (Windows) atau `./deploy/cloudflare/start-quick.sh`.
4. Salin URL `https://xxxx.trycloudflare.com` dari jendela cloudflared.
5. Isi `APP_URL=` dengan URL itu, set `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, lalu:
   `php artisan config:clear`
6. Buka URL di HP -> menu browser -> **Tambahkan ke layar utama / Install app**.

Catatan: URL tunnel cepat berubah setiap dijalankan, jadi `APP_URL` perlu diperbarui.

## B. Tunnel permanen (punya domain di Cloudflare)
```
cloudflared tunnel login
cloudflared tunnel create scp
cloudflared tunnel route dns scp scp.domainanda.com
```
Salin `config.yml.example` ke `~/.cloudflared/config.yml` (isi TUNNEL-ID dan hostname), lalu:
```
php artisan serve --host=127.0.0.1 --port=8000
cloudflared tunnel run scp
```
Agar jalan otomatis: `cloudflared service install`.
Set `APP_URL=https://scp.domainanda.com` (lihat `.env.tunnel.example`).

## Checklist keamanan (aplikasi jadi publik)
- `APP_DEBUG=false` dan `APP_ENV=production`.
- Jangan upload `.env` ke GitHub; beri password pada user MySQL dan jangan pakai `root` tanpa password.
- Folder `public/uploads` dan `storage/app/jurnal_draft` berisi data siswa; jangan ikut di-commit.
- Opsional: batasi akses dengan **Cloudflare Access** (Zero Trust) agar hanya email sekolah yang bisa masuk.

## Pemecahan masalah
- Tampilan tanpa CSS / redirect ke `http://localhost` -> `APP_URL` belum diisi https, lalu `php artisan config:clear`.
- Error 419 (page expired) saat login -> pastikan `SESSION_SECURE_COOKIE=true` hanya dipakai saat diakses via https, dan hapus cookie lama.
- Tombol install tidak muncul -> harus diakses lewat https (tunnel sudah https), buka sekali lalu refresh.
- Setelah mengubah CSS/JS, naikkan `VERSION` di `public/sw.js`.
