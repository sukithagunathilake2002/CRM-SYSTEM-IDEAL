<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\EnquiryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FollowUpController;
use App\Http\Controllers\Api\ProspectController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Test routes
Route::get('/test', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'API is working!',
        'timestamp' => now()->toDateTimeString()
    ]);
});

Route::get('/ping', function () {
    return response()->json(['message' => 'pong']);
});

// Public routes - NO ROLE REQUIRED FOR LOGIN
Route::post('/login', [AuthController::class, 'login']);

// Vehicle routes (public - no auth needed for dropdown data)
Route::get('/vehicles/models', [VehicleController::class, 'getModels']);
Route::get('/vehicles/engines/{model}', [VehicleController::class, 'getEngines']);
Route::get('/vehicles/variants/{model}/{engine}', [VehicleController::class, 'getVariants']);
Route::get('/vehicles/all', [VehicleController::class, 'getAllVehicles']);

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/district-data', [DashboardController::class, 'getDistrictData']);
    
    // Enquiries
    Route::get('/enquiries', [EnquiryController::class, 'list']);
    Route::get('/enquiries/{enquiry}', [EnquiryController::class, 'show']);
    Route::post('/enquiries', [EnquiryController::class, 'store']);
    
    // Followups
    Route::get('/followup/{enquiry}', [FollowUpController::class, 'show']);
    Route::post('/followup/{enquiry}/status', [FollowUpController::class, 'updateStatus']);
    
    // Prospect
    Route::get('/prospect/{enquiry}', [ProspectController::class, 'show']);
    Route::post('/prospect/{enquiry}', [ProspectController::class, 'store']);
});