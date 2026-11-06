{{--
    Partial dashboard seragam untuk semua role.
    Variabel yang dibutuhkan:
      $dashIcon      : kelas bootstrap-icons untuk ikon welcome
      $dashWelcome   : teks pengantar di bawah ucapan selamat datang
      $dashFunctions : array of ['icon' => '', 'title' => '', 'desc' => '']
      $dashRules     : array of string (ketentuan penggunaan)
      $dashExtraView : (opsional) nama view tambahan yang ditampilkan di bawah welcome
--}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="page-welcome reveal">
    <div class="pw-icon"><i class="bi {{ $dashIcon }}"></i></div>
    <div class="pw-text">
        <h2>Selamat datang, {{ session('nama_lengkap') }}</h2>
        <p>{{ $dashWelcome }}</p>
    </div>
</div>

{{-- Slot opsional: view tambahan yang tampil di bawah welcome (mis. status putaran di dashboard guru) --}}
@isset($dashExtraView)
    @include($dashExtraView)
@endisset

<div class="dash-section-title reveal"><i class="bi bi-grid-fill"></i> Fungsi Anda</div>
<div class="empty-hint-grid reveal-stagger">
    @foreach($dashFunctions as $fn)
        <div class="empty-hint-card">
            <div class="ehc-icon"><i class="bi {{ $fn['icon'] }}"></i></div>
            <div>
                <h6>{{ $fn['title'] }}</h6>
                <p>{{ $fn['desc'] }}</p>
            </div>
        </div>
    @endforeach
</div>

<div class="dash-section-title reveal"><i class="bi bi-shield-check"></i> Ketentuan</div>
<div class="card shadow-sm reveal">
    <div class="card-body">
        <ul class="dash-rules mb-0">
            @foreach($dashRules as $rule)
                <li>{{ $rule }}</li>
            @endforeach
        </ul>
    </div>
</div>
