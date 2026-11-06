@extends('layouts.app')

@section('title', 'Konfirmasi Email Baru - UKK SCP')
@section('page-title', 'Konfirmasi Email Baru')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-5">
        <div class="card shadow-sm reveal">
            <div class="card-header bg-primary">
                <h5 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Konfirmasi Email Baru</h5>
            </div>
            <div class="card-body">
                <p class="mb-3">Kode OTP 6 digit telah dikirim ke <strong>{{ $emailSamar }}</strong>.
                    Kode berlaku {{ $masaBerlaku }} menit. Email baru baru dipakai setelah kode ini dikonfirmasi.</p>

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

                <form action="{{ route('profil.email.verifikasi.kirim') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Kode OTP</label>
                        <input type="text" name="otp" class="form-control text-center fs-4"
                               style="letter-spacing: .5rem;"
                               inputmode="numeric" pattern="[0-9]{6}" maxlength="6" minlength="6"
                               placeholder="------" required autofocus autocomplete="one-time-code">
                    </div>
                    <button type="submit" class="btn btn-success w-100 mb-3">Konfirmasi Email</button>
                </form>

                <div class="d-flex justify-content-between small">
                    @if($masihBisaKirimUlang)
                        <form action="{{ route('profil.email.kirim-ulang') }}" method="POST">
                            @csrf
                            <button type="submit" id="btnKirimUlang" class="btn btn-link p-0 small" {{ $sisaKirim > 0 ? 'disabled' : '' }}>
                                Kirim ulang kode<span id="hitung">{{ $sisaKirim > 0 ? ' ('.$sisaKirim.' dtk)' : '' }}</span>
                            </button>
                        </form>
                    @else
                        <span class="text-muted">Batas kirim ulang tercapai.</span>
                    @endif
                    <form action="{{ route('profil.email.batal') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-link p-0 small text-danger">Batalkan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

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
@endsection
