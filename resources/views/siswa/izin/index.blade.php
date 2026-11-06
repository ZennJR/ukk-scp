@extends('layouts.app')

@section('title', 'Portal Pengajuan Izin Siswa - UKK SCP')
@section('page-title', 'Pengajuan Izin / Sakit Siswa')

@push('styles')
<style>
    .izin-submit { position: relative; z-index: 2; touch-action: manipulation; }
    .izin-form-card { scroll-margin-bottom: 6rem; }
    #izinPesan:empty { display: none; }
</style>
@endpush

@section('content')
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <div class="bento-grid reveal-stagger" style="grid-template-columns: 1.5fr 1fr;">
                <!-- Riwayat Pengajuan Izin (kini di kiri, lebih lebar) -->
                <div class="card shadow-sm order-md-1">
                    <div class="card-header bg-dark text-white">Riwayat Pengajuan Izin Saya</div>
                    <div class="card-body">
                        <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Alasan</th>
                                    <th>Dokumen</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($riwayatIzin as $izin)
                                <tr>
                                    <td>{{ $izin->start_date }} s/d {{ $izin->end_date }}</td>
                                    <td>{{ $izin->reason }}</td>
                                    <td>
                                        @if($izin->document_path)
                                            <a href="{{ asset($izin->document_path) }}" target="_blank" class="btn btn-sm btn-outline-info">Lihat</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if($izin->status_approval == 'Approved')
                                            <span class="badge bg-success">Disetujui</span>
                                        @elseif($izin->status_approval == 'Rejected')
                                            <span class="badge bg-danger">Ditolak</span>
                                            @if($izin->reject_reason)
                                                <div class="small text-danger mt-1"><strong>Alasan:</strong> {{ $izin->reject_reason }}</div>
                                            @endif
                                        @else
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">
                                        @if($izin->status_approval !== 'Approved' && $izin->status_approval !== 'Rejected')
                                            <form action="{{ route('siswa.izin.destroy', $izin->id) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('Hapus pengajuan izin ini? Tindakan ini tidak bisa dibatalkan.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash3"></i> Hapus
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center text-muted">Belum ada riwayat izin.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>

                <!-- Form Pengajuan (kini di kanan, ringkas) -->
                <div class="card shadow-sm order-md-2 izin-form-card">
                    <div class="card-header bg-primary text-white">Form Surat Izin</div>
                    <div class="card-body">
                        <form id="formIzin" action="{{ route('siswa.izin.store') }}" method="POST" enctype="multipart/form-data" novalidate>
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Tanggal Mulai</label>
                                <input type="date" class="form-control" value="{{ date('Y-m-d') }}" disabled>
                                <small class="text-muted">Otomatis tanggal hari ini.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tanggal Selesai</label>
                                <input type="date" name="end_date" class="form-control" min="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Alasan (cth: Sakit Demam / Izin Acara)</label>
                                <textarea name="reason" class="form-control" rows="3" maxlength="500" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Unggah Bukti (Surat Dokter/Foto)</label>
                                <input type="file" name="document" id="izinDokumen" class="form-control" accept=".pdf,.jpg,.jpeg,.png,image/*" required>
                                <small class="text-muted">PDF/JPG/PNG, maksimal 2 MB. Foto besar dikecilkan otomatis.</small>
                            </div>
                            <div id="izinPesan" class="alert alert-danger py-2 small" role="alert" aria-live="polite"></div>
                            <button type="submit" id="izinKirim" class="btn btn-primary w-100 izin-submit">Kirim Pengajuan</button>
                        </form>
                    </div>
                </div>
            </div>
@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('formIzin');
    if (!form) return;
    var file = document.getElementById('izinDokumen');
    var pesan = document.getElementById('izinPesan');
    var tombol = document.getElementById('izinKirim');
    var teksAwal = tombol.textContent;
    var MAKS = 2 * 1024 * 1024;
    var memproses = false, mengirim = false;

    function tampilPesan(t) { pesan.textContent = t || ''; if (t) pesan.scrollIntoView({ behavior: 'smooth', block: 'center' }); }

    // Kecilkan foto dari kamera HP (biasanya 3-8 MB) agar lolos batas 2 MB server
    function kecilkan(f) {
        return new Promise(function (resolve) {
            var url = URL.createObjectURL(f);
            var img = new Image();
            img.onload = function () {
                var sisi = 1600, s = Math.min(1, sisi / Math.max(img.width, img.height));
                var c = document.createElement('canvas');
                c.width = Math.round(img.width * s); c.height = Math.round(img.height * s);
                c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
                URL.revokeObjectURL(url);
                var q = 0.85;
                (function coba() {
                    c.toBlob(function (b) {
                        if (!b) return resolve(f);
                        if (b.size <= MAKS * 0.95 || q <= 0.4) {
                            resolve(new File([b], f.name.replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg' }));
                        } else { q -= 0.1; coba(); }
                    }, 'image/jpeg', q);
                })();
            };
            img.onerror = function () { URL.revokeObjectURL(url); resolve(f); };
            img.src = url;
        });
    }

    file.addEventListener('change', async function () {
        tampilPesan('');
        var f = file.files[0];
        if (!f || f.type === 'application/pdf' || f.size <= MAKS || f.type.indexOf('image/') !== 0) return;
        memproses = true; tombol.textContent = 'Memproses foto...';
        var hasil = await kecilkan(f);
        if (hasil !== f) {
            var dt = new DataTransfer(); dt.items.add(hasil); file.files = dt.files;
        }
        memproses = false; tombol.textContent = teksAwal;
    });

    form.addEventListener('submit', function (e) {
        if (memproses) { e.preventDefault(); tampilPesan('Foto masih diproses, tunggu sebentar lalu tekan kirim lagi.'); return; }
        if (mengirim) { e.preventDefault(); return; }

        var akhir = form.querySelector('[name="end_date"]').value;
        var alasan = form.querySelector('[name="reason"]').value.trim();
        var f = file.files[0];
        var err = '';
        if (!akhir) err = 'Tanggal selesai wajib diisi.';
        else if (!alasan) err = 'Alasan wajib diisi.';
        else if (!f) err = 'Bukti (surat dokter/foto) wajib diunggah.';
        else if (f.size > MAKS) err = 'Ukuran bukti melebihi 2 MB. Gunakan foto/PDF yang lebih kecil.';
        if (err) { e.preventDefault(); tampilPesan(err); return; }

        mengirim = true; tombol.textContent = 'Mengirim...'; tombol.classList.add('disabled');
    });

    // Kembali lewat tombol back (bfcache): aktifkan lagi tombolnya
    window.addEventListener('pageshow', function () { mengirim = false; tombol.textContent = teksAwal; tombol.classList.remove('disabled'); });
})();
</script>
@endpush
