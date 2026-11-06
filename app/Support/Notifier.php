<?php

namespace App\Support;

use App\Models\Notification;
use Illuminate\Support\Facades\DB;

/**
 * Fungsi bantu untuk membuat notifikasi dalam aplikasi.
 *
 * Notifikasi disimpan di tabel `notifications` dan ditampilkan lewat ikon
 * lonceng di topbar (lihat resources/views/layouts/app.blade.php) serta
 * NotificationController.
 */
class Notifier
{
    /**
     * Kirim satu notifikasi ke satu user (berdasarkan id_user).
     *
     * @param string|null $uniqueKey Jika diisi, notifikasi dengan kunci yang sama
     *                                tidak akan dikirim dua kali (mis. pengingat harian).
     */
    public static function kirim(int $idUser, string $type, string $title, string $message, ?string $url = null, ?string $uniqueKey = null): bool
    {
        if ($uniqueKey !== null && Notification::where('unique_key', $uniqueKey)->exists()) {
            return false;
        }

        try {
            Notification::create([
                'id_user'    => $idUser,
                'type'       => $type,
                'title'      => $title,
                'message'    => $message,
                'url'        => $url,
                'unique_key' => $uniqueKey,
                'is_read'    => false,
                'created_at' => now(),
            ]);
            return true;
        } catch (\Illuminate\Database\QueryException $e) {
            // Kemungkinan besar bentrok unique_key (race condition) - aman diabaikan.
            return false;
        }
    }

    /**
     * Kirim notifikasi yang sama ke semua user dengan role tertentu (bisa lebih dari satu role).
     *
     * @param int|array $idRole
     */
    public static function kirimKeRole($idRole, string $type, string $title, string $message, ?string $url = null): void
    {
        $roles = is_array($idRole) ? $idRole : [$idRole];

        // Peran utama (users.id_role) + peran tambahan (user_roles), mis. Guru yang juga Wali Kelas.
        $userIds = DB::table('users')
            ->where('is_aktif', 1)
            ->where(function ($q) use ($roles) {
                $q->whereIn('id_role', $roles);
                if (Peran::tabelAda()) {
                    $q->orWhereIn('id_user', DB::table('user_roles')->whereIn('id_role', $roles)->select('id_user'));
                }
            })
            ->pluck('id_user');

        foreach ($userIds as $idUser) {
            // Setiap user butuh unique_key sendiri kalau memang perlu anti-dobel,
            // jadi di sini cukup kirim biasa (dipanggil untuk event yang memang sekali terjadi).
            static::kirim($idUser, $type, $title, $message, $url);
        }
    }

    /**
     * Kirim notifikasi ke pemilik akun berdasarkan nomor induk (NIP guru / NISN siswa).
     */
    public static function untukNomorInduk(string $nomorInduk, string $type, string $title, string $message, ?string $url = null, ?string $uniqueKey = null): bool
    {
        $user = \App\Support\Identitas::cariUserLogin($nomorInduk);
        if (!$user) {
            return false;
        }

        return static::kirim($user->id_user, $type, $title, $message, $url, $uniqueKey);
    }
}
