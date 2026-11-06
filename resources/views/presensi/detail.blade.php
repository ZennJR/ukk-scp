@extends('layouts.app')

@section('title', 'Detail Presensi Siswa - UKK SCP')
@section('page-title', 'Detail Presensi Siswa')
@section('back-url', route('presensi.rekap'))

@section('content')
    <a href="{{ route('presensi.rekap') }}" class="btn btn-sm btn-outline-secondary mb-3">
        <i class="bi bi-arrow-left me-1"></i>Kembali ke Rekap Presensi
    </a>

    <div class="card shadow-sm reveal mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between gap-3">
                <div>
                    <h5 class="mb-1">{{ $siswa->nama_siswa }}</h5>
                    <div class="text-muted small">NISN: {{ $siswa->nisn }} &middot; Kelas: {{ $siswa->kelas->nama_kelas ?? '-' }}</div>
                </div>
                <form method="GET" action="{{ route('presensi.siswa', $siswa->id_siswa) }}" class="row g-2 align-items-end">
                    <div class="col-auto">
                        <label class="form-label mb-1 small text-muted">Dari</label>
                        <input type="date" name="dari" value="{{ $dari }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-auto">
                        <label class="form-label mb-1 small text-muted">Sampai</label>
                        <input type="date" name="sampai" value="{{ $sampai }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-auto"><button class="btn btn-sm btn-primary">Terapkan</button></div>
                </form>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-3">
                <span class="badge bg-success fs-6">Hadir {{ $hitung['Hadir'] }}</span>
                <span class="badge bg-warning text-dark fs-6">Izin {{ $hitung['Izin'] }}</span>
                <span class="badge bg-info text-dark fs-6">Sakit {{ $hitung['Sakit'] }}</span>
                <span class="badge bg-danger fs-6">Alfa {{ $hitung['Alfa'] }}</span>
                <span class="badge bg-secondary fs-6">
                    Kehadiran: {{ $persen === null ? '-' : $persen . '%' }} ({{ $total }} pertemuan)
                </span>
            </div>
        </div>
    </div>

    <div class="card shadow-sm reveal">
        <div class="card-header bg-dark text-white"><h5 class="mb-0">Riwayat Presensi</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center">No</th>
                            <th>Tanggal</th>
                            <th>Mata Pelajaran</th>
                            <th>Guru</th>
                            <th>Topik</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($riwayat as $i => $r)
                            @php
                                $jadwal = $r->journal->jadwal ?? null;
                                $warna = ['Hadir' => 'success', 'Izin' => 'warning text-dark', 'Sakit' => 'info text-dark', 'Alfa' => 'danger'][$r->status] ?? 'secondary';
                            @endphp
                            <tr>
                                <td class="text-center">{{ $i + 1 }}</td>
                                <td>{{ $r->journal->date ?? '-' }}</td>
                                <td>
                                    @if($jadwal)
                                        {{ $jadwal->mataPelajaran->nama_mapel ?? $jadwal->id_mapel }}<br>
                                        <small class="text-muted">{{ substr($jadwal->jam_mulai, 0, 5) }} - {{ substr($jadwal->jam_selesai, 0, 5) }}@if($jadwal->jam_ke), {{ $jadwal->jam_ke }}@endif</small>
                                    @else
                                        <span class="text-muted fst-italic">Jadwal sudah dihapus</span>
                                    @endif
                                </td>
                                <td>{{ $jadwal->guru->nama_guru ?? '-' }}</td>
                                <td>{{ $r->journal->topic_description ?? '-' }}</td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $warna }}">{{ $r->status }}</span>
                                    @if($r->status === 'Izin' && !empty($r->keterangan))
                                        <div class="small text-muted mt-1">{{ $r->keterangan }}</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted">Belum ada data presensi untuk siswa ini pada periode tersebut.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
