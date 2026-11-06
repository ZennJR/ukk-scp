@extends('layouts.app')

@section('title', 'Rekap Tukar Ruangan - Waka Kurikulum')
@section('page-title', 'Rekap Tukar Ruangan Guru')

@section('content')
    @php
        $badge = ['Menunggu' => 'warning text-dark', 'Disetujui' => 'success', 'Ditolak' => 'danger', 'Dibatalkan' => 'secondary', 'Kedaluwarsa' => 'secondary'];
    @endphp

    @if(!$tabelAda)
        <div class="alert alert-warning"><strong>Tabel tukar ruangan belum dibuat.</strong> Jalankan <code>php artisan migrate</code>
            atau impor <code>database/add_tukar_ruangan.sql</code> lewat phpMyAdmin, lalu muat ulang halaman.</div>
    @else
        <div class="scp-stats reveal-stagger">
            <div class="scp-stat" style="--c:#2563eb"><div class="scp-stat-label">Total Permintaan</div><div class="scp-stat-value">{{ $stat['total'] }}</div></div>
            <div class="scp-stat" style="--c:#d97706"><div class="scp-stat-label">Menunggu</div><div class="scp-stat-value">{{ $stat['menunggu'] }}</div></div>
            <div class="scp-stat" style="--c:#16a34a"><div class="scp-stat-label">Disetujui</div><div class="scp-stat-value">{{ $stat['disetujui'] }}</div></div>
            <div class="scp-stat" style="--c:#dc2626"><div class="scp-stat-label">Ditolak / Batal / Kedaluwarsa</div><div class="scp-stat-value">{{ $stat['ditolak'] }}</div></div>
        </div>

        <form method="GET" action="{{ route('waka.tukar-ruangan.rekap') }}" class="card shadow-sm mb-3">
            <div class="card-body d-flex gap-2 flex-wrap align-items-end">
                <div>
                    <label class="form-label small fw-semibold mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="semua" @selected($status === 'semua')>Semua</option>
                        @foreach(['Menunggu', 'Disetujui', 'Ditolak', 'Dibatalkan', 'Kedaluwarsa'] as $s)
                            <option value="{{ $s }}" @selected($status === $s)>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>

        <div class="card shadow-sm reveal">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center">No</th><th>Tanggal</th><th>Pemohon</th><th>Ditukar dengan</th>
                                <th>Ruangan</th><th>Alasan</th><th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($list as $i => $r)
                                <tr>
                                    <td class="text-center">{{ $i + 1 }}</td>
                                    <td class="text-nowrap">{{ \Illuminate\Support\Carbon::parse($r->tanggal)->format('d-m-Y') }}</td>
                                    <td><strong>{{ $r->nama_pemohon ?? 'Guru' }}</strong><br><small class="text-muted">{{ $r->info_pemohon }}</small></td>
                                    <td><strong>{{ $r->nama_tujuan ?? 'Guru' }}</strong><br><small class="text-muted">{{ $r->info_tujuan }}</small></td>
                                    <td class="text-nowrap">{{ $r->ruangan_pemohon_nama }} &harr; {{ $r->ruangan_tujuan_nama }}</td>
                                    <td>{{ $r->alasan }}</td>
                                    <td>
                                        <span class="badge bg-{{ $badge[$r->status] ?? 'secondary' }}">{{ $r->status }}</span>
                                        @if($r->status === 'Ditolak' && $r->alasan_tolak)<div class="small text-danger">{{ $r->alasan_tolak }}</div>@endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-4">Belum ada data tukar ruangan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection
