@extends('layouts.app')

@section('title', 'Manajemen Jadwal Pelajaran - UKK SCP')
@section('page-title', 'Manajemen Jadwal Pelajaran & Anti-Tabrakan')

@section('content')
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(!$isWaka && empty($putaranBuka))
                <div class="alert alert-warning">
                    Tidak ada putaran yang terbuka, sehingga Anda belum dapat menambah jadwal.
                </div>
            @endif

            @if($guruLogin)
                <!-- Jadwal milik guru yang sedang login -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Jadwal Saya ({{ $guruLogin->nama_guru }})</h5>
                        <div class="d-flex align-items-center gap-2">
                            <span class="count-pill">{{ $jadwalSaya->count() }} jadwal</span>
                            <a href="#form-jadwal" class="btn btn-success btn-sm"><i class="bi bi-plus-lg"></i> Tambah Jadwal</a>
                        </div>
                    </div>
                    <div class="card-body">
                        @php
                            $urutHari = ['Senin' => 1, 'Selasa' => 2, 'Rabu' => 3, 'Kamis' => 4, 'Jumat' => 5, 'Sabtu' => 6, 'Minggu' => 7];
                            $sayaPerPutaran = [];
                            foreach (['Putaran 1', 'Putaran 2'] as $namaP) {
                                $sayaPerPutaran[$namaP] = $jadwalSaya->where('cycle_type', $namaP)
                                    ->sortBy(fn ($x) => sprintf('%d-%s', $urutHari[$x->hari] ?? 9, $x->jam_mulai))
                                    ->values();
                            }
                        @endphp

                        @foreach($sayaPerPutaran as $namaP => $listSaya)
                            <div class="putaran-block">
                                <div class="putaran-head">
                                    <h6>{{ $namaP }}</h6>
                                    @if($statusPutaran[$namaP] ?? false)
                                        <span class="tag tag-lock"><i class="bi bi-lock-fill"></i> Dikunci (sedang berjalan)</span>
                                    @else
                                        <span class="tag tag-open"><i class="bi bi-unlock-fill"></i> Terbuka</span>
                                    @endif
                                    <span class="count ms-auto">{{ $listSaya->count() }} jadwal</span>
                                </div>

                                @if($listSaya->isEmpty())
                                    <div class="hari-block is-empty"><span>Belum ada jadwal di {{ $namaP }}.</span></div>
                                @else
                                    <div class="table-responsive">
                                        <table class="table table-tidy align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Hari</th>
                                                    <th>Jam</th>
                                                    <th>Kelas</th>
                                                    <th>Mata Pelajaran</th>
                                                    <th>Ruangan</th>
                                                    <th>Tahun Ajaran</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($listSaya as $j)
                                                    <tr>
                                                        <td class="cell-title">{{ $j->hari }}</td>
                                                        <td>
                                                            <div class="time-range">{{ substr($j->jam_mulai, 0, 5) }} – {{ substr($j->jam_selesai, 0, 5) }}</div>
                                                            @if($j->jam_ke)<span class="tag tag-jam mt-1">{{ $j->jam_ke }}</span>@endif
                                                        </td>
                                                        <td class="cell-title">{{ $j->kelas->nama_kelas ?? $j->id_kelas }}</td>
                                                        <td>{{ $j->mataPelajaran->nama_mapel ?? $j->id_mapel }}</td>
                                                        <td class="text-nowrap">{{ $j->ruangan->nama_ruangan ?? $j->id_ruangan_default }}</td>
                                                        <td class="cell-sub text-nowrap">{{ $j->tahunAjaran->nama_semester ?? $j->id_tahun_ajaran }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        @endforeach

                        @if($jadwalSaya->isEmpty())
                            <p class="text-center text-muted mb-0 mt-3">Anda belum membuat jadwal.</p>
                        @endif
                    </div>
                </div>
            @endif

            <div class="split-grid">
                <!-- Daftar Jadwal Aktif (kini di kiri, lebih lebar) -->
                <div class="card shadow-sm order-md-1">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">Daftar Jadwal Pelajaran Aktif</h5>
                    </div>
                    <div class="card-body">
                        @include('jadwal._filter_kelas', ['kelas' => $kelas, 'mulaiKosong' => true])

                    @php
                            $jadwalPutaran1 = $jadwals->where('cycle_type', 'Putaran 1')->values();
                            $jadwalPutaran2 = $jadwals->where('cycle_type', 'Putaran 2')->values();
                        @endphp

                        @php
                            $daftarHari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
                            if ($jadwals->where('hari', 'Sabtu')->isNotEmpty()) {
                                $daftarHari[] = 'Sabtu';
                            }
                            $putaranList = ['Putaran 1' => $jadwalPutaran1, 'Putaran 2' => $jadwalPutaran2];
                        @endphp

                        @foreach($putaranList as $namaPutaran => $jadwalPutaran)
<div class="putaran-block">
                            <div class="putaran-head"><h6>{{ $namaPutaran }}</h6>
                                @if($statusPutaran[$namaPutaran] ?? false)
                                    <span class="tag tag-lock"><i class="bi bi-lock-fill"></i> Dikunci (sedang berjalan)</span>
                                @else
                                    <span class="tag tag-open"><i class="bi bi-unlock-fill"></i> Terbuka</span>
                                @endif
                            </div>

                            @foreach($daftarHari as $hari)
                                @php $jadwalHari = $jadwalPutaran->where('hari', $hari)->values(); @endphp
                                @if($jadwalHari->isEmpty())
                                    <div class="hari-block is-empty"><span class="hari-title">{{ $hari }}</span><span>Belum ada jadwal</span></div>
                                @else
                                <div class="hari-block">
                                    <div class="hari-title">{{ $hari }} <span class="count">{{ $jadwalHari->count() }} jadwal</span></div>
                                    <div class="table-responsive">
                                        <table class="table table-tidy sched-table align-middle mb-0">
                                            <thead>
                                                <tr><th>Jam</th><th>Kelas &amp; Mapel</th><th>Guru &amp; Ruangan</th></tr>
                                            </thead>
                                            <tbody>
                                                @foreach($jadwalHari as $j)
                                                    <tr data-kelas="{{ $j->kelas_search }}">
                                                        <td>
                                                            <div class="time-range">{{ substr($j->jam_mulai, 0, 5) }} – {{ substr($j->jam_selesai, 0, 5) }}</div>
                                                            @if($j->jam_ke)<span class="tag tag-jam mt-1">{{ $j->jam_ke }}</span>@endif
                                                        </td>
                                                        <td>
                                                            <div class="cell-title">{{ $j->kelas->nama_kelas ?? $j->id_kelas }}</div>
                                                            <div class="cell-sub">{{ $j->mataPelajaran->nama_mapel ?? $j->id_mapel }}</div>
                                                        </td>
                                                        <td>
                                                            <div>{{ $j->guru->nama_guru ?? $j->id_guru }}</div>
                                                            <div class="cell-meta"><span><i class="bi bi-door-open"></i>{{ $j->ruangan->nama_ruangan ?? $j->id_ruangan_default }}</span></div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                @endif
                            @endforeach
                        </div>
@endforeach
                    </div>
                </div>

                <!-- Form Input Jadwal (kini di kanan, ringkas) -->
                <div class="card shadow-sm order-md-2" id="form-jadwal">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Form Input Jadwal & Uji Anti-Tabrakan</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('jadwal.store') }}" method="POST">
                            @csrf

                            <!-- Pilihan Putaran (putaran yang sedang berjalan otomatis terkunci dan tidak bisa dipilih guru) -->
                            <div class="mb-3">
                                <label class="form-label">Putaran Jadwal</label>
                                <select name="cycle_type" class="form-select" required {{ $putaranDipilih === null ? 'disabled' : '' }}>
                                    @foreach($statusPutaran as $namaPutaran => $terkunci)
                                        <option value="{{ $namaPutaran }}"
                                            {{ ($terkunci && !$isWaka) ? 'disabled' : '' }}
                                            {{ $putaranDipilih === $namaPutaran ? 'selected' : '' }}>
                                            {{ $namaPutaran }}{{ $terkunci ? ' (Sedang berjalan - terkunci)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Pilih putaran tempat jadwal ini dibuat. Putaran yang sedang berjalan otomatis terkunci dan tidak dapat dipilih.</small>
                            </div>

                            <!-- Dropdown Tahun Ajaran -->
                            <div class="mb-3">
                                <label class="form-label">ID Tahun Ajaran</label>
                                <select name="id_tahun_ajaran" class="form-select" required>
                                    <option value="">-- Pilih Tahun Ajaran / Semester --</option>
                                    @foreach($tahunAjaran as $ta)
                                        <option value="{{ $ta->id_tahun_ajaran }}" {{ old('id_tahun_ajaran') == $ta->id_tahun_ajaran ? 'selected' : '' }}>
                                            {{ $ta->nama_semester ?? $ta->id_tahun_ajaran }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Dropdown Kelas -->
                            <div class="mb-3">
                                <label class="form-label">ID Kelas</label>
                                <select name="id_kelas" class="form-select" required>
                                    <option value="">-- Pilih Kelas --</option>
                                    @foreach($kelas as $k)
                                        <option value="{{ $k->id_kelas }}" {{ old('id_kelas') == $k->id_kelas ? 'selected' : '' }}>
                                            {{ $k->tingkat ?? '' }} {{ $k->id_jurusan ?? $k->nama_kelas ?? $k->id_kelas }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Dropdown Mata Pelajaran -->
                            <div class="mb-3">
                                <label class="form-label">Mata Pelajaran</label>
                                <select name="id_mapel" class="form-select" required>
                                    <option value="">-- Pilih Mata Pelajaran --</option>
                                    @foreach($mapels as $m)
                                        <option value="{{ $m->id_mapel }}" {{ old('id_mapel') == $m->id_mapel ? 'selected' : '' }}>
                                            {{ $m->nama_mapel }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Kolom Guru Pengampu (Otomatis Terkunci Jika Login Sebagai Guru) -->
                            <div class="mb-3">
                                <label class="form-label">Guru Pengampu</label>
                                @if(isset($guruLogin) && $guruLogin)
                                    <!-- Jika yang login adalah Guru, kunci kolom dan isi otomatis namanya -->
                                    <input type="hidden" name="id_guru" value="{{ $guruLogin->id_guru }}">
                                    <input type="text" class="form-control bg-light text-dark fw-bold" value="{{ $guruLogin->nama_guru }}" readonly>
                                    <small class="text-muted">Guru pengampu terisi otomatis sesuai akun Anda.</small>
                                @else
                                    <!-- Jika yang login Admin / Waka, tampilkan dropdown pilihan guru bebas -->
                                    <select name="id_guru" class="form-select" required>
                                        <option value="">-- Pilih Guru --</option>
                                        @foreach($gurus as $g)
                                            <option value="{{ $g->id_guru }}" {{ old('id_guru') == $g->id_guru ? 'selected' : '' }}>
                                                {{ $g->nama_guru }}
                                            </option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>

                            <!-- Dropdown Ruangan -->
                            <div class="mb-3">
                                <label class="form-label">Ruangan Default</label>
                                <select name="id_ruangan_default" class="form-select" required>
                                    <option value="">-- Pilih Ruangan --</option>
                                    @foreach($ruangans as $r)
                                        <option value="{{ $r->id_ruangan }}" {{ old('id_ruangan_default') == $r->id_ruangan ? 'selected' : '' }}>
                                            {{ $r->nama_ruangan ?? 'Ruang '.$r->id_ruangan }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Hari -->
                            <div class="mb-3">
                                <label class="form-label">Hari</label>
                                <select name="hari" id="selectHari" class="form-select" required>
                                    <option value="">-- Pilih Hari --</option>
                                    <option value="Senin">Senin</option>
                                    <option value="Selasa">Selasa</option>
                                    <option value="Rabu">Rabu</option>
                                    <option value="Kamis">Kamis</option>
                                    <option value="Jumat">Jumat</option>
                                </select>
                            </div>

                            <!-- Jam Mulai & Selesai -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Jam Mulai</label>
                                    <select name="jam_mulai" id="selectJamMulai" class="form-select" required disabled>
                                        <option value="">-- Pilih hari dulu --</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Jam Selesai</label>
                                    <select name="jam_selesai" id="selectJamSelesai" class="form-select" required disabled>
                                        <option value="">-- Pilih hari dulu --</option>
                                    </select>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-success w-100" {{ $putaranDipilih === null ? 'disabled' : '' }}>Simpan & Uji Jadwal</button>
                        </form>
                    </div>
                </div>
            </div>
@endsection

@push('scripts')
<script>
(function () {
    // Urutan jam pelajaran per hari, dari config/jam_pelajaran.php
    const JAM = @json(config('jam_pelajaran'));
    const oldHari = @json(old('hari'));
    const oldMulai = @json(old('jam_mulai'));
    const oldSelesai = @json(old('jam_selesai'));

    const selHari = document.getElementById('selectHari');
    const selMulai = document.getElementById('selectJamMulai');
    const selSelesai = document.getElementById('selectJamSelesai');
    if (!selHari || !selMulai || !selSelesai) return;

    function reset(sel, text) { sel.innerHTML = ''; sel.add(new Option(text, '')); }

    function isiMulai(hari, pilih) {
        reset(selMulai, '-- Pilih Jam Mulai --');
        reset(selSelesai, '-- Pilih Jam Mulai dulu --');
        selSelesai.disabled = true;
        const slots = JAM[hari] || [];
        slots.forEach(function (s) {
            selMulai.add(new Option('Jam ke-' + s[0] + ' (' + s[1] + ')', s[1] + ':00'));
        });
        selMulai.disabled = slots.length === 0;
        if (pilih) selMulai.value = pilih;
    }

    // Jam selesai hanya menampilkan slot yang berada di/setelah jam mulai terpilih
    function isiSelesai(hari, mulai, pilih) {
        reset(selSelesai, '-- Pilih Jam Selesai --');
        (JAM[hari] || []).forEach(function (s) {
            if (s[1] + ':00' >= mulai) {
                selSelesai.add(new Option('Jam ke-' + s[0] + ' (' + s[2] + ')', s[2] + ':00'));
            }
        });
        selSelesai.disabled = !mulai;
        if (pilih) selSelesai.value = pilih;
    }

    selHari.addEventListener('change', function () { isiMulai(selHari.value, null); });
    selMulai.addEventListener('change', function () {
        if (!selMulai.value) { reset(selSelesai, '-- Pilih Jam Mulai dulu --'); selSelesai.disabled = true; return; }
        isiSelesai(selHari.value, selMulai.value, null);
    });

    // Pulihkan pilihan setelah submit gagal (mis. jadwal bentrok)
    if (oldHari) {
        selHari.value = oldHari;
        isiMulai(oldHari, oldMulai);
        if (oldMulai) isiSelesai(oldHari, oldMulai, oldSelesai);
    }
})();
</script>
@endpush