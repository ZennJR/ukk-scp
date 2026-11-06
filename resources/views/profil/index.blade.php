@extends('layouts.app')

@section('title', 'Profil - UKK SCP')
@section('page-title', 'Profil Saya')

@section('content')

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->has('email') && !old('email'))
        <div class="alert alert-danger">{{ $errors->first('email') }}</div>
    @endif

    <div class="row g-4">

        {{-- Informasi akun (hanya lihat) --}}
        <div class="col-lg-5">
            <div class="card shadow-sm reveal h-100">
                <div class="card-header bg-primary">
                    <h5 class="mb-0"><i class="bi bi-person-badge me-2"></i>Informasi Akun</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="user-avatar" style="width:72px;height:72px;font-size:1.8rem;border-radius:18px;">@if($fotoUrl)<img src="{{ $fotoUrl }}" alt="Foto profil">@else{{ strtoupper(substr($user->nama_lengkap, 0, 1)) }}@endif</div>
                        <div>
                            <h5 class="mb-0">{{ $user->nama_lengkap }}</h5>
                            <span class="text-muted small">{{ $roleLabel }}</span>
                        </div>
                    </div>

                    <dl class="row mb-3">
                        <dt class="col-sm-4 text-muted fw-semibold">{{ $labelIdentitas }}</dt>
                        <dd class="col-sm-8">
                            @if($nomorIdentitas)
                                <span class="fw-semibold">{{ $nomorIdentitas }}</span>
                            @else
                                <span class="text-muted fst-italic">Belum terdaftar di data {{ $labelIdentitas === 'NISN' ? 'siswa' : 'guru' }}</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4 text-muted fw-semibold">Nama Lengkap</dt>
                        <dd class="col-sm-8">{{ $user->nama_lengkap }}</dd>

                        <dt class="col-sm-4 text-muted fw-semibold">Role</dt>
                        <dd class="col-sm-8">{{ $roleLabel }}</dd>

                        <dt class="col-sm-4 text-muted fw-semibold">Email</dt>
                        <dd class="col-sm-8">
                            @if($user->email ?? null)
                                {{ $user->email }}
                            @else
                                <span class="text-muted fst-italic">Belum diisi</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4 text-muted fw-semibold mb-0">No. HP</dt>
                        <dd class="col-sm-8 mb-0">
                            @if($user->no_hp ?? null)
                                {{ $user->no_hp }}
                            @else
                                <span class="text-muted fst-italic">Belum diisi</span>
                            @endif
                        </dd>
                    </dl>

                    @unless($isWaka)
                        <div class="alert alert-info small mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            Nama lengkap dan {{ $labelIdentitas }} hanya dapat diubah oleh Waka Kurikulum.
                            Hubungi Waka jika ada data yang perlu dikoreksi.
                        </div>
                    @endunless
                </div>
            </div>
        </div>

        <div class="col-lg-7">

            {{-- Foto profil --}}
            <div class="card shadow-sm reveal mb-4">
                <div class="card-header bg-primary">
                    <h5 class="mb-0"><i class="bi bi-camera-fill me-2"></i>Foto Profil</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('profil.foto') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Pilih Foto Baru</label>
                            <input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp"
                                   class="form-control @error('foto') is-invalid @enderror" required>
                            @error('foto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Format JPG, PNG, atau WEBP. Maksimal 2 MB.</div>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Simpan Foto</button>
                    </form>

                    @if($fotoUrl)
                        <form action="{{ route('profil.foto.hapus') }}" method="POST" class="mt-2"
                              onsubmit="return confirm('Hapus foto profil?');">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger btn-sm w-100">Hapus Foto</button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- Ubah nama lengkap: khusus Waka Kurikulum --}}
            @if($isWaka)
                <div class="card shadow-sm reveal mb-4">
                    <div class="card-header bg-primary">
                        <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Ubah Nama Lengkap</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('profil.nama') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" name="nama_lengkap" value="{{ old('nama_lengkap', $user->nama_lengkap) }}"
                                       class="form-control @error('nama_lengkap') is-invalid @enderror" required maxlength="100">
                                @error('nama_lengkap')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">Sebagai Waka, Anda juga dapat mengubah nama dan NIP/NISN semua akun lewat Manajemen Akun.</div>
                            </div>
                            <button type="submit" class="btn btn-success w-100">Simpan Nama</button>
                        </form>
                    </div>
                </div>
            @endif

            {{-- Ubah email & nomor HP: semua role boleh mengubah data kontak sendiri --}}
            <div class="card shadow-sm reveal mb-4">
                <div class="card-header bg-primary">
                    <h5 class="mb-0"><i class="bi bi-person-lines-fill me-2"></i>Informasi Kontak</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('profil.kontak') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" value="{{ old('email', $user->email ?? null) }}"
                                   class="form-control @error('email') is-invalid @enderror" maxlength="100"
                                   placeholder="nama@email.com">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Jika email diganti, kode OTP akan dikirim ke email baru untuk dikonfirmasi.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">No. HP</label>
                            <input type="text" name="no_hp" value="{{ old('no_hp', $user->no_hp ?? null) }}"
                                   class="form-control @error('no_hp') is-invalid @enderror" maxlength="20"
                                   placeholder="08xxxxxxxxxx">
                            @error('no_hp')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-success w-100">Simpan Informasi Kontak</button>
                    </form>
                </div>
            </div>

            {{-- Ganti password --}}
            <div class="card shadow-sm reveal">
                <div class="card-header bg-primary">
                    <h5 class="mb-0"><i class="bi bi-key-fill me-2"></i>Ganti Password</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('profil.password') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Password Saat Ini</label>
                            <input type="password" name="password_lama"
                                   class="form-control @error('password_lama') is-invalid @enderror" required autocomplete="current-password">
                            @error('password_lama')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password Baru</label>
                                <input type="password" name="password_baru"
                                       class="form-control @error('password_baru') is-invalid @enderror" required minlength="8" autocomplete="new-password">
                                @error('password_baru')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">Minimal 8 karakter.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ulangi Password Baru</label>
                                <input type="password" name="password_baru_confirmation" class="form-control" required minlength="8" autocomplete="new-password">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Simpan Password Baru</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection