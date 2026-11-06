@extends('layouts.app')

@section('title', 'Dashboard Wali Kelas - UKK SCP')
@section('page-title', 'Dashboard Wali Kelas')

@section('content')
    @php
        $dashIcon = 'bi-person-badge-fill';
        $dashWelcome = 'Anda bertugas memverifikasi pengajuan izin dan sakit yang dikirim oleh siswa.'
            . (!empty($namaKelasDiampu) ? ' Kelas yang Anda ampu: ' . implode(', ', $namaKelasDiampu) . '.' : '');
        $dashFunctions = [
            ['icon' => 'bi-patch-check-fill', 'title' => 'Verifikasi Izin', 'desc' => 'Tinjau pengajuan izin/sakit siswa lalu setujui atau tolak.'],
            ['icon' => 'bi-clipboard2-check-fill', 'title' => 'Rekap Presensi', 'desc' => 'Pantau rekap kehadiran, izin, sakit, dan alfa siswa per kelas dan periode.'],
        ];
        $dashRules = [
            'Setiap pengajuan hanya dapat ditetapkan sebagai Disetujui (Approved) atau Ditolak (Rejected).',
            'Periksa alasan dan dokumen pendukung siswa sebelum mengambil keputusan.',
            'Keputusan Anda tercatat bersama identitas pemverifikasi.',
            'Login menggunakan NIP dan password. NIP dan nama lengkap hanya dapat diubah oleh Waka Kurikulum.',
        ];
    @endphp

    @include('partials.dashboard-info')
@endsection