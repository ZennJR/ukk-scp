<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, Helvetica, sans-serif; color: #1f2937; line-height: 1.6;">
    <h2 style="margin-bottom: 4px;">Kode OTP Reset Password</h2>
    <p>Halo {{ $nama }},</p>
    <p>Gunakan kode berikut untuk melanjutkan reset password akun UKK SCP (SMK Negeri 1 Bantul):</p>
    <p style="margin: 24px 0; font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #2563eb;">{{ $otp }}</p>
    <p>Kode berlaku selama {{ $menit }} menit dan hanya bisa dipakai sekali.
       <strong>Jangan bagikan kode ini kepada siapa pun.</strong>
       Jika Anda tidak merasa meminta reset password, abaikan email ini &mdash; password Anda tidak berubah.</p>
</body>
</html>
