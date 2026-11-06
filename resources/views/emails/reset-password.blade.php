<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, Helvetica, sans-serif; color: #1f2937; line-height: 1.6;">
    <h2 style="margin-bottom: 4px;">Reset Password UKK SCP</h2>
    <p>Halo {{ $nama }},</p>
    <p>Kami menerima permintaan untuk mengatur ulang password akun Anda di UKK SCP (SMK Negeri 1 Bantul).
       Klik tombol di bawah untuk membuat password baru:</p>
    <p style="margin: 24px 0;">
        <a href="{{ $url }}" style="background:#2563eb;color:#ffffff;padding:12px 22px;border-radius:8px;text-decoration:none;font-weight:bold;">Buat Password Baru</a>
    </p>
    <p>Atau salin tautan ini ke browser:<br><a href="{{ $url }}">{{ $url }}</a></p>
    <p>Tautan berlaku selama {{ $menit }} menit dan hanya bisa dipakai sekali.
       Jika Anda tidak merasa meminta reset password, abaikan email ini &mdash; password Anda tidak berubah.</p>
</body>
</html>
