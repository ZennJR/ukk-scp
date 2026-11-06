@extends('layouts.app')

@section('title', 'Data Kelas - UKK SCP')
@section('page-title', 'Data Kelas')

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
                <h5 class="mb-0">Tambah Kelas</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('kelas.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Kode Kelas</label>
                            <input type="text" name="id_kelas" class="form-control" required maxlength="20" placeholder="Contoh: 41">
                            <div class="form-text">ID unik internal, boleh berupa angka urut.</div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Tingkat</label>
                            <select name="tingkat" class="form-select" required>
                                <option value="">-- Pilih --</option>
                                <option value="X">X</option>
                                <option value="XI">XI</option>
                                <option value="XII">XII</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Jurusan / Rombel</label>
                            <select name="id_jurusan" class="form-select">
                                <option value="">-- Tanpa Jurusan --</option>
                                @foreach($jurusanOptions as $idJurusan => $namaJurusan)
                                    <option value="{{ $idJurusan }}">{{ $idJurusan }} - {{ $namaJurusan }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success">Simpan Kelas</button>
                </form>
            </div>
        </div>

        <div class="card shadow-sm reveal">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Daftar Kelas ({{ $kelas->count() }})</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kode</th>
                                <th>Nama Kelas</th>
                                <th>Tingkat</th>
                                <th>Jurusan / Rombel</th>
                                <th class="text-center" style="width: 140px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($kelas as $index => $k)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $k->id_kelas }}</td>
                                    <td><strong>{{ $k->nama_kelas }}</strong></td>
                                    <td>{{ $k->tingkat }}</td>
                                    <td>{{ $k->id_jurusan ?? '-' }}</td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editKelas{{ $loop->index }}">
                                            <i class="bi bi-pencil-fill"></i>
                                        </button>
                                        <form action="{{ route('kelas.delete', $k->id_kelas) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Hapus kelas \'{{ $k->nama_kelas }}\'?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-trash-fill"></i></button>
                                        </form>
                                    </td>
                                </tr>

                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada data kelas.</td></tr>
                                @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
@endsection

@push('modals')
    @foreach($kelas as $index => $k)
        <div class="modal fade" id="editKelas{{ $index }}" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('kelas.update', $k->id_kelas) }}" method="POST">
                        @csrf @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Kelas: {{ $k->nama_kelas }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Kode Kelas</label>
                                <input type="text" class="form-control" value="{{ $k->id_kelas }}" disabled>
                                <div class="form-text">Kode tidak dapat diubah.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tingkat</label>
                                <select name="tingkat" class="form-select" required>
                                    @foreach(['X','XI','XII'] as $t)
                                        <option value="{{ $t }}" {{ $k->tingkat == $t ? 'selected' : '' }}>{{ $t }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Jurusan / Rombel</label>
                                <select name="id_jurusan" class="form-select">
                                    <option value="">-- Tanpa Jurusan --</option>
                                    @foreach($jurusanOptions as $idJurusan => $namaJurusan)
                                        <option value="{{ $idJurusan }}" {{ $k->id_jurusan == $idJurusan ? 'selected' : '' }}>{{ $idJurusan }} - {{ $namaJurusan }}</option>
                                    @endforeach
                                </select>
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