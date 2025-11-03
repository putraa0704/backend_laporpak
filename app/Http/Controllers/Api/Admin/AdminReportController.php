<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\ReportHistory;
use Illuminate\Http\Request;

class AdminReportController extends Controller
{
    // Get laporan yang perlu di-approve (untuk halaman Approvement)
    public function needApproval(Request $request)
    {
        $query = Report::with(['user', 'approvedByRT'])->latest();

        // Tab filter
        if ($request->has('tab')) {
            switch ($request->tab) {
                case 'laporan':
                    // Laporan yang sudah direkomendasikan RT, menunggu admin konfirmasi
                    $query->where('rt_recommended', true)
                          ->where('status', 'pending');
                    break;
                case 'dalam_proses':
                    // Laporan yang sudah dikonfirmasi admin dan sedang diproses
                    $query->where('status', 'in_progress');
                    break;
                case 'selesai':
                    // Laporan yang sudah selesai
                    $query->where('status', 'done');
                    break;
                case 'semua':
                default:
                    // Semua laporan yang sudah direkomendasi RT
                    $query->where('rt_recommended', true);
                    break;
            }
        }

        $reports = $query->paginate($request->per_page ?? 10);

        return response()->json([
            'success' => true,
            'data' => $reports
        ]);
    }

    // KONFIRMASI Laporan dari RT (Tombol Ungu "Konfirmasi")
    // Admin menerima rekomendasi RT dan mulai proses (pending -> in_progress)
    public function confirmReport(Request $request, $id)
    {
        $report = Report::with(['user', 'approvedByRT'])->findOrFail($id);

        // Validasi: hanya bisa konfirmasi laporan yang sudah direkomendasikan RT
        if (!$report->rt_recommended || $report->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Laporan ini tidak bisa dikonfirmasi. Pastikan sudah direkomendasikan oleh RT dan masih berstatus pending.'
            ], 400);
        }

        $request->validate([
            'notes' => 'nullable|string',
        ]);

        // Update status ke in_progress
        $report->update([
            'status' => 'in_progress',
            'admin_notes' => $request->notes ?? 'Laporan dikonfirmasi dan sedang dalam proses penanganan',
        ]);

        // Create history
        ReportHistory::create([
            'report_id' => $report->id,
            'status' => 'in_progress',
            'notes' => 'Admin mengkonfirmasi laporan: ' . ($request->notes ?? 'Laporan sedang diproses'),
            'changed_at' => now(),
            'changed_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Laporan berhasil dikonfirmasi dan sedang diproses',
            'data' => $report->fresh()
        ]);
    }

    // SELESAIKAN Laporan (Tombol Hijau "Selesaikan")
    // Admin menyelesaikan laporan (any status -> done)
    public function completeReport(Request $request, $id)
    {
        $report = Report::with(['user', 'approvedByRT'])->findOrFail($id);

        // Validasi: tidak bisa selesaikan laporan yang sudah done
        if ($report->status === 'done') {
            return response()->json([
                'success' => false,
                'message' => 'Laporan ini sudah diselesaikan sebelumnya'
            ], 400);
        }

        $request->validate([
            'notes' => 'nullable|string',
        ]);

        // Update status ke done
        $report->update([
            'status' => 'done',
            'admin_notes' => $request->notes ?? 'Laporan telah diselesaikan',
        ]);

        // Create history
        ReportHistory::create([
            'report_id' => $report->id,
            'status' => 'done',
            'notes' => $request->notes ?? 'Laporan diselesaikan oleh Admin',
            'changed_at' => now(),
            'changed_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Laporan berhasil diselesaikan',
            'data' => $report->fresh()
        ]);
    }

    // Get laporan by date untuk kalender
    public function reportsByDate(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020',
        ]);

        $reports = Report::with(['user', 'approvedByRT'])
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

    // Get statistik untuk admin dashboard
    public function dashboardStats(Request $request)
    {
        $total = Report::count();
        
        $byStatus = [
            'all' => $total,
            'pending' => Report::where('status', 'pending')->where('rt_recommended', true)->count(),
            'in_progress' => Report::where('status', 'in_progress')->count(),
            'done' => Report::where('status', 'done')->count(),
            'on_hold' => Report::where('status', 'on_hold')->count(),
        ];

        $today = Report::whereDate('created_at', today())->count();
        $thisMonth = Report::whereMonth('created_at', now()->month)
                          ->whereYear('created_at', now()->year)
                          ->count();

        // Laporan dari RT yang perlu dikonfirmasi admin
        $needReview = Report::where('rt_recommended', true)
                            ->where('status', 'pending')
                            ->count();

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

    // Get all reports (untuk DateAdmin - kalender)
    public function index(Request $request)
    {
        $query = Report::with(['user', 'approvedByRT'])->latest();

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

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
}