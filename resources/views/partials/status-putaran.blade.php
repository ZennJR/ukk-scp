{{--
    Status kunci tiap putaran jadwal (otomatis: putaran yang berjalan = terkunci), dipakai di dashboard Guru/BK.
    Variabel yang dibutuhkan:
      $statusPutaran : array ['Putaran 1' => bool terkunci, 'Putaran 2' => bool terkunci]
      $putaranAktif  : nama putaran yang sedang berjalan (opsional)
--}}
@php
    $adaTerbuka   = collect($statusPutaran)->contains(fn ($terkunci) => !$terkunci);
    $putaranAktif = $putaranAktif ?? null;
@endphp

@if($putaranAktif)
    <div class="alert alert-primary d-flex align-items-center gap-2 reveal">
        <i class="bi bi-arrow-repeat fs-5"></i>
        <div>Putaran yang sedang berjalan: <strong>{{ $putaranAktif }}</strong></div>
    </div>
@endif

<div class="dash-section-title reveal"><i class="bi bi-lock-fill"></i> Status Putaran Jadwal</div>
<div class="empty-hint-grid reveal-stagger">
    @foreach($statusPutaran as $nama => $terkunci)
        <div class="empty-hint-card">
            <div class="ehc-icon"><i class="bi {{ $terkunci ? 'bi-lock-fill' : 'bi-unlock-fill' }}"></i></div>
            <div>
                <h6>
                    {{ $nama }}
                    @if($putaranAktif === $nama)
                        <span class="badge bg-primary ms-1">Sedang Berjalan</span>
                    @endif
                    <span class="badge {{ $terkunci ? 'bg-danger' : 'bg-success' }} ms-1">{{ $terkunci ? 'Terkunci' : 'Terbuka' }}</span>
                </h6>
                @if($terkunci)
                    <p>Putaran ini sedang berjalan sehingga otomatis terkunci. Jadwal pada putaran ini tidak dapat ditambah.</p>
                @else
                    <p>Masih terbuka. Anda dapat membuat jadwal pada putaran ini.</p>
                    <a href="{{ route('jadwal.index', ['putaran' => $nama]) }}" class="btn btn-sm btn-primary mt-1">
                        <i class="bi bi-calendar-plus"></i> Buat Jadwal {{ $nama }}
                    </a>
                @endif
            </div>
        </div>
    @endforeach
</div>

@unless($adaTerbuka)
    <div class="alert alert-warning mt-3 reveal">
        Tidak ada putaran yang terbuka. Hubungi Waka Kurikulum jika Anda perlu menambah jadwal.
    </div>
@endunless
