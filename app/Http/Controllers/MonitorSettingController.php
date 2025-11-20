<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MonitorSetting;

class MonitorSettingController extends Controller
{
    // ==============================
    // TAMPILKAN HALAMAN PENGATURAN
    // ==============================
    public function settings()
    {
        // Ambil satu baris setting, jika belum ada dibuat default
        $settings = MonitorSetting::first() ?? MonitorSetting::create([
            'video_url'    => '',
            'running_text' => 'Selamat datang di Sistem GASPUL!',
        ]);

        return view('admin.monitor.settings', compact('settings'));
    }

    // ==============================
    // SIMPAN PERUBAHAN MANUAL
    // ==============================
    public function update(Request $request)
    {
        $request->validate([
            'video_url'    => 'nullable|string|max:1000',
            'running_text' => 'required|string|max:1000',
        ]);

        $settings = MonitorSetting::firstOrCreate(['id' => 1]);

        $settings->update([
            'video_url'    => $request->video_url,
            'running_text' => $request->running_text,
        ]);

        return back()->with('success', 'Pengaturan monitor berhasil diperbarui.');
    }

    // ==============================
    // RESET KE DEFAULT
    // ==============================
    public function reset()
    {
        $settings = MonitorSetting::firstOrCreate(['id' => 1]);

        $settings->update([
            'video_url'    => '',
            'running_text' => 'Selamat datang di Sistem GASPUL!',
        ]);

        return back()->with('success', 'Pengaturan telah dikembalikan ke default.');
    }

    // ==============================
    // AUTO SAVE (AJAX)
    // ==============================
    public function autosave(Request $request)
    {
        $settings = MonitorSetting::firstOrCreate(['id' => 1]);

        $settings->update([
            'video_url'    => $request->video_url,
            'running_text' => $request->running_text,
        ]);

        return response()->json(['message' => 'Auto-saved']);
    }
}
