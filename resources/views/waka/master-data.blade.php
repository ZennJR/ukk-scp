@extends('layouts.app')

@section('title', 'Ruangan, Kelas & Mapel - UKK SCP')
@section('page-title', 'Data Ruangan, Kelas & Mata Pelajaran')

@section('content')
    <div class="mb-3">
        <a href="{{ route('waka.dashboard') }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
        </a>
    </div>

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

    <ul class="nav nav-tabs mb-4" id="masterDataTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-ruangan" type="button">
                <i class="bi bi-door-open-fill me-1"></i> Ruangan
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-kelas" type="button">
                <i class="bi bi-people-fill me-1"></i> Kelas
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-mapel" type="button">
                <i class="bi bi-journal-bookmark-fill me-1"></i> Mata Pelajaran
            </button>
        </li>
    </ul>

    <div class="tab-content">

        {{-- ================= RUANGAN ================= --}}
        <div class="tab-pane fade show active" id="tab-ruangan" role="tabpanel">
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

                                    <div class="modal fade" id="editRuangan{{ $loop->index }}" tabindex="-1">
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
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">Belum ada data ruangan.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ================= KELAS ================= --}}
        <div class="tab-pane fade" id="tab-kelas" role="tabpanel">
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

                                    <div class="modal fade" id="editKelas{{ $loop->index }}" tabindex="-1">
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
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada data kelas.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ================= MATA PELAJARAN ================= --}}
        <div class="tab-pane fade" id="tab-mapel" role="tabpanel">
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

                                    <div class="modal fade" id="editMapel{{ $loop->index }}" tabindex="-1">
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
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-4">Belum ada data mata pelajaran.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
