@extends('layouts.app')

@section('title', 'Rekap Presensi Siswa - UKK SCP')
@section('page-title', 'Rekap Presensi Siswa')

@section('content')
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Filter -->
    <div class="card shadow-sm reveal mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('presensi.rekap') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label mb-1 small text-muted">Kelas</label>
                    <select name="kelas" class="form-select">
                        <option value="">Semua Kelas</option>
                        @foreach($kelasList as $k)
                            <option value="{{ $k->id_kelas }}" {{ (string) $kelasId === (string) $k->id_kelas ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1 small text-muted">Dari Tanggal</label>
                    <input type="date" name="dari" value="{{ $dari }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1 small text-muted">Sampai Tanggal</label>
                    <input type="date" name="sampai" value="{{ $sampai }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1 small text-muted">Cari Nama / NISN</label>
                    <input type="text" name="q" value="{{ $keyword }}" class="form-control" placeholder="Ketik nama atau NISN...">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">Tampilkan</button>
                    @if($kelasId !== '' || $keyword !== '' || $dari || $sampai)
                        <a href="{{ route('presensi.rekap') }}" class="btn btn-outline-secondary">Reset</a>
                    @endif
                </div>
            </form>
            <div class="small text-muted mt-2">
                Periode: {{ $dari ? date('d-m-Y', strtotime($dari)) : 'awal' }} s/d {{ $sampai ? date('d-m-Y', strtotime($sampai)) : 'sekarang' }}.
                Data bersumber dari presensi yang diisi guru pada jurnal mengajar.
            </div>
        </div>
    </div>

    <!-- Ringkasan -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md"><div class="card shadow-sm h-100"><div class="card-body py-3"><div class="small text-muted">Siswa</div><div class="fs-4 fw-bold">{{ $ringkasan['siswa'] }}</div></div></div></div>
        <div class="col-6 col-md"><div class="card shadow-sm h-100"><div class="card-body py-3"><div class="small text-muted">Hadir</div><div class="fs-4 fw-bold text-success">{{ $ringkasan['hadir'] }}</div></div></div></div>
        <div class="col-6 col-md"><div class="card shadow-sm h-100"><div class="card-body py-3"><div class="small text-muted">Izin</div><div class="fs-4 fw-bold text-warning">{{ $ringkasan['izin'] }}</div></div></div></div>
        <div class="col-6 col-md"><div class="card shadow-sm h-100"><div class="card-body py-3"><div class="small text-muted">Sakit</div><div class="fs-4 fw-bold text-info">{{ $ringkasan['sakit'] }}</div></div></div></div>
        <div class="col-6 col-md"><div class="card shadow-sm h-100"><div class="card-body py-3"><div class="small text-muted">Alfa</div><div class="fs-4 fw-bold text-danger">{{ $ringkasan['alfa'] }}</div></div></div></div>
    </div>

    <!-- Tabel rekap -->
    <div class="card shadow-sm reveal">
        <div class="card-header bg-dark text-white"><h5 class="mb-0">Rekap per Siswa</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center">No</th>
                            <th>NISN</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th class="text-center">Hadir</th>
                            <th class="text-center">Izin</th>
                            <th class="text-center">Sakit</th>
                            <th class="text-center">Alfa</th>
                            <th class="text-center">% Hadir</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($baris as $i => $b)
                            <tr>
                                <td class="text-center">{{ $i + 1 }}</td>
                                <td>{{ $b->siswa->nisn }}</td>
                                <td>{{ $b->siswa->nama_siswa }}</td>
                                <td>{{ $b->siswa->kelas->nama_kelas ?? '-' }}</td>
                                <td class="text-center">{{ $b->hadir }}</td>
                                <td class="text-center">{{ $b->izin }}</td>
                                <td class="text-center">{{ $b->sakit }}</td>
                                <td class="text-center {{ $b->alfa > 0 ? 'text-danger fw-bold' : '' }}">{{ $b->alfa }}</td>
                                <td class="text-center">
                                    @if($b->persen === null)
                                        <span class="text-muted">-</span>
                                    @else
                                        <span class="badge bg-{{ $b->persen >= 90 ? 'success' : ($b->persen >= 75 ? 'warning text-dark' : 'danger') }}">{{ $b->persen }}%</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('presensi.siswa', ['id' => $b->siswa->id_siswa, 'dari' => $dari, 'sampai' => $sampai]) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center text-muted">Tidak ada siswa yang cocok. Pastikan siswa sudah memiliki kelas di Manajemen Akun.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
