<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use App\Support\Identitas;
use App\Support\Notifier;
use App\Support\ResetPassword;

/**
 * Lupa password untuk SEMUA role (Waka, Guru, Wali Kelas, BK, Siswa).
 *
 * Alur (3 langkah):
 *  1. Pengguna mengisi NISN/NIP + email. Keduanya harus cocok dengan SATU akun aktif.
 *  2. Jika cocok, kode OTP 6 digit dikirim ke email yang tersimpan di data akun tersebut,
 *     lalu pengguna memasukkan OTP.
 *  3. Jika OTP benar, pengguna membuat password baru.
 *
 * Pengamanan: OTP di-hash, kedaluwarsa 5 menit, maksimal 5x salah, jeda kirim ulang 60 detik,
 * pesan error seragam (anti enumerasi akun), token reset terikat ke sesi browser & sekali pakai.
 */
class PasswordResetController extends Controller
{
    // ------------------------------------------------------------------
    // LANGKAH 1: validasi NISN/NIP + email, lalu kirim OTP
    // ------------------------------------------------------------------

    public function showRequestForm()
    {
        return view('auth.lupa-password');
    }

    public function sendLink(Request $request)
    {
        $request->validate([
            'nomor_induk' => 'required|string|max:50',
            'email'       => 'required|email|max:100',
        ], [
            'nomor_induk.required' => 'NISN / NIP wajib diisi.',
            'email.required'       => 'Email wajib diisi.',
            'email.email'          => 'Format email tidak valid.',
        ]);

        Identitas::pastikanKolom();
        ResetPassword::pastikanTabel();

        $nomor = trim($request->nomor_induk);

        // Batasi percobaan salah (per nomor induk + IP) agar identitas tidak bisa ditebak-tebak.
        $kunciGagal = 'lupa-gagal:' . sha1(Str::lower($nomor) . '|' . $request->ip());
        if (RateLimiter::tooManyAttempts($kunciGagal, 5)) {
            return $this->kembaliDenganError($request, $this->pesanTunggu(RateLimiter::availableIn($kunciGagal)));
        }

        // Batasi jumlah OTP yang dikirim ke satu akun (mencegah spam ke inbox pemilik akun).
        $kunciKirim = 'lupa-kirim:' . sha1(Str::lower($nomor));
        if (RateLimiter::tooManyAttempts($kunciKirim, 5)) {
            return $this->kembaliDenganError($request, $this->pesanTunggu(RateLimiter::availableIn($kunciKirim)));
        }

        $user = Identitas::cariUserLogin($nomor);

        $cocok = $user
            && (int) ($user->is_aktif ?? 1) === 1
            && !empty($user->email)
            && strcasecmp(trim($user->email), trim($request->email)) === 0;

        if (!$cocok) {
            RateLimiter::hit($kunciGagal, 900);

            // Pesan sengaja seragam: tidak membocorkan apakah NISN/NIP atau email yang salah.
            return $this->kembaliDenganError(
                $request,
                'NISN / NIP atau email tidak cocok dengan data akun. Jika belum mengisi email di halaman Profil, hubungi Waka Kurikulum untuk reset password.'
            );
        }

        RateLimiter::clear($kunciGagal);

        // OTP dikirim ke email yang tersimpan di data akun (bukan sekadar yang diketik pengguna).
        if (!$this->buatDanKirimOtp($user, 0)) {
            return $this->kembaliDenganError($request, 'Kode OTP gagal dikirim. Coba lagi beberapa saat atau hubungi Waka Kurikulum.');
        }
        RateLimiter::hit($kunciKirim, 900);

        // Mulai sesi reset baru: bersihkan sisa token lama.
        $request->session()->regenerate();
        $request->session()->forget('reset.token');
        DB::table('reset_password_tokens')->where('id_user', $user->id_user)->delete();
        $request->session()->put('reset.user_id', (int) $user->id_user);

        return redirect()->route('password.otp')
            ->with('success', 'Kode OTP telah dikirim ke email ' . $this->samarkanEmail($user->email) . '.');
    }

    // ------------------------------------------------------------------
    // LANGKAH 2: verifikasi OTP
    // ------------------------------------------------------------------

    public function showOtpForm(Request $request)
    {
        ResetPassword::pastikanTabel();

        [$user, $baris] = $this->ambilAlurOtp($request);
        if (!$user || !$baris) {
            return $this->alurHabis($request);
        }

        return view('auth.verifikasi-otp', [
            'emailSamar'          => $this->samarkanEmail($user->email),
            'sisaKirim'           => $this->sisaJeda($baris),
            'masaBerlaku'         => ResetPassword::OTP_MASA_BERLAKU,
            'masihBisaKirimUlang' => $baris->resend_count < ResetPassword::OTP_MAKS_KIRIM_ULANG,
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
        ], [
            'otp.required' => 'Kode OTP wajib diisi.',
            'otp.digits'   => 'Kode OTP harus 6 digit angka.',
        ]);

        ResetPassword::pastikanTabel();

        [$user, $baris] = $this->ambilAlurOtp($request);
        if (!$user || !$baris) {
            return $this->alurHabis($request);
        }

        if ($baris->attempts >= ResetPassword::OTP_MAKS_SALAH) {
            return $this->alurHabis($request, 'Terlalu banyak kode yang salah. Silakan ajukan ulang.');
        }

        // Catat percobaan lebih dulu (atomik), baru dibandingkan.
        DB::table('reset_password_otps')->where('id', $baris->id)->increment('attempts');
        $percobaan = $baris->attempts + 1;

        if (!hash_equals($baris->otp_hash, $this->hashOtp($request->otp, (int) $user->id_user))) {
            $sisa = ResetPassword::OTP_MAKS_SALAH - $percobaan;
            if ($sisa <= 0) {
                return $this->alurHabis($request, 'Terlalu banyak kode yang salah. Silakan ajukan ulang.');
            }
            return back()->withErrors(['otp' => "Kode OTP salah. Sisa percobaan: {$sisa}."]);
        }

        // OTP benar: hanguskan OTP (sekali pakai), terbitkan token reset yang terikat ke sesi ini.
        DB::table('reset_password_otps')->where('id_user', $user->id_user)->delete();
        DB::table('reset_password_tokens')
            ->where('expires_at', '<', now())
            ->orWhere('id_user', $user->id_user)
            ->delete();

        $token = Str::random(64);
        DB::table('reset_password_tokens')->insert([
            'id_user'    => $user->id_user,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addMinutes(ResetPassword::MASA_BERLAKU),
            'created_at' => now(),
        ]);

        $request->session()->regenerate();
        $request->session()->forget('reset.user_id');
        $request->session()->put('reset.token', $token);

        return redirect()->route('password.reset');
    }

    public function resendOtp(Request $request)
    {
        ResetPassword::pastikanTabel();

        [$user, $baris] = $this->ambilAlurOtp($request);
        if (!$user || !$baris) {
            return $this->alurHabis($request);
        }

        if ($baris->resend_count >= ResetPassword::OTP_MAKS_KIRIM_ULANG) {
            return $this->alurHabis($request, 'Batas kirim ulang OTP tercapai. Silakan ajukan ulang dari awal.');
        }

        $jeda = $this->sisaJeda($baris);
        if ($jeda > 0) {
            return back()->withErrors(['otp' => "Tunggu {$jeda} detik sebelum meminta kode baru."]);
        }

        $kunciKirim = 'lupa-kirim:' . sha1(Str::lower((string) Identitas::dariUser($user)));
        if (RateLimiter::tooManyAttempts($kunciKirim, 5)) {
            return back()->withErrors(['otp' => $this->pesanTunggu(RateLimiter::availableIn($kunciKirim))]);
        }

        if (!$this->buatDanKirimOtp($user, $baris->resend_count + 1)) {
            return back()->withErrors(['otp' => 'Kode OTP gagal dikirim. Coba lagi beberapa saat.']);
        }
        RateLimiter::hit($kunciKirim, 900);

        return back()->with('success', 'Kode OTP baru telah dikirim ke ' . $this->samarkanEmail($user->email) . '.');
    }

    // ------------------------------------------------------------------
    // LANGKAH 3: buat password baru
    // ------------------------------------------------------------------

    public function showResetForm(Request $request)
    {
        ResetPassword::pastikanTabel();

        $token = (string) $request->session()->get('reset.token', '');
        if ($token === '' || !$this->cariToken($token)) {
            return $this->alurHabis($request, 'Sesi reset password tidak valid atau sudah kedaluwarsa. Silakan ajukan ulang.');
        }

        return view('auth.reset-password', ['token' => $token]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token'    => 'required|string',
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'password.required'  => 'Password baru wajib diisi.',
            'password.min'       => 'Password minimal :min karakter.',
            'password.letters'   => 'Password harus mengandung huruf.',
            'password.numbers'   => 'Password harus mengandung angka.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        ResetPassword::pastikanTabel();

        // Token di form harus sama dengan token yang diterbitkan untuk SESI ini (bukan token curian/tebakan).
        $tokenSesi = (string) $request->session()->get('reset.token', '');
        if ($tokenSesi === '' || !hash_equals($tokenSesi, (string) $request->token)) {
            return $this->alurHabis($request, 'Sesi reset password tidak valid. Silakan ajukan ulang.');
        }

        $baris = $this->cariToken($tokenSesi);
        if (!$baris) {
            return $this->alurHabis($request, 'Sesi reset password sudah kedaluwarsa. Silakan ajukan ulang.');
        }

        $user = DB::table('users')->where('id_user', $baris->id_user)->first();
        if (!$user) {
            return $this->alurHabis($request);
        }

        if (Hash::check($request->password, $user->password_hash)) {
            return back()->withErrors(['password' => 'Password baru tidak boleh sama dengan password lama.']);
        }

        DB::transaction(function () use ($baris, $request) {
            DB::table('users')->where('id_user', $baris->id_user)->update([
                'password_hash' => Hash::make($request->password),
            ]);

            // Sekali pakai: hapus semua token & OTP milik user ini
            DB::table('reset_password_tokens')->where('id_user', $baris->id_user)->delete();
            DB::table('reset_password_otps')->where('id_user', $baris->id_user)->delete();
        });

        $request->session()->forget(['reset.token', 'reset.user_id']);

        Notifier::kirim(
            (int) $baris->id_user,
            'akun',
            'Password Diubah',
            'Password akun Anda baru saja diubah lewat fitur Lupa Password. Jika ini bukan Anda, segera hubungi Waka Kurikulum.',
            route('profil.index')
        );

        return redirect()->route('login')->with('success', 'Password berhasil diubah. Silakan login dengan password baru.');
    }

    // ------------------------------------------------------------------
    // Helper
    // ------------------------------------------------------------------

    /** Buat OTP baru, simpan hash-nya, lalu kirim ke email akun. false bila email gagal terkirim. */
    private function buatDanKirimOtp(object $user, int $jumlahKirimUlang): bool
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('reset_password_otps')->where('id_user', $user->id_user)->delete();
        DB::table('reset_password_otps')->insert([
            'id_user'      => $user->id_user,
            'otp_hash'     => $this->hashOtp($otp, (int) $user->id_user),
            'attempts'     => 0,
            'resend_count' => $jumlahKirimUlang,
            'expires_at'   => now()->addMinutes(ResetPassword::OTP_MASA_BERLAKU),
            'last_sent_at' => now(),
            'created_at'   => now(),
        ]);

        try {
            Mail::send('emails.otp-reset', [
                'nama'  => $user->nama_lengkap,
                'otp'   => $otp,
                'menit' => ResetPassword::OTP_MASA_BERLAKU,
            ], function ($pesan) use ($user) {
                $pesan->to($user->email, $user->nama_lengkap)
                      ->subject('Kode OTP Reset Password - UKK SCP');
            });
        } catch (\Throwable $e) {
            Log::error('Gagal mengirim OTP reset password: ' . $e->getMessage());
            DB::table('reset_password_otps')->where('id_user', $user->id_user)->delete();
            return false;
        }

        return true;
    }

    /** OTP tidak pernah disimpan polos; di-HMAC dengan APP_KEY + id user. */
    private function hashOtp(string $otp, int $idUser): string
    {
        return hash_hmac('sha256', $otp . '|' . $idUser, (string) config('app.key'));
    }

    /** Ambil [user, baris OTP] dari sesi alur reset; [null, null] jika tidak ada / kedaluwarsa. */
    private function ambilAlurOtp(Request $request): array
    {
        $idUser = (int) $request->session()->get('reset.user_id', 0);
        if ($idUser <= 0) {
            return [null, null];
        }

        $user  = DB::table('users')->where('id_user', $idUser)->first();
        $baris = DB::table('reset_password_otps')->where('id_user', $idUser)->first();

        if (!$user || !$baris || Carbon::parse($baris->expires_at)->isPast()) {
            return [null, null];
        }

        return [$user, $baris];
    }

    private function alurHabis(Request $request, string $pesan = 'Sesi verifikasi berakhir atau OTP kedaluwarsa. Silakan ajukan ulang.')
    {
        if ($idUser = (int) $request->session()->get('reset.user_id', 0)) {
            DB::table('reset_password_otps')->where('id_user', $idUser)->delete();
        }
        $request->session()->forget(['reset.user_id', 'reset.token']);

        return redirect()->route('password.request')->withErrors(['error' => $pesan]);
    }

    private function sisaJeda(object $baris): int
    {
        $lewat = now()->timestamp - Carbon::parse($baris->last_sent_at)->timestamp;
        return max(0, ResetPassword::OTP_JEDA_KIRIM - $lewat);
    }

    private function kembaliDenganError(Request $request, string $pesan)
    {
        return back()->withErrors(['error' => $pesan])->withInput($request->only('nomor_induk'));
    }

    private function pesanTunggu(int $detik): string
    {
        $menit = (int) ceil($detik / 60);
        return "Terlalu banyak percobaan. Coba lagi sekitar {$menit} menit lagi.";
    }

    /** budi.santoso@gmail.com -> bu*********o@gmail.com */
    private function samarkanEmail(string $email): string
    {
        [$lokal, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $panjang = mb_strlen($lokal);
        if ($panjang <= 2) {
            return mb_substr($lokal, 0, 1) . '*@' . $domain;
        }
        return mb_substr($lokal, 0, 2) . str_repeat('*', max(3, $panjang - 3)) . mb_substr($lokal, -1) . '@' . $domain;
    }

    private function cariToken(string $token)
    {
        return DB::table('reset_password_tokens')
            ->where('token_hash', hash('sha256', $token))
            ->where('expires_at', '>', now())
            ->first();
    }
}
