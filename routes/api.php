<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\LeadTransferController;
use App\Http\Controllers\Api\EnquiryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FollowUpController;
use App\Http\Controllers\Api\ProspectController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\DeliveryController;
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
    Route::get('/dashboard/area-manager', [DashboardController::class, 'areaManager']);
    Route::post('/dashboard/area-manager/sales-consultants', [DashboardController::class, 'registerSalesConsultant']);
    Route::get('/lead-transfer/request', [LeadTransferController::class, 'requestData']);
    Route::post('/lead-transfer/request', [LeadTransferController::class, 'store']);
    Route::get('/lead-transfer/approvals', [LeadTransferController::class, 'approvals']);
    Route::post('/lead-transfer/{transferRequest}/approve', [LeadTransferController::class, 'approve']);
    Route::post('/lead-transfer/{transferRequest}/reject', [LeadTransferController::class, 'reject']);
    
    // Enquiries - Now supports all filters: booking, inquiry, delivery, etc.
    Route::get('/enquiries', [EnquiryController::class, 'list']);
    Route::get('/enquiries/call', [EnquiryController::class, 'listCallEpds']);
    Route::get('/enquiries/showroom', [EnquiryController::class, 'listShowroomEpds']);
    Route::get('/enquiries/home', [EnquiryController::class, 'listHomeEpds']);
    Route::get('/enquiries/{enquiry}', [EnquiryController::class, 'show']);
    Route::post('/enquiries', [EnquiryController::class, 'store']);
    
    // Followups
    Route::get('/followup/{enquiry}', [FollowUpController::class, 'show']);
    Route::post('/followup/{enquiry}/status', [FollowUpController::class, 'updateStatus']);
    
    // Prospect
    Route::get('/prospect/{enquiry}', [ProspectController::class, 'show']);
    Route::post('/prospect/{enquiry}', [ProspectController::class, 'store']);
    
    // Booking - FIXED: Added missing booking routes
    Route::get('/booking/{enquiry}', [BookingController::class, 'show']);
    Route::post('/booking/{enquiry}', [BookingController::class, 'store']);
    
    // Delivery - FIXED: Added missing delivery routes
    Route::get('/delivery/{enquiry}', [DeliveryController::class, 'show']);
    Route::post('/delivery/{enquiry}', [DeliveryController::class, 'store']);
});
