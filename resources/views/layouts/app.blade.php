<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>@yield('title', 'UKK SCP')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-smkn1-bantul.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/mobile.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tables-tidy.css') }}">
    @stack('styles')
    @include('partials.pwa')
</head>
<body>

    @php
        // Satu akun boleh punya beberapa peran (mis. Guru + Wali Kelas): menu digabung.
        $__roles = \App\Support\Peran::semua();
        $__has = fn (int ...$r) => count(array_intersect($__roles, $r)) > 0;
        $__roleLabel = \App\Support\Peran::labelSemua();
        $__hariID = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
        $__bulanID = ['January'=>'Januari','February'=>'Februari','March'=>'Maret','April'=>'April','May'=>'Mei','June'=>'Juni','July'=>'Juli','August'=>'Agustus','September'=>'September','October'=>'Oktober','November'=>'November','December'=>'Desember'];
        $__todayRaw = date('l, d F Y');
        $__today = strtr($__todayRaw, $__hariID + $__bulanID);
        $__foto = \App\Http\Controllers\ProfilController::fotoUrl(session('id_user'));

        $__notifItems = collect();
        $__notifUnread = 0;
        if (session('id_user')) {
            $__notifItems = \App\Models\Notification::where('id_user', session('id_user'))
                ->orderByDesc('created_at')
                ->limit(8)
                ->get();
            $__notifUnread = \App\Models\Notification::where('id_user', session('id_user'))
                ->where('is_read', false)
                ->count();
        }
    @endphp

    <div class="app-layout">

        <input type="checkbox" id="sidebarToggle" class="sidebar-toggle-state">
        <input type="checkbox" id="sidebarCollapse" class="sidebar-collapse-state">
        <label for="sidebarToggle" class="sidebar-backdrop"></label>

        <aside class="sidebar">
            <div class="sidebar-brand">
                <span class="brand-icon has-logo"><img src="{{ asset('images/logo-smkn1-bantul.png') }}" alt="Logo SMK Negeri 1 Bantul"></span>
                <span class="brand-text">UKK SCP<small>Sistem Curriculum Panel</small></span>
                <label for="sidebarToggle" class="sidebar-close"><i class="bi bi-x-lg"></i></label>
            </div>

            <div class="sidebar-divider"></div>

            <nav class="sidebar-nav">
                <div class="side-section-label">Menu</div>
                @if($__has(4))
                    <a href="{{ route('waka.dashboard') }}" class="side-link {{ request()->routeIs('waka.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2"></i><span>Dashboard</span>
                    </a>

                    <div class="side-section-label side-section-gap">Rekap &amp; Laporan</div>
                    <a href="{{ route('waka.jurnal.rekap') }}" class="side-link {{ request()->routeIs('waka.jurnal.rekap') ? 'active' : '' }}">
                        <i class="bi bi-journal-richtext"></i><span>Rekap Jurnal</span>
                    </a>
                    <a href="{{ route('presensi.rekap') }}" class="side-link {{ request()->routeIs('presensi.*') ? 'active' : '' }}">
                        <i class="bi bi-clipboard2-check-fill"></i><span>Rekap Presensi</span>
                    </a>
                    <a href="{{ route('waka.jadwal.rekap') }}" class="side-link {{ request()->routeIs('waka.jadwal.rekap') ? 'active' : '' }}">
                        <i class="bi bi-calendar2-week-fill"></i><span>Rekap Jadwal</span>
                    </a>
                    <a href="{{ route('kesiswaan.laporan.harian') }}" class="side-link {{ request()->routeIs('kesiswaan.laporan.harian') ? 'active' : '' }}">
                        <i class="bi bi-calendar-day-fill"></i><span>Laporan Izin Harian</span>
                    </a>
                    <a href="{{ route('waka.pelanggaran.rekap') }}" class="side-link {{ request()->routeIs('waka.pelanggaran.*') ? 'active' : '' }}">
                        <i class="bi bi-exclamation-octagon-fill"></i><span>Rekap Pelanggaran Guru</span>
                    </a>
                    <a href="{{ route('waka.tukar-ruangan.rekap') }}" class="side-link {{ request()->routeIs('waka.tukar-ruangan.*') ? 'active' : '' }}">
                        <i class="bi bi-arrow-left-right"></i><span>Rekap Tukar Ruangan</span>
                    </a>

                    <div class="side-section-label side-section-gap">Data</div>
                    <a href="{{ route('waka.master-data.ruangan') }}" class="side-link {{ request()->routeIs('waka.master-data.ruangan') ? 'active' : '' }}">
                        <i class="bi bi-door-open-fill"></i><span>Data Ruangan</span>
                    </a>
                    <a href="{{ route('waka.master-data.kelas') }}" class="side-link {{ request()->routeIs('waka.master-data.kelas') ? 'active' : '' }}">
                        <i class="bi bi-people-fill"></i><span>Data Kelas</span>
                    </a>
                    <a href="{{ route('waka.master-data.mapel') }}" class="side-link {{ request()->routeIs('waka.master-data.mapel') ? 'active' : '' }}">
                        <i class="bi bi-journal-bookmark-fill"></i><span>Data Mata Pelajaran</span>
                    </a>

                    <div class="side-section-label side-section-gap">Master</div>
                    <a href="{{ route('akun.index') }}" class="side-link {{ request()->routeIs('akun.index') ? 'active' : '' }}">
                        <i class="bi bi-people-fill"></i><span>Manajemen Akun</span>
                    </a>
                    <a href="{{ route('waka.pengaturan') }}" class="side-link {{ request()->routeIs('waka.pengaturan') ? 'active' : '' }}">
                        <i class="bi bi-sliders"></i><span>Siklus &amp; Lock Jadwal</span>
                    </a>
                @endif

                {{-- Menu mengajar: Guru (2) dan Guru BK (7) --}}
                @if($__has(2, 7))
                    <a href="{{ route('guru.dashboard') }}" class="side-link {{ request()->routeIs('guru.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2"></i><span>Dashboard</span>
                    </a>
                    <a href="{{ route('jurnal.index') }}" class="side-link {{ request()->routeIs('jurnal.index') ? 'active' : '' }}">
                        <i class="bi bi-journal-richtext"></i><span>Jurnal Mengajar</span>
                    </a>
                    <a href="{{ route('jadwal.index') }}" class="side-link {{ request()->routeIs('jadwal.index') ? 'active' : '' }}">
                        <i class="bi bi-calendar2-week-fill"></i><span>Jadwal Pelajaran</span>
                    </a>
                    <a href="{{ route('guru.tukar-ruangan') }}" class="side-link {{ request()->routeIs('guru.tukar-ruangan*') ? 'active' : '' }}">
                        <i class="bi bi-arrow-left-right"></i><span>Tukar Ruangan</span>
                    </a>
                @endif

                {{-- Menu kesiswaan: Wali Kelas (3) dan Guru BK (7). Laporan & rekap sudah ada di menu Waka bila ia juga Waka. --}}
                @if($__has(3, 7))
                    @if(!$__has(2, 7, 4))
                        <a href="{{ route('kesiswaan.dashboard') }}" class="side-link {{ request()->routeIs('kesiswaan.dashboard') ? 'active' : '' }}">
                            <i class="bi bi-speedometer2"></i><span>Dashboard</span>
                        </a>
                    @endif
                    <a href="{{ route('kesiswaan.izin.index') }}" class="side-link {{ request()->routeIs('kesiswaan.izin.index') ? 'active' : '' }}">
                        <i class="bi bi-patch-check-fill"></i><span>Verifikasi Izin</span>
                    </a>
                    @if(!$__has(4))
                        <a href="{{ route('kesiswaan.laporan.harian') }}" class="side-link {{ request()->routeIs('kesiswaan.laporan.harian') ? 'active' : '' }}">
                            <i class="bi bi-calendar-day-fill"></i><span>Laporan Izin Harian</span>
                        </a>
                        <a href="{{ route('presensi.rekap') }}" class="side-link {{ request()->routeIs('presensi.*') ? 'active' : '' }}">
                            <i class="bi bi-clipboard2-check-fill"></i><span>Rekap Presensi</span>
                        </a>
                    @endif
                @endif

                {{-- Menu siswa --}}
                @if($__has(5))
                    <a href="{{ route('siswa.dashboard') }}" class="side-link {{ request()->routeIs('siswa.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2"></i><span>Dashboard</span>
                    </a>
                    <a href="{{ route('siswa.izin.index') }}" class="side-link {{ request()->routeIs('siswa.izin.index') ? 'active' : '' }}">
                        <i class="bi bi-file-earmark-medical-fill"></i><span>Pengajuan Izin</span>
                    </a>
                    <a href="{{ route('siswa.presensi') }}" class="side-link {{ request()->routeIs('siswa.presensi') ? 'active' : '' }}">
                        <i class="bi bi-clipboard2-check-fill"></i><span>Presensi Saya</span>
                    </a>
                @endif
            </nav>

            <div class="sidebar-footer">
                @if(session('nama_lengkap'))
                    <div class="sidebar-user">
                        <div class="user-avatar">@if($__foto)<img src="{{ $__foto }}" alt="Foto profil">@else{{ strtoupper(substr(session('nama_lengkap'), 0, 1)) }}@endif</div>
                        <div class="user-meta">
                            <div class="user-name">{{ session('nama_lengkap') }}</div>
                            <div class="user-role">{{ $__roleLabel }}</div>
                        </div>
                    </div>
                    <form action="{{ route('logout') }}" method="POST" class="sidebar-logout-form">
                        @csrf
                        <button type="submit" class="side-link logout-link">
                            <i class="bi bi-box-arrow-right"></i><span>Logout</span>
                        </button>
                    </form>
                @endif
            </div>
        </aside>

        <div class="main-wrapper">
            <header class="topbar @hasSection('back-url') has-back @endif">
                @hasSection('back-url')
                    <a href="@yield('back-url')" class="topbar-back" aria-label="Kembali"><i class="bi bi-chevron-left"></i></a>
                @endif
                <label for="sidebarToggle" class="topbar-burger"><i class="bi bi-list"></i></label>
                <label for="sidebarCollapse" class="topbar-collapse-btn"><i class="bi bi-layout-sidebar-inset"></i></label>

                <div class="topbar-title-wrap">
                    <h1 class="topbar-title">@yield('page-title', 'Dashboard')</h1>
                    <div class="topbar-date">{{ $__today }}</div>
                </div>

                <div class="topbar-right">
                    @if(session('id_user'))
                        <div class="dropdown notif-dropdown">
                            <button class="topbar-notif-btn" type="button" id="notifBellBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-bell-fill"></i>
                                <span class="notif-badge {{ $__notifUnread > 0 ? '' : 'd-none' }}" id="notifBadge">{{ $__notifUnread > 99 ? '99+' : $__notifUnread }}</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm mt-2 notif-menu" id="notifMenu">
                                <li class="d-flex justify-content-between align-items-center px-3 py-2">
                                    <h6 class="dropdown-header p-0 m-0">Notifikasi</h6>
                                    <form action="{{ route('notifikasi.baca-semua') }}" method="POST" class="m-0">
                                        @csrf
                                        <button type="submit" class="btn btn-link btn-sm p-0 notif-mark-all">Tandai semua dibaca</button>
                                    </form>
                                </li>
                                <li><hr class="dropdown-divider m-0"></li>
                                <li id="notifEmptyState" class="{{ $__notifItems->isEmpty() ? '' : 'd-none' }}">
                                    <span class="dropdown-item-text text-muted small py-3 text-center d-block">Belum ada notifikasi.</span>
                                </li>
                                <li id="notifListWrap">
                                    @foreach($__notifItems as $n)
                                        <a class="dropdown-item notif-item {{ $n->is_read ? '' : 'notif-unread' }}" href="{{ route('notifikasi.buka', $n->id) }}">
                                            <div class="notif-title">{{ $n->title }}</div>
                                            <div class="notif-message text-muted small">{{ $n->message }}</div>
                                            <div class="notif-time text-muted">{{ \Illuminate\Support\Carbon::parse($n->created_at)->diffForHumans() }}</div>
                                        </a>
                                    @endforeach
                                </li>
                            </ul>
                        </div>
                    @endif
                    @if(session('nama_lengkap'))
                        <div class="dropdown">
                            <button class="topbar-user-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="user-avatar">@if($__foto)<img src="{{ $__foto }}" alt="Foto profil">@else{{ strtoupper(substr(session('nama_lengkap'), 0, 1)) }}@endif</span>
                                <span class="text-start">
                                    <span class="tu-name d-block">{{ session('nama_lengkap') }}</span>
                                    <span class="tu-role d-block">{{ $__roleLabel }}</span>
                                </span>
                                <i class="bi bi-chevron-down"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm mt-2">
                                <li><h6 class="dropdown-header">{{ session('nama_lengkap') }}</h6></li>
                                <li><span class="dropdown-item-text text-muted small">{{ $__roleLabel }}</span></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('profil.index') }}">
                                        <i class="bi bi-person-circle me-2"></i>Profil Saya
                                    </a>
                                </li>
                                <li>
                                    <form action="{{ route('logout') }}" method="POST" class="m-0">
                                        @csrf
                                        <button type="submit" class="dropdown-item text-danger">
                                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    @endif
                </div>
            </header>

            <main class="content">
                @yield('content')
            </main>
        </div>

        {{-- Bottom navigation (hanya tampil di HP). Isinya mengikuti menu sidebar sesuai role. --}}
        @php
            // [route, pola route aktif, ikon, label singkat]
$__navPerPeran = [
                4 => [
                    ['waka.dashboard', 'waka.dashboard', 'bi-speedometer2', 'Dashboard'],
                    ['akun.index', 'akun.*', 'bi-people-fill', 'Akun'],
                    ['waka.jurnal.rekap', 'waka.jurnal.*', 'bi-journal-richtext', 'Jurnal'],
                    ['presensi.rekap', 'presensi.*', 'bi-clipboard2-check-fill', 'Presensi'],
                    ['kesiswaan.laporan.harian', 'kesiswaan.laporan.*', 'bi-calendar-day-fill', 'Izin Harian'],
                    ['waka.jadwal.rekap', 'waka.jadwal.*', 'bi-calendar2-week-fill', 'Jadwal'],
                    ['waka.pelanggaran.rekap', 'waka.pelanggaran.*', 'bi-exclamation-octagon-fill', 'Pelanggaran'],
                    ['waka.tukar-ruangan.rekap', 'waka.tukar-ruangan.*', 'bi-arrow-left-right', 'Tukar Ruang'],
                    ['waka.pengaturan', 'waka.pengaturan', 'bi-sliders', 'Siklus'],
                    ['waka.master-data.ruangan', 'waka.master-data.ruangan', 'bi-door-open-fill', 'Ruangan'],
                    ['waka.master-data.kelas', 'waka.master-data.kelas', 'bi-people-fill', 'Kelas'],
                    ['waka.master-data.mapel', 'waka.master-data.mapel', 'bi-journal-bookmark-fill', 'Mapel'],
                ],
                2 => [
                    ['guru.dashboard', 'guru.dashboard', 'bi-speedometer2', 'Dashboard'],
                    ['jurnal.index', 'jurnal.*', 'bi-journal-richtext', 'Jurnal'],
                    ['jadwal.index', 'jadwal.*', 'bi-calendar2-week-fill', 'Jadwal'],
                    ['guru.tukar-ruangan', 'guru.tukar-ruangan*', 'bi-arrow-left-right', 'Tukar Ruang'],
                ],
                7 => [
                    ['guru.dashboard', 'guru.dashboard', 'bi-speedometer2', 'Dashboard'],
                    ['jurnal.index', 'jurnal.*', 'bi-journal-richtext', 'Jurnal'],
                    ['jadwal.index', 'jadwal.*', 'bi-calendar2-week-fill', 'Jadwal'],
                    ['guru.tukar-ruangan', 'guru.tukar-ruangan*', 'bi-arrow-left-right', 'Tukar Ruang'],
                    ['kesiswaan.izin.index', 'kesiswaan.izin.*', 'bi-patch-check-fill', 'Verifikasi'],
                    ['kesiswaan.laporan.harian', 'kesiswaan.laporan.*', 'bi-calendar-day-fill', 'Izin Harian'],
                    ['presensi.rekap', 'presensi.*', 'bi-clipboard2-check-fill', 'Presensi'],
                ],
                3 => [
                    ['kesiswaan.dashboard', 'kesiswaan.dashboard', 'bi-speedometer2', 'Dashboard'],
                    ['kesiswaan.izin.index', 'kesiswaan.izin.*', 'bi-patch-check-fill', 'Verifikasi'],
                    ['kesiswaan.laporan.harian', 'kesiswaan.laporan.*', 'bi-calendar-day-fill', 'Izin Harian'],
                    ['presensi.rekap', 'presensi.*', 'bi-clipboard2-check-fill', 'Rekap'],
                ],
                5 => [
                    ['siswa.dashboard', 'siswa.dashboard', 'bi-speedometer2', 'Dashboard'],
                    ['siswa.izin.index', 'siswa.izin.*', 'bi-file-earmark-medical-fill', 'Izin'],
                    ['siswa.presensi', 'siswa.presensi', 'bi-clipboard2-check-fill', 'Presensi'],
                ],
            ];
            // Gabungkan menu semua peran milik user; buang yang kembar (berdasarkan route).
            $__bottomNav = [];
            foreach ([4, 2, 7, 3, 5] as $__rid) {
                if (!in_array($__rid, $__roles, true)) continue;
                foreach ($__navPerPeran[$__rid] as $__item) {
                    // Dashboard wali kelas tidak perlu bila user sudah punya dashboard Guru/Waka
                    if ($__item[0] === 'kesiswaan.dashboard' && $__has(2, 7, 4)) continue;
                    $__bottomNav[$__item[0]] ??= $__item;
                }
            }
            $__bottomNav = array_values($__bottomNav);
        @endphp
        @if(count($__bottomNav))
            <nav class="bottom-nav {{ count($__bottomNav) > 4 ? 'bn-scroll' : '' }}" aria-label="Navigasi utama">
                @foreach($__bottomNav as [$__r, $__match, $__ic, $__lb])
                    <a href="{{ route($__r) }}" class="bn-item {{ request()->routeIs($__match) ? 'active' : '' }}">
                        <i class="bi {{ $__ic }}"></i><span>{{ $__lb }}</span>
                    </a>
                @endforeach
            </nav>
        @endif
    </div>

    @stack('modals')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/ui.js') }}"></script>
    <script src="{{ asset('js/mobile.js') }}"></script>
    @if(session('id_user'))
        <script src="{{ asset('js/notifikasi.js') }}"></script>
    @endif
    @stack('scripts')
</body>
</html>