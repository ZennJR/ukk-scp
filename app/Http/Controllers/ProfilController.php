<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use App\Support\GantiEmail;
use App\Support\Identitas;
use App\Support\Notifier;

class ProfilController extends Controller
{
    private function roleLabel($idRole): string
    {
        return match ((int) $idRole) {
            1 => 'Admin',
            2 => 'Guru',
            3 => 'Wali Kelas',
            4 => 'Waka Kurikulum',
            5 => 'Siswa',
            6 => 'Orang Tua',
            7 => 'Guru BK',
            default => 'Pengguna',
        };
    }

    /**
     * URL foto profil user (null jika belum punya).
     * Foto disimpan di public/uploads/profil/user_{id_user}.{ext}, jadi tidak perlu kolom baru di database.
     */
    public static function fotoUrl($idUser): ?string
    {
        if (!$idUser) {
            return null;
        }

        $files = glob(public_path('uploads/profil/user_' . (int) $idUser . '.*'));
        if (!$files) {
            return null;
        }

        return asset('uploads/profil/' . basename($files[0])) . '?v=' . filemtime($files[0]);
    }

    private function hapusFotoLama($idUser): void
    {
        foreach (glob(public_path('uploads/profil/user_' . (int) $idUser . '.*')) ?: [] as $file) {
            @unlink($file);
        }
    }

    /**
     * Ambil user yang sedang login dari database (null jika sesi tidak valid).
     */
    private function currentUser()
    {
        if (!session()->has('id_user')) {
            return null;
        }
        return DB::table('users')->where('id_user', session('id_user'))->first();
    }

    /**
     * Cari data identitas (NIP guru / NISN siswa) milik user.
     * Dicari lewat nomor induk (users.nomor_induk), bukan username, sehingga
     * NIP/NISN tetap walau username diganti. Untuk akun lama yang kolomnya masih
     * kosong, nomor induk = username.
     * Return: [label, nomor, record]
     */
    private function identitas($user): array
    {
        $nomor = Identitas::dariUser($user);

        if ((int) $user->id_role === 5) {
            $siswa = DB::table('siswa')->where('nisn', $nomor)->first();
            return ['NISN', $siswa->nisn ?? null, $siswa];
        }

        $guru = DB::table('guru')->where('nip', $nomor)->first();
        return ['NIP', $guru->nip ?? null, $guru];
    }

    private function denyIfGuest()
    {
        return redirect()->route('login')->withErrors(['error' => 'Silakan login terlebih dahulu!']);
    }

    public function index()
    {
        $user = $this->currentUser();
        if (!$user) {
            session()->flush();
            return $this->denyIfGuest();
        }

        [$labelIdentitas, $nomorIdentitas] = $this->identitas($user);

        return view('profil.index', [
            'user'           => $user,
            'roleLabel'      => \App\Support\Peran::labelSemua(),
            'labelIdentitas' => $labelIdentitas,
            'nomorIdentitas' => $nomorIdentitas,
            'isWaka'         => \App\Support\Peran::punya(4),
            'fotoUrl'        => self::fotoUrl($user->id_user),
        ]);
    }

    /**
     * Ubah email & nomor HP.
     * - No. HP langsung disimpan.
     * - Email yang BERUBAH tidak langsung disimpan: OTP dikirim ke email baru dan baru
     *   dipakai setelah kode dikonfirmasi (lihat verifikasiEmail).
     */
    public function updateKontak(Request $request)
    {
        $user = $this->currentUser();
        if (!$user) {
            session()->flush();
            return $this->denyIfGuest();
        }

        $request->validate([
            'email' => ['nullable', 'email', 'max:100'],
            'no_hp' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s]+$/'],
        ], [
            'email.email'   => 'Format email tidak valid.',
            'email.max'     => 'Email maksimal 100 karakter.',
            'no_hp.max'     => 'Nomor HP maksimal 20 karakter.',
            'no_hp.regex'   => 'Nomor HP hanya boleh berisi angka, spasi, "+", atau "-".',
        ]);

        DB::table('users')->where('id_user', $user->id_user)->update([
            'no_hp' => $request->no_hp ?: null,
        ]);

        $emailBaru = trim((string) $request->email);
        $emailLama = trim((string) ($user->email ?? ''));

        // Email kosong / tidak berubah: tidak ada yang perlu dikonfirmasi.
        // (Email yang sudah terdaftar tidak bisa dikosongkan lewat form ini agar fitur Lupa Password tetap aman.)
        if ($emailBaru === '' || strcasecmp($emailBaru, $emailLama) === 0) {
            return redirect()->route('profil.index')->with('success', 'Informasi kontak berhasil diperbarui.');
        }

        $dipakai = DB::table('users')
            ->where('id_user', '!=', $user->id_user)
            ->whereRaw('LOWER(email) = ?', [Str::lower($emailBaru)])
            ->exists();
        if ($dipakai) {
            return back()->withErrors(['email' => 'Email ini sudah dipakai akun lain.'])->withInput();
        }

        GantiEmail::pastikanTabel();

        $kunci = 'ganti-email:' . $user->id_user;
        if (RateLimiter::tooManyAttempts($kunci, 5)) {
            $menit = (int) ceil(RateLimiter::availableIn($kunci) / 60);
            return back()->withErrors(['email' => "Terlalu banyak permintaan. Coba lagi sekitar {$menit} menit lagi."])->withInput();
        }

        if (!$this->kirimOtpEmail($user, $emailBaru, 0)) {
            return back()->withErrors(['email' => 'Kode OTP gagal dikirim ke email baru. Periksa alamatnya lalu coba lagi.'])->withInput();
        }
        RateLimiter::hit($kunci, 900);

        return redirect()->route('profil.email.verifikasi')
            ->with('success', 'Kode OTP telah dikirim ke ' . $this->samarkanEmail($emailBaru) . '. Masukkan kode untuk mengonfirmasi email baru.');
    }

    public function formVerifikasiEmail()
    {
        $user = $this->currentUser();
        if (!$user) {
            session()->flush();
            return $this->denyIfGuest();
        }

        GantiEmail::pastikanTabel();

        $baris = $this->barisOtpEmail($user);
        if (!$baris) {
            return redirect()->route('profil.index')->withErrors(['email' => 'Tidak ada perubahan email yang menunggu konfirmasi, atau kode sudah kedaluwarsa.']);
        }

        return view('profil.verifikasi-email', [
            'emailSamar'          => $this->samarkanEmail($baris->email_baru),
            'sisaKirim'           => $this->sisaJedaEmail($baris),
            'masaBerlaku'         => GantiEmail::OTP_MASA_BERLAKU,
            'masihBisaKirimUlang' => $baris->resend_count < GantiEmail::OTP_MAKS_KIRIM_ULANG,
        ]);
    }

    public function verifikasiEmail(Request $request)
    {
        $user = $this->currentUser();
        if (!$user) {
            session()->flush();
            return $this->denyIfGuest();
        }

        $request->validate(['otp' => 'required|digits:6'], [
            'otp.required' => 'Kode OTP wajib diisi.',
            'otp.digits'   => 'Kode OTP harus 6 digit angka.',
        ]);

        GantiEmail::pastikanTabel();

        $baris = $this->barisOtpEmail($user);
        if (!$baris) {
            return redirect()->route('profil.index')->withErrors(['email' => 'Kode sudah kedaluwarsa. Silakan ubah email lagi.']);
        }

        if ($baris->attempts >= GantiEmail::OTP_MAKS_SALAH) {
            DB::table('email_change_otps')->where('id_user', $user->id_user)->delete();
            return redirect()->route('profil.index')->withErrors(['email' => 'Terlalu banyak kode yang salah. Silakan ubah email lagi.']);
        }

        DB::table('email_change_otps')->where('id', $baris->id)->increment('attempts');
        $percobaan = $baris->attempts + 1;

        if (!hash_equals($baris->otp_hash, $this->hashOtpEmail($request->otp, (int) $user->id_user, $baris->email_baru))) {
            $sisa = GantiEmail::OTP_MAKS_SALAH - $percobaan;
            if ($sisa <= 0) {
                DB::table('email_change_otps')->where('id_user', $user->id_user)->delete();
                return redirect()->route('profil.index')->withErrors(['email' => 'Terlalu banyak kode yang salah. Silakan ubah email lagi.']);
            }
            return back()->withErrors(['otp' => "Kode OTP salah. Sisa percobaan: {$sisa}."]);
        }

        // Cek ulang: email baru belum dipakai akun lain selagi menunggu konfirmasi.
        $dipakai = DB::table('users')
            ->where('id_user', '!=', $user->id_user)
            ->whereRaw('LOWER(email) = ?', [Str::lower($baris->email_baru)])
            ->exists();
        if ($dipakai) {
            DB::table('email_change_otps')->where('id_user', $user->id_user)->delete();
            return redirect()->route('profil.index')->withErrors(['email' => 'Email ini sudah dipakai akun lain.']);
        }

        $emailLama = $user->email;

        DB::transaction(function () use ($user, $baris) {
            DB::table('users')->where('id_user', $user->id_user)->update(['email' => $baris->email_baru]);
            DB::table('email_change_otps')->where('id_user', $user->id_user)->delete();
        });

        Notifier::kirim(
            (int) $user->id_user,
            'akun',
            'Email Diubah',
            'Email akun Anda berhasil diganti. Jika ini bukan Anda, segera hubungi Waka Kurikulum.',
            route('profil.index')
        );

        // Beri tahu alamat email LAMA (jika ada) bahwa email akun sudah diganti.
        if (!empty($emailLama)) {
            try {
                Mail::send('emails.email-diganti', [
                    'nama'      => $user->nama_lengkap,
                    'emailBaru' => $baris->email_baru,
                ], function ($pesan) use ($emailLama, $user) {
                    $pesan->to($emailLama, $user->nama_lengkap)->subject('Email Akun UKK SCP Diubah');
                });
            } catch (\Throwable $e) {
                Log::warning('Gagal mengirim pemberitahuan email lama: ' . $e->getMessage());
            }
        }

        return redirect()->route('profil.index')->with('success', 'Email berhasil dikonfirmasi dan diperbarui.');
    }

    public function kirimUlangOtpEmail(Request $request)
    {
        $user = $this->currentUser();
        if (!$user) {
            session()->flush();
            return $this->denyIfGuest();
        }

        GantiEmail::pastikanTabel();

        $baris = $this->barisOtpEmail($user);
        if (!$baris) {
            return redirect()->route('profil.index')->withErrors(['email' => 'Kode sudah kedaluwarsa. Silakan ubah email lagi.']);
        }

        if ($baris->resend_count >= GantiEmail::OTP_MAKS_KIRIM_ULANG) {
            DB::table('email_change_otps')->where('id_user', $user->id_user)->delete();
            return redirect()->route('profil.index')->withErrors(['email' => 'Batas kirim ulang tercapai. Silakan ubah email lagi.']);
        }

        $jeda = $this->sisaJedaEmail($baris);
        if ($jeda > 0) {
            return back()->withErrors(['otp' => "Tunggu {$jeda} detik sebelum meminta kode baru."]);
        }

        if (!$this->kirimOtpEmail($user, $baris->email_baru, $baris->resend_count + 1)) {
            return back()->withErrors(['otp' => 'Kode OTP gagal dikirim. Coba lagi beberapa saat.']);
        }

        return back()->with('success', 'Kode OTP baru telah dikirim ke ' . $this->samarkanEmail($baris->email_baru) . '.');
    }

    public function batalGantiEmail()
    {
        $user = $this->currentUser();
        if (!$user) {
            session()->flush();
            return $this->denyIfGuest();
        }

        GantiEmail::pastikanTabel();
        DB::table('email_change_otps')->where('id_user', $user->id_user)->delete();

        return redirect()->route('profil.index')->with('success', 'Perubahan email dibatalkan.');
    }

    // ---- Helper perubahan email ----

    private function kirimOtpEmail($user, string $emailBaru, int $jumlahKirimUlang): bool
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('email_change_otps')->where('id_user', $user->id_user)->delete();
        DB::table('email_change_otps')->insert([
            'id_user'      => $user->id_user,
            'email_baru'   => $emailBaru,
            'otp_hash'     => $this->hashOtpEmail($otp, (int) $user->id_user, $emailBaru),
            'attempts'     => 0,
            'resend_count' => $jumlahKirimUlang,
            'expires_at'   => now()->addMinutes(GantiEmail::OTP_MASA_BERLAKU),
            'last_sent_at' => now(),
            'created_at'   => now(),
        ]);

        try {
            // OTP dikirim ke EMAIL BARU: membuktikan pemilik akun memang memegang email tersebut.
            Mail::send('emails.otp-ganti-email', [
                'nama'  => $user->nama_lengkap,
                'otp'   => $otp,
                'menit' => GantiEmail::OTP_MASA_BERLAKU,
            ], function ($pesan) use ($user, $emailBaru) {
                $pesan->to($emailBaru, $user->nama_lengkap)->subject('Kode OTP Konfirmasi Email Baru - UKK SCP');
            });
        } catch (\Throwable $e) {
            Log::error('Gagal mengirim OTP ganti email: ' . $e->getMessage());
            DB::table('email_change_otps')->where('id_user', $user->id_user)->delete();
            return false;
        }

        return true;
    }

    /** OTP terikat ke id user DAN email baru, jadi tidak bisa dipakai untuk email lain. */
    private function hashOtpEmail(string $otp, int $idUser, string $emailBaru): string
    {
        return hash_hmac('sha256', $otp . '|' . $idUser . '|' . Str::lower($emailBaru), (string) config('app.key'));
    }

    private function barisOtpEmail($user)
    {
        $baris = DB::table('email_change_otps')->where('id_user', $user->id_user)->first();
        if (!$baris) {
            return null;
        }
        if (Carbon::parse($baris->expires_at)->isPast()) {
            DB::table('email_change_otps')->where('id_user', $user->id_user)->delete();
            return null;
        }
        return $baris;
    }

    private function sisaJedaEmail($baris): int
    {
        $lewat = now()->timestamp - Carbon::parse($baris->last_sent_at)->timestamp;
        return max(0, GantiEmail::OTP_JEDA_KIRIM - $lewat);
    }

    private function samarkanEmail(string $email): string
    {
        [$lokal, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $panjang = mb_strlen($lokal);
        if ($panjang <= 2) {
            return mb_substr($lokal, 0, 1) . '*@' . $domain;
        }
        return mb_substr($lokal, 0, 2) . str_repeat('*', max(3, $panjang - 3)) . mb_substr($lokal, -1) . '@' . $domain;
    }

    public function updateFoto(Request $request)
    {
        $user = $this->currentUser();
        if (!$user) {
            session()->flush();
            return $this->denyIfGuest();
        }

        $request->validate([
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'foto.required' => 'Pilih foto terlebih dahulu.',
            'foto.image'    => 'File harus berupa gambar.',
            'foto.mimes'    => 'Format foto harus JPG, PNG, atau WEBP.',
            'foto.max'      => 'Ukuran foto maksimal 2 MB.',
        ]);

        $dir = public_path('uploads/profil');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $file = $request->file('foto');
        $this->hapusFotoLama($user->id_user);
        $file->move($dir, 'user_' . (int) $user->id_user . '.' . $file->extension());

        return redirect()->route('profil.index')->with('success', 'Foto profil berhasil diperbarui.');
    }

    public function hapusFoto()
    {
        $user = $this->currentUser();
        if (!$user) {
            session()->flush();
            return $this->denyIfGuest();
        }

        $this->hapusFotoLama($user->id_user);

        return redirect()->route('profil.index')->with('success', 'Foto profil dihapus.');
    }

    /**
     * Ubah nama lengkap lewat profil. Hanya Waka Kurikulum (id_role 4).
     * Pengguna lain tidak bisa mengubah nama sendiri; perubahan dilakukan oleh Waka lewat Manajemen Akun.
     */
    public function updateNama(Request $request)
    {
        $user = $this->currentUser();
        if (!$user) {
            session()->flush();
            return $this->denyIfGuest();
        }

        if (!\App\Support\Peran::punya(4)) {
            return redirect()->route('profil.index')
                ->withErrors(['nama_lengkap' => 'Nama lengkap hanya dapat diubah oleh Waka Kurikulum.']);
        }

        $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:100'],
        ], [
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'nama_lengkap.max'      => 'Nama lengkap maksimal 100 karakter.',
        ]);

        $nama = trim($request->nama_lengkap);

        DB::table('users')->where('id_user', $user->id_user)->update(['nama_lengkap' => $nama]);

        // Sinkronkan ke data guru bila ada (dicari lewat NIP)
        DB::table('guru')->where('nip', Identitas::dariUser($user))->update(['nama_guru' => $nama]);

        session(['nama_lengkap' => $nama]);

        return redirect()->route('profil.index')->with('success', 'Nama lengkap berhasil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        $user = $this->currentUser();
        if (!$user) {
            session()->flush();
            return $this->denyIfGuest();
        }

        $request->validate([
            'password_lama' => ['required'],
            'password_baru' => ['required', 'min:8', 'confirmed', 'different:password_lama'],
        ], [
            'password_lama.required'  => 'Password saat ini wajib diisi.',
            'password_baru.required'  => 'Password baru wajib diisi.',
            'password_baru.min'       => 'Password baru minimal 8 karakter.',
            'password_baru.confirmed' => 'Konfirmasi password baru tidak cocok.',
            'password_baru.different' => 'Password baru tidak boleh sama dengan password saat ini.',
        ]);

        if (!Hash::check($request->password_lama, $user->password_hash)) {
            return back()->withErrors(['password_lama' => 'Password saat ini salah.']);
        }

        DB::table('users')->where('id_user', $user->id_user)->update([
            'password_hash' => Hash::make($request->password_baru),
        ]);

        return redirect()->route('profil.index')->with('success', 'Password berhasil diganti.');
    }
}