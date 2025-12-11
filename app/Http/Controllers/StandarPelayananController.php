<?php

namespace App\Http\Controllers;

use App\Models\StandarPelayanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class StandarPelayananController extends Controller
{
    /**
     * ADMIN: List seluruh standar pelayanan
     */
    public function index()
    {
        Log::info('StandarPelayananController@index called');

        $items = StandarPelayanan::orderBy('bidang')
                                 ->orderBy('layanan')
                                 ->get();

        Log::info('StandarPelayanan retrieved', [
            'count' => $items->count(),
            'items' => $items->pluck('bidang', 'layanan')
        ]);

        return view('admin.standar-pelayanan.index', compact('items'));
    }

    /**
     * ADMIN: Form tambah standar pelayanan
     */
    public function create()
    {
        Log::info('StandarPelayananController@create called');

        $layananPerBidang = [
            "Bagian Tata Usaha" => [
                "Permohonan Audiensi",
                "Penerbitan Surat Izin Magang",
                "Penerbitan Surat Izin Penelitian",
                "Permohonan Data dan Informasi Keagamaan",
                "Permohonan Rohaniwan",
                "Penerbitan Rekomendasi Kegiatan Keagamaan",
                "Pelayanan Do'a Keagamaan",
                "Layanan Bantuan Rumah Ibadah / Lembaga Keagamaan",
            ],
            "Bidang Bimbingan Masyarakat Islam" => [
                "Penerbitan Izin Operasional Lembaga Amil Zakat Kabupaten",
                "Penerbitan Izin Operasional Lembaga Amil Zakat Nasional Perwakilan Provinsi",
                "Kalibrasi Arah Kiblat",
                "Layanan Bantuan Rumah Ibadah / Lembaga Keagamaan",
            ],
            "Bidang Pendidikan Madrasah" => [
                "Legalisir Ijazah Madrasah",
            ],
            "Bimas Kristen" => [
                "Layanan Surat Tanda Lapor / Tanda Daftar Rumah Ibadah / Lembaga Keagamaan Kristen",
                "Layanan Bantuan Rumah Ibadah / Lembaga Keagamaan Kristen",
            ],
            "Bimas Katolik" => [
                "Layanan Bantuan Rumah Ibadah / Lembaga Keagamaan Katolik",
            ],
            "Bimas Hindu" => [
                "Layanan Surat Tanda Lapor / Tanda Daftar Rumah Ibadah / Lembaga Keagamaan Hindu",
                "Layanan Bantuan Rumah Ibadah / Lembaga Keagamaan Hindu",
            ],
            "Bimas Buddha" => [
                "Layanan Bantuan Rumah Ibadah / Lembaga Keagamaan Buddha",
            ],
        ];

        Log::info('Layanan per bidang prepared', ['layananPerBidang' => $layananPerBidang]);

        return view('admin.standar-pelayanan.create', compact('layananPerBidang'));
    }

    /**
     * ADMIN: Form edit standar pelayanan
     */
    public function edit($id)
    {
        Log::info('StandarPelayananController@edit called', ['id' => $id]);

        $item = StandarPelayanan::findOrFail($id);

        Log::info('StandarPelayanan item found', ['item' => $item]);

        return view('admin.standar-pelayanan.edit', compact('item'));
    }

/**
 * ADMIN: Simpan atau update file standar pelayanan
 */
public function store(Request $request)
{
    \Log::info('StandarPelayananController@store called', ['request' => $request->all()]);

    $request->validate([
        'bidang'  => 'required|string',
        'layanan' => 'required|string',
        'file'    => 'nullable|mimes:pdf|max:5120', // Maks 5MB
    ]);

    \Log::info('Validation passed');

    // Cari data existing berdasarkan bidang + layanan
    $existing = StandarPelayanan::where('bidang', $request->bidang)
                                ->where('layanan', $request->layanan)
                                ->first();

    \Log::info('Existing record check', ['existing' => $existing]);

    $path = $existing->file_path ?? null;

    if ($request->hasFile('file')) {
        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();          // Nama asli file
        $filename = time() . '_' . $originalName;               // Tambah timestamp unik

        \Log::info('File detected in request', ['originalName' => $originalName, 'filename' => $filename]);

        // Hapus file lama jika ada
        if ($path && \Storage::disk('public')->exists($path)) {
            \Storage::disk('public')->delete($path);
            \Log::info('Old file deleted', ['old_path' => $path]);
        }

        // Simpan file baru dengan nama unik
        $path = $file->storeAs('standar-pelayanan', $filename, 'public');
        \Log::info('New file uploaded', ['new_path' => $path]);
    } else {
        \Log::info('No new file uploaded, using existing path', ['path' => $path]);
    }

    // Simpan atau update record
    $record = StandarPelayanan::updateOrCreate(
        [
            'bidang'  => $request->bidang,
            'layanan' => $request->layanan,
        ],
        [
            'file_path' => $path,
        ]
    );

    \Log::info('StandarPelayanan record saved', ['record' => $record]);

    return redirect()->back()->with('success', 'Standar pelayanan berhasil disimpan.');
}


    /**
     * ADMIN: Hapus file + data
     */
    public function destroy($id)
    {
        Log::info('StandarPelayananController@destroy called', ['id' => $id]);

        $item = StandarPelayanan::findOrFail($id);
        Log::info('StandarPelayanan item found', ['item' => $item]);

        if ($item->file_path && Storage::disk('public')->exists($item->file_path)) {
            Storage::disk('public')->delete($item->file_path);
            Log::info('File deleted from storage', ['file_path' => $item->file_path]);
        }

        $item->delete();
        Log::info('StandarPelayanan record deleted', ['id' => $id]);

        return redirect()->back()->with('success', 'Data berhasil dihapus.');
    }

    /**
     * API Publik: Ambil URL standar pelayanan berdasarkan bidang + layanan
     */
    public function getFile(Request $request)
    {
        Log::info('StandarPelayananController@getFile called', ['request' => $request->all()]);

        $request->validate([
            'bidang'  => 'required|string',
            'layanan' => 'required|string',
        ]);

        Log::info('Validation passed for getFile');

        $item = StandarPelayanan::where('bidang', $request->bidang)
                                ->where('layanan', $request->layanan)
                                ->first();

        Log::info('Query executed', ['item' => $item]);

        if (!$item || !$item->file_path) {
            Log::warning('File not found for requested bidang/layanan', [
                'bidang' => $request->bidang,
                'layanan' => $request->layanan,
            ]);

            return response()->json([
                'exists' => false,
                'url' => null,
            ]);
        }

        Log::info('File found', ['file_url' => $item->file_url]);

        return response()->json([
            'exists' => true,
            'url'    => $item->file_url,
        ]);
    }
}
