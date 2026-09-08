<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Delivery;
use App\Models\Enquiry;
use App\Models\LeadTransferRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    private function resolveAccessibleUserIds(User $viewer): array
    {
        if ($viewer->role === User::ROLE_SUPER_ADMIN) {
            return User::query()->pluck('id')->map(fn($id) => (int) $id)->values()->all();
        }

        $resolvedIds = [(int) $viewer->id];
        $frontier = [(int) $viewer->id];

        while (!empty($frontier)) {
            $childIds = User::query()
                ->whereIn('manager_id', $frontier)
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->values()
                ->all();

            $next = array_values(array_diff($childIds, $resolvedIds));
            if (empty($next)) {
                break;
            }

            $resolvedIds = array_values(array_unique(array_merge($resolvedIds, $next)));
            $frontier = $next;
        }

        return $resolvedIds;
    }

    public function stats(Request $request)
    {
        $viewer = $request->user();
        $accessibleUserIds = $this->resolveAccessibleUserIds($viewer);

        $today = now('Asia/Colombo');
        $todayStart = $today->copy()->startOfDay();
        $todayEnd = $today->copy()->endOfDay();
        $todayDate = $today->toDateString();

        // Total leads
        $totalLeads = Enquiry::whereIn('user_id', $accessibleUserIds)->count();
        
        // Today's leads
        $todayLeads = Enquiry::whereIn('user_id', $accessibleUserIds)
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->count();

        // Pending followups (due today)
        $pendingFollowups = Enquiry::whereIn('user_id', $accessibleUserIds)
            ->whereRaw("LOWER(COALESCE(followup_status, '')) <> ?", ['done'])
            ->whereDate('follow_date', $todayDate)
            ->count();

        // EPR counts by follow type (matches PC version dashboard)
        $baseQuery = Enquiry::with(['customer', 'vehicle'])
            ->whereIn('user_id', $accessibleUserIds)
            ->nonTerminalLead()
            ->whereRaw("LOWER(COALESCE(followup_status, 'pending')) NOT IN (?, ?)", ['done', 'not_done']);

        $dueFollowupQuery = (clone $baseQuery)
            ->whereDate('follow_date', '<=', $todayDate);

        $callCount = (clone $dueFollowupQuery)
            ->whereRaw('LOWER(COALESCE(follow_type, \'\')) LIKE ?', ['%call%'])
            ->count();

        $showroomCount = (clone $dueFollowupQuery)
            ->whereRaw('LOWER(COALESCE(follow_type, \'\')) LIKE ?', ['%showroom%'])
            ->count();

        $homeCount = (clone $dueFollowupQuery)
            ->whereRaw('LOWER(COALESCE(follow_type, \'\')) LIKE ?', ['%home%'])
            ->count();

        $totalEprCount = Enquiry::query()
            ->whereIn('user_id', $accessibleUserIds)
            ->pendingRegistration()
            ->whereRaw("LOWER(COALESCE(followup_status, 'pending')) NOT IN (?, ?)", ['done', 'not_done'])
            ->count();

        // Today's followups for the logged-in user (only their own)
        $todayFollowups = Enquiry::with(['customer:id,title,name'])
            ->where('user_id', $viewer->id)
            ->whereDate('follow_date', $todayDate)
            ->whereRaw("LOWER(COALESCE(followup_status, '')) <> ?", ['done'])
            ->orderBy('follow_time')
            ->limit(10)
            ->get()
            ->map(function ($enquiry) {
                return [
                    'id' => $enquiry->id,
                    'customer_name' => trim(($enquiry->customer?->title ? $enquiry->customer->title . ' ' : '') . ($enquiry->customer?->name ?? 'Unknown')),
                    'follow_type' => $enquiry->follow_type,
                    'follow_time' => $enquiry->follow_time,
                ];
            });

        // Use the same registered-lead scope as the web dashboard.
        $leadStatusCounts = collect(['hot', 'warm', 'cold'])
            ->mapWithKeys(function (string $status) use ($accessibleUserIds): array {
                $count = Enquiry::query()
                    ->whereIn('user_id', $accessibleUserIds)
                    ->registeredLead()
                    ->whereHas('prospectSheet', function ($query) use ($status): void {
                        $query->whereRaw("LOWER(COALESCE(lead_status, '')) = ?", [$status]);
                    })
                    ->count();

                return [$status => $count];
            })
            ->all();

        // Match the web dashboard stage scopes exactly.
        $activeBookings = Enquiry::query()
            ->whereIn('user_id', $accessibleUserIds)
            ->activeBookingStage()
            ->count();

        $activeInquiries = Enquiry::query()
            ->whereIn('user_id', $accessibleUserIds)
            ->activeInquiryStage()
            ->count();

        return response()->json([
            'total_leads' => $totalLeads,
            'today_leads' => $todayLeads,
            'pending_followups' => $pendingFollowups,
            'lead_status_counts' => $leadStatusCounts,
            'today_followups' => $todayFollowups,
            'active_bookings' => $activeBookings,
            'active_inquiries' => $activeInquiries,
            // EPR counts by follow type (matches PC version)
            'call_count' => $callCount,
            'showroom_count' => $showroomCount,
            'home_count' => $homeCount,
            'total_epr_count' => $totalEprCount,
            'user' => [
                'name' => $viewer->name,
                'role_label' => $viewer->role_label,
            ],
        ]);
    }

    public function getDistrictData(Request $request)
    {
        $viewer = $request->user();
        $accessibleUserIds = $this->resolveAccessibleUserIds($viewer);

        $districtData = [];
        foreach (User::DISTRICT_OPTIONS as $district) {
            $count = Enquiry::with(['customer'])
                ->whereIn('user_id', $accessibleUserIds)
                ->whereHas('customer', function ($query) use ($district) {
                    $query->whereRaw('LOWER(TRIM(COALESCE(district, \'\'))) = ?', [strtolower($district)]);
                })
                ->whereRaw("LOWER(COALESCE(followup_status, '')) <> ?", ['done'])
                ->count();

            $districtData[] = [
                'district' => $district,
                'count' => $count,
            ];
        }

        return response()->json($districtData);
    }

    public function areaManager(Request $request)
    {
        $viewer = $request->user();
        abort_unless($viewer?->role === User::ROLE_AREA_MANAGER, 403);

        $consultants = User::query()
            ->where('role', User::ROLE_SALES_CONSULTANT)
            ->where('manager_id', $viewer->id)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'employee_number', 'phone', 'role', 'manager_id']);

        $deliveryQuery = Delivery::query()
            ->whereHas('enquiry.user', function ($query) use ($viewer): void {
                $query->where('manager_id', $viewer->id);
            });

        $deliveryApprovals = [
            'pending' => (clone $deliveryQuery)->where('approval_status', Delivery::APPROVAL_PENDING)->count(),
            'approved' => (clone $deliveryQuery)->where('approval_status', Delivery::APPROVAL_APPROVED)->count(),
            'rejected' => (clone $deliveryQuery)->where('approval_status', Delivery::APPROVAL_REJECTED)->count(),
        ];

        $consultantDetails = $consultants->map(function (User $consultant): array {
            $consultantId = (int) $consultant->id;
            $pendingRegistration = Enquiry::query()->where('user_id', $consultantId)->pendingRegistration()->count();
            $pendingFollowup = Enquiry::query()
                ->where('user_id', $consultantId)
                ->whereRaw("LOWER(COALESCE(followup_status, 'pending')) <> ?", ['done'])
                ->count();
            $pendingBooking = Enquiry::query()
                ->where('user_id', $consultantId)->registeredLead()->doesntHave('booking')->count();
            $pendingDelivery = Enquiry::query()
                ->where('user_id', $consultantId)
                ->whereHas('booking', fn($query) => $query->whereNotNull('booking_completed_at'))
                ->doesntHave('delivery')->count();
            $counts = [
                'pending_registration' => $pendingRegistration,
                'pending_followup' => $pendingFollowup,
                'pending_booking' => $pendingBooking,
                'pending_delivery' => $pendingDelivery,
            ];

            return [
                'id' => $consultantId,
                'name' => $consultant->name,
                'email' => $consultant->email,
                'employee_number' => $consultant->employee_number,
                'phone' => $consultant->phone,
                'counts' => $counts,
                'total_pending' => array_sum($counts),
            ];
        })->values();

        $districtLeadRows = collect(User::DISTRICT_OPTIONS)->map(function (string $district) use ($consultants): array {
            $count = Enquiry::query()
                ->whereIn('user_id', $consultants->pluck('id')->all())
                ->whereHas('customer', fn($query) =>
                    $query->whereRaw('LOWER(TRIM(COALESCE(district, \'\'))) = ?', [strtolower($district)]))
                ->count();
            return ['district' => $district, 'leads' => $count];
        })->values();

        $districtCounts = $districtLeadRows->mapWithKeys(
            fn(array $row): array => [strtolower($row['district']) => (int) $row['leads']]
        );
        $provinceLeadRows = collect(User::PROVINCE_DISTRICT_MAP)->map(
            fn(array $districts, string $province): array => [
                'province' => $province,
                'leads' => collect($districts)->sum(
                    fn(string $district): int => (int) $districtCounts->get(strtolower($district), 0)
                ),
            ]
        )->values();
        $mapPath = public_path('data/sri-lanka-districts-map.json');
        $mapData = is_file($mapPath)
            ? json_decode((string) file_get_contents($mapPath), true)
            : null;

        return response()->json([
            'sales_consultants' => $consultantDetails,
            'pending_transfer_requests' => LeadTransferRequest::query()
                ->where('area_manager_id', $viewer->id)
                ->where('status', LeadTransferRequest::STATUS_PENDING)
                ->count(),
            'delivery_approvals' => $deliveryApprovals,
            'districts' => User::DISTRICT_OPTIONS,
            'analytics' => $this->areaManagerAnalytics($request, $viewer, $consultants->pluck('id')->all()),
            'lead_overview' => [
                'map' => $mapData,
                'districts' => $districtLeadRows,
                'provinces' => $provinceLeadRows,
                'province_district_map' => User::PROVINCE_DISTRICT_MAP,
            ],
        ]);
    }

    public function registerSalesConsultant(Request $request)
    {
        $viewer = $request->user();
        abort_unless($viewer?->role === User::ROLE_AREA_MANAGER, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'employee_number' => ['required', 'regex:/^M\d{5}$/', Rule::unique('users', 'employee_number')],
            'phone' => ['nullable', 'regex:/^0\d{9}$/'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'phone.regex' => 'Phone number must start with 0 and contain exactly 10 digits.',
            'employee_number.regex' => 'Employee number must start with M followed by exactly 5 digits.',
        ]);

        $consultant = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'employee_number' => $validated['employee_number'],
            'phone' => $validated['phone'] ?? null,
            'role' => User::ROLE_SALES_CONSULTANT,
            'manager_id' => $viewer->id,
            'password' => $validated['password'],
            'permitted_districts' => null,
        ]);

        return response()->json([
            'message' => 'Sales Consultant registered successfully.',
            'user' => $consultant->only(['id', 'name', 'email', 'employee_number', 'phone', 'role', 'manager_id']),
        ], 201);
    }

    private function areaManagerAnalytics(Request $request, User $viewer, array $consultantIds): array
    {
        $selectedConsultant = $request->integer('consultant_id');
        if ($selectedConsultant > 0) {
            abort_unless(in_array($selectedConsultant, array_map('intval', $consultantIds), true), 403);
            $consultantIds = [$selectedConsultant];
        }

        $query = Enquiry::query()->whereIn('user_id', $consultantIds);
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', Carbon::parse((string) $request->string('from_date'))->toDateString());
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', Carbon::parse((string) $request->string('to_date'))->toDateString());
        }
        if ($request->filled('district')) {
            $district = User::normalizeDistrictName((string) $request->string('district'));
            abort_unless($district !== null, 422, 'Please select a valid district.');
            $query->whereHas('customer', fn($customerQuery) =>
                $customerQuery->whereRaw('LOWER(TRIM(COALESCE(district, \'\'))) = ?', [strtolower($district)]));
        }
        if ($request->filled('lead_result')) {
            $leadResult = strtolower(trim((string) $request->string('lead_result')));
            abort_unless(in_array($leadResult, ['active', 'lost', 'closed'], true), 422);
            $query->whereRaw("LOWER(COALESCE(followup_result, '')) = ?", [$leadResult]);
        }
        if ($request->filled('lead_temperature')) {
            $temperature = strtolower(trim((string) $request->string('lead_temperature')));
            abort_unless(in_array($temperature, ['hot', 'warm', 'cold'], true), 422);
            $query->whereRaw("LOWER(COALESCE(followup_lead_temperature, '')) = ?", [$temperature]);
        }
        if ($request->filled('follow_type')) {
            $followType = strtolower(trim((string) $request->string('follow_type')));
            abort_unless(in_array($followType, ['call', 'showroom', 'home'], true), 422);
            $query->whereRaw("LOWER(COALESCE(follow_type, '')) LIKE ?", ["%{$followType}%"]);
        }
        if ($request->filled('followup_status')) {
            $status = strtolower(trim((string) $request->string('followup_status')));
            abort_unless(in_array($status, ['done', 'pending'], true), 422);
            $status === 'done'
                ? $query->whereRaw("LOWER(COALESCE(followup_status, '')) = ?", ['done'])
                : $query->whereRaw("LOWER(COALESCE(followup_status, '')) <> ?", ['done']);
        }

        $total = (clone $query)->count();
        $leadResultCount = fn(string $result): int => (clone $query)
            ->whereRaw("LOWER(COALESCE(followup_result, '')) = ?", [$result])->count();
        $leadStatusCount = fn(string $status): int => (clone $query)
            ->whereHas('prospectSheet', fn($prospectQuery) =>
                $prospectQuery->whereRaw("LOWER(COALESCE(lead_status, '')) = ?", [$status]))->count();

        return [
            'total_leads' => $total,
            'active_leads' => $leadResultCount('active'),
            'lost_leads' => $leadResultCount('lost'),
            'closed_leads' => $leadResultCount('closed'),
            'hot_leads' => $leadStatusCount('hot'),
            'warm_leads' => $leadStatusCount('warm'),
            'cold_leads' => $leadStatusCount('cold'),
            'pending_followups' => (clone $query)
                ->whereRaw("LOWER(COALESCE(followup_status, 'pending')) <> ?", ['done'])->count(),
        ];
    }
}
