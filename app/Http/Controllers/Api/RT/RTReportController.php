<?php
// app/Http/Controllers/Api/RT/RTReportController.php

namespace App\Http\Controllers\Api\RT;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\ReportHistory;
use Illuminate\Http\Request;

class RTReportController extends Controller
{
    // Get laporan berdasarkan tab approval
    public function getApprovalReports(Request $request)
    {
        $query = Report::with(['user', 'approvedByRT'])->latest();

        $tab = $request->get('tab', 'semua');
        
        switch ($tab) {
            case 'laporan':
                // Hanya laporan baru yang belum diproses RT (pending & belum direkomendasi)
                $query->where('status', 'pending')
                      ->where('rt_recommended', false);
                break;
            case 'dalam_proses':
                // Laporan yang sudah direkomendasi ke admin dan sedang diproses admin
                $query->where('rt_recommended', true)
                      ->where('status', 'in_progress');
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

    // KONFIRMASI & REKOMENDASIKAN ke Admin (Tombol Ungu "Konfirmasi")
    // RT setuju dengan laporan dan mengirimkannya ke Admin
    public function confirmAndRecommend(Request $request, $id)
    {
        $report = Report::with('user')->findOrFail($id);

        // Validasi: hanya bisa konfirmasi laporan yang masih pending
        if ($report->status !== 'pending' || $report->rt_recommended) {
            return response()->json([
                'success' => false,
                'message' => 'Laporan ini sudah diproses sebelumnya'
            ], 400);
        }

        $request->validate([
            'notes' => 'nullable|string',
        ]);

        // Update: RT merekomendasikan ke admin (status masih pending)
        $report->update([
            'rt_recommended' => true,
            'approved_by_rt' => $request->user()->id,
            'rt_approved_at' => now(),
            'rt_notes' => $request->notes ?? 'Laporan disetujui dan direkomendasikan ke Admin untuk ditindaklanjuti',
        ]);

        // Create history
        ReportHistory::create([
            'report_id' => $report->id,
            'status' => 'pending', // Status masih pending, menunggu admin
            'notes' => 'RT merekomendasikan laporan ke Admin: ' . ($request->notes ?? 'Laporan valid dan perlu ditindaklanjuti'),
            'changed_at' => now(),
            'changed_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Laporan berhasil direkomendasikan ke Admin',
            'data' => $report->fresh()
        ]);
    }

    // TOLAK Laporan (Tombol Merah "Tolak")
    // RT menolak laporan
    public function rejectReport(Request $request, $id)
    {
        $report = Report::with('user')->findOrFail($id);

        // Validasi: hanya bisa tolak laporan yang masih pending
        if ($report->status !== 'pending' || $report->rt_recommended) {
            return response()->json([
                'success' => false,
                'message' => 'Laporan ini sudah diproses sebelumnya'
            ], 400);
        }

        $request->validate([
            'reason' => 'required|string',
        ]);

        // Update: Ubah status ke on_hold (ditolak)
        $report->update([
            'status' => 'on_hold',
            'rt_notes' => 'Ditolak oleh RT: ' . $request->reason,
        ]);

        // Create history
        ReportHistory::create([
            'report_id' => $report->id,
            'status' => 'on_hold',
            'notes' => 'Ditolak oleh RT: ' . $request->reason,
            'changed_at' => now(),
            'changed_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Laporan berhasil ditolak',
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

    // Get statistik untuk RT dashboard
    public function dashboardStats(Request $request)
    {
        $total = Report::count();
        
        $byStatus = [
            'all' => $total,
            'pending' => Report::where('status', 'pending')->where('rt_recommended', false)->count(),
            'recommended' => Report::where('rt_recommended', true)->where('status', '!=', 'done')->count(),
            'in_progress' => Report::where('status', 'in_progress')->count(),
            'done' => Report::where('status', 'done')->count(),
            'rejected' => Report::where('status', 'on_hold')->count(),
        ];

        $today = Report::whereDate('created_at', today())->count();
        $thisMonth = Report::whereMonth('created_at', now()->month)
                          ->whereYear('created_at', now()->year)
                          ->count();

        // Laporan baru yang perlu direview RT
        $needReview = Report::where('status', 'pending')
                            ->where('rt_recommended', false)
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

    // Get all reports (untuk DateRT - kalender)
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