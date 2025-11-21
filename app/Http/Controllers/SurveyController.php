<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use Illuminate\Http\Request;
use PDF;
use Carbon\Carbon;

class SurveyController extends Controller
{
    /**
     * 📊 Menampilkan daftar survey (untuk admin panel) dengan filter waktu
     */
    public function index(Request $request)
    {
        $filter = $request->query('filter', 'all');
        $date   = $request->query('date', null);

        $query = Survey::query();

        switch ($filter) {
            case 'today':
                $query->whereDate('created_at', now()->toDateString());
                break;
            case 'custom':
                if ($date) $query->whereDate('created_at', $date);
                break;
            case 'all':
            default:
                break;
        }

        $surveys = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.survey.index', compact('surveys', 'filter', 'date'));
    }

    /**
     * 🧾 Menyimpan data survey dari API (Flutter)
     */
public function store(Request $request)
{
    $validated = $request->validate([
        'antrian_id'       => 'nullable|integer|exists:antrian,id',
        'nomor_antrian'    => 'nullable|string|max:10',
        'nama_responden'   => 'required|string|max:255',
        'no_hp_wa'         => 'nullable|string|max:20',
        'usia'             => 'nullable|integer',
        'jenis_kelamin'    => 'nullable|string|max:20',
        'pendidikan'       => 'nullable|string|max:100',
        'pekerjaan'        => 'nullable|string|max:100',
        'bidang'           => 'nullable|string|max:150',
        'tanggal'          => 'nullable|date',
        'jawaban'          => 'nullable|array',
        'saran'            => 'nullable|string',
    ]);

    // ✅ Jika nomor_antrian dikirim tapi antrian_id tidak, cari antrian_id berdasarkan nomor_antrian
    $antrianId = $validated['antrian_id'] ?? null;

    if (!$antrianId && !empty($validated['nomor_antrian'])) {
        $antrian = \App\Models\Antrian::where('nomor_antrian', $validated['nomor_antrian'])
            ->whereDate('tanggal_layanan', now('Asia/Makassar'))
            ->first();

        if ($antrian) {
            $antrianId = $antrian->id;
        }
    }

    $survey = Survey::create([
        'antrian_id'       => $antrianId,
        'nomor_antrian'    => $validated['nomor_antrian'] ?? null,
        'nama_responden'   => $validated['nama_responden'],
        'no_hp_wa'         => $validated['no_hp_wa'] ?? null,
        'usia'             => $validated['usia'] ?? null,
        'jenis_kelamin'    => $validated['jenis_kelamin'] ?? null,
        'pendidikan'       => $validated['pendidikan'] ?? null,
        'pekerjaan'        => $validated['pekerjaan'] ?? null,
        'bidang'           => $validated['bidang'] ?? null,
        'tanggal'          => $validated['tanggal'] ?? now(),
        'jawaban'          => isset($validated['jawaban']) ? json_encode($validated['jawaban']) : null,
        'saran'            => $validated['saran'] ?? null,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Survey berhasil disimpan.',
        'data'    => $survey,
    ], 201);
}


    /**
     * 🔍 Menampilkan detail survey tertentu (untuk admin)
     */
    public function show($id)
    {
        $survey = Survey::findOrFail($id);

        // Decode jawaban JSON agar bisa ditampilkan
        $jawaban = $survey->jawaban ? json_decode($survey->jawaban, true) : [];

        return view('admin.survey.show', compact('survey', 'jawaban'));
    }

    /**
     * ❌ Menghapus survey tertentu
     */
public function destroy($id)
{
    try {
        $survey = Survey::findOrFail($id);
        $survey->delete();

        return response()->json(['success' => true]);
    } catch (\Exception $e) {
        \Log::error('Gagal hapus survey: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Gagal menghapus survey'
        ], 500);
    }
}


    /**
     * 📄 Download daftar survey PDF sesuai filter waktu
     */
public function downloadPdf(Request $request)
{
    $filter = $request->query('filter', 'all');
    $date   = $request->query('date', null);

    $query = Survey::query();

    switch ($filter) {
        case 'today':
            $query->whereDate('tanggal', now()->toDateString());
            $dateText = now()->format('d-m-Y'); // ⬅️ ganti slash ke dash
            break;
        case 'all':
            $dateText = 'Semua';
            break;
        case 'custom':
            if ($date) $query->whereDate('tanggal', $date);
            $dateText = $date ? \Carbon\Carbon::parse($date)->format('d-m-Y') : '-';
            break;
        default:
            $dateText = '-';
            break;
    }

    $surveys = $query->orderBy('tanggal', 'desc')->get();

    // Nama file PDF valid tanpa slash
    $filename = "Daftar Survey - {$dateText}.pdf";

    return PDF::loadView('admin.exports.survey_pdf', [
        'surveys' => $surveys,
        'dateText' => $dateText
    ])
    ->setPaper('a4', 'portrait')
    ->stream($filename);
}

}
