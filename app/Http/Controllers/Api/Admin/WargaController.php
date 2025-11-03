<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class WargaController extends Controller
{
    // Get all warga
    public function index(Request $request)
    {
        $warga = User::where('role', 'warga')
            ->withCount(['reports as total_reports'])
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $warga
        ]);
    }

    // Get single warga with their reports
    public function show($id)
    {
        $warga = User::where('role', 'warga')
            ->with(['reports' => function($query) {
                $query->latest()->limit(10);
            }])
            ->withCount('reports')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $warga
        ]);
    }

    // Get warga statistics
    public function statistics($id)
    {
        $warga = User::where('role', 'warga')->findOrFail($id);

        $stats = [
            'total_reports' => $warga->reports()->count(),
            'pending' => $warga->reports()->where('status', 'pending')->count(),
            'on_hold' => $warga->reports()->where('status', 'on_hold')->count(),
            'in_progress' => $warga->reports()->where('status', 'in_progress')->count(),
            'done' => $warga->reports()->where('status', 'done')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    // Suspend/unsuspend warga (optional feature)
    public function toggleStatus(Request $request, $id)
    {
        $warga = User::where('role', 'warga')->findOrFail($id);

        $warga->update([
            'is_active' => !$warga->is_active
        ]);

        $status = $warga->is_active ? 'diaktifkan' : 'ditangguhkan';

        return response()->json([
            'success' => true,
            'message' => "Akun warga berhasil {$status}",
            'data' => $warga
        ]);
    }
}