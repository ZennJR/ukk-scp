@extends('layouts.app')

@section('title', 'Modul Jurnal Mengajar - UKK SCP')
@section('page-title', 'Modul Jurnal Mengajar & Presensi Guru')

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

            @include('partials.pelanggaran-guru')
            @include('partials.jurnal-belum-diisi')

            <div class="split-grid reveal-stagger">
                <!-- Tabel Riwayat Jurnal (kini di kiri, lebih lebar) -->
                <div class="card shadow-sm order-md-1">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">Riwayat Jurnal Mengajar</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-tidy align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="col-shrink">Tanggal</th>
                                        <th>Jadwal &amp; Topik</th>
                                        <th class="col-shrink">Presensi</th>
                                        <th class="col-shrink text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($jurnalList as $item)
                                        @php
                                            $tgl = \Illuminate\Support\Carbon::parse($item->date);
                                            $rekap = $item->attendances->groupBy('status')->map->count();
                                        @endphp
                                        <tr>
                                            <td class="col-shrink">
                                                <div class="cell-date">{{ $tgl->format('d M Y') }}<small>{{ [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'][$tgl->dayOfWeekIso] }}</small>
                                                    @if($item->filled_at)
                                                        @php
                                                            $telat = $item->jadwal && $item->filled_at->gt(\App\Support\PelanggaranGuru::batasWaktu($item->jadwal, $item->date));
                                                        @endphp
                                                        <small class="{{ $telat ? 'text-danger fw-semibold' : 'text-success' }}" title="Jam jurnal diisi"><i class="bi bi-clock-history"></i> Diisi {{ $item->filled_at->format('d-m-Y') }}, pukul {{ $item->filled_at->format('H:i') }} WIB{{ $telat ? ' (terlambat)' : '' }}</small>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                @if($item->jadwal)
                                                    <div class="cell-title">Kelas {{ $item->jadwal->kelas_label }}</div>
                                                    <div class="cell-sub">{{ $item->jadwal->mapel_label }}</div>
                                                    <div class="cell-meta">
                                                        <span><i class="bi bi-clock"></i>{{ substr($item->jadwal->jam_mulai, 0, 5) }}–{{ substr($item->jadwal->jam_selesai, 0, 5) }}</span>
                                                        @if($item->jadwal->jam_ke)<span class="tag tag-jam">{{ $item->jadwal->jam_ke }}</span>@endif
                                                        <span><i class="bi bi-door-open"></i>{{ $item->jadwal->ruangan_label }}</span>
                                                        <span class="tag">{{ $item->jadwal->cycle_type }}</span>
                                                    </div>
                                                @else
                                                    <span class="text-muted">Jadwal dihapus</span>
                                                @endif
                                                @if($item->topic_description)
                                                    <div class="cell-topic" title="{{ $item->topic_description }}">{{ $item->topic_description }}</div>
                                                @endif
                                            </td>
                                            <td class="col-shrink">
                                                @if($item->attendances->isEmpty())
                                                    <span class="text-muted">–</span>
                                                @else
                                                    @php $chips = ['H' => ['Hadir','att-h'], 'I' => ['Izin','att-i'], 'S' => ['Sakit','att-s'], 'A' => ['Alfa','att-a']]; @endphp
                                                    <div class="att-row">
                                                        @foreach($chips as $huruf => [$status, $kelasCss])
                                                            @php $n = $rekap->get($status, 0); @endphp
                                                            <span class="att-chip {{ $kelasCss }} {{ $n === 0 ? 'is-zero' : '' }}" title="{{ $status }}">{{ $huruf }} {{ $n }}</span>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="col-shrink">
                                                <div class="act-group">
                                                    @if($item->photo_path)
                                                        <a href="{{ asset($item->photo_path) }}" target="_blank" class="btn-icon" title="Lihat foto" aria-label="Lihat foto"><i class="bi bi-image"></i></a>
                                                    @endif
                                                    @if($item->jadwal)
                                                        @if($tgl->isToday())
                                                            <a href="{{ route('jurnal.presensi.edit', $item->id) }}" class="btn-icon is-success" title="Edit presensi (hanya hari ini)" aria-label="Edit presensi"><i class="bi bi-pencil-square"></i></a>
                                                        @else
                                                            <span class="btn-icon text-muted" style="opacity:.45;cursor:not-allowed" title="Terkunci: jurnal hanya bisa diedit pada hari yang sama" aria-label="Terkunci"><i class="bi bi-lock-fill"></i></span>
                                                        @endif
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">Belum ada jurnal.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Form Input Jurnal (kini di kanan, ringkas) -->
                <div class="card shadow-sm order-md-2">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Form Jurnal (Langkah 1 dari 2)</h5>
                    </div>
                    <div class="card-body">
                        <form id="formJurnal" action="{{ route('jurnal.lanjut') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Pilih Jadwal Mengajar <small class="text-muted fw-normal">(hari ini)</small></label>
                                <select name="schedule_id" class="form-select" required @disabled($jadwals->isEmpty())>
                                    <option value="">{{ $jadwals->isEmpty() ? '-- Tidak ada jadwal hari ini --' : '-- Pilih Jadwal Hari Ini --' }}</option>
                                    @foreach($jadwals as $j)
                                        <option value="{{ $j->id_jadwal }}" @selected(old('schedule_id') == $j->id_jadwal)>
                                            {{ substr($j->jam_mulai, 0, 5) }} - {{ substr($j->jam_selesai, 0, 5) }}@if($j->jam_ke) ({{ $j->jam_ke }})@endif | Kelas: {{ $j->kelas_label }} | Mapel: {{ $j->mapel_label }} | Ruang: {{ $j->ruangan_label }}
                                        </option>
                                    @endforeach
                                </select>
                                @if($jadwals->isEmpty())
                                    <small class="text-muted">Tidak ada jadwal mengajar hari ini yang belum dijurnal.</small>
                                @endif
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tanggal</label>
                                <input type="text" class="form-control" value="{{ date('d-m-Y') }}" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Deskripsi Topik / Materi</label>
                                <textarea name="topic_description" class="form-control" rows="3" required placeholder="Tuliskan materi pembelajaran...">{{ old('topic_description') }}</textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Foto Bukti KBM (Kamera)</label>

                                {{-- Input asli disembunyikan; diisi otomatis dari hasil jepretan kamera --}}
                                <input type="file" name="photo" id="fotoBukti" class="d-none" accept="image/jpeg">

                                <div id="kameraBox" class="border rounded-3 overflow-hidden bg-light">
                                    <div id="kameraLayar" class="position-relative d-none bg-dark">
                                        <video id="kameraVideo" class="w-100 d-block" autoplay playsinline muted></video>
                                    </div>
                                    <img id="kameraHasil" class="w-100 d-none" alt="Hasil foto bukti KBM">
                                    <div id="kameraKosong" class="text-center text-muted py-4 small">
                                        <i class="bi bi-camera fs-3 d-block mb-1"></i>
                                        Belum ada foto. Ambil foto langsung dengan kamera.
                                    </div>
                                </div>

                                <div class="d-flex gap-2 mt-2 flex-wrap">
                                    <button type="button" id="btnBukaKamera" class="btn btn-outline-success btn-sm flex-fill">
                                        <i class="bi bi-camera-fill me-1"></i><span>Buka Kamera</span>
                                    </button>
                                    <button type="button" id="btnJepret" class="btn btn-success btn-sm flex-fill d-none">
                                        <i class="bi bi-record-circle me-1"></i>Ambil Foto
                                    </button>
                                    <button type="button" id="btnGantiKamera" class="btn btn-outline-secondary btn-sm d-none" title="Ganti kamera depan/belakang">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </button>
                                    <button type="button" id="btnBatalKamera" class="btn btn-outline-secondary btn-sm d-none">Batal</button>
                                </div>
                                <small id="kameraPesan" class="text-danger d-block mt-1"></small>
                                <small class="text-muted">Foto hanya bisa diambil langsung dari kamera (tidak bisa dari galeri/file).</small>
                            </div>

                            <button type="submit" class="btn btn-success w-100">Lanjut ke Presensi <i class="bi bi-arrow-right ms-1"></i></button>
                            <small class="text-muted d-block mt-2">Jurnal baru tersimpan setelah presensi siswa diisi dan disimpan.</small>
                        </form>
                    </div>
                </div>
            </div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('formJurnal');
    if (!form) return;

    const input   = document.getElementById('fotoBukti');
    const video   = document.getElementById('kameraVideo');
    const layar   = document.getElementById('kameraLayar');
    const hasil   = document.getElementById('kameraHasil');
    const kosong  = document.getElementById('kameraKosong');
    const pesan   = document.getElementById('kameraPesan');
    const btnBuka = document.getElementById('btnBukaKamera');
    const btnJepret = document.getElementById('btnJepret');
    const btnGanti  = document.getElementById('btnGantiKamera');
    const btnBatal  = document.getElementById('btnBatalKamera');

    let stream = null;
    let facing = 'environment';
    const MAKS_SISI = 1280;   // perkecil agar < 2 MB
    const KUALITAS  = 0.8;

    const tampil = (el, ya) => el.classList.toggle('d-none', !ya);

    function matikanKamera() {
        if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; }
        video.srcObject = null;
    }

    function modeAwal(adaFoto) {
        matikanKamera();
        tampil(layar, false);
        tampil(btnJepret, false);
        tampil(btnGanti, false);
        tampil(btnBatal, false);
        tampil(hasil, adaFoto);
        tampil(kosong, !adaFoto);
        tampil(btnBuka, true);
        btnBuka.querySelector('span').textContent = adaFoto ? 'Ulangi Foto' : 'Buka Kamera';
    }

    async function bukaKamera() {
        pesan.textContent = '';
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            pesan.textContent = 'Kamera tidak didukung/diblokir di browser ini. Gunakan HTTPS dan browser terbaru.';
            return;
        }
        try {
            matikanKamera();
            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: facing }, width: { ideal: 1920 }, height: { ideal: 1080 } },
                audio: false
            });
            video.srcObject = stream;
            await video.play();
            tampil(layar, true);
            tampil(hasil, false);
            tampil(kosong, false);
            tampil(btnBuka, false);
            tampil(btnJepret, true);
            tampil(btnGanti, true);
            tampil(btnBatal, true);
        } catch (e) {
            const nama = e && e.name;
            pesan.textContent = (nama === 'NotAllowedError' || nama === 'SecurityError')
                ? 'Izin kamera ditolak. Aktifkan izin kamera di pengaturan browser lalu coba lagi.'
                : (nama === 'NotFoundError' || nama === 'OverconstrainedError')
                    ? 'Kamera tidak ditemukan di perangkat ini.'
                    : 'Gagal membuka kamera. Coba lagi.';
            modeAwal(!!input.files.length);
        }
    }

    function jepret() {
        const w = video.videoWidth, h = video.videoHeight;
        if (!w || !h) { pesan.textContent = 'Kamera belum siap, coba lagi sebentar.'; return; }
        const skala = Math.min(1, MAKS_SISI / Math.max(w, h));
        const canvas = document.createElement('canvas');
        canvas.width = Math.round(w * skala);
        canvas.height = Math.round(h * skala);
        canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);

        canvas.toBlob(function (blob) {
            if (!blob) { pesan.textContent = 'Gagal mengambil foto. Coba lagi.'; return; }
            const nama = 'bukti-kbm-' + Date.now() + '.jpg';
            const file = new File([blob], nama, { type: 'image/jpeg', lastModified: Date.now() });
            const dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;

            if (hasil.dataset.url) URL.revokeObjectURL(hasil.dataset.url);
            hasil.dataset.url = URL.createObjectURL(blob);
            hasil.src = hasil.dataset.url;
            pesan.textContent = '';
            modeAwal(true);
        }, 'image/jpeg', KUALITAS);
    }

    btnBuka.addEventListener('click', bukaKamera);
    btnJepret.addEventListener('click', jepret);
    btnBatal.addEventListener('click', function () { modeAwal(!!input.files.length); });
    btnGanti.addEventListener('click', function () {
        facing = facing === 'environment' ? 'user' : 'environment';
        bukaKamera();
    });

    form.addEventListener('submit', function (e) {
        if (!input.files.length) {
            e.preventDefault();
            pesan.textContent = 'Foto bukti KBM wajib diambil dengan kamera.';
            btnBuka.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });

    window.addEventListener('pagehide', matikanKamera);
    document.addEventListener('visibilitychange', function () { if (document.hidden) matikanKamera(); });
})();
</script>
@endpush
