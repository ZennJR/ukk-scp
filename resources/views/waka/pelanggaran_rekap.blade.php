@extends('layouts.app')

@section('title', 'Rekap Pelanggaran Guru - Waka Kurikulum')
@section('page-title', 'Rekap Pelanggaran Guru')

@section('content')
    @php
        $namaBulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    @endphp

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(!$tabelAda)
        <div class="alert alert-warning">
            <strong>Tabel pelanggaran belum dibuat.</strong> Jalankan <code>php artisan migrate</code>, atau impor
            file <code>database/add_pelanggaran_guru.sql</code> lewat phpMyAdmin pada database aplikasi, lalu muat ulang halaman ini.
        </div>
    @else
        <div class="alert alert-info d-flex gap-2 align-items-start">
            <i class="bi bi-info-circle-fill mt-1"></i>
            <div>
                <strong>Aturan:</strong> jurnal wajib diisi paling lambat <strong>{{ $batasJam }} jam setelah jam selesai jadwal</strong>.
                Jika lewat dan belum diisi, guru tercatat <em>Tidak Mengisi</em>; jika diisi setelah batas, tercatat <em>Terlambat</em>.
                Guru dan Waka otomatis mendapat notifikasi.
            </div>
        </div>

        {{-- Filter --}}
        <form method="GET" action="{{ route('waka.pelanggaran.rekap') }}" class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold mb-1">Bulan</label>
                        <select name="bulan" class="form-select form-select-sm">
                            <option value="semua" @selected($filter['bulan'] === 'semua')>Semua waktu</option>
                            @for($i = 0; $i < 12; $i++)
                                @php $b = now()->startOfMonth()->subMonths($i); @endphp
                                <option value="{{ $b->format('Y-m') }}" @selected($filter['bulan'] === $b->format('Y-m'))>{{ $namaBulan[(int) $b->format('n')] }} {{ $b->format('Y') }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold mb-1">Guru</label>
                        <select name="guru" class="form-select form-select-sm">
                            <option value="">Semua guru</option>
                            @foreach($gurus as $g)
                                <option value="{{ $g->id_guru }}" @selected((string) $filter['idGuru'] === (string) $g->id_guru)>{{ $g->nama_guru }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-semibold mb-1">Jenis</label>
                        <select name="jenis" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            <option value="Tidak Mengisi" @selected($filter['jenis'] === 'Tidak Mengisi')>Tidak Mengisi</option>
                            <option value="Terlambat" @selected($filter['jenis'] === 'Terlambat')>Terlambat</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-semibold mb-1">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="Aktif" @selected($filter['status'] === 'Aktif')>Aktif</option>
                            <option value="Dibatalkan" @selected($filter['status'] === 'Dibatalkan')>Dibatalkan</option>
                            <option value="semua" @selected($filter['status'] === 'semua')>Semua</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2 d-flex gap-2">
                        <button class="btn btn-primary btn-sm flex-fill" type="submit"><i class="bi bi-funnel-fill"></i> Terapkan</button>
                        <a href="{{ route('waka.pelanggaran.rekap') }}" class="btn btn-outline-secondary btn-sm" title="Reset filter"><i class="bi bi-arrow-counterclockwise"></i></a>
                    </div>
                </div>
            </div>
        </form>

        {{-- Statistik --}}
        <div class="scp-stats reveal-stagger">
            <div class="scp-stat" style="--c:#dc2626">
                <div class="scp-stat-label">Total Pelanggaran</div>
                <div class="scp-stat-value">{{ $stat['total'] }}</div>
            </div>
            <div class="scp-stat" style="--c:#2563eb">
                <div class="scp-stat-label">Guru Terkena</div>
                <div class="scp-stat-value">{{ $stat['guru'] }}</div>
            </div>
            <div class="scp-stat" style="--c:#d97706">
                <div class="scp-stat-label">Tidak Mengisi</div>
                <div class="scp-stat-value">{{ $stat['tidak_mengisi'] }}</div>
            </div>
            <div class="scp-stat" style="--c:#7c3aed">
                <div class="scp-stat-label">Terlambat Diisi</div>
                <div class="scp-stat-value">{{ $stat['terlambat'] }}</div>
            </div>
        </div>

        {{-- Ringkasan per guru --}}
        <div class="card shadow-sm mb-3 reveal">
            <div class="card-header bg-dark text-white"><h6 class="mb-0">Ringkasan per Guru (pelanggaran aktif)</h6></div>
            <div class="card-body">
                @if($perGuru->isEmpty())
                    <div class="text-center text-muted py-3"><i class="bi bi-check2-circle fs-4 d-block"></i>Tidak ada pelanggaran pada filter ini.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width:60px">No</th>
                                    <th>Nama Guru</th>
                                    <th class="text-center">Tidak Mengisi</th>
                                    <th class="text-center">Terlambat</th>
                                    <th class="text-center">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($perGuru as $i => $r)
                                    <tr>
                                        <td class="text-center">{{ $i + 1 }}</td>
                                        <td><strong>{{ $r->nama_guru }}</strong>@if($r->nip)<br><small class="text-muted">NIP {{ $r->nip }}</small>@endif</td>
                                        <td class="text-center">{{ $r->tidak_mengisi }}</td>
                                        <td class="text-center">{{ $r->terlambat }}</td>
                                        <td class="text-center"><span class="badge bg-danger">{{ $r->total }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- Detail --}}
        <div class="card shadow-sm reveal">
            <div class="card-header bg-dark text-white"><h6 class="mb-0">Detail Pelanggaran</h6></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center">No</th>
                                <th>Tanggal</th>
                                <th>Guru</th>
                                <th>Kelas &amp; Mapel</th>
                                <th class="text-nowrap">Jam Jadwal</th>
                                <th class="text-nowrap">Batas Isi</th>
                                <th class="text-nowrap">Diisi Pukul</th>
                                <th>Jenis</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($list as $i => $p)
                                <tr class="{{ $p->status === 'Dibatalkan' ? 'text-muted' : '' }}">
                                    <td class="text-center">{{ $i + 1 }}</td>
                                    <td class="text-nowrap">{{ \Illuminate\Support\Carbon::parse($p->tanggal)->format('d-m-Y') }}</td>
                                    <td>{{ $p->nama_guru ?? ('Guru #' . $p->id_guru) }}</td>
                                    <td><strong>{{ $p->kelas }}</strong><br><small class="text-muted">{{ $p->mapel }}</small></td>
                                    <td class="text-nowrap">{{ substr($p->jam_mulai, 0, 5) }} - {{ substr($p->jam_selesai, 0, 5) }}</td>
                                    <td class="text-nowrap">{{ \Illuminate\Support\Carbon::parse($p->batas_waktu)->format('d-m H:i') }}</td>
                                    <td class="text-nowrap">
                                        @if($p->jurnal_diisi_at)
                                            {{ \Illuminate\Support\Carbon::parse($p->jurnal_diisi_at)->format('d-m H:i') }}
                                        @else
                                            <span class="text-muted">Belum diisi</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $p->jenis === 'Terlambat' ? 'bg-warning text-dark' : 'bg-danger' }}">{{ $p->jenis }}</span>
                                        @if($p->status === 'Dibatalkan')
                                            <br><span class="badge bg-secondary mt-1">Dibatalkan</span>
                                            @if($p->catatan)<br><small>{{ $p->catatan }}</small>@endif
                                        @endif
                                    </td>
                                    <td class="text-center text-nowrap">
                                        @if($p->status === 'Aktif')
                                            <form action="{{ route('waka.pelanggaran.status', $p->id) }}" method="POST" class="d-inline form-batal">
                                                @csrf
                                                <input type="hidden" name="status" value="Dibatalkan">
                                                <input type="hidden" name="catatan" value="">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary" title="Batalkan pelanggaran ini (mis. jadwal tidak berlangsung / guru dinas)">
                                                    <i class="bi bi-x-circle"></i> Batalkan
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('waka.pelanggaran.status', $p->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="status" value="Aktif">
                                                <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-counterclockwise"></i> Aktifkan</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center text-muted py-4">Belum ada data pelanggaran pada filter ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
<script>
document.querySelectorAll('.form-batal').forEach(function (f) {
    f.addEventListener('submit', function (e) {
        var alasan = window.prompt('Alasan pembatalan pelanggaran (mis. guru dinas luar / jadwal tidak berlangsung):');
        if (alasan === null) { e.preventDefault(); return; }
        f.querySelector('input[name="catatan"]').value = alasan.trim();
    });
});
</script>
@endpush
