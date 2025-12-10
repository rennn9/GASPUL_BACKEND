<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'nip' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('nip', $request->nip)->first();

        if (! $user) {
            return back()->withErrors(['nip' => 'NIP tidak ditemukan'])->withInput();
        }

        if (! Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Password salah'])->withInput();
        }

        // Login user dan regenerate session
        Auth::login($user);
        $request->session()->regenerate();

        // Redirect berdasarkan role (aturan yang kita sepakati)
        // - superadmin, admin, operator -> default ke Statistik (karena mereka bisa melihat statistik)
        // - operator_bidang -> langsung ke Layanan Publik (hanya akses layanan bidangnya)
        // - user / fallback -> dashboard
        $role = $user->role;

        if (in_array($role, ['superadmin', 'admin', 'operator'])) {
            return redirect()->route('admin.statistik');
        }

        if ($role === 'operator_bidang') {
            return redirect()->route('admin.layanan.index');
        }

        // fallback aman
        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
