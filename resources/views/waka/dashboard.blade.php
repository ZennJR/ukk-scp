@extends('layouts.app')

@section('title', 'Panel Waka Kurikulum - SCP')
@section('page-title', 'Panel Waka Kurikulum')

@section('content')
    @php
        $dashIcon = 'bi-speedometer2';
        $dashWelcome = 'Anda mengelola akun pengguna, memantau jurnal mengajar guru, meninjau jadwal pelajaran, dan mengatur siklus serta penguncian jadwal.';
        $dashFunctions = [
            ['icon' => 'bi-people-fill',          'title' => 'Manajemen Akun',    'desc' => 'Tambah, ubah, dan hapus akun pengguna beserta role-nya.'],
            ['icon' => 'bi-journal-richtext',     'title' => 'Rekap Jurnal',      'desc' => 'Pantau seluruh jurnal mengajar yang diisi guru.'],
            ['icon' => 'bi-calendar2-week-fill',  'title' => 'Rekap Jadwal',      'desc' => 'Lihat, ubah, dan hapus jadwal pelajaran seluruh guru.'],
            ['icon' => 'bi-sliders',              'title' => 'Pengaturan Sistem', 'desc' => 'Atur putaran blok aktif dan kunci jadwal untuk guru.'],
            ['icon' => 'bi-door-open-fill',        'title' => 'Data Ruangan',   'desc' => 'Tambah, ubah, dan hapus data ruangan.'],
            ['icon' => 'bi-people-fill',           'title' => 'Data Kelas',     'desc' => 'Tambah, ubah, dan hapus data kelas.'],
            ['icon' => 'bi-journal-bookmark-fill', 'title' => 'Data Mata Pelajaran', 'desc' => 'Tambah, ubah, dan hapus data mata pelajaran.'],
        ];
        $dashRules = [
            'Halaman dan fitur pada panel ini hanya dapat diakses oleh Waka Kurikulum.',
            'Putaran blok aktif berlaku secara global dan memengaruhi tampilan jadwal seluruh pengguna.',
            'Putaran yang sedang berjalan otomatis terkunci; guru hanya dapat menambah jadwal pada putaran lain.',
            'Perubahan jadwal tidak boleh bentrok pada hari, jam, ruangan, kelas, atau guru yang sama.',
            'Ruangan, kelas, atau mata pelajaran yang masih dipakai pada jadwal tidak dapat dihapus.',
            'Akun yang sedang Anda gunakan untuk login tidak dapat dihapus.',
            'Login semua pengguna memakai NIP/NISN. Nama lengkap dan NIP/NISN akun hanya dapat diubah oleh Waka melalui Manajemen Akun.',
        ];
    @endphp

    @include('partials.dashboard-info')
@endsection