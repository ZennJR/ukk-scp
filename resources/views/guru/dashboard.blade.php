@extends('layouts.app')

@section('title', 'Dashboard Guru - UKK SCP')
@section('page-title', 'Dashboard Guru')

@section('content')
    @php
        $__hari = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
        $__bulan = ['January'=>'Januari','February'=>'Februari','March'=>'Maret','April'=>'April','May'=>'Mei','June'=>'Juni','July'=>'Juli','August'=>'Agustus','September'=>'September','October'=>'Oktober','November'=>'November','December'=>'Desember'];
        $tanggalHariIni = strtr(date('l, j F Y'), $__hari + $__bulan);

        $pill = [
            'terisi'      => ['Jurnal Terisi', 'ok'],
            'terlambat'   => ['Belum Jurnal', 'bad'],
            'menunggu'    => ['Isi Jurnal', 'warn'],
            'berlangsung' => ['Berlangsung', 'info'],
            'akan_datang' => ['Akan Datang', 'info'],
        ];
    @endphp

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @include('partials.pelanggaran-guru')

    {{-- Guru yang juga Wali Kelas: pintasan ke tugas kewalikelasan --}}
    @if(\App\Support\Peran::punya(3))
        <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2 reveal">
            <span><i class="bi bi-person-badge-fill me-1"></i> Anda juga bertugas sebagai <strong>Wali Kelas</strong>. Verifikasi izin siswa kelas Anda tersedia di menu samping.</span>
            <a href="{{ route('kesiswaan.izin.index') }}" class="btn btn-sm btn-warning"><i class="bi bi-patch-check-fill"></i> Verifikasi Izin</a>
        </div>
    @endif

    {{-- Judul + badge putaran aktif --}}
    <div class="scp-head reveal">
        <div>
            <h2>Selamat datang, {{ session('nama_lengkap') }}</h2>
            <p>Pantau jadwal mengajar dan jurnal Anda hari ini.</p>
        </div>
        <span class="scp-badge"><i class="bi bi-arrow-repeat"></i> {{ $putaranAktif }} Aktif</span>
    </div>

    {{-- Dashboard Alert: jadwal selesai > 30 menit tetapi jurnal belum diisi --}}
    @if($jurnalBelumDiisi->isNotEmpty())
        @php $pertama = $jurnalBelumDiisi->first(); @endphp
        <div class="scp-alert reveal" role="alert">
            <div class="scp-alert-icon"><i class="bi bi-exclamation-lg"></i></div>
            <div class="scp-alert-text">
                <strong>Pengingat Jurnal: {{ $jurnalBelumDiisi->count() }} jadwal belum diisi jurnalnya</strong>
                <span>
                    {{ $pertama->kelas->nama_kelas ?? $pertama->id_kelas }} &ndash; {{ $pertama->mataPelajaran->nama_mapel ?? $pertama->id_mapel }}
                    ({{ substr($pertama->jam_mulai, 0, 5) }}&ndash;{{ substr($pertama->jam_selesai, 0, 5) }})
                    selesai {{ $pertama->terlambat_teks }} lalu
                    @if($jurnalBelumDiisi->count() > 1) dan {{ $jurnalBelumDiisi->count() - 1 }} jadwal lainnya @endif
                </span>
            </div>
            <a href="{{ route('jurnal.index') }}" class="scp-alert-btn"><i class="bi bi-journal-richtext me-1"></i>Isi Jurnal</a>
        </div>
    @endif

    {{-- Kartu statistik --}}
    <div class="scp-stats reveal-stagger">
        <div class="scp-stat" style="--c:#2563eb">
            <div class="scp-stat-label">Jadwal Hari Ini</div>
            <div class="scp-stat-value">{{ $stat['jadwal'] }}</div>
        </div>
        <div class="scp-stat" style="--c:#16a34a">
            <div class="scp-stat-label">Jurnal Terisi</div>
            <div class="scp-stat-value">{{ $stat['terisi'] }}</div>
        </div>
        <div class="scp-stat" style="--c:#dc2626">
            <div class="scp-stat-label">Belum Diisi</div>
            <div class="scp-stat-value">{{ $stat['belum'] }}</div>
        </div>
        <div class="scp-stat" style="--c:#d97706">
            <div class="scp-stat-label">Status Jadwal</div>
            <div class="scp-stat-value scp-stat-text">{{ $stat['terkunci'] ? 'Terkunci' : 'Terbuka' }}</div>
        </div>
    </div>

    {{-- Jadwal mengajar hari ini --}}
    <div class="scp-panel reveal">
        <div class="scp-panel-head">
            <h5>Jadwal Mengajar Hari Ini</h5>
            <span class="text-muted small">{{ $tanggalHariIni }}</span>
        </div>

        @if($jadwalHariIni->isEmpty())
            <div class="scp-empty">
                <i class="bi bi-calendar-x"></i>
                <p>Tidak ada jadwal mengajar untuk Anda hari ini pada {{ $putaranAktif }}.</p>
            </div>
        @else
            {{-- Tampilan HP: daftar kartu --}}
            <div class="sched-list">
                @foreach($jadwalHariIni as $j)
                    @php [$label, $warna] = $pill[$j->status_jurnal]; @endphp
                    <div class="sched-card {{ $j->status_jurnal === 'terlambat' ? 'row-bad' : '' }}">
                        <div>
                            <div class="sched-time">{{ substr($j->jam_mulai, 0, 5) }}&ndash;{{ substr($j->jam_selesai, 0, 5) }}</div>
                            <div class="sched-title">{{ $j->kelas->nama_kelas ?? $j->id_kelas }} &middot; {{ $j->mataPelajaran->nama_mapel ?? $j->id_mapel }}</div>
                            <div class="sched-room">Ruang {{ $j->ruangan->nama_ruangan ?? $j->id_ruangan_default }}@if(!empty($j->ruangan_ditukar)) <span class="badge bg-info text-dark">tukar ruangan</span>@endif</div>
                        </div>
                        @if(in_array($j->status_jurnal, ['terlambat', 'menunggu']))
                            <a href="{{ route('jurnal.index') }}" class="scp-pill scp-pill-{{ $warna }}">{{ $label }}</a>
                        @else
                            <span class="scp-pill scp-pill-{{ $warna }}">{{ $label }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
            {{-- Tampilan desktop/tablet: tabel --}}
            <div class="table-responsive sched-table">
                <table class="scp-table no-stack">
                    <thead>
                        <tr>
                            <th>Jam</th>
                            <th>Kelas</th>
                            <th>Mata Pelajaran</th>
                            <th>Ruang</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($jadwalHariIni as $j)
                            @php [$label, $warna] = $pill[$j->status_jurnal]; @endphp
                            <tr class="{{ $j->status_jurnal === 'terlambat' ? 'row-bad' : '' }}">
                                <td class="fw-semibold">{{ substr($j->jam_mulai, 0, 5) }}&ndash;{{ substr($j->jam_selesai, 0, 5) }}</td>
                                <td>{{ $j->kelas->nama_kelas ?? $j->id_kelas }}</td>
                                <td>{{ $j->mataPelajaran->nama_mapel ?? $j->id_mapel }}</td>
                                <td>{{ $j->ruangan->nama_ruangan ?? $j->id_ruangan_default }}@if(!empty($j->ruangan_ditukar)) <span class="badge bg-info text-dark">tukar</span>@endif</td>
                                <td>
                                    @if(in_array($j->status_jurnal, ['terlambat', 'menunggu']))
                                        <a href="{{ route('jurnal.index') }}" class="scp-pill scp-pill-{{ $warna }}">{{ $label }}</a>
                                    @else
                                        <span class="scp-pill scp-pill-{{ $warna }}">{{ $label }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Status kunci tiap putaran (diatur Waka Kurikulum) --}}
    @include('partials.status-putaran')
@endsection
