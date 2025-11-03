<?php
// ===================================
// routes/api.php - UPDATE dengan Admin Routes
// ===================================

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\Admin\AdminReportController;
use App\Http\Controllers\Api\Admin\PetugasController;
use App\Http\Controllers\Api\Admin\WargaController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

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

    // Report routes (Warga & Admin)
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
    Route::prefix('rt')->middleware('role:admin,petugas,rt')->group(function () {
        
        // RT Report Management (sama seperti admin tapi lebih sederhana)
        Route::prefix('reports')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\RT\RTReportController::class, 'index']);
            Route::get('/by-date', [\App\Http\Controllers\Api\RT\RTReportController::class, 'reportsByDate']);
            Route::get('/approval', [\App\Http\Controllers\Api\RT\RTReportController::class, 'getApprovalReports']);
            Route::get('/approval/{id}', [\App\Http\Controllers\Api\RT\RTReportController::class, 'getApprovalDetail']);
            Route::get('/dashboard-stats', [\App\Http\Controllers\Api\RT\RTReportController::class, 'dashboardStats']);
            
            // Action buttons
            Route::post('/{id}/confirm', [\App\Http\Controllers\Api\RT\RTReportController::class, 'confirmReport']);
            Route::post('/{id}/complete', [\App\Http\Controllers\Api\RT\RTReportController::class, 'completeReport']);
            Route::post('/{id}/update-status', [\App\Http\Controllers\Api\RT\RTReportController::class, 'updateStatus']);
        });
    });

    // ========================================
    // ADMIN ROUTES
    // ========================================
    Route::prefix('admin')->middleware('role:admin,petugas')->group(function () {
        
        // Admin Report Management
        Route::prefix('reports')->group(function () {
            Route::get('/', [AdminReportController::class, 'index']);
            Route::get('/by-date', [AdminReportController::class, 'reportsByDate']);
            Route::get('/need-approval', [AdminReportController::class, 'needApproval']);
            Route::get('/dashboard-stats', [AdminReportController::class, 'dashboardStats']);
            Route::post('/{id}/approve', [AdminReportController::class, 'approve']);
            Route::post('/{id}/assign', [AdminReportController::class, 'assignToPetugas']);
        });

        // Petugas Management (Admin only)
        Route::middleware('role:admin')->prefix('petugas')->group(function () {
            Route::get('/', [PetugasController::class, 'index']);
            Route::get('/available', [PetugasController::class, 'available']);
            Route::post('/', [PetugasController::class, 'store']);
            Route::get('/{id}', [PetugasController::class, 'show']);
            Route::put('/{id}', [PetugasController::class, 'update']);
            Route::delete('/{id}', [PetugasController::class, 'destroy']);
        });

        // Warga Management (Admin only)
        Route::middleware('role:admin')->prefix('warga')->group(function () {
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