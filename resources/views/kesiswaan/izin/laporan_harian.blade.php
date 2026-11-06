@extends('layouts.app')

@section('title', 'Laporan Izin Siswa Harian - UKK SCP')
@section('page-title', 'Laporan Izin Siswa Harian')

@push('styles')
<style>
    @media print {
        .sidebar, .topbar, .bottom-nav, .no-print, .btn-close { display: none !important; }
        .main-content, .content, main { margin: 0 !important; padding: 0 !important; }
        .card { box-shadow: none !important; border: 1px solid #ccc !important; }
    }
    .print-only { display: none; }
    @media print { .print-only { display: block; } }
</style>
@endpush

@section('content')
    @php
        $tglCarbon = \Illuminate\Support\Carbon::parse($tanggal);
        $labelStatus = ['Approved' => 'Disetujui', 'Pending' => 'Pending', 'Rejected' => 'Ditolak'];
        $warnaStatus = ['Approved' => 'success', 'Pending' => 'warning text-dark', 'Rejected' => 'danger'];
    @endphp

    <div class="card shadow-sm reveal mb-3 no-print">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('kesiswaan.laporan.harian') }}" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label mb-1 small text-muted">Tanggal</label>
                    <input type="date" name="tanggal" value="{{ $tanggal }}" class="form-control">
                </div>
                <div class="col-auto">
                    <label class="form-label mb-1 small text-muted">Kelas</label>
                    <select name="kelas" class="form-select">
                        <option value="">Semua kelas</option>
                        @foreach($kelasList as $k)
                            <option value="{{ $k->id_kelas }}" @selected($kelasId === (string) $k->id_kelas)>{{ $k->nama_kelas }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label mb-1 small text-muted">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua status</option>
                        @foreach($labelStatus as $nilai => $label)
                            <option value="{{ $nilai }}" @selected($status === $nilai)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Tampilkan</button>
                    <a href="{{ route('kesiswaan.laporan.harian') }}" class="btn btn-outline-secondary">Hari ini</a>
                    <button type="button" class="btn btn-outline-dark" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i>Cetak
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="print-only mb-3">
        <h4 class="mb-0">Laporan Izin Siswa Harian</h4>
        <div>{{ $tglCarbon->translatedFormat('l, d F Y') }}</div>
    </div>

    <div class="d-flex gap-2 flex-wrap mb-3 reveal">
        <span class="badge bg-dark fs-6">Total: {{ $ringkasan['total'] }}</span>
        <span class="badge bg-success fs-6">Disetujui: {{ $ringkasan['Approved'] }}</span>
        <span class="badge bg-warning text-dark fs-6">Pending: {{ $ringkasan['Pending'] }}</span>
        <span class="badge bg-danger fs-6">Ditolak: {{ $ringkasan['Rejected'] }}</span>
        <span class="badge bg-info text-dark fs-6">Di antaranya sakit: {{ $ringkasan['sakit'] }}</span>
    </div>

    <div class="card shadow-sm reveal">
        <div class="card-header bg-success text-white">
            Siswa Izin / Sakit pada {{ $tglCarbon->translatedFormat('l, d F Y') }}
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nama & NISN</th>
                            <th>Kelas</th>
                            <th>Periode Izin</th>
                            <th>Alasan</th>
                            <th>Status</th>
                            <th class="no-print">Dokumen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($laporan as $i => $item)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>
                                    <strong>{{ $item->student->nama_siswa ?? 'Siswa Tidak Ditemukan' }}</strong><br>
                                    <small class="text-muted">NISN: {{ $item->student->nisn ?? '-' }}</small>
                                </td>
                                <td>{{ $item->student->kelas->nama_kelas ?? '-' }}</td>
                                <td>{{ date('d-m-Y', strtotime($item->start_date)) }} s/d {{ date('d-m-Y', strtotime($item->end_date)) }}</td>
                                <td>{{ $item->reason }}</td>
                                <td>
                                    <span class="badge bg-{{ $warnaStatus[$item->status_approval] ?? 'secondary' }}">
                                        {{ $labelStatus[$item->status_approval] ?? $item->status_approval }}
                                    </span>
                                    @if($item->status_approval === 'Rejected' && $item->reject_reason)
                                        <div class="small text-danger mt-1">Alasan penolakan: {{ $item->reject_reason }}</div>
                                    @endif
                                </td>
                                <td class="no-print">
                                    @if($item->document_path)
                                        <a href="{{ asset($item->document_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">Lihat</a>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">Tidak ada siswa yang izin pada tanggal ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
