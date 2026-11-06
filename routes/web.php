<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\WakaController;
use App\Http\Controllers\PelanggaranGuruController;
use App\Http\Controllers\TukarRuanganController;
use App\Http\Controllers\GuruController;
use App\Http\Controllers\JadwalPelajaranController;
use App\Http\Controllers\JurnalController;
use App\Http\Controllers\SiswaIzinController;
use App\Http\Controllers\KesiswaanController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PresensiRekapController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

// ==========================================
// ROUTE AUTENTIKASI & LOGOUT
// ==========================================
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');

Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
Route::post('/register-process', [AuthController::class, 'register'])->name('register.post');

// Lupa password (semua role): identitas -> OTP email -> password baru
Route::get('/lupa-password', [PasswordResetController::class, 'showRequestForm'])->name('password.request');
Route::post('/lupa-password', [PasswordResetController::class, 'sendLink'])->middleware('throttle:5,1')->name('password.email');
Route::get('/lupa-password/verifikasi', [PasswordResetController::class, 'showOtpForm'])->name('password.otp');
Route::post('/lupa-password/verifikasi', [PasswordResetController::class, 'verifyOtp'])->middleware('throttle:10,1')->name('password.otp.verify');
Route::post('/lupa-password/kirim-ulang', [PasswordResetController::class, 'resendOtp'])->middleware('throttle:3,1')->name('password.otp.resend');
Route::get('/reset-password', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:10,1')->name('password.update');

// Route Logout yang terhubung dengan tombol logout baru
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');


// ==========================================
// ROUTE WAKA KURIKULUM
// ==========================================
Route::get('/waka/dashboard', [WakaController::class, 'dashboard'])->name('waka.dashboard');
Route::get('/waka/pengaturan', [WakaController::class, 'pengaturan'])->name('waka.pengaturan');

// ==========================================
// DATA INDUK: RUANGAN, KELAS, MATA PELAJARAN (KHUSUS WAKA)
// ==========================================
Route::get('/waka/master-data/ruangan', [MasterDataController::class, 'ruangan'])->name('waka.master-data.ruangan');
Route::get('/waka/master-data/kelas', [MasterDataController::class, 'kelas'])->name('waka.master-data.kelas');
Route::get('/waka/master-data/mapel', [MasterDataController::class, 'mapel'])->name('waka.master-data.mapel');
Route::post('/waka/master-data/ruangan', [MasterDataController::class, 'storeRuangan'])->name('ruangan.store');
Route::put('/waka/master-data/ruangan/{id}', [MasterDataController::class, 'updateRuangan'])->name('ruangan.update');
Route::delete('/waka/master-data/ruangan/{id}', [MasterDataController::class, 'destroyRuangan'])->name('ruangan.delete');
Route::post('/waka/master-data/kelas', [MasterDataController::class, 'storeKelas'])->name('kelas.store');
Route::put('/waka/master-data/kelas/{id}', [MasterDataController::class, 'updateKelas'])->name('kelas.update');
Route::delete('/waka/master-data/kelas/{id}', [MasterDataController::class, 'destroyKelas'])->name('kelas.delete');
Route::post('/waka/master-data/mapel', [MasterDataController::class, 'storeMapel'])->name('mapel.store');
Route::put('/waka/master-data/mapel/{id}', [MasterDataController::class, 'updateMapel'])->name('mapel.update');
Route::delete('/waka/master-data/mapel/{id}', [MasterDataController::class, 'destroyMapel'])->name('mapel.delete');

Route::post('/waka/config/update', [WakaController::class, 'updateConfig'])->name('waka.config.update');
Route::post('/waka/putaran/aktif', [WakaController::class, 'setPutaranAktif'])->name('waka.putaran.aktif');
Route::get('/waka/jurnal/rekap', [JurnalController::class, 'rekapWaka'])->name('waka.jurnal.rekap');
Route::put('/waka/jurnal/{id}/update', [JurnalController::class, 'updateWaka'])->name('waka.jurnal.update');
Route::delete('/waka/jurnal/{id}/delete', [JurnalController::class, 'destroyWaka'])->name('waka.jurnal.delete');

// Rekap pelanggaran guru (jurnal tidak diisi > 5 jam setelah jadwal selesai) - khusus Waka
Route::get('/waka/pelanggaran-guru', [PelanggaranGuruController::class, 'rekap'])->name('waka.pelanggaran.rekap');
Route::post('/waka/pelanggaran-guru/{id}/status', [PelanggaranGuruController::class, 'ubahStatus'])->whereNumber('id')->name('waka.pelanggaran.status');

// Tukar ruangan antar guru
Route::get('/guru/tukar-ruangan', [TukarRuanganController::class, 'index'])->name('guru.tukar-ruangan');
Route::post('/guru/tukar-ruangan', [TukarRuanganController::class, 'store'])->name('guru.tukar-ruangan.store');
Route::post('/guru/tukar-ruangan/{id}/setuju', [TukarRuanganController::class, 'setuju'])->whereNumber('id')->name('guru.tukar-ruangan.setuju');
Route::post('/guru/tukar-ruangan/{id}/tolak', [TukarRuanganController::class, 'tolak'])->whereNumber('id')->name('guru.tukar-ruangan.tolak');
Route::post('/guru/tukar-ruangan/{id}/batal', [TukarRuanganController::class, 'batal'])->whereNumber('id')->name('guru.tukar-ruangan.batal');
Route::get('/waka/tukar-ruangan', [TukarRuanganController::class, 'rekapWaka'])->name('waka.tukar-ruangan.rekap');

// Rekap Jadwal seluruh guru (khusus Waka) - lihat, edit, hapus
Route::get('/waka/jadwal/rekap', [JadwalPelajaranController::class, 'rekapJadwalWaka'])->name('waka.jadwal.rekap');
Route::put('/waka/jadwal/{id}/update', [JadwalPelajaranController::class, 'updateJadwalWaka'])->name('waka.jadwal.update');
Route::delete('/waka/jadwal/{id}/delete', [JadwalPelajaranController::class, 'destroyJadwalWaka'])->name('waka.jadwal.delete');


// ==========================================
// ROUTE MANAJEMEN AKUN & ROLE
// ==========================================
Route::get('/manajemen-akun', [JadwalPelajaranController::class, 'indexAkun'])->name('akun.index');
Route::post('/manajemen-akun/store', [JadwalPelajaranController::class, 'storeAkun'])->name('akun.store');
Route::put('/manajemen-akun/{id}/update', [JadwalPelajaranController::class, 'updateAkun'])->name('akun.update');
Route::delete('/manajemen-akun/{id}/delete', [JadwalPelajaranController::class, 'destroyAkun'])->name('akun.delete');


// ==========================================
// ROUTE GURU
// ==========================================
Route::get('/guru/dashboard', [GuruController::class, 'dashboard'])->name('guru.dashboard');


// ==========================================
// ROUTE JADWAL
// ==========================================
Route::get('/jadwal', [JadwalPelajaranController::class, 'index'])->name('jadwal.index');
Route::post('/jadwal/store', [JadwalPelajaranController::class, 'store'])->name('jadwal.store');
Route::post('/jadwal/config/update', [JadwalPelajaranController::class, 'updateConfig'])->name('jadwal.config.update');


// ==========================================
// ROUTE JURNAL
// ==========================================
Route::get('/jurnal', [JurnalController::class, 'index'])->name('jurnal.index');
Route::post('/jurnal/lanjut', [JurnalController::class, 'lanjut'])->name('jurnal.lanjut');
Route::get('/jurnal/presensi', [JurnalController::class, 'presensi'])->name('jurnal.presensi');
Route::post('/jurnal/batal', [JurnalController::class, 'batal'])->name('jurnal.batal');
Route::post('/jurnal/store', [JurnalController::class, 'store'])->name('jurnal.store');
Route::get('/jurnal/{id}/presensi/edit', [JurnalController::class, 'editPresensi'])->whereNumber('id')->name('jurnal.presensi.edit');
Route::put('/jurnal/{id}/presensi', [JurnalController::class, 'updatePresensi'])->whereNumber('id')->name('jurnal.presensi.update');


// ==========================================
// ROUTE REKAP PRESENSI SISWA (WAKA, WALI KELAS, GURU BK)
// ==========================================
Route::get('/presensi/rekap', [PresensiRekapController::class, 'index'])->name('presensi.rekap');
Route::get('/presensi/rekap/{id}', [PresensiRekapController::class, 'detail'])->name('presensi.siswa');


// ==========================================
// ROUTE SISWA (PENGAJUAN IZIN / SAKIT)
// ==========================================
Route::get('/siswa/dashboard', [SiswaIzinController::class, 'dashboard'])->name('siswa.dashboard');
Route::get('/siswa/izin', [SiswaIzinController::class, 'index'])->name('siswa.izin.index');
Route::get('/siswa/presensi', [PresensiRekapController::class, 'saya'])->name('siswa.presensi');
Route::post('/siswa/izin', [SiswaIzinController::class, 'store'])->name('siswa.izin.store');
Route::delete('/siswa/izin/{id}', [SiswaIzinController::class, 'destroy'])->whereNumber('id')->name('siswa.izin.destroy');


// ==========================================
// ROUTE KESISWAAN (WALI KELAS & GURU BK) - VERIFIKASI IZIN
// ==========================================
Route::get('/kesiswaan/dashboard', [KesiswaanController::class, 'dashboard'])->name('kesiswaan.dashboard');
Route::get('/kesiswaan/izin', [KesiswaanController::class, 'index'])->name('kesiswaan.izin.index');
Route::post('/kesiswaan/izin/{id}/update', [KesiswaanController::class, 'updateStatus'])->name('kesiswaan.izin.update');
Route::get('/kesiswaan/laporan-harian', [KesiswaanController::class, 'laporanHarian'])->name('kesiswaan.laporan.harian');


// ==========================================
// ROUTE NOTIFIKASI (SEMUA ROLE YANG SUDAH LOGIN)
// ==========================================
Route::get('/notifikasi/cek', [NotificationController::class, 'cek'])->name('notifikasi.cek');
Route::get('/notifikasi/{id}/buka', [NotificationController::class, 'buka'])->name('notifikasi.buka');
Route::post('/notifikasi/baca-semua', [NotificationController::class, 'bacaSemua'])->name('notifikasi.baca-semua');


// ==========================================
// ROUTE PROFIL (SEMUA ROLE YANG SUDAH LOGIN)
// ==========================================
Route::get('/profil', [ProfilController::class, 'index'])->name('profil.index');
Route::post('/profil/foto', [ProfilController::class, 'updateFoto'])->name('profil.foto');
Route::post('/profil/foto/hapus', [ProfilController::class, 'hapusFoto'])->name('profil.foto.hapus');
Route::post('/profil/nama', [ProfilController::class, 'updateNama'])->name('profil.nama');
Route::post('/profil/kontak', [ProfilController::class, 'updateKontak'])->name('profil.kontak');
Route::post('/profil/password', [ProfilController::class, 'updatePassword'])->name('profil.password');

// Konfirmasi OTP untuk perubahan email (OTP dikirim ke email baru)
Route::get('/profil/email/verifikasi', [ProfilController::class, 'formVerifikasiEmail'])->name('profil.email.verifikasi');
Route::post('/profil/email/verifikasi', [ProfilController::class, 'verifikasiEmail'])->middleware('throttle:10,1')->name('profil.email.verifikasi.kirim');
Route::post('/profil/email/kirim-ulang', [ProfilController::class, 'kirimUlangOtpEmail'])->middleware('throttle:3,1')->name('profil.email.kirim-ulang');
Route::post('/profil/email/batal', [ProfilController::class, 'batalGantiEmail'])->name('profil.email.batal');