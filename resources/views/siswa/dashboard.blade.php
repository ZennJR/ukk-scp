@extends('layouts.app')

@section('title', 'Dashboard Siswa - UKK SCP')
@section('page-title', 'Dashboard Siswa')

@section('content')
    @php
        $dashIcon = 'bi-mortarboard-fill';
        $dashWelcome = 'Ajukan izin atau sakit, pantau status pengajuan, dan lihat rekap presensi Anda melalui menu pada sidebar.';
        $dashFunctions = [
            ['icon' => 'bi-file-earmark-medical-fill', 'title' => 'Pengajuan Izin', 'desc' => 'Kirim pengajuan izin/sakit dan lihat riwayat serta statusnya.'],
            ['icon' => 'bi-clipboard2-check-fill',   'title' => 'Presensi Saya', 'desc' => 'Lihat berapa kali Anda hadir, izin, sakit, atau alfa beserta riwayat per pertemuan.'],
        ];
        $dashRules = [
            'Pengajuan wajib memuat tanggal mulai, tanggal selesai, dan alasan.',
            'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.',
            'Dokumen pendukung wajib dilampirkan (format PDF/JPG/PNG, maksimal 2 MB).',
            'Pengajuan berstatus Pending sampai diverifikasi oleh Wali Kelas/BK.',
            'Presensi diisi oleh guru pada setiap pertemuan; izin/sakit yang sudah disetujui otomatis tercatat.',
            'Login menggunakan NISN dan password. NISN dan nama lengkap hanya dapat diubah oleh Waka Kurikulum.',
        ];
    @endphp

    @include('partials.dashboard-info')
@endsection