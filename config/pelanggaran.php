<?php

return [
    // Batas waktu pengisian jurnal: berapa jam setelah jam SELESAI jadwal.
    // Lewat dari batas ini = tercatat sebagai pelanggaran guru.
    'batas_jam' => (int) env('PELANGGARAN_BATAS_JAM', 5),

    // Pelanggaran baru dihitung untuk jadwal pada tanggal ini dan sesudahnya.
    // Mencegah jadwal lama (sebelum fitur aktif) ikut dihitung sebagai pelanggaran.
    // Ubah lewat .env: PELANGGARAN_MULAI=2026-11-06
    'mulai_berlaku' => env('PELANGGARAN_MULAI', '2026-11-06'),

    // Berapa hari ke belakang dipindai tiap kali (menutup hari yang terlewat tanpa cron).
    'lookback_hari' => 3,
];
