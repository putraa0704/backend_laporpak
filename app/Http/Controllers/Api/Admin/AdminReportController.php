<?php
// ===================================
// app/Http/Controllers/Api/Admin/AdminReportController.php
// Controller khusus untuk Admin mengelola laporan
// ===================================

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\ReportHistory;
use Illuminate\Http\Request;

class AdminReportController extends Controller
{
    // Get semua laporan untuk admin dashboard
    public function index(Request $request)
    {
        $query = Report::with('user')->latest();

        // Filter by status untuk tab
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by date
        if ($request->has('date')) {
            $query->whereDate('report_date', $request->date);
        }

        // Filter by month & year untuk kalender
        if ($request->has('month') && $request->has('year')) {
            $query->whereMonth('report_date', $request->month)
                  ->whereYear('report_date', $request->year);
        }

        $reports = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $reports
        ]);
    }

    // Get laporan by date untuk kalender
    public function reportsByDate(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020',
        ]);

        $reports = Report::with('user')
            ->whereMonth('report_date', $request->month)
            ->whereYear('report_date', $request->year)
            ->get()
            ->groupBy(function($report) {
                return $report->report_date->format('Y-m-d');
            });

        return response()->json([
            'success' => true,
            'data' => $reports
        ]);
    }

    // Approve/Reject laporan
    public function approve(Request $request, $id)
    {
        $request->validate([
            'approved' => 'required|boolean',
            'reason' => 'nullable|string', // Alasan jika di-reject
        ]);

        $report = Report::with('user')->findOrFail($id);

        if ($request->approved) {
            // Approve - update status ke in_progress
            $report->update([
                'status' => 'in_progress',
                'admin_notes' => 'Laporan disetujui dan sedang diproses',
            ]);

            $statusText = 'disetujui';
            $newStatus = 'in_progress';
        } else {
            // Reject - bisa tetap pending atau buat status rejected
            $report->update([
                'status' => 'pending',
                'admin_notes' => 'Laporan ditolak: ' . ($request->reason ?? 'Tidak memenuhi kriteria'),
            ]);

            $statusText = 'ditolak';
            $newStatus = 'pending';
        }

        // Create history
        ReportHistory::create([
            'report_id' => $report->id,
            'status' => $newStatus,
            'notes' => $request->approved 
                ? 'Laporan disetujui oleh admin' 
                : 'Laporan ditolak: ' . ($request->reason ?? 'Tidak memenuhi kriteria'),
            'changed_at' => now(),
            'changed_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Laporan berhasil {$statusText}",
            'data' => $report->fresh()
        ]);
    }

    // Assign laporan ke petugas
    public function assignToPetugas(Request $request, $id)
    {
        $request->validate([
            'petugas_id' => 'required|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $report = Report::findOrFail($id);

        // Update report dengan info petugas
        $report->update([
            'assigned_to' => $request->petugas_id,
            'status' => 'in_progress',
            'admin_notes' => $request->notes ?? 'Laporan ditugaskan ke petugas',
        ]);

        // Create history
        ReportHistory::create([
            'report_id' => $report->id,
            'status' => 'in_progress',
            'notes' => 'Laporan ditugaskan ke petugas' . ($request->notes ? ': ' . $request->notes : ''),
            'changed_at' => now(),
            'changed_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Laporan berhasil ditugaskan ke petugas',
            'data' => $report->fresh()
        ]);
    }

    // Get statistik untuk admin dashboard
    public function dashboardStats(Request $request)
    {
        // Total laporan
        $total = Report::count();
        
        // By status
        $byStatus = [
            'all' => $total,
            'pending' => Report::where('status', 'pending')->count(),
            'on_hold' => Report::where('status', 'on_hold')->count(),
            'in_progress' => Report::where('status', 'in_progress')->count(),
            'done' => Report::where('status', 'done')->count(),
        ];

        // Laporan hari ini
        $today = Report::whereDate('created_at', today())->count();

        // Laporan bulan ini
        $thisMonth = Report::whereMonth('created_at', now()->month)
                          ->whereYear('created_at', now()->year)
                          ->count();

        // Laporan pending yang perlu direview
        $needReview = Report::where('status', 'pending')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'by_status' => $byStatus,
                'today' => $today,
                'this_month' => $thisMonth,
                'need_review' => $needReview,
            ]
        ]);
    }

    // Get laporan yang perlu di-approve (untuk halaman Approvement)
    public function needApproval(Request $request)
    {
        $query = Report::with('user')
            ->where('status', 'pending')
            ->latest();

        // Tab filter
        if ($request->has('tab')) {
            switch ($request->tab) {
                case 'semua':
                    // Show all pending
                    break;
                case 'laporan':
                    // Filter by type if needed
                    break;
                case 'dalam_proses':
                    $query->where('status', 'in_progress');
                    break;
                case 'selesai':
                    $query->where('status', 'done');
                    break;
            }
        }

        $reports = $query->paginate($request->per_page ?? 10);

        return response()->json([
            'success' => true,
            'data' => $reports
        ]);
    }
}