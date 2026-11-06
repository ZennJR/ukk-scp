<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, Helvetica, sans-serif; color: #1f2937; line-height: 1.6;">
    <h2 style="margin-bottom: 4px;">Konfirmasi Email Baru</h2>
    <p>Halo {{ $nama }},</p>
    <p>Email ini diajukan sebagai email baru untuk akun UKK SCP (SMK Negeri 1 Bantul). Masukkan kode berikut untuk mengonfirmasi:</p>
    <p style="margin: 24px 0; font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #2563eb;">{{ $otp }}</p>
    <p>Kode berlaku selama {{ $menit }} menit dan hanya bisa dipakai sekali.
       <strong>Jangan bagikan kode ini kepada siapa pun.</strong>
       Jika Anda tidak merasa mengajukan perubahan email, abaikan email ini &mdash; tidak ada yang berubah.</p>
</body>
</html>
