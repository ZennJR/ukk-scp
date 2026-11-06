<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'nomor_induk' => 'required',
            'password'    => 'required',
        ], [
            'nomor_induk.required' => 'NISN / NIP wajib diisi.',
            'password.required'    => 'Password wajib diisi.',
        ]);

        // Login memakai NISN/NIP (bukan username)
        \App\Support\Identitas::pastikanKolom();
        $user = \App\Support\Identitas::cariUserLogin(trim($request->nomor_induk));

        if ($user && Hash::check($request->password, $user->password_hash)) {
            session([
                'id_user' => $user->id_user,
                'username' => $user->username,
                'nomor_induk' => \App\Support\Identitas::dariUser($user),
                'nama_lengkap' => $user->nama_lengkap,
                'id_role' => $user->id_role
            ]);

            // Dashboard tujuan ditentukan peran UTAMA (users.id_role). Peran tambahan
            // (mis. Guru yang juga Wali Kelas) tetap aktif lewat menu sidebar.
            // REDIRECT BERDASARKAN ID_ROLE SECARA SPESIFIK
            // 4 = Waka Kurikulum
            // 3 = Wali Kelas (dashboard kesiswaan)
            // 2 = Guru, 7 = BK
            // 5 = Siswa
            switch ($user->id_role) {
                case 4:
                    return redirect()->route('waka.dashboard')->with('success', 'Berhasil login sebagai Waka Kurikulum!');
                case 3:
                    return redirect()->route('kesiswaan.dashboard')->with('success', 'Berhasil login sebagai Wali Kelas!');
                case 2:
                case 7:
                    return redirect()->route('guru.dashboard')->with('success', 'Berhasil login!');
                case 5:
                    return redirect()->route('siswa.dashboard')->with('success', 'Berhasil login sebagai Siswa!');
                default:
                    return redirect()->route('login')->withErrors(['nomor_induk' => 'Role pengguna tidak dikenali sistem.']);
            }
        }

        return back()->withErrors(['nomor_induk' => 'NISN / NIP atau password salah.'])->withInput($request->only('nomor_induk'));
    }

    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        \App\Support\Identitas::pastikanKolom();

        $request->validate([
            'nomor_induk' => 'required|string|max:20|unique:users,nomor_induk|unique:users,username',
            'nama_lengkap' => 'required|string|max:100',
            'password' => 'required|string|min:8',
            'no_hp' => 'nullable|string|max:15',
        ], [
            'password.min' => 'Password minimal :min karakter.',
        ]);

        DB::table('users')->insert([
            'username' => $request->nomor_induk, // kolom username hanya penyimpan internal, disamakan dengan NISN
            'nomor_induk' => $request->nomor_induk,
            'nama_lengkap' => $request->nama_lengkap,
            'password_hash' => Hash::make($request->password),
            'no_hp' => $request->no_hp,
            'id_role' => 5, // Otomatis diatur sebagai Siswa
            'is_aktif' => 1,
        ]);

        return redirect()->route('login')->with('success', 'Registrasi berhasil! Silakan login.');
    }

    public function logout(Request $request)
    {
        $request->session()->flush();
        return redirect()->route('login')->with('success', 'Berhasil logout!');
    }
}