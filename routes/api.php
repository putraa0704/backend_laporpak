<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\Admin\AdminReportController;
use App\Http\Controllers\Api\Admin\PetugasController;
use App\Http\Controllers\Api\Admin\WargaController;
use App\Http\Controllers\Api\RT\RTReportController;
use App\Http\Controllers\Api\ImageController;

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login'])->name('login');
});
Route::get('storage/{path}', [ImageController::class, 'show'])->where('path', '.*');

// Protected routes - HAPUS check.token.expiry middleware
Route::middleware(['auth:sanctum'])->group(function () {

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

    // RT ROUTES
    Route::prefix('rt')->middleware('role:rt')->group(function () {
        Route::prefix('reports')->group(function () {
            Route::get('/', [RTReportController::class, 'index']);
            Route::get('/by-date', [RTReportController::class, 'reportsByDate']);
            Route::get('/approval', [RTReportController::class, 'getApprovalReports']);
            Route::get('/dashboard-stats', [RTReportController::class, 'dashboardStats']);
            Route::post('/{id}/confirm-recommend', [RTReportController::class, 'confirmAndRecommend']);
            Route::post('/{id}/reject', [RTReportController::class, 'rejectReport']);
        });
    });

    // ADMIN ROUTES
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::prefix('reports')->group(function () {
            Route::get('/', [AdminReportController::class, 'index']);
            Route::get('/by-date', [AdminReportController::class, 'reportsByDate']);
            Route::get('/need-approval', [AdminReportController::class, 'needApproval']);
            Route::get('/dashboard-stats', [AdminReportController::class, 'dashboardStats']);
            Route::post('/{id}/confirm', [AdminReportController::class, 'confirmReport']);
            Route::post('/{id}/complete', [AdminReportController::class, 'completeReport']);
        });

        Route::prefix('petugas')->group(function () {
            Route::get('/', [PetugasController::class, 'index']);
            Route::get('/available', [PetugasController::class, 'available']);
            Route::post('/', [PetugasController::class, 'store']);
            Route::get('/{id}', [PetugasController::class, 'show']);
            Route::put('/{id}', [PetugasController::class, 'update']);
            Route::delete('/{id}', [PetugasController::class, 'destroy']);
        });

        Route::prefix('warga')->group(function () {
            Route::get('/', [WargaController::class, 'index']);
            Route::get('/{id}', [WargaController::class, 'show']);
            Route::get('/{id}/statistics', [WargaController::class, 'statistics']);
            Route::post('/{id}/toggle-status', [WargaController::class, 'toggleStatus']);
        });
    });
});

Route::get('health', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is running',
        'timestamp' => now()
    ]);
});