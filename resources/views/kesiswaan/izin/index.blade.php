@extends('layouts.app')

@section('title', 'Verifikasi Izin Siswa - Wali Kelas & BK')
@section('page-title', 'Panel Verifikasi Izin Siswa (Wali Kelas & Guru BK)')

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

            <div class="card shadow-sm reveal mb-3">
                <div class="card-body py-3">
                    <form method="GET" action="{{ route('kesiswaan.izin.index') }}" class="row g-2 align-items-end">
                        <div class="col-auto">
                            <label class="form-label mb-1 small text-muted">Filter berdasarkan tanggal</label>
                            <input type="date" name="tanggal" value="{{ $tanggalFilter }}" class="form-control">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary">Tampilkan</button>
                        </div>
                        @if($tanggalFilter)
                            <div class="col-auto">
                                <a href="{{ route('kesiswaan.izin.index') }}" class="btn btn-outline-secondary">Reset</a>
                            </div>
                        @endif
                    </form>
                    @if($tanggalFilter)
                        <div class="small text-muted mt-2">
                            Menampilkan siswa yang mengajukan izin/sakit mencakup tanggal
                            <strong>{{ \Illuminate\Support\Carbon::parse($tanggalFilter)->translatedFormat('d F Y') }}</strong>.
                        </div>
                    @endif
                </div>
            </div>

            <div class="card shadow-sm reveal">
                <div class="card-header bg-success text-white">Daftar Masuk Pengajuan Izin Siswa</div>
                <div class="card-body">
                    <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Nama & NISN Siswa</th>
                                <th>Tanggal</th>
                                <th>Alasan</th>
                                <th>Dokumen</th>
                                <th>Status Saat Ini</th>
                                <th class="text-center">Aksi Verifikasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pendingLeaves as $item)
                            <tr>
                                <td>
                                    <strong>{{ $item->student->nama_siswa ?? 'Siswa Tidak Ditemukan' }}</strong><br>
                                    <small class="text-muted">NISN: {{ $item->student->nisn ?? '-' }}</small>
                                </td>
                                <td>{{ $item->start_date }} s/d {{ $item->end_date }}</td>
                                <td>{{ $item->reason }}</td>
                                <td>
                                    @if($item->document_path)
                                        <a href="{{ asset($item->document_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">Unduh/Lihat</a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($item->status_approval == 'Approved')
                                        <span class="badge bg-success">Approved</span>
                                    @elseif($item->status_approval == 'Rejected')
                                        <span class="badge bg-danger">Rejected</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @endif
                                    @if($item->status_approval == 'Rejected' && $item->reject_reason)
                                        <div class="small text-danger mt-1"><strong>Alasan penolakan:</strong> {{ $item->reject_reason }}</div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <form action="{{ route('kesiswaan.izin.update', $item->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="status_approval" value="Approved">
                                        <button type="submit" class="btn btn-sm btn-success">Setujui</button>
                                    </form>
                                    <button type="button" class="btn btn-sm btn-danger btn-tolak"
                                            data-bs-toggle="modal" data-bs-target="#modalTolak"
                                            data-action="{{ route('kesiswaan.izin.update', $item->id) }}"
                                            data-siswa="{{ $item->student->nama_siswa ?? 'Siswa' }}"
                                            data-alasan="{{ $item->reject_reason }}">Tolak</button>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center text-muted">
                                @if($tanggalFilter)
                                    Tidak ada siswa yang izin pada tanggal tersebut.
                                @else
                                    Tidak ada pengajuan izin masuk.
                                @endif
                            </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
@endsection

@push('modals')
<div class="modal fade" id="modalTolak" tabindex="-1" aria-labelledby="modalTolakLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formTolak" method="POST" action="" class="modal-content">
            @csrf
            <input type="hidden" name="status_approval" value="Rejected">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTolakLabel">Tolak Pengajuan Izin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Siswa: <strong id="tolakSiswa">-</strong></p>
                <label for="tolakAlasan" class="form-label">Alasan penolakan <span class="text-danger">*</span></label>
                <textarea id="tolakAlasan" name="reject_reason" class="form-control" rows="3" maxlength="500" required
                          placeholder="Contoh: Surat keterangan tidak jelas / tidak sesuai."></textarea>
                <small class="text-muted">Alasan akan dikirim ke siswa lewat notifikasi dan tampil di riwayat izinnya.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger">Tolak Pengajuan</button>
            </div>
        </form>
    </div>
</div>
@endpush

@push('scripts')
<script>
    document.querySelectorAll('.btn-tolak').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('formTolak').action = btn.dataset.action;
            document.getElementById('tolakSiswa').textContent = btn.dataset.siswa;
            document.getElementById('tolakAlasan').value = btn.dataset.alasan || '';
        });
    });
</script>
@endpush
