@extends('layouts.app')

@section('title', 'Rekap Jurnal Mengajar Guru - Waka Kurikulum')
@section('page-title', 'Rekap Seluruh Jurnal Mengajar Guru')

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

            <div class="card shadow-sm reveal">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th class="text-center">No</th>
                                    <th>Tanggal</th>
                                    <th>Nama Guru</th>
                                    <th>Kelas & Mapel</th>
                                    <th class="text-nowrap">Waktu Diisi</th>
                                    <th>Topik / Materi</th>
                                    <th class="text-center">Foto KBM</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($jurnalList as $index => $jurnal)
                                    @php
                                        $namaHariId = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
                                        $tglJurnal = \Illuminate\Support\Carbon::parse($jurnal->date);
                                        $hariTanggal = $namaHariId[$tglJurnal->dayOfWeekIso];
                                        $hariTidakCocok = $jurnal->jadwal && $jurnal->jadwal->hari && $jurnal->jadwal->hari !== $hariTanggal;
                                    @endphp
                                    <tr>
                                        <td class="text-center">{{ $index + 1 }}</td>
                                        <td class="text-nowrap">{{ $tglJurnal->format('d-m-Y') }}<br><small class="text-muted">{{ $hariTanggal }}</small></td>
                                        <td>{{ $jurnal->jadwal ? ($jurnal->jadwal->guru->nama_guru ?? 'Guru Tidak Ditemukan') : '-' }}</td>
                                        <td>
                                            @if($jurnal->jadwal)
                                                <strong>{{ $jurnal->jadwal->kelas_label }}</strong><br>
                                                <small class="text-muted">{{ $jurnal->jadwal->mapel_label }}</small><br>
                                                <small class="text-muted">Jadwal {{ $jurnal->jadwal->hari }}, {{ substr($jurnal->jadwal->jam_mulai, 0, 5) }} - {{ substr($jurnal->jadwal->jam_selesai, 0, 5) }}</small>
                                                @if($hariTidakCocok)<br><span class="badge bg-warning text-dark" title="Jurnal dibuat sebelum aturan 'hanya jadwal hari ini'">Tanggal tidak sesuai hari jadwal</span>@endif
                                                @if($jurnal->jadwal->jam_ke)<br><small class="text-primary fw-semibold">{{ $jurnal->jadwal->jam_ke }}</small>@endif
                                            @else
                                                <small class="text-muted fst-italic">Jadwal sudah dihapus</small>
                                            @endif
                                        </td>
                                        <td class="text-nowrap">
                                            @if($jurnal->filled_at)
                                                @php $telat = $jurnal->jadwal && $jurnal->filled_at->gt(\App\Support\PelanggaranGuru::batasWaktu($jurnal->jadwal, $jurnal->date)); @endphp
                                                {{ $jurnal->filled_at->format('d-m-Y') }}<br><strong>{{ $jurnal->filled_at->format('H:i') }} WIB</strong>
                                                @if($telat)<br><span class="badge bg-danger">Terlambat</span>@endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>{{ $jurnal->topic_description }}</td>
                                        <td class="text-center">
                                            @if($jurnal->photo_path)
                                                <a href="{{ asset($jurnal->photo_path) }}" target="_blank" class="btn btn-sm btn-info text-white">Lihat Foto</a>
                                            @else
                                                <span class="badge bg-secondary">Tidak ada</span>
                                            @endif
                                        </td>
                                        <td class="text-center text-nowrap">
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editJurnalModal{{ $jurnal->id }}">
                                                <i class="bi bi-pencil-square"></i> Edit
                                            </button>
                                            <form action="{{ route('waka.jurnal.delete', $jurnal->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus jurnal ini beserta fotonya? Tindakan ini tidak bisa dibatalkan.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash3"></i> Hapus
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">Belum ada rekap jurnal yang tersedia dari guru-guru.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
@endsection

@push('modals')
    @foreach($jurnalList as $jurnal)
        <div class="modal fade" id="editJurnalModal{{ $jurnal->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('waka.jurnal.update', $jurnal->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">
                                Edit Jurnal
                                @if($jurnal->jadwal)
                                    : {{ $jurnal->jadwal->kelas_label }} - {{ $jurnal->jadwal->mapel_label }}
                                @endif
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Tanggal</label>
                                <input type="date" name="date" class="form-control" value="{{ $jurnal->date }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Topik / Materi</label>
                                <textarea name="topic_description" class="form-control" rows="4" required>{{ $jurnal->topic_description }}</textarea>
                            </div>
                            <div class="mb-1">
                                <label class="form-label fw-semibold">Ganti Foto KBM (opsional)</label>
                                <input type="file" name="photo" class="form-control" accept=".jpg,.jpeg,.png">
                                <small class="text-muted">
                                    JPG/PNG, maks. 2 MB.
                                    @if($jurnal->photo_path)
                                        Kosongkan jika tidak ingin mengganti foto saat ini (<a href="{{ asset($jurnal->photo_path) }}" target="_blank">lihat</a>).
                                    @endif
                                </small>
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
    @endforeach
@endpush