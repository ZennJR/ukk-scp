@extends('layouts.app')

@section('title', 'Tukar Ruangan - UKK SCP')
@section('page-title', 'Tukar Ruangan dengan Guru Lain')

@push('styles')
<style>
    .tr-pilih { display: block; border: 1px solid var(--border, #dee2e6); border-radius: 12px; padding: .65rem .8rem; margin-bottom: .5rem; cursor: pointer; background: #fff; }
    .tr-pilih:hover { border-color: #2563eb; }
    .tr-pilih input { margin-right: .5rem; }
    .tr-pilih small { display: block; color: #6c757d; }
</style>
@endpush

@section('content')
    @php
        $badge = ['Menunggu' => 'warning text-dark', 'Disetujui' => 'success', 'Ditolak' => 'danger', 'Dibatalkan' => 'secondary', 'Kedaluwarsa' => 'secondary'];
    @endphp

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(!$tabelAda)
        <div class="alert alert-warning"><strong>Tabel tukar ruangan belum dibuat.</strong> Jalankan <code>php artisan migrate</code>
            atau impor <code>database/add_tukar_ruangan.sql</code> lewat phpMyAdmin, lalu muat ulang halaman.</div>
    @else
        <div class="alert alert-info d-flex gap-2 align-items-start">
            <i class="bi bi-info-circle-fill mt-1"></i>
            <div>
                Tukar ruangan berlaku <strong>hanya untuk tanggal yang dipilih</strong> (jadwal mingguan tidak berubah) dan harus
                <strong>disetujui guru yang bersangkutan</strong>. Syaratnya: jam kedua jadwal bersinggungan dan kelas belum dimulai.
            </div>
        </div>

        <div class="bento-grid reveal-stagger" style="grid-template-columns: 1fr 1fr;">
            {{-- Ajukan --}}
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">Ajukan Tukar Ruangan</div>
                <div class="card-body">
                    <form method="GET" action="{{ route('guru.tukar-ruangan') }}" class="mb-3">
                        <label class="form-label">1. Tanggal</label>
                        <select name="tanggal" class="form-select" onchange="this.form.submit()">
                            @foreach($pilihanTanggal as $val => $label)
                                <option value="{{ $val }}" @selected($tanggal === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </form>

                    <form method="GET" action="{{ route('guru.tukar-ruangan') }}" class="mb-3">
                        <input type="hidden" name="tanggal" value="{{ $tanggal }}">
                        <label class="form-label">2. Jadwal Anda yang ruangannya ingin ditukar</label>
                        @forelse($jadwalSaya as $j)
                            <label class="tr-pilih">
                                <input type="radio" name="jadwal" value="{{ $j->id_jadwal }}" @checked($dipilih && (int) $dipilih->id_jadwal === (int) $j->id_jadwal) onchange="this.form.submit()">
                                <strong>{{ substr($j->jam_mulai, 0, 5) }}-{{ substr($j->jam_selesai, 0, 5) }}</strong> &middot; {{ $j->kelas_label }} &middot; {{ $j->mapel_label }}
                                <small><i class="bi bi-door-open"></i> Ruang {{ $j->ruangan_label }}</small>
                            </label>
                        @empty
                            <div class="text-muted small">Tidak ada jadwal Anda yang bisa ditukar pada tanggal ini (tidak ada jadwal, sudah dimulai, atau sudah dalam proses tukar).</div>
                        @endforelse
                    </form>

                    @if($dipilih)
                        <form method="POST" action="{{ route('guru.tukar-ruangan.store') }}">
                            @csrf
                            <input type="hidden" name="tanggal" value="{{ $tanggal }}">
                            <input type="hidden" name="id_jadwal_saya" value="{{ $dipilih->id_jadwal }}">

                            <label class="form-label">3. Tukar dengan jadwal guru lain (jam bersinggungan)</label>
                            @forelse($kandidat as $k)
                                <label class="tr-pilih">
                                    <input type="radio" name="id_jadwal_tujuan" value="{{ $k->id_jadwal }}" required>
                                    <strong>{{ $k->guru->nama_guru ?? 'Guru' }}</strong>
                                    <small>{{ substr($k->jam_mulai, 0, 5) }}-{{ substr($k->jam_selesai, 0, 5) }} &middot; {{ $k->kelas_label }} &middot; {{ $k->mapel_label }}</small>
                                    <small><i class="bi bi-door-open"></i> Ruang {{ $k->ruangan_label }}</small>
                                </label>
                            @empty
                                <div class="text-muted small mb-2">Tidak ada jadwal guru lain yang bisa ditukar dengan jadwal ini.</div>
                            @endforelse

                            @if($kandidat->isNotEmpty())
                                <div class="mb-3 mt-2">
                                    <label class="form-label">4. Alasan</label>
                                    <input type="text" name="alasan" class="form-control" maxlength="255" required value="{{ old('alasan') }}"
                                           placeholder="Mis. praktik butuh ruang lab / proyektor rusak">
                                </div>
                                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-send-fill me-1"></i>Kirim Permintaan</button>
                            @endif
                        </form>
                    @endif
                </div>
            </div>

            {{-- Permintaan masuk --}}
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">Permintaan Masuk untuk Saya</div>
                <div class="card-body">
                    @forelse($masuk as $r)
                        <div class="border rounded-3 p-2 mb-2">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <strong>{{ $r->nama_lawan ?? 'Guru' }}</strong> &middot; {{ \Illuminate\Support\Carbon::parse($r->tanggal)->format('d-m-Y') }}
                                    <div class="small text-muted">Jadwal Anda: {{ $r->info_tujuan }}</div>
                                    <div class="small">Ruang <strong>{{ $r->ruangan_tujuan_nama }}</strong> &rarr; <strong>{{ $r->ruangan_pemohon_nama }}</strong></div>
                                    <div class="small text-muted">Alasan: {{ $r->alasan }}</div>
                                </div>
                                <span class="badge bg-{{ $badge[$r->status] ?? 'secondary' }}">{{ $r->status }}</span>
                            </div>
                            @if($r->status === 'Menunggu')
                                <div class="d-flex gap-2 mt-2">
                                    <form action="{{ route('guru.tukar-ruangan.setuju', $r->id) }}" method="POST" class="flex-fill">
                                        @csrf
                                        <button class="btn btn-success btn-sm w-100"><i class="bi bi-check2-circle"></i> Setujui</button>
                                    </form>
                                    <form action="{{ route('guru.tukar-ruangan.tolak', $r->id) }}" method="POST" class="flex-fill form-tolak">
                                        @csrf
                                        <input type="hidden" name="alasan_tolak" value="">
                                        <button class="btn btn-outline-danger btn-sm w-100"><i class="bi bi-x-circle"></i> Tolak</button>
                                    </form>
                                </div>
                            @elseif($r->status === 'Ditolak' && $r->alasan_tolak)
                                <div class="small text-danger mt-1">Alasan penolakan: {{ $r->alasan_tolak }}</div>
                            @endif
                        </div>
                    @empty
                        <div class="text-muted small text-center py-3">Belum ada permintaan tukar ruangan dari guru lain.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Permintaan saya --}}
        <div class="card shadow-sm mt-3 reveal">
            <div class="card-header bg-dark text-white">Permintaan yang Saya Ajukan</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Tanggal</th><th>Jadwal Saya</th><th>Ditukar dengan</th><th>Ruangan</th><th>Status</th><th>Aksi</th></tr>
                        </thead>
                        <tbody>
                            @forelse($keluar as $r)
                                <tr>
                                    <td class="text-nowrap">{{ \Illuminate\Support\Carbon::parse($r->tanggal)->format('d-m-Y') }}</td>
                                    <td>{{ $r->info_pemohon }}</td>
                                    <td>{{ $r->nama_lawan ?? 'Guru' }}<br><small class="text-muted">{{ $r->info_tujuan }}</small></td>
                                    <td class="text-nowrap">{{ $r->ruangan_pemohon_nama }} &rarr; <strong>{{ $r->ruangan_tujuan_nama }}</strong></td>
                                    <td>
                                        <span class="badge bg-{{ $badge[$r->status] ?? 'secondary' }}">{{ $r->status }}</span>
                                        @if($r->status === 'Ditolak' && $r->alasan_tolak)<div class="small text-danger">{{ $r->alasan_tolak }}</div>@endif
                                    </td>
                                    <td class="text-nowrap">
                                        @if($r->bisa_batal)
                                            <form action="{{ route('guru.tukar-ruangan.batal', $r->id) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('Batalkan tukar ruangan ini?');">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle"></i> Batalkan</button>
                                            </form>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted">Belum ada permintaan yang Anda ajukan.</td></tr>
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
document.querySelectorAll('.form-tolak').forEach(function (f) {
    f.addEventListener('submit', function (e) {
        var alasan = window.prompt('Alasan menolak (boleh dikosongkan):');
        if (alasan === null) { e.preventDefault(); return; }
        f.querySelector('input[name="alasan_tolak"]').value = alasan.trim();
    });
});
</script>
@endpush
