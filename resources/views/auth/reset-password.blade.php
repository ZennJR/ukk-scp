<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Password Baru - UKK SCP</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-smkn1-bantul.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    @include('partials.pwa')
</head>
<body class="auth-page">
    <div class="auth-split">

        <div class="auth-split-form">
            <div class="auth-split-form-inner">
                <img src="{{ asset('images/logo-smkn1-bantul.png') }}" alt="Logo SMK Negeri 1 Bantul" class="auth-logo-split">

                <h3>Buat Password Baru</h3>
                <p class="auth-sub">Masukkan password baru untuk akun Anda (minimal 8 karakter).</p>

                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('password.update') }}" method="POST">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <div class="mb-3">
                        <label class="form-label">Password Baru</label>
                        <input type="password" name="password" class="form-control" required minlength="8" autofocus autocomplete="new-password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ulangi Password Baru</label>
                        <input type="password" name="password_confirmation" class="form-control" required minlength="8" autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn auth-btn-split w-100 mb-3">Simpan Password Baru</button>
                    <div class="text-center">
                        <a href="{{ route('login') }}" class="small"><i class="bi bi-arrow-left"></i> Kembali ke halaman login</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="auth-split-visual">
            <div class="auth-split-visual-top">
                <span class="auth-split-badge"><i class="bi bi-mortarboard-fill"></i></span>
                <span>UKK SCP</span>
            </div>
            <div class="auth-split-visual-bottom">
                <h2>Satu Langkah Lagi.</h2>
                <p>Gunakan password yang kuat dan jangan dibagikan ke siapa pun.</p>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/ui.js') }}"></script>
</body>
</html>
