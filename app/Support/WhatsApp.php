<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pengirim pesan WhatsApp + normalisasi nomor telepon Indonesia.
 */
class WhatsApp
{
    /** Ubah 0812..., +62 812..., 62812..., 812... menjadi 62812... (hanya digit). */
    public static function normalisasi(?string $nomor): string
    {
        $digit = preg_replace('/\D+/', '', (string) $nomor);

        if ($digit === '') {
            return '';
        }
        if (str_starts_with($digit, '62')) {
            return $digit;
        }
        if (str_starts_with($digit, '0')) {
            return '62' . substr($digit, 1);
        }
        return '62' . $digit;
    }

    /** Samarkan nomor untuk ditampilkan: 0812****789 */
    public static function samarkan(?string $nomor): string
    {
        $n = self::normalisasi($nomor);
        if ($n === '') {
            return '-';
        }
        $lokal = '0' . substr($n, 2);
        return substr($lokal, 0, 4) . str_repeat('*', max(1, strlen($lokal) - 7)) . substr($lokal, -3);
    }

    /**
     * Kirim pesan. Mengembalikan null bila berhasil, atau string alasan bila gagal.
     */
    public static function kirim(string $nomor, string $pesan): ?string
    {
        $target = self::normalisasi($nomor);
        if ($target === '') {
            return 'Nomor tujuan kosong.';
        }

        $driver = config('whatsapp.driver', 'log');

        if ($driver === 'log') {
            Log::info("[WhatsApp:log] ke {$target}: {$pesan}");
            return null;
        }

        if ($driver === 'fonnte') {
            $cfg = config('whatsapp.fonnte');
            if (empty($cfg['token'])) {
                return 'FONNTE_TOKEN belum diisi di .env.';
            }

            try {
                $res = Http::timeout(15)
                    ->withHeaders(['Authorization' => $cfg['token']])
                    ->asForm()
                    ->post($cfg['url'], [
                        'target'      => $target,
                        'message'     => $pesan,
                        'countryCode' => $cfg['country_code'],
                    ]);
            } catch (\Throwable $e) {
                Log::error('Gagal menghubungi Fonnte: ' . $e->getMessage());
                return $e->getMessage();
            }

            $json = $res->json();
            if ($res->successful() && ($json['status'] ?? false) === true) {
                return null;
            }

            $alasan = $json['reason'] ?? $res->body();
            Log::error('Fonnte menolak pengiriman: ' . $alasan);
            return (string) $alasan;
        }

        return "Driver WhatsApp '{$driver}' tidak dikenal.";
    }
}
