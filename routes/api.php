<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\EnquiryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

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

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Vehicle routes (public - no auth needed for dropdown data)
Route::get('/vehicles/models', [VehicleController::class, 'getModels']);
Route::get('/vehicles/engines/{model}', [VehicleController::class, 'getEngines']);
Route::get('/vehicles/variants/{model}/{engine}', [VehicleController::class, 'getVariants']);
Route::get('/vehicles/all', [VehicleController::class, 'getAllVehicles']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Enquiries
    Route::get('/enquiries', [EnquiryController::class, 'list']);
    Route::get('/enquiries/{enquiry}', [EnquiryController::class, 'show']);
    Route::post('/enquiries', [EnquiryController::class, 'store']);
});