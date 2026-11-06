@extends('layouts.app')

@section('title', 'Rekap Jadwal Pelajaran - Waka Kurikulum')
@section('page-title', 'Rekap Jadwal Pelajaran Seluruh Guru')

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

            @php
                $filterAktif = $hariPilihan !== $hariIni || $putaranPilihan !== '' || $kelasCari !== '' || $cari !== '';
                $daftarHari = $hariPilihan === 'Semua'
                    ? array_merge(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'], $jadwals->where('hari', 'Sabtu')->isNotEmpty() ? ['Sabtu'] : [])
                    : [$hariPilihan];
                $putaranList = [
                    'Putaran 1' => $jadwals->where('cycle_type', 'Putaran 1')->values(),
                    'Putaran 2' => $jadwals->where('cycle_type', 'Putaran 2')->values(),
                ];
            @endphp

            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Seluruh Jadwal Pelajaran Terdaftar</h5>
                </div>
                <div class="card-body">

                    <!-- Filter (diproses di server). Bawaan: jadwal hari ini -->
                    <form method="GET" action="{{ route('waka.jadwal.rekap') }}" class="row g-2 align-items-end mb-2">
                        <div class="col-6 col-md-2">
                            <label class="form-label small mb-1">Hari</label>
                            <select name="hari" class="form-select">
                                <option value="Semua" {{ $hariPilihan === 'Semua' ? 'selected' : '' }}>Semua hari</option>
                                @if(!in_array($hariPilihan, $daftarPilihanHari, true) && $hariPilihan !== 'Semua')
                                    <option value="{{ $hariPilihan }}" selected>{{ $hariPilihan }} (hari ini)</option>
                                @endif
                                @foreach($daftarPilihanHari as $h)
                                    <option value="{{ $h }}" {{ $hariPilihan === $h ? 'selected' : '' }}>{{ $h }}{{ $h === $hariIni ? ' (hari ini)' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label small mb-1">Putaran</label>
                            <select name="putaran" class="form-select">
                                <option value="">Semua putaran</option>
                                <option value="Putaran 1" {{ $putaranPilihan === 'Putaran 1' ? 'selected' : '' }}>Putaran 1</option>
                                <option value="Putaran 2" {{ $putaranPilihan === 'Putaran 2' ? 'selected' : '' }}>Putaran 2</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label small mb-1">Kelas</label>
                            <input type="text" name="kelas" value="{{ $kelasCari }}" class="form-control" list="daftarKelasRekap"
                                   placeholder="mis. XII RPL 1" autocomplete="off">
                            <datalist id="daftarKelasRekap">
                                @foreach($kelas as $k)
                                    <option value="{{ trim(($k->tingkat ?? '') . ' ' . ($k->id_jurusan ?? '')) }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label small mb-1">Guru / Mata Pelajaran</label>
                            <input type="text" name="q" value="{{ $cari }}" class="form-control" placeholder="Cari nama guru atau mapel" autocomplete="off">
                        </div>
                        <div class="col-12 col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-search"></i> Tampilkan</button>
                            <a href="{{ route('waka.jadwal.rekap') }}" class="btn btn-outline-secondary" title="Kembali ke jadwal hari ini">Hari Ini</a>
                        </div>
                    </form>

                    <small class="text-muted d-block mb-3">
                        @if(!$filterAktif)
                            Menampilkan jadwal <strong>hari ini ({{ $hariIni }})</strong>: {{ $jadwals->count() }} jadwal.
                            Gunakan filter di atas untuk melihat jadwal hari, kelas, atau guru lain.
                        @else
                            Menampilkan {{ $jadwals->count() }} jadwal
                            @php
                                $infoFilter = ['hari: ' . ($hariPilihan === 'Semua' ? 'semua' : $hariPilihan)];
                                if ($putaranPilihan !== '') { $infoFilter[] = $putaranPilihan; }
                                if ($kelasCari !== '') { $infoFilter[] = 'kelas "' . $kelasCari . '"'; }
                                if ($cari !== '') { $infoFilter[] = '"' . $cari . '"'; }
                            @endphp
                            ({{ implode(', ', $infoFilter) }}).
                        @endif
                    </small>

                    @if($jadwals->isEmpty())
                        <div class="alert alert-warning mb-0">
                            @if(!$filterAktif)
                                Tidak ada jadwal pada hari ini ({{ $hariIni }}). Pilih hari lain pada filter untuk melihat jadwal.
                            @else
                                Tidak ada jadwal yang cocok dengan filter.
                            @endif
                        </div>
                    @endif

                    @foreach($putaranList as $namaPutaran => $jadwalPutaran)
                        @continue($jadwalPutaran->isEmpty())
                        <div class="putaran-block">
                            <h6 class="fw-bold mb-3 {{ $loop->first ? '' : 'mt-4' }}"><span class="badge bg-info text-dark me-1">{{ $namaPutaran }}</span>
                                <small class="text-muted fw-normal">{{ $jadwalPutaran->count() }} jadwal</small></h6>

                            @foreach($daftarHari as $hari)
                                @php $jadwalHari = $jadwalPutaran->where('hari', $hari)->values(); @endphp
                                @continue($jadwalHari->isEmpty())
                                <div class="hari-block"><div class="fw-semibold mb-1">{{ $hari }}</div>
                                <div class="table-responsive mb-3">
                                    <table class="table table-bordered table-striped align-middle mb-0">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Jam</th>
                                                <th>Kelas & Mapel</th>
                                                <th>Guru & Ruangan</th>
                                                <th class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($jadwalHari as $j)
                                                <tr>
                                                    <td>
                                                        <small>{{ substr($j->jam_mulai, 0, 5) }} - {{ substr($j->jam_selesai, 0, 5) }}</small>
                                                        @if($j->jam_ke)<br><small class="text-primary fw-semibold">{{ $j->jam_ke }}</small>@endif
                                                    </td>
                                                    <td>
                                                        {{ $j->kelas->tingkat ?? '' }} {{ $j->kelas->id_jurusan ?? $j->id_kelas }}<br>
                                                        <small class="text-muted">{{ $j->mataPelajaran->nama_mapel ?? $j->id_mapel }}</small>
                                                    </td>
                                                    <td>
                                                        Guru: {{ $j->guru->nama_guru ?? $j->id_guru }}<br>
                                                        <small class="text-muted">Ruang: {{ $j->ruangan->nama_ruangan ?? $j->id_ruangan_default }}</small>
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-sm btn-outline-primary btn-edit-jadwal"
                                                                data-bs-toggle="modal" data-bs-target="#editJadwalModal"
                                                                data-id="{{ $j->id_jadwal }}"
                                                                data-judul="{{ ($j->kelas->id_jurusan ?? $j->id_kelas) }} - {{ $j->mataPelajaran->nama_mapel ?? $j->id_mapel }}"
                                                                data-ta="{{ $j->id_tahun_ajaran }}"
                                                                data-putaran="{{ $j->cycle_type }}"
                                                                data-kelas="{{ $j->id_kelas }}"
                                                                data-mapel="{{ $j->id_mapel }}"
                                                                data-guru="{{ $j->id_guru }}"
                                                                data-ruangan="{{ $j->id_ruangan_default }}"
                                                                data-hari="{{ $j->hari }}"
                                                                data-mulai="{{ substr($j->jam_mulai, 0, 5) }}"
                                                                data-selesai="{{ substr($j->jam_selesai, 0, 5) }}">
                                                            <i class="bi bi-pencil-square"></i> Edit
                                                        </button>
                                                        <form action="{{ route('waka.jadwal.delete', $j->id_jadwal) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus jadwal ini? Tindakan ini tidak bisa dibatalkan.');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                <i class="bi bi-trash3"></i> Hapus
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div></div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
@endsection

@push('modals')
    <!-- Satu modal edit dipakai bersama untuk semua jadwal (diisi lewat JavaScript) -->
    <div class="modal fade" id="editJadwalModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="formEditJadwal" action="" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Jadwal: <span id="editJadwalJudul"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tahun Ajaran</label>
                                <select name="id_tahun_ajaran" class="form-select" required>
                                    @foreach($tahunAjaran as $ta)
                                        <option value="{{ $ta->id_tahun_ajaran }}">{{ $ta->nama_semester ?? $ta->id_tahun_ajaran }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Putaran</label>
                                <select name="cycle_type" class="form-select" required>
                                    <option value="Putaran 1">Putaran 1</option>
                                    <option value="Putaran 2">Putaran 2</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kelas</label>
                                <select name="id_kelas" class="form-select" required>
                                    @foreach($kelas as $k)
                                        <option value="{{ $k->id_kelas }}">{{ $k->tingkat ?? '' }} {{ $k->id_jurusan ?? $k->id_kelas }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mata Pelajaran</label>
                                <select name="id_mapel" class="form-select" required>
                                    @foreach($mapels as $m)
                                        <option value="{{ $m->id_mapel }}">{{ $m->nama_mapel }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Guru Pengampu</label>
                                <select name="id_guru" class="form-select" required>
                                    @foreach($gurus as $g)
                                        <option value="{{ $g->id_guru }}">{{ $g->nama_guru }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ruangan</label>
                                <select name="id_ruangan_default" class="form-select" required>
                                    @foreach($ruangans as $r)
                                        <option value="{{ $r->id_ruangan }}">{{ $r->nama_ruangan ?? 'Ruang '.$r->id_ruangan }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Hari</label>
                                <select name="hari" class="form-select" required>
                                    @foreach(['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'] as $hariOpt)
                                        <option value="{{ $hariOpt }}">{{ $hariOpt }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Jam Mulai</label>
                                <input type="time" name="jam_mulai" class="form-control" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Jam Selesai</label>
                                <input type="time" name="jam_selesai" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
<script>
(function () {
    const urlTemplate = @json(route('waka.jadwal.update', ['id' => '__ID__']));
    const form = document.getElementById('formEditJadwal');
    const judul = document.getElementById('editJadwalJudul');
    if (!form) return;

    function isi(nama, nilai) {
        const el = form.elements[nama];
        if (el && nilai !== undefined) el.value = nilai;
    }

    // Isi modal dari atribut data-* tombol Edit yang diklik
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-edit-jadwal');
        if (!btn) return;
        const d = btn.dataset;
        form.action = urlTemplate.replace('__ID__', d.id);
        judul.textContent = d.judul || '';
        isi('id_tahun_ajaran', d.ta);
        isi('cycle_type', d.putaran);
        isi('id_kelas', d.kelas);
        isi('id_mapel', d.mapel);
        isi('id_guru', d.guru);
        isi('id_ruangan_default', d.ruangan);
        isi('hari', d.hari);
        isi('jam_mulai', d.mulai);
        isi('jam_selesai', d.selesai);
    });
})();
</script>
@endpush
