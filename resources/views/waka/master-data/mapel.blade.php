@extends('layouts.app')

@section('title', 'Data Mata Pelajaran - UKK SCP')
@section('page-title', 'Data Mata Pelajaran')

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

        <div class="card shadow-sm reveal mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Tambah Mata Pelajaran</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('mapel.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Kode Mapel</label>
                            <input type="text" name="id_mapel" class="form-control text-uppercase" required maxlength="20" placeholder="Contoh: SEJ">
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Nama Mata Pelajaran</label>
                            <input type="text" name="nama_mapel" class="form-control" required maxlength="100" placeholder="Contoh: Sejarah Indonesia">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success">Simpan Mata Pelajaran</button>
                </form>
            </div>
        </div>

        <div class="card shadow-sm reveal">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Daftar Mata Pelajaran ({{ $mapel->count() }})</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kode</th>
                                <th>Nama Mata Pelajaran</th>
                                <th class="text-center" style="width: 140px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($mapel as $index => $m)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $m->id_mapel }}</td>
                                    <td>{{ $m->nama_mapel }}</td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editMapel{{ $loop->index }}">
                                            <i class="bi bi-pencil-fill"></i>
                                        </button>
                                        <form action="{{ route('mapel.delete', $m->id_mapel) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Hapus mata pelajaran \'{{ $m->nama_mapel }}\'?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-trash-fill"></i></button>
                                        </form>
                                    </td>
                                </tr>

                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-4">Belum ada data mata pelajaran.</td></tr>
                                @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
@endsection

@push('modals')
    @foreach($mapel as $index => $m)
        <div class="modal fade" id="editMapel{{ $index }}" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('mapel.update', $m->id_mapel) }}" method="POST">
                        @csrf @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Mata Pelajaran: {{ $m->nama_mapel }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Kode Mapel</label>
                                <input type="text" class="form-control" value="{{ $m->id_mapel }}" disabled>
                                <div class="form-text">Kode tidak dapat diubah.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nama Mata Pelajaran</label>
                                <input type="text" name="nama_mapel" class="form-control" value="{{ $m->nama_mapel }}" required maxlength="100">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-success">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endpush