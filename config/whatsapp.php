<?php

/**
 * Pengiriman pesan WhatsApp (dipakai untuk OTP Lupa Password).
 *
 * WhatsApp tidak punya "SMTP"; pengiriman butuh layanan gateway. Driver yang tersedia:
 *   - fonnte : https://fonnte.com  (isi FONNTE_TOKEN dari perangkat yang sudah terhubung)
 *   - log    : tidak mengirim, hanya menulis pesan ke storage/logs/laravel.log (untuk uji coba)
 */
return [
    'driver' => env('WHATSAPP_DRIVER', 'log'),

    'fonnte' => [
        'token'        => env('FONNTE_TOKEN'),
        'url'          => env('FONNTE_URL', 'https://api.fonnte.com/send'),
        'country_code' => env('FONNTE_COUNTRY_CODE', '62'),
    ],
];
