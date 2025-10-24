<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    // List semua user
    public function index()
    {
        Log::info('Masuk UserController@index');

        if(auth()->check()){
            Log::info('User login: ID=' . auth()->id() . ', role=' . auth()->user()->role);
        } else {
            Log::info('Tidak ada user yang login di controller');
        }

        try {
            $users = \App\Models\User::paginate(10); // pagination 10 per halaman
            Log::info('Jumlah user ditemukan: ' . $users->count());
        } catch (\Exception $e) {
            Log::error('Error saat mengambil user: ' . $e->getMessage());
            throw $e;
        }

        return view('admin.users.index', compact('users'));
    }

    // Form tambah user baru
    public function create()
    {
        return view('admin.users.create');
    }

    // Simpan user baru
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nip' => 'required|string|unique:users,nip',
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:superadmin,admin,operator',
        ]);

        User::create([
            'nip' => $validated['nip'],
            'name' => $validated['name'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User berhasil ditambahkan');
    }

    // Form edit user
    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    // Update user
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'nip' => 'required|string|unique:users,nip,' . $user->id,
            'name' => 'required|string|max:255',
            'password' => 'nullable|string|min:6|confirmed',
            'role' => 'required|in:superadmin,admin',
        ]);

        $user->nip = $validated['nip'];
        $user->name = $validated['name'];
        $user->role = $validated['role'];
        if(!empty($validated['password'])){
            $user->password = Hash::make($validated['password']);
        }
        $user->save();

        return redirect()->route('admin.users.index')->with('success', 'User berhasil diperbarui');
    }

    // Hapus user
    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'User berhasil dihapus');
    }
}
