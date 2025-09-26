<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    // 🔹 Tampilkan form login
    public function showLoginForm()
    {
        return view('auth.login');
    }

    // 🔹 Proses login
    public function login(Request $request)
    {
        $request->validate([
            'nip' => 'required|string',
            'password' => 'required|string',
        ]);

        // Hardcode admin login (nip/password)
        $adminNip = '1234567890'; // bisa diubah sesuai kebutuhan
        $adminPassword = 'password123'; // bisa diubah sesuai kebutuhan

        if ($request->nip === $adminNip && $request->password === $adminPassword) {
            session(['admin_logged_in' => true]);
            return redirect()->route('admin.dashboard');
        }

        return back()
            ->withErrors(['nip' => 'NIP atau password salah'])
            ->withInput();
    }

    // 🔹 Logout
    public function logout(Request $request)
    {
        $request->session()->forget('admin_logged_in');
        return redirect()->route('login');
    }
}
