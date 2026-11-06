@extends('layouts.app')

@section('title', 'Data Ruangan - UKK SCP')
@section('page-title', 'Data Ruangan')

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
                <h5 class="mb-0">Tambah Ruangan</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('ruangan.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Kode Ruangan</label>
                            <input type="text" name="id_ruangan" class="form-control" required maxlength="20" placeholder="Contoh: 21">
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="form-label">Nama Ruangan</label>
                            <input type="text" name="nama_ruangan" class="form-control" required maxlength="50" placeholder="Contoh: R 21">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Jenis (opsional)</label>
                            <input type="text" name="jenis" class="form-control" maxlength="20" placeholder="Contoh: Ruangan, Lab, Bengkel">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success">Simpan Ruangan</button>
                </form>
            </div>
        </div>

        <div class="card shadow-sm reveal">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Daftar Ruangan ({{ $ruangan->count() }})</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kode</th>
                                <th>Nama Ruangan</th>
                                <th>Jenis</th>
                                <th class="text-center" style="width: 140px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ruangan as $index => $r)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $r->id_ruangan }}</td>
                                    <td>{{ $r->nama_ruangan }}</td>
                                    <td>{{ $r->jenis ?? '-' }}</td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editRuangan{{ $loop->index }}">
                                            <i class="bi bi-pencil-fill"></i>
                                        </button>
                                        <form action="{{ route('ruangan.delete', $r->id_ruangan) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Hapus ruangan \'{{ $r->nama_ruangan }}\'?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-trash-fill"></i></button>
                                        </form>
                                    </td>
                                </tr>

                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">Belum ada data ruangan.</td></tr>
                                @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
@endsection

@push('modals')
    @foreach($ruangan as $index => $r)
        <div class="modal fade" id="editRuangan{{ $index }}" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('ruangan.update', $r->id_ruangan) }}" method="POST">
                        @csrf @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Ruangan: {{ $r->nama_ruangan }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Kode Ruangan</label>
                                <input type="text" class="form-control" value="{{ $r->id_ruangan }}" disabled>
                                <div class="form-text">Kode tidak dapat diubah.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nama Ruangan</label>
                                <input type="text" name="nama_ruangan" class="form-control" value="{{ $r->nama_ruangan }}" required maxlength="50">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Jenis</label>
                                <input type="text" name="jenis" class="form-control" value="{{ $r->jenis }}" maxlength="20">
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