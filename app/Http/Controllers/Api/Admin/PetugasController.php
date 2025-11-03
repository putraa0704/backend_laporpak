<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PetugasController extends Controller
{
    // Get all petugas
    public function index(Request $request)
    {
        $petugas = User::where('role', 'petugas')
            ->withCount(['reports as total_reports'])
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $petugas
        ]);
    }

    // Get single petugas with their reports
    public function show($id)
    {
        $petugas = User::where('role', 'petugas')
            ->with(['reports' => function($query) {
                $query->latest()->limit(10);
            }])
            ->withCount('reports')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $petugas
        ]);
    }

    // Create new petugas
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
        ]);

        $petugas = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'petugas',
            'phone' => $request->phone,
            'address' => $request->address,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Petugas berhasil ditambahkan',
            'data' => $petugas
        ], 201);
    }

    // Update petugas
    public function update(Request $request, $id)
    {
        $petugas = User::where('role', 'petugas')->findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'phone' => 'sometimes|string',
            'address' => 'sometimes|string',
            'password' => 'sometimes|string|min:8',
        ]);

        $data = $request->only(['name', 'email', 'phone', 'address']);

        if ($request->has('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $petugas->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Data petugas berhasil diupdate',
            'data' => $petugas
        ]);
    }

    // Delete petugas
    public function destroy($id)
    {
        $petugas = User::where('role', 'petugas')->findOrFail($id);
        $petugas->delete();

        return response()->json([
            'success' => true,
            'message' => 'Petugas berhasil dihapus'
        ]);
    }

    // Get available petugas (untuk assign laporan)
    public function available()
    {
        $petugas = User::where('role', 'petugas')
            ->select('id', 'name', 'email', 'phone')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $petugas
        ]);
    }
}
