@extends('layouts.app')

@section('title', 'Manajemen Akun & Role - UKK SCP')
@section('page-title', 'Manajemen Akun & Pengaturan Role')

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

            <!-- Form Buat Akun Baru: sekarang full-width di atas, fieldnya disusun berjajar -->
            <div class="card shadow-sm reveal mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Buat Akun & Atur Role</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('akun.store') }}" method="POST" class="form-akun">
                        @csrf
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">NIP / NISN</label>
                                <input type="text" name="nomor_induk" class="form-control" value="{{ old('nomor_induk') }}" required placeholder="Untuk login: NIP guru / NISN siswa">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Nama Lengkap / Nama Guru</label>
                                <input type="text" name="nama_lengkap" class="form-control" value="{{ old('nama_lengkap') }}" required placeholder="Contoh: Budi Santoso, S.Pd.">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required placeholder="Minimal 8 karakter" minlength="8">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Pilih Role / Hak Akses</label>
                                <select name="id_role" class="form-select role-select" required>
                                    <option value="">-- Pilih Role --</option>
                                    @foreach($roleOptions as $roleId => $roleLabel)
                                        <option value="{{ $roleId }}" {{ old('id_role') == $roleId ? 'selected' : '' }}>{{ $roleLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row siswa-kelas-wrap" style="display:none;">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Jurusan <span class="text-danger">*</span></label>
                                @if($kelasList->isEmpty())<div class="small text-danger">Data kelas masih kosong. Tambahkan dulu di menu Data Kelas.</div>@endif
                                <select class="form-select jurusan-select" disabled>
                                    <option value="">-- Pilih Jurusan --</option>
                                    @foreach($kelasList->pluck('id_jurusan')->unique()->values() as $jur)
                                        <option value="{{ $jur }}">{{ $jur }}@if(!empty($jurusanList[$jur])) - {{ $jurusanList[$jur] }}@endif</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kelas <span class="text-danger">*</span></label>
                                <select name="id_kelas" class="form-select kelas-select" data-selected="{{ old('id_kelas') }}" disabled>
                                    <option value="">-- Pilih Kelas --</option>
                                    @foreach($kelasList as $k)
                                        <option value="{{ $k->id_kelas }}" data-jurusan="{{ $k->id_jurusan }}">{{ $k->nama_kelas }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Dipakai untuk daftar presensi siswa saat guru mengisi jurnal.</div>
                            </div>
                        </div>

                        <div class="peran-tambahan-wrap mb-3" style="display:none;">
                            <label class="form-label">Peran Tambahan <span class="text-muted small">(opsional)</span></label>
                            <div class="d-flex flex-wrap gap-3">
                                @foreach($peranTambahanOpsi as $pid => $plabel)
                                    <div class="form-check">
                                        <input class="form-check-input peran-cb" type="checkbox" name="peran_tambahan[]" value="{{ $pid }}" id="pt_baru_{{ $pid }}" {{ in_array($pid, (array) old('peran_tambahan', [])) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="pt_baru_{{ $pid }}">{{ $plabel }}</label>
                                    </div>
                                @endforeach
                            </div>
                            <div class="form-text">Contoh: Guru yang diangkat menjadi Wali Kelas &rarr; Role utama <strong>Guru Pengampu</strong>, centang <strong>Wali Kelas</strong>.</div>
                        </div>
                        <div class="wali-kelas-wrap mb-3" style="display:none;">
                            <label class="form-label">Kelas yang Diampu (Wali Kelas)</label>
                            <select name="id_kelas_wali" class="form-select">
                                <option value="">-- Semua kelas (belum ditetapkan) --</option>
                                @foreach($kelasList as $k)
                                    <option value="{{ $k->id_kelas }}" {{ old('id_kelas_wali') == $k->id_kelas ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Wali Kelas hanya dapat memverifikasi izin &amp; melihat presensi siswa kelas ini.</div>
                        </div>

                        <button type="submit" class="btn btn-success">Simpan Akun Baru</button>
                    </form>
                </div>
            </div>

            <!-- Tabel Daftar Akun: sekarang full-width supaya kolom Aksi tidak terpotong -->
            <div class="card shadow-sm reveal">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Daftar Akun Terdaftar</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('akun.index') }}" class="row g-2 align-items-end mb-3">
                        <div class="col-sm-4 col-md-3">
                            <label class="form-label mb-1 small text-muted">Filter Role</label>
                            <select name="role" class="form-select">
                                <option value="">Semua Role</option>
                                @foreach($roleOptions as $roleId => $roleName)
                                    <option value="{{ $roleId }}" {{ (string) $roleFilter === (string) $roleId ? 'selected' : '' }}>{{ $roleName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6 col-md-4">
                            <label class="form-label mb-1 small text-muted">Cari Nama / NIP / NISN</label>
                            <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Ketik nama, NIP, atau NISN...">
                        </div>
                        <div class="col-sm-auto">
                            <button type="submit" class="btn btn-primary">Cari</button>
                        </div>
                        @if($roleFilter || $search)
                            <div class="col-sm-auto">
                                <a href="{{ route('akun.index') }}" class="btn btn-outline-secondary">Reset</a>
                            </div>
                        @endif
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>NIP / NISN</th>
                                    <th>Nama Lengkap</th>
                                    <th>Role</th>
                                    <th>Kelas</th>
                                    <th class="text-center" style="width: 160px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $index => $u)
                                    <tr>
                                        <td>{{ $users->firstItem() + $index }}</td>
                                        <td>{{ $u->nomor_induk ?? $u->username }}</td>
                                        <td>{{ $u->nama_lengkap }}</td>
                                        <td>
                                            @php
                                                $__badge = [4 => ['bg-danger', 'Waka / Admin'], 2 => ['bg-primary', 'Guru'], 3 => ['bg-warning text-dark', 'Wali Kelas'], 7 => ['bg-info text-dark', 'Guru BK'], 5 => ['bg-secondary', 'Siswa']];
                                                $__extra = $peranTambahan[$u->id_user] ?? [];
                                            @endphp
                                            @php [$__bc, $__bl] = $__badge[$u->id_role] ?? ['bg-secondary', 'Role (' . $u->id_role . ')']; @endphp
                                            <span class="badge {{ $__bc }}">{{ $__bl }}</span>
                                            @foreach($__extra as $__rid => $__kid)
                                                @continue((int) $__rid === (int) $u->id_role)
                                                @php [$__bc2, $__bl2] = $__badge[$__rid] ?? ['bg-secondary', 'Role (' . $__rid . ')']; @endphp
                                                <span class="badge {{ $__bc2 }}">+ {{ $__bl2 }}@if($__rid == 3 && $__kid) ({{ optional($kelasList->firstWhere('id_kelas', $__kid))->nama_kelas ?? $__kid }})@endif</span>
                                            @endforeach
                                            @if($u->id_role == 3 && !empty($__extra[3]))
                                                <div class="small text-muted">Kelas: {{ optional($kelasList->firstWhere('id_kelas', $__extra[3]))->nama_kelas ?? $__extra[3] }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($u->id_role == 5)
                                                @php
                                                    $idKelasU = $kelasSiswa[$u->nomor_induk ?? $u->username] ?? null;
                                                    $kelasU = $idKelasU ? $kelasList->firstWhere('id_kelas', $idKelasU) : null;
                                                @endphp
                                                @if($kelasU)
                                                    {{ $kelasU->nama_kelas }}
                                                @else
                                                    <span class="badge bg-warning text-dark">Belum ada kelas</span>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex flex-column flex-md-row gap-1 justify-content-center">
                                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editAkunModal{{ $u->id_user }}">
                                                    <i class="bi bi-pencil-square"></i> Edit
                                                </button>
                                                <form action="{{ route('akun.delete', $u->id_user) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus akun NIP/NISN {{ $u->nomor_induk ?? $u->username }}? Tindakan ini tidak bisa dibatalkan.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-trash3"></i> Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            @if(!$sudahFilter)
                                                <i class="bi bi-funnel fs-4 d-block mb-1"></i>
                                                Daftar akun belum ditampilkan. Pilih <strong>Role</strong> atau ketik <strong>nama / NIP / NISN</strong>, lalu klik <strong>Cari</strong>.
                                            @else
                                                Tidak ada akun yang cocok dengan filter/pencarian.
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($sudahFilter && $users->total() > 0)
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 mt-3">
                            <div class="small text-muted">Menampilkan {{ $users->firstItem() }}&ndash;{{ $users->lastItem() }} dari {{ $users->total() }} akun</div>
                            {{ $users->links('pagination::bootstrap-5') }}
                        </div>
                    @endif
                </div>
            </div>
@endsection

@push('modals')
    @foreach($users as $u)
        <div class="modal fade" id="editAkunModal{{ $u->id_user }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('akun.update', $u->id_user) }}" method="POST" class="form-akun">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Akun: {{ $u->nama_lengkap }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">NIP / NISN</label>
                                <input type="text" name="nomor_induk" class="form-control" value="{{ $u->nomor_induk ?? $u->username }}" required>
                                <div class="form-text">Dipakai untuk login. Data guru/siswa yang tertaut ikut diperbarui.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nama Lengkap / Nama Guru</label>
                                <input type="text" name="nama_lengkap" class="form-control" value="{{ $u->nama_lengkap }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password Baru</label>
                                <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak ingin mengganti password (minimal 8 karakter)" minlength="8">
                                <div class="form-text">Biarkan kosong jika tidak ingin mengganti password akun ini.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Role / Hak Akses</label>
                                <select name="id_role" class="form-select role-select" required>
                                    @foreach($roleOptions as $roleId => $roleLabel)
                                        <option value="{{ $roleId }}" {{ $u->id_role == $roleId ? 'selected' : '' }}>{{ $roleLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="siswa-kelas-wrap" style="display:none;">
                            <div class="mb-3">
                                <label class="form-label">Jurusan <span class="text-danger">*</span></label>
                                @if($kelasList->isEmpty())<div class="small text-danger">Data kelas masih kosong. Tambahkan dulu di menu Data Kelas.</div>@endif
                                <select class="form-select jurusan-select" disabled>
                                    <option value="">-- Pilih Jurusan --</option>
                                    @foreach($kelasList->pluck('id_jurusan')->unique()->values() as $jur)
                                        <option value="{{ $jur }}">{{ $jur }}@if(!empty($jurusanList[$jur])) - {{ $jurusanList[$jur] }}@endif</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Kelas <span class="text-danger">*</span></label>
                                <select name="id_kelas" class="form-select kelas-select" data-selected="{{ $kelasSiswa[$u->nomor_induk ?? $u->username] ?? '' }}" disabled>
                                    <option value="">-- Pilih Kelas --</option>
                                    @foreach($kelasList as $k)
                                        <option value="{{ $k->id_kelas }}" data-jurusan="{{ $k->id_jurusan }}">{{ $k->nama_kelas }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Dipakai untuk daftar presensi siswa saat guru mengisi jurnal.</div>
                            </div>
                        </div>
                        </div>
                        @php
                            $__ex = $peranTambahan[$u->id_user] ?? [];
                            $__kw = $__ex[3] ?? '';
                        @endphp
                        <div class="peran-tambahan-wrap mb-3 px-3" style="display:none;">
                            <label class="form-label">Peran Tambahan <span class="text-muted small">(opsional)</span></label>
                            <div class="d-flex flex-wrap gap-3">
                                @foreach($peranTambahanOpsi as $pid => $plabel)
                                    <div class="form-check">
                                        <input class="form-check-input peran-cb" type="checkbox" name="peran_tambahan[]" value="{{ $pid }}" id="pt_{{ $u->id_user }}_{{ $pid }}" {{ ($u->id_role != $pid && array_key_exists($pid, $__ex)) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="pt_{{ $u->id_user }}_{{ $pid }}">{{ $plabel }}</label>
                                    </div>
                                @endforeach
                            </div>
                            <div class="form-text">Guru yang diangkat jadi Wali Kelas: biarkan role utama Guru, centang Wali Kelas.</div>
                        </div>
                        <div class="wali-kelas-wrap mb-3 px-3" style="display:none;">
                            <label class="form-label">Kelas yang Diampu (Wali Kelas)</label>
                            <select name="id_kelas_wali" class="form-select">
                                <option value="">-- Semua kelas (belum ditetapkan) --</option>
                                @foreach($kelasList as $k)
                                    <option value="{{ $k->id_kelas }}" {{ $__kw == $k->id_kelas ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Wali Kelas hanya dapat memverifikasi izin &amp; melihat presensi siswa kelas ini.</div>
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

@push('scripts')
<script>
    // Peran tambahan & kelas wali: tampil untuk staf (bukan Siswa).
    // Centang yang sama dengan role utama dinonaktifkan; kelas wali muncul bila ada peran Wali Kelas (3).
    document.querySelectorAll('.form-akun').forEach(function (form) {
        var role = form.querySelector('.role-select');
        var tambahan = form.querySelector('.peran-tambahan-wrap');
        var wali = form.querySelector('.wali-kelas-wrap');
        if (!role || !tambahan || !wali) return;
        var cbs = form.querySelectorAll('.peran-cb');

        function refreshPeran() {
            var utama = role.value;
            var staf = utama !== '' && utama !== '5';
            tambahan.style.display = staf ? '' : 'none';
            var jadiWali = staf && (utama === '3' || !!form.querySelector('.peran-cb[value="3"]:checked'));
            cbs.forEach(function (cb) {
                var sama = cb.value === utama;
                cb.disabled = sama || !staf;
                if (sama || !staf) cb.checked = false;
            });
            jadiWali = staf && (utama === '3' || !!form.querySelector('.peran-cb[value="3"]:checked'));
            wali.style.display = jadiWali ? '' : 'none';
            var sel = wali.querySelector('select');
            if (sel) sel.disabled = !jadiWali;
        }
        role.addEventListener('change', refreshPeran);
        cbs.forEach(function (cb) { cb.addEventListener('change', refreshPeran); });
        refreshPeran();
    });

    // Pilihan Jurusan -> Kelas, hanya tampil jika role = Siswa (5)
    document.querySelectorAll('.form-akun').forEach(function (form) {
        var role = form.querySelector('.role-select');
        var wrap = form.querySelector('.siswa-kelas-wrap');
        var jur = form.querySelector('.jurusan-select');
        var kelas = form.querySelector('.kelas-select');
        if (!role || !wrap || !jur || !kelas) return;

        var placeholder = kelas.querySelector('option:not([data-jurusan])');
        var semuaOpsi = Array.prototype.slice.call(kelas.querySelectorAll('option[data-jurusan]'));

        function isiKelas(pilih) {
            kelas.querySelectorAll('option[data-jurusan]').forEach(function (o) { o.remove(); });
            semuaOpsi.forEach(function (o) {
                if (jur.value && o.getAttribute('data-jurusan') === jur.value) kelas.appendChild(o);
            });
            kelas.value = pilih || '';
            if (kelas.value !== (pilih || '')) kelas.value = '';
        }

        function refresh() {
            var siswa = role.value === '5';
            wrap.style.display = siswa ? '' : 'none';
            jur.disabled = !siswa;
            kelas.disabled = !siswa || !jur.value;
            jur.required = siswa;
            kelas.required = siswa;
            placeholder.textContent = jur.value ? '-- Pilih Kelas --' : '-- Pilih jurusan terlebih dahulu --';
        }

        // Nilai awal: kelas tersimpan / old input -> tentukan jurusannya
        var awal = kelas.getAttribute('data-selected') || '';
        if (awal) {
            var opsi = semuaOpsi.find(function (o) { return o.value === awal; });
            if (opsi) jur.value = opsi.getAttribute('data-jurusan');
        }
        isiKelas(awal);
        refresh();

        jur.addEventListener('change', function () { isiKelas(''); refresh(); });
        role.addEventListener('change', refresh);
    });
</script>
@endpush
