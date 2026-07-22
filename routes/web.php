<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\EmiController;
use App\Http\Controllers\EnquiryController;
use App\Http\Controllers\FollowUpController;
use App\Http\Controllers\LeadTransferRequestController;
use App\Http\Controllers\ProspectSheetController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard.home')
        : redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showCommonLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'loginCommon'])->name('auth.login.common.submit');

/*
|--------------------------------------------------------------------------
| Role Based Auth
|--------------------------------------------------------------------------
*/
Route::get('/auth', [AuthController::class, 'roles'])->name('auth.roles');
Route::get('/login/{role}', [AuthController::class, 'showLoginForm'])->name('auth.login.form');
Route::post('/login/{role}', [AuthController::class, 'login'])->name('auth.login.submit');
Route::get('/register/{role}', [AuthController::class, 'showRegistrationForm'])->name('auth.register.form');
Route::post('/register/{role}', [AuthController::class, 'register'])->name('auth.register.submit');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('auth.logout');

/*
|--------------------------------------------------------------------------
| Dashboards (Separate Per Role)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/crm-dashboard', [DashboardController::class, 'main'])->name('dashboard.main');

    Route::get('/dashboard', [DashboardController::class, 'home'])->name('dashboard.home');

    Route::get('/dashboard/super-admin', [DashboardController::class, 'superAdmin'])
        ->middleware('role:' . User::ROLE_SUPER_ADMIN)
        ->name('dashboard.super_admin');

    Route::get('/dashboard/head-of-sales', [DashboardController::class, 'headOfSales'])
        ->middleware('role:' . User::ROLE_HEAD_OF_SALES)
        ->name('dashboard.head_of_sales');

    Route::get('/dashboard/area-manager', [DashboardController::class, 'areaManager'])
        ->middleware('role:' . User::ROLE_AREA_MANAGER)
        ->name('dashboard.area_manager');

    Route::post('/dashboard/area-manager/consultants/{consultant}/reminder/system', [DashboardController::class, 'sendSalesConsultantSystemReminder'])
        ->middleware('role:' . User::ROLE_AREA_MANAGER)
        ->name('dashboard.area_manager.consultant_reminder.system');

    Route::post('/dashboard/reminders/{reminder}/read', [DashboardController::class, 'markSalesConsultantReminderRead'])
        ->name('dashboard.reminders.read');

    Route::get('/dashboard/sales-consultant', [DashboardController::class, 'salesConsultant'])
        ->middleware('role:' . User::ROLE_SALES_CONSULTANT)
        ->name('dashboard.sales_consultant');

    Route::get('/dashboard/analytics-report', [DashboardController::class, 'downloadAnalyticsReport'])
        ->name('dashboard.analytics.report');

    Route::get('/dashboard/analytics', [DashboardController::class, 'analytics'])
        ->name('dashboard.analytics');

    Route::get('/dashboard/followup-summary', [DashboardController::class, 'followupSummary'])
        ->name('dashboard.followup_summary');

    Route::get('/dashboard/followup-tracker', [DashboardController::class, 'followupTracker'])
        ->name('dashboard.followup_tracker');

    Route::get('/dashboard/followup-tracker/{section}', [DashboardController::class, 'followupTrackerSection'])
        ->whereIn('section', ['today-due', 'today-attempted', 'total-followed', 'total-attempted'])
        ->name('dashboard.followup_tracker.section');

    Route::get('/dashboard/analytics/{section}', [DashboardController::class, 'analyticsDetail'])
        ->name('dashboard.analytics.detail');

    // District EPR API routes
    Route::get('/dashboard/district/{district}/eprs', [DashboardController::class, 'getDistrictEprs'])
        ->name('dashboard.district.eprs');

    Route::get('/dashboard/super-admin/consultant-transfer', [DashboardController::class, 'showConsultantTransferForm'])
        ->middleware('role:' . User::ROLE_SUPER_ADMIN)
        ->name('dashboard.super_admin.consultant_transfer.form');

    Route::post('/dashboard/super-admin/consultant-transfer', [DashboardController::class, 'transferConsultantData'])
        ->middleware('role:' . User::ROLE_SUPER_ADMIN)
        ->name('dashboard.super_admin.consultant_transfer.run');

    Route::get('/dashboard/super-admin/users/{managedUser}/edit', [DashboardController::class, 'editUser'])
        ->middleware('role:' . User::ROLE_SUPER_ADMIN)
        ->name('dashboard.super_admin.users.edit');

    Route::put('/dashboard/super-admin/users/{managedUser}', [DashboardController::class, 'updateUser'])
        ->middleware('role:' . User::ROLE_SUPER_ADMIN)
        ->name('dashboard.super_admin.users.update');

    Route::get('/lead-transfer/request', [LeadTransferRequestController::class, 'create'])
        ->middleware('role:' . User::ROLE_SALES_CONSULTANT)
        ->name('lead_transfer.request.create');

    Route::post('/lead-transfer/request', [LeadTransferRequestController::class, 'store'])
        ->middleware('role:' . User::ROLE_SALES_CONSULTANT)
        ->name('lead_transfer.request.store');

    Route::get('/lead-transfer/approvals', [LeadTransferRequestController::class, 'approvals'])
        ->middleware('role:' . User::ROLE_AREA_MANAGER)
        ->name('lead_transfer.approvals');

    Route::post('/lead-transfer/{transferRequest}/approve', [LeadTransferRequestController::class, 'approve'])
        ->middleware('role:' . User::ROLE_AREA_MANAGER)
        ->name('lead_transfer.approve');

    Route::post('/lead-transfer/{transferRequest}/reject', [LeadTransferRequestController::class, 'reject'])
        ->middleware('role:' . User::ROLE_AREA_MANAGER)
        ->name('lead_transfer.reject');
});

/*
|--------------------------------------------------------------------------
| CRM Module
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/emi-calculator', [EmiController::class, 'index'])->name('emi.calculator');

    Route::get('/new-enquiry', [EnquiryController::class, 'create']);
    Route::get('/get-engines/{model}', [EnquiryController::class, 'getEngines']);
    Route::get('/get-variants/{model}/{engine}', [EnquiryController::class, 'getVariants']);
    Route::post('/save-enquiry', [EnquiryController::class, 'store'])->name('save.customer');
    
    // EPR List Routes with Filters
    Route::get('/epr', [EnquiryController::class, 'list'])->name('enquiries.list');
    Route::get('/epr/call', [EnquiryController::class, 'listCallEpds'])->name('enquiries.list.call');
    Route::get('/epr/showroom', [EnquiryController::class, 'listShowroomEpds'])->name('enquiries.list.showroom');
    Route::get('/epr/home', [EnquiryController::class, 'listHomeEpds'])->name('enquiries.list.home');
    
    Route::get('/followup/{enquiry}', [FollowUpController::class, 'show'])->name('followup.show');
    Route::post('/followup/{enquiry}/status', [FollowUpController::class, 'updateStatus'])->name('followup.update_status');

    Route::get('/prospect/{enquiry}', [ProspectSheetController::class, 'show'])->name('prospect.show');
    Route::post('/prospect/{enquiry}', [ProspectSheetController::class, 'store'])->name('prospect.store');

    Route::get('/booking/{enquiry}', [BookingController::class, 'show'])->name('booking.show');
    Route::post('/booking/{enquiry}', [BookingController::class, 'store'])->name('booking.store');

    Route::get('/delivery/{enquiry}', [DeliveryController::class, 'show'])->name('delivery.show');
    Route::post('/delivery/{enquiry}', [DeliveryController::class, 'store'])->name('delivery.store');
});
