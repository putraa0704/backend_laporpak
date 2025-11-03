<?php
// routes/api.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\Admin\AdminReportController;
use App\Http\Controllers\Api\Admin\PetugasController;
use App\Http\Controllers\Api\Admin\WargaController;
use App\Http\Controllers\Api\RT\RTReportController;

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('update-profile', [AuthController::class, 'updateProfile']);
    });

    // Report routes (Warga)
    Route::prefix('reports')->group(function () {
        Route::get('/', [ReportController::class, 'index']);
        Route::post('/', [ReportController::class, 'store']);
        Route::get('/statistics', [ReportController::class, 'statistics']);
        Route::get('/{id}', [ReportController::class, 'show']);
        Route::post('/{id}/update-status', [ReportController::class, 'updateStatus']);
        Route::delete('/{id}', [ReportController::class, 'destroy']);
    });

    // ========================================
    // RT ROUTES (Rukun Tetangga)
    // ========================================
    Route::prefix('rt')->middleware('role:rt')->group(function () {
        Route::prefix('reports')->group(function () {
            Route::get('/', [RTReportController::class, 'index']);
            Route::get('/by-date', [RTReportController::class, 'reportsByDate']);
            Route::get('/approval', [RTReportController::class, 'getApprovalReports']);
            Route::get('/dashboard-stats', [RTReportController::class, 'dashboardStats']);
            
            // Action buttons RT
            Route::post('/{id}/confirm-recommend', [RTReportController::class, 'confirmAndRecommend']); // Konfirmasi & Rekomendasikan ke Admin
            Route::post('/{id}/reject', [RTReportController::class, 'rejectReport']); // Tolak laporan
        });
    });

    // ========================================
    // ADMIN ROUTES
    // ========================================
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        
        // Admin Report Management
        Route::prefix('reports')->group(function () {
            Route::get('/', [AdminReportController::class, 'index']);
            Route::get('/by-date', [AdminReportController::class, 'reportsByDate']);
            Route::get('/need-approval', [AdminReportController::class, 'needApproval']);
            Route::get('/dashboard-stats', [AdminReportController::class, 'dashboardStats']);
            
            // Action buttons Admin
            Route::post('/{id}/confirm', [AdminReportController::class, 'confirmReport']); // Konfirmasi (pending -> in_progress)
            Route::post('/{id}/complete', [AdminReportController::class, 'completeReport']); // Selesaikan (-> done)
        });

        // Petugas Management
        Route::prefix('petugas')->group(function () {
            Route::get('/', [PetugasController::class, 'index']);
            Route::get('/available', [PetugasController::class, 'available']);
            Route::post('/', [PetugasController::class, 'store']);
            Route::get('/{id}', [PetugasController::class, 'show']);
            Route::put('/{id}', [PetugasController::class, 'update']);
            Route::delete('/{id}', [PetugasController::class, 'destroy']);
        });

        // Warga Management
        Route::prefix('warga')->group(function () {
            Route::get('/', [WargaController::class, 'index']);
            Route::get('/{id}', [WargaController::class, 'show']);
            Route::get('/{id}/statistics', [WargaController::class, 'statistics']);
            Route::post('/{id}/toggle-status', [WargaController::class, 'toggleStatus']);
        });
    });
});

// Health check
Route::get('health', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is running',
        'timestamp' => now()
    ]);
});