@extends('layouts.app')

@section('title', 'Presensi Siswa - UKK SCP')
@section('page-title', 'Presensi Siswa')
@section('back-url', route('jurnal.index'))

@push('styles')
<style>
    .presensi-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
    .presensi-row .nama { font-weight: 500; }
    .presensi-row .btn { min-width: 64px; }
</style>
@endpush

@section('content')
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
        $namaKelas = $jadwal->kelas_label;
        $namaMapel = $jadwal->mataPelajaran->nama_mapel ?? $jadwal->id_mapel;
        $adaOtomatis = $daftarSiswa->contains(fn ($s) => $s->status_izin);
    @endphp

    <div class="bento-grid reveal-stagger" style="grid-template-columns: 1fr 1.5fr;">
        <!-- Ringkasan jurnal (langkah 1) -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Ringkasan Jurnal</h5>
            </div>
            <div class="card-body">
                <dl class="mb-0">
                    <dt>Mata Pelajaran / Kelas</dt>
                    <dd>{{ $namaMapel }} - {{ $namaKelas }}</dd>
                    <dt>Tanggal</dt>
                    <dd>{{ date('d-m-Y', strtotime($draft['tanggal'])) }}</dd>
                    <dt>Topik Pembelajaran</dt>
                    <dd>{{ $draft['topic_description'] }}</dd>
                    <dt>Foto Bukti</dt>
                    <dd class="mb-0"><i class="bi bi-check-circle-fill text-success me-1"></i>Siap disimpan</dd>
                </dl>
            </div>
        </div>

        <!-- Presensi siswa (langkah 2) -->
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Presensi Siswa (Langkah 2 dari 2)</h5>
            </div>
            <div class="card-body">
                <h6 class="fw-bold mb-3">{{ $namaKelas }} - {{ $daftarSiswa->count() }} siswa</h6>

                <form id="form-presensi" action="{{ route('jurnal.store') }}" method="POST">
                    @csrf

                    @forelse($daftarSiswa as $i => $siswa)
                        <div class="border rounded p-2 mb-2 presensi-row">
                            <span class="nama">{{ $i + 1 }}. {{ $siswa->nama_siswa }}</span>

                            @if($siswa->status_izin)
                                {{-- Izin/Sakit Approved: dikunci dari sistem --}}
                                <span class="badge bg-warning text-dark fs-6" title="{{ $siswa->alasan_izin }}">
                                    <i class="bi bi-lock-fill me-1"></i>{{ $siswa->status_izin }}
                                </span>
                            @else
                                <div class="btn-group btn-group-sm" role="group" aria-label="Status {{ $siswa->nama_siswa }}">
                                    @foreach(['Hadir' => 'success', 'Izin' => 'warning', 'Sakit' => 'warning', 'Alfa' => 'danger'] as $status => $warna)
                                        <input type="radio" class="btn-check status-radio" name="attendance[{{ $siswa->id_siswa }}]"
                                               id="att-{{ $siswa->id_siswa }}-{{ $status }}" value="{{ $status }}"
                                               {{ old("attendance.{$siswa->id_siswa}", 'Hadir') === $status ? 'checked' : '' }}>
                                        <label class="btn btn-outline-{{ $warna }}" for="att-{{ $siswa->id_siswa }}-{{ $status }}">{{ $status }}</label>
                                    @endforeach
                                </div>
                                <div class="w-100 keterangan-izin" id="ket-wrap-{{ $siswa->id_siswa }}" hidden>
                                    <input type="text" name="keterangan[{{ $siswa->id_siswa }}]" class="form-control form-control-sm"
                                           maxlength="255" list="saran-keterangan" autocomplete="off"
                                           placeholder="Keterangan izin, mis. mengikuti kegiatan OSIS"
                                           value="{{ old("keterangan.{$siswa->id_siswa}", null) }}">
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="alert alert-secondary mb-3">
                            Belum ada siswa aktif yang terdaftar di kelas <strong>{{ $namaKelas }}</strong>.
                            Jurnal tetap bisa disimpan tanpa data presensi.
                        </div>
                    @endforelse

                    <datalist id="saran-keterangan">
                        <option value="Mengikuti kegiatan OSIS">
                        <option value="Dispensasi lomba / kegiatan sekolah">
                        <option value="Mengikuti kegiatan ekstrakurikuler">
                        <option value="Urusan keluarga">
                        <option value="Mengurus administrasi sekolah">
                    </datalist>

                    @if($adaOtomatis)
                        <div class="alert alert-warning py-2 mt-3">
                            <i class="bi bi-info-circle me-1"></i>Izin/Sakit terisi otomatis dari pengajuan yang disetujui.
                        </div>
                    @endif

                    @if($daftarSiswa->isNotEmpty())
                        <div class="d-flex gap-3 flex-wrap small text-muted mb-3" id="ringkasan-presensi">
                            <span>Hadir: <strong id="c-Hadir">0</strong></span>
                            <span>Izin: <strong id="c-Izin">0</strong></span>
                            <span>Sakit: <strong id="c-Sakit">0</strong></span>
                            <span>Alfa: <strong id="c-Alfa">0</strong></span>
                        </div>
                    @endif

                    <div class="d-flex gap-2">
                        <button type="submit" form="form-batal" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Kembali
                        </button>
                        <button type="submit" class="btn btn-success flex-grow-1">
                            <i class="bi bi-check2-circle me-1"></i>Simpan Jurnal & Presensi
                        </button>
                    </div>
                </form>

                <form id="form-batal" action="{{ route('jurnal.batal') }}" method="POST">@csrf</form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        var kunci = @json($daftarSiswa->pluck('status_izin')->filter()->values());
        function hitung() {
            var total = {Hadir: 0, Izin: 0, Sakit: 0, Alfa: 0};
            kunci.forEach(function (s) { total[s]++; });
            document.querySelectorAll('.status-radio:checked').forEach(function (r) { total[r.value]++; });
            Object.keys(total).forEach(function (k) {
                var el = document.getElementById('c-' + k);
                if (el) el.textContent = total[k];
            });
        }
        function tampilKeterangan() {
            document.querySelectorAll('.keterangan-izin').forEach(function (box) {
                var id = box.id.replace('ket-wrap-', '');
                var izin = document.getElementById('att-' + id + '-Izin');
                box.hidden = !(izin && izin.checked);
            });
        }
        document.querySelectorAll('.status-radio').forEach(function (r) {
            r.addEventListener('change', function () { hitung(); tampilKeterangan(); });
        });
        hitung();
        tampilKeterangan();
    })();
</script>
@endpush
