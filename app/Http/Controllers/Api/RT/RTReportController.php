<?php

namespace App\Http\Controllers\Api\RT;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\ReportHistory;
use Illuminate\Http\Request;

class RTReportController extends Controller
{
    // Get semua laporan untuk RT (sama seperti admin)
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

    // Get laporan by date untuk kalender (sama seperti admin)
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

    // Get laporan berdasarkan tab approval
    public function getApprovalReports(Request $request)
    {
        $query = Report::with('user')->latest();

        // Filter berdasarkan tab
        $tab = $request->get('tab', 'semua');
        
        switch ($tab) {
            case 'laporan':
                // Hanya laporan yang belum dikonfirmasi (pending)
                $query->where('status', 'pending');
                break;
            case 'dalam_proses':
                // Laporan yang sudah dikonfirmasi dan dalam proses
                $query->where('status', 'in_progress');
                break;
            case 'selesai':
                // Laporan yang sudah selesai
                $query->where('status', 'done');
                break;
            case 'semua':
            default:
                // Semua laporan
                break;
        }

        $reports = $query->paginate($request->per_page ?? 10);

        return response()->json([
            'success' => true,
            'data' => $reports
        ]);
    }

    // Get detail laporan untuk approval
    public function getApprovalDetail($id)
    {
        $report = Report::with(['user', 'history.changedBy'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    // Konfirmasi laporan (tombol ungu "Konfirmasi")
    // Mengubah status dari pending ke in_progress
    public function confirmReport(Request $request, $id)
    {
        $report = Report::with('user')->findOrFail($id);

        // Validasi: hanya bisa konfirmasi laporan yang masih pending
        if ($report->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Laporan ini sudah dikonfirmasi sebelumnya'
            ], 400);
        }

        // Update status ke in_progress
        $report->update([
            'status' => 'in_progress',
            'admin_notes' => 'Laporan dikonfirmasi oleh RT dan sedang diproses',
        ]);

        // Create history
        ReportHistory::create([
            'report_id' => $report->id,
            'status' => 'in_progress',
            'notes' => 'Laporan dikonfirmasi oleh RT',
            'changed_at' => now(),
            'changed_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Laporan berhasil dikonfirmasi',
            'data' => $report->fresh()
        ]);
    }

    // Selesaikan laporan (tombol hijau "Selesaikan")
    // Mengubah status menjadi done
    public function completeReport(Request $request, $id)
    {
        $report = Report::with('user')->findOrFail($id);

        // Validasi: hanya bisa diselesaikan jika sudah dalam proses
        if (!in_array($report->status, ['pending', 'in_progress', 'on_hold'])) {
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
            'notes' => $request->notes ?? 'Laporan diselesaikan oleh RT',
            'changed_at' => now(),
            'changed_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Laporan berhasil diselesaikan',
            'data' => $report->fresh()
        ]);
    }

    // Get statistik untuk RT dashboard (sama seperti admin)
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

        // Laporan pending yang perlu dikonfirmasi
        $needConfirmation = Report::where('status', 'pending')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'by_status' => $byStatus,
                'today' => $today,
                'this_month' => $thisMonth,
                'need_confirmation' => $needConfirmation,
            ]
        ]);
    }

    // Update status laporan (untuk perubahan manual jika diperlukan)
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,on_hold,in_progress,done',
            'notes' => 'nullable|string',
        ]);

        $report = Report::findOrFail($id);
        $oldStatus = $report->status;

        $report->update([
            'status' => $request->status,
            'admin_notes' => $request->notes,
        ]);

        // Create history
        ReportHistory::create([
            'report_id' => $report->id,
            'status' => $request->status,
            'notes' => $request->notes ?? "Status diubah dari $oldStatus ke {$request->status}",
            'changed_at' => now(),
            'changed_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status laporan berhasil diupdate',
            'data' => $report
        ]);
    }
}
