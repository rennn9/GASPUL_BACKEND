<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PengaduanMasyarakat;
use App\Models\PengaduanPelayanan;
use PDF;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function dashboard()
    {
        $pengaduanMasyarakat = PengaduanMasyarakat::latest()->get();
        $pengaduanPelayanan  = PengaduanPelayanan::latest()->get();

        return view('admin.dashboard', compact('pengaduanMasyarakat', 'pengaduanPelayanan'));
    }

    public function multiDelete(Request $request)
    {
        $type = $request->input('type'); // masyarakat atau pelayanan
        $ids  = $request->input('ids');

        if ($type === 'masyarakat') {
            PengaduanMasyarakat::whereIn('id', $ids)->delete();
        } elseif ($type === 'pelayanan') {
            PengaduanPelayanan::whereIn('id', $ids)->delete();
        }

        return back()->with('success', 'Data berhasil dihapus.');
    }

    // =========================
    // === Export PDF (with filter)
    // =========================
    public function exportMasyarakatPdf(Request $request)
    {
        $filter = $request->get('filter', 'all');
        $query  = PengaduanMasyarakat::query();
        $query  = $this->applyFilter($query, $filter);

        $pengaduanMasyarakat = $query->latest()->get();
        $title = $this->getPdfTitle('masyarakat', $filter);

        $pdf = \PDF::loadView('admin.exports.masyarakat_pdf', compact('pengaduanMasyarakat', 'filter', 'title'));
        return $pdf->download('pengaduan_masyarakat_' . $filter . '.pdf');
    }

    public function exportPelayananPdf(Request $request)
    {
        $filter = $request->get('filter', 'all');
        $query  = PengaduanPelayanan::query();
        $query  = $this->applyFilter($query, $filter);

        $pengaduanPelayanan = $query->latest()->get();
        $title = $this->getPdfTitle('pelayanan', $filter);

        $pdf = \PDF::loadView('admin.exports.pelayanan_pdf', compact('pengaduanPelayanan', 'filter', 'title'));
        return $pdf->download('pengaduan_pelayanan_' . $filter . '.pdf');
    }

    // =========================
    // === AJAX filter
    // =========================
    public function filterMasyarakat(Request $request)
    {
        $query = PengaduanMasyarakat::query();
        $query = $this->applyFilter($query, $request->filter);
        $pengaduanMasyarakat = $query->latest()->get();

        return view('admin.partials.masyarakat_table', compact('pengaduanMasyarakat'));
    }

    public function filterPelayanan(Request $request)
    {
        $query = PengaduanPelayanan::query();
        $query = $this->applyFilter($query, $request->filter);
        $pengaduanPelayanan = $query->latest()->get();

        return view('admin.partials.pelayanan_table', compact('pengaduanPelayanan'));
    }

    // =========================
    // === Helper filter
    // =========================
    private function applyFilter($query, $filter)
    {
        switch ($filter) {
            case 'week':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
                break;
            case 'year':
                $query->whereYear('created_at', now()->year);
                break;
            case 'last_year':
                $query->whereYear('created_at', now()->subYear()->year);
                break;
        }
        return $query;
    }

    // =========================
    // === Helper PDF Title
    // =========================
    private function getPdfTitle($type, $filter)
    {
        $base = "Daftar Pengaduan " . ucfirst($type);

        switch ($filter) {
            case 'week':
                return $base . " Minggu Ini (" . now()->startOfWeek()->format('d M Y') . " - " . now()->endOfWeek()->format('d M Y') . ")";
            case 'month':
                return $base . " Bulan " . now()->translatedFormat('F Y');
            case 'year':
                return $base . " Tahun " . now()->year;
            case 'last_year':
                return $base . " Tahun " . now()->subYear()->year;
            default:
                return $base . " (Semua Data)";
        }
    }
}
