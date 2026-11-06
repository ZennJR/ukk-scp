<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi OTP - UKK SCP</title>
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

                <h3>Verifikasi OTP</h3>
                <p class="auth-sub">Kode 6 digit telah dikirim ke <strong>{{ $emailSamar }}</strong>.<br>
                    Kode berlaku {{ $masaBerlaku }} menit.</p>

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

                <form action="{{ route('password.otp.verify') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Kode OTP</label>
                        <input type="text" name="otp" class="form-control text-center fs-4"
                               style="letter-spacing: .5rem;"
                               inputmode="numeric" pattern="[0-9]{6}" maxlength="6" minlength="6"
                               placeholder="------" required autofocus autocomplete="one-time-code">
                    </div>
                    <button type="submit" class="btn auth-btn-split w-100 mb-3">Verifikasi</button>
                </form>

                <div class="text-center small mb-3">
                    @if($masihBisaKirimUlang)
                        <form action="{{ route('password.otp.resend') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" id="btnKirimUlang" class="btn btn-link p-0 small" {{ $sisaKirim > 0 ? 'disabled' : '' }}>
                                Kirim ulang kode<span id="hitung">{{ $sisaKirim > 0 ? ' ('.$sisaKirim.' dtk)' : '' }}</span>
                            </button>
                        </form>
                    @else
                        <span class="text-muted">Batas kirim ulang tercapai.</span>
                    @endif
                </div>

                <div class="text-center">
                    <a href="{{ route('password.request') }}" class="small"><i class="bi bi-arrow-left"></i> Ganti NISN / NIP atau email</a>
                </div>
            </div>
        </div>

        <div class="auth-split-visual">
            <div class="auth-split-visual-top">
                <span class="auth-split-badge"><i class="bi bi-mortarboard-fill"></i></span>
                <span>UKK SCP</span>
            </div>
            <div class="auth-split-visual-bottom">
                <h2>Cek Email Anda.</h2>
                <p>Jangan bagikan kode OTP kepada siapa pun, termasuk guru atau admin.</p>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/ui.js') }}"></script>
    <script>
        (function () {
            var sisa = {{ (int) $sisaKirim }};
            var btn = document.getElementById('btnKirimUlang');
            var label = document.getElementById('hitung');
            if (!btn || sisa <= 0) return;
            var t = setInterval(function () {
                sisa--;
                if (sisa <= 0) { clearInterval(t); btn.disabled = false; label.textContent = ''; }
                else { label.textContent = ' (' + sisa + ' dtk)'; }
            }, 1000);
        })();
    </script>
</body>
</html>
