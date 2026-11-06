<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - UKK SCP</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-smkn1-bantul.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    @include('partials.pwa')
</head>
<body class="auth-page">
    <div class="auth-split">

        {{-- Panel kiri: form --}}
        <div class="auth-split-form">
            <div class="auth-split-form-inner">
                <img src="{{ asset('images/logo-smkn1-bantul.png') }}" alt="Logo SMK Negeri 1 Bantul" class="auth-logo-split">

                <h3>Lupa Password</h3>
                <p class="auth-sub">Masukkan NISN / NIP dan email yang terdaftar di Profil Anda.<br>Jika data cocok, kode OTP akan dikirim ke email tersebut.</p>

                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('password.email') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">NISN / NIP</label>
                        <input type="text" name="nomor_induk" class="form-control" placeholder="Masukkan NISN (siswa) atau NIP (guru)"
                               required autofocus autocomplete="username" value="{{ old('nomor_induk') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Terdaftar</label>
                        <input type="email" name="email" class="form-control" placeholder="nama@email.com"
                               required autocomplete="email" value="{{ old('email') }}">
                    </div>
                    <button type="submit" class="btn auth-btn-split w-100 mb-3">Kirim Kode OTP</button>
                    <div class="text-center">
                        <a href="{{ route('login') }}" class="small"><i class="bi bi-arrow-left"></i> Kembali ke halaman login</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Panel kanan: visual --}}
        <div class="auth-split-visual">
            <div class="auth-split-visual-top">
                <span class="auth-split-badge"><i class="bi bi-mortarboard-fill"></i></span>
                <span>UKK SCP</span>
            </div>
            <div class="auth-split-visual-bottom">
                <h2>Akses Akun Kembali dengan Aman.</h2>
                <p>Berlaku untuk semua peran: Waka Kurikulum, Guru, Wali Kelas, Guru BK, dan Siswa.</p>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/ui.js') }}"></script>
</body>
</html>
