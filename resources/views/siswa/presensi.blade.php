@extends('layouts.app')

@section('title', 'Presensi Saya - UKK SCP')
@section('page-title', 'Presensi Saya')

@section('content')
    @if(!$siswa)
        <div class="alert alert-warning">
            Data siswa Anda belum terdaftar di sistem, sehingga presensi belum bisa ditampilkan. Hubungi Waka Kurikulum.
        </div>
    @else
        <div class="card shadow-sm reveal mb-4">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between gap-3">
                    <div>
                        <h5 class="mb-1">{{ $siswa->nama_siswa }}</h5>
                        <div class="text-muted small">NISN: {{ $siswa->nisn }} &middot; Kelas: {{ $siswa->kelas->nama_kelas ?? 'Belum ada kelas' }}</div>
                    </div>
                    <form method="GET" action="{{ route('siswa.presensi') }}" class="row g-2 align-items-end">
                        <div class="col-auto">
                            <label class="form-label mb-1 small text-muted">Dari</label>
                            <input type="date" name="dari" value="{{ $dari }}" class="form-control form-control-sm">
                        </div>
                        <div class="col-auto">
                            <label class="form-label mb-1 small text-muted">Sampai</label>
                            <input type="date" name="sampai" value="{{ $sampai }}" class="form-control form-control-sm">
                        </div>
                        <div class="col-auto"><button class="btn btn-sm btn-primary">Terapkan</button></div>
                        @if($dari || $sampai)
                            <div class="col-auto"><a href="{{ route('siswa.presensi') }}" class="btn btn-sm btn-outline-secondary">Reset</a></div>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-6 col-md"><div class="card shadow-sm h-100"><div class="card-body py-3"><div class="small text-muted">Hadir</div><div class="fs-3 fw-bold text-success">{{ $hitung['Hadir'] }}</div><div class="small text-muted">kali</div></div></div></div>
            <div class="col-6 col-md"><div class="card shadow-sm h-100"><div class="card-body py-3"><div class="small text-muted">Izin</div><div class="fs-3 fw-bold text-warning">{{ $hitung['Izin'] }}</div><div class="small text-muted">kali</div></div></div></div>
            <div class="col-6 col-md"><div class="card shadow-sm h-100"><div class="card-body py-3"><div class="small text-muted">Sakit</div><div class="fs-3 fw-bold text-info">{{ $hitung['Sakit'] }}</div><div class="small text-muted">kali</div></div></div></div>
            <div class="col-6 col-md"><div class="card shadow-sm h-100"><div class="card-body py-3"><div class="small text-muted">Alfa</div><div class="fs-3 fw-bold text-danger">{{ $hitung['Alfa'] }}</div><div class="small text-muted">kali</div></div></div></div>
            <div class="col-12 col-md"><div class="card shadow-sm h-100"><div class="card-body py-3"><div class="small text-muted">Kehadiran</div><div class="fs-3 fw-bold">{{ $persen === null ? '-' : $persen . '%' }}</div><div class="small text-muted">dari {{ $total }} pertemuan</div></div></div></div>
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
                                            <small class="text-muted">{{ substr($jadwal->jam_mulai, 0, 5) }} - {{ substr($jadwal->jam_selesai, 0, 5) }}</small>
                                        @else
                                            <span class="text-muted fst-italic">-</span>
                                        @endif
                                    </td>
                                    <td>{{ $jadwal->guru->nama_guru ?? '-' }}</td>
                                    <td class="text-center"><span class="badge bg-{{ $warna }}">{{ $r->status }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted">Belum ada data presensi pada periode ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection
