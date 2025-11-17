<?php
// app/Http/Controllers/Api/ReportController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\ReportHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    // Create new report
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'complaint_description' => 'required|string',
            'location_description' => 'required|string',
            'report_date' => 'required|date',
            'report_time' => 'required',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $data = $request->all();
        $data['user_id'] = $request->user()->id;
        $data['status'] = 'pending';

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            
            // Generate unique filename
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            
            // Simpan ke storage/app/public/reports
            $path = $file->storeAs('reports', $filename, 'public');
            
            $data['photo'] = $path; // Simpan path: reports/filename.jpg
            
            // Log untuk debugging
            Log::info('Photo uploaded:', [
                'original_name' => $file->getClientOriginalName(),
                'stored_path' => $path,
                'storage_disk' => 'public',
                'full_url' => url('storage/' . $path)
            ]);
        }

        $report = Report::create($data);

        // Create history
        ReportHistory::create([
            'report_id' => $report->id,
            'status' => 'pending',
            'notes' => 'Laporan dibuat',
            'changed_at' => now(),
            'changed_by' => $request->user()->id,
        ]);

        // Load relationship dan return dengan photo_url
        $report->load('user');
        

        $reportArray = $report->toArray();
        
        Log::info('Report created:', [
            'id' => $report->id,
            'photo' => $report->photo,
            'photo_url' => $report->photo_url,
            'full_response' => $reportArray
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Laporan berhasil dibuat',
            'data' => $reportArray
        ], 201);
    }

    // Get all reports (with filters)
    public function index(Request $request)
    {
        $query = Report::with('user')->latest();

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by user (untuk melihat laporan sendiri)
        if ($request->has('my_reports')) {
            $query->where('user_id', $request->user()->id);
        }

        // Filter by date
        if ($request->has('date')) {
            $query->whereDate('report_date', $request->date);
        }

        $reports = $query->paginate($request->per_page ?? 10);

        // Log untuk debugging
        Log::info('Reports fetched:', [
            'count' => $reports->count(),
            'first_report_photo' => $reports->first()?->photo ?? 'no photo',
            'first_report_photo_url' => $reports->first()?->photo_url ?? 'no photo_url'
        ]);

        return response()->json([
            'success' => true,
            'data' => $reports
        ]);
    }

    // Get single report
    public function show($id)
    {
        $report = Report::with(['user', 'history.changedBy'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    // Update report status (Admin/Petugas only)
    public function updateStatus(Request $request, $id)
    {
        $user = $request->user();
        
        if (!$user->isAdmin() && !$user->isPetugas()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

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
            'changed_by' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status laporan berhasil diupdate',
            'data' => $report
        ]);
    }

    // Delete report
    public function destroy(Request $request, $id)
    {
        $report = Report::findOrFail($id);

        // Only owner or admin can delete
        if ($report->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Delete photo if exists
        if ($report->photo) {
            Storage::disk('public')->delete($report->photo);
        }

        $report->delete();

        return response()->json([
            'success' => true,
            'message' => 'Laporan berhasil dihapus'
        ]);
    }

    // Get report statistics
    public function statistics(Request $request)
    {
        $userId = $request->has('my_stats') ? $request->user()->id : null;

        $query = Report::query();
        if ($userId) {
            $query->where('user_id', $userId);
        }

        $stats = [
            'total' => $query->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'on_hold' => (clone $query)->where('status', 'on_hold')->count(),
            'in_progress' => (clone $query)->where('status', 'in_progress')->count(),
            'done' => (clone $query)->where('status', 'done')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}