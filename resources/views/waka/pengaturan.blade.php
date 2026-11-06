@extends('layouts.app')

@section('title', 'Siklus Blok & Lock Jadwal - SCP')
@section('page-title', 'Siklus Blok & Lock Jadwal')

@section('content')

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="scp-head reveal">
        <div>
            <h2>Siklus Blok & Lock Jadwal</h2>
            <p>Atur putaran yang berjalan; putaran tersebut otomatis terkunci untuk pengisian jadwal guru.</p>
        </div>
        <span class="scp-badge"><i class="bi bi-arrow-repeat"></i> {{ $config->active_cycle }} Aktif</span>
    </div>

    <div class="scp-grid-2 reveal">
        {{-- Putaran aktif (toggle global) --}}
        <div class="scp-panel mb-0">
            <div class="scp-panel-head"><h5>Putaran Aktif Minggu Ini</h5></div>
            <p class="scp-panel-note">Putaran yang dipilih otomatis terkunci, putaran lain terbuka. Seluruh dashboard guru ikut berubah.</p>

            <form action="{{ route('waka.putaran.aktif') }}" method="POST">
                @csrf
                <div class="scp-segment">
                    @foreach(array_keys($statusPutaran) as $nama)
                        <button type="submit" name="active_cycle" value="{{ $nama }}"
                                class="{{ $config->active_cycle === $nama ? 'active' : '' }}"
                                {{ $config->active_cycle === $nama ? 'aria-pressed=true' : '' }}>{{ $nama }}</button>
                    @endforeach
                </div>
            </form>

            <div class="mt-3 d-flex align-items-center gap-2">
                <span class="text-muted small">Status saat ini:</span>
                <span class="scp-pill scp-pill-info">{{ $config->active_cycle }} Aktif</span>
            </div>
            <div class="text-muted small mt-3">
                Terakhir diperbarui
                @if($diperbaruiOleh) oleh {{ $diperbaruiOleh }} @endif
                @if($config->updated_at) &middot; {{ \Carbon\Carbon::parse($config->updated_at)->diffForHumans() }} @endif
            </div>
        </div>

        {{-- Lock schedule: otomatis mengikuti putaran yang berjalan --}}
        <div class="scp-panel mb-0">
            <div class="scp-panel-head"><h5>Lock Schedule Otomatis</h5></div>
            <p class="scp-panel-note">Putaran yang sedang berjalan otomatis terkunci. Guru hanya dapat menambah jadwal pada putaran yang tidak berjalan.</p>

            @foreach($statusPutaran as $nama => $terkunci)
                <div class="scp-lock-item">
                    <div>
                        <h6>{{ $nama }}</h6>
                        <span class="scp-pill {{ $terkunci ? 'scp-pill-bad' : 'scp-pill-ok' }}">
                            <i class="bi {{ $terkunci ? 'bi-lock-fill' : 'bi-unlock-fill' }}"></i>
                            {{ $terkunci ? 'Terkunci (sedang berjalan)' : 'Terbuka' }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Kartu statistik --}}
    <div class="scp-stats reveal-stagger">
        <div class="scp-stat" style="--c:#2563eb">
            <div class="scp-stat-label">Total Guru</div>
            <div class="scp-stat-value">{{ $stat['total_guru'] }}</div>
        </div>
        <div class="scp-stat" style="--c:#16a34a">
            <div class="scp-stat-label">Jurnal Hari Ini</div>
            <div class="scp-stat-value">{{ $stat['jurnal_hari_ini'] }}</div>
        </div>
        <div class="scp-stat" style="--c:#dc2626">
            <div class="scp-stat-label">Belum Diisi</div>
            <div class="scp-stat-value">{{ $stat['belum_diisi'] }}</div>
        </div>
        <div class="scp-stat" style="--c:#d97706">
            <div class="scp-stat-label">Jadwal Terkunci</div>
            <div class="scp-stat-value">{{ $stat['terkunci'] }}/{{ $stat['total_putaran'] }}</div>
        </div>
    </div>

    {{-- Notifikasi rekap guru yang belum mengisi jurnal --}}
    <div class="scp-panel reveal">
        <div class="scp-panel-head">
            <h5>Notifikasi: Guru yang Belum Mengisi Jurnal</h5>
            <span class="text-muted small">Jadwal selesai lebih dari 30 menit &middot; {{ $config->active_cycle }}</span>
        </div>

        @if($belumJurnal->isEmpty())
            <div class="scp-empty">
                <i class="bi bi-check2-circle"></i>
                <p>Semua guru sudah mengisi jurnal untuk jadwal yang telah selesai hari ini.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="scp-table">
                    <thead>
                        <tr>
                            <th>Guru</th>
                            <th>Kelas / Mapel</th>
                            <th>Jam Selesai</th>
                            <th>Keterlambatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($belumJurnal as $j)
                            <tr>
                                <td class="fw-semibold">{{ $j->guru->nama_guru ?? ('Guru #' . $j->id_guru) }}</td>
                                <td>{{ $j->kelas->nama_kelas ?? $j->id_kelas }} &ndash; {{ $j->mataPelajaran->nama_mapel ?? $j->id_mapel }}</td>
                                <td>{{ substr($j->jam_selesai, 0, 5) }}</td>
                                <td><span class="scp-pill scp-pill-bad">{{ $j->terlambat_teks }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
