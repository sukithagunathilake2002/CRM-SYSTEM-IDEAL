<?php

namespace App\Http\Controllers;

use App\Models\Enquiry;
use App\Models\FollowupAttempt;
use App\Models\LeadTransferRequest;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function home(Request $request): RedirectResponse
    {
        $user = $request->user();

        $routeByRole = [
            User::ROLE_SUPER_ADMIN => 'dashboard.super_admin',
            User::ROLE_HEAD_OF_SALES => 'dashboard.head_of_sales',
            User::ROLE_AREA_MANAGER => 'dashboard.area_manager',
            User::ROLE_SALES_CONSULTANT => 'dashboard.main',
        ];

        $routeName = $routeByRole[$user->role] ?? 'dashboard.sales_consultant';

        return redirect()->route($routeName);
    }

    public function main(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user?->role === User::ROLE_SUPER_ADMIN) {
            return redirect()->route('dashboard.super_admin');
        }

        if ($user?->role === User::ROLE_HEAD_OF_SALES) {
            return redirect()->route('dashboard.head_of_sales');
        }

        $dashboardEpds = $this->getDashboardEpData($user);

        return view('dashboard', compact('dashboardEpds'));
    }

    public function superAdmin(Request $request): View
    {
        $user = $request->user();
        $counts = $this->roleCounts();
        $heads = User::query()
            ->where('role', User::ROLE_HEAD_OF_SALES)
            ->orderBy('name')
            ->get();

        $headIds = $heads
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->values()
            ->all();

        $areaManagers = collect();
        $salesConsultants = collect();

        if (!empty($headIds)) {
            $areaManagers = User::query()
                ->where('role', User::ROLE_AREA_MANAGER)
                ->whereIn('manager_id', $headIds)
                ->orderBy('name')
                ->get();

            $areaManagerIds = $areaManagers
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->values()
                ->all();

            if (!empty($areaManagerIds)) {
                $salesConsultants = User::query()
                    ->where('role', User::ROLE_SALES_CONSULTANT)
                    ->whereIn('manager_id', $areaManagerIds)
                    ->orderBy('name')
                    ->get();
            }
        }

        $areaManagersByHead = $areaManagers->groupBy(fn(User $areaManager): int => (int) $areaManager->manager_id);
        $salesConsultantsByArea = $salesConsultants->groupBy(fn(User $salesConsultant): int => (int) $salesConsultant->manager_id);

        $headHierarchy = $heads->map(function (User $head) use ($areaManagersByHead, $salesConsultantsByArea): array {
            $areaRows = ($areaManagersByHead->get((int) $head->id) ?? collect())
                ->map(function (User $areaManager) use ($salesConsultantsByArea): array {
                    $consultants = ($salesConsultantsByArea->get((int) $areaManager->id) ?? collect())
                        ->map(fn(User $salesConsultant): array => [
                            'id' => (int) $salesConsultant->id,
                            'name' => $salesConsultant->name,
                            'email' => $salesConsultant->email,
                            'phone' => $salesConsultant->phone,
                        ])
                        ->values()
                        ->all();

                    return [
                        'id' => (int) $areaManager->id,
                        'name' => $areaManager->name,
                        'email' => $areaManager->email,
                        'phone' => $areaManager->phone,
                        'sales_consultants_count' => count($consultants),
                        'sales_consultants' => $consultants,
                    ];
                })
                ->values()
                ->all();

            $areaManagersCount = count($areaRows);
            $salesConsultantsCount = array_sum(array_map(
                fn(array $areaRow): int => (int) $areaRow['sales_consultants_count'],
                $areaRows
            ));

            return [
                'id' => (int) $head->id,
                'name' => $head->name,
                'email' => $head->email,
                'phone' => $head->phone,
                'area_managers_count' => $areaManagersCount,
                'sales_consultants_count' => $salesConsultantsCount,
                'dependent_users_count' => $areaManagersCount + $salesConsultantsCount,
                'area_managers' => $areaRows,
            ];
        })
            ->values()
            ->all();

        $manageableUsers = User::query()
            ->with('manager:id,name')
            ->orderBy('role')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'employee_number', 'phone', 'role', 'manager_id']);

        $analytics = $this->buildAnalytics($user, $request);
        $followupEscalations = $this->buildFollowupEscalations($user);
        
        $dashboardEpds = $this->getDashboardEpData($user);
        
        $districtEpData = $this->getDistrictEpData($user);

        return view('dashboards.super-admin', compact('counts', 'headHierarchy', 'manageableUsers', 'analytics', 'dashboardEpds', 'districtEpData', 'followupEscalations'));
    }

    public function headOfSales(Request $request): View
    {
        $user = $request->user();
        $areaManagers = User::query()
            ->where('role', User::ROLE_AREA_MANAGER)
            ->where('manager_id', $user->id)
            ->orderBy('name')
            ->get();

        $areaManagerIds = $areaManagers
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->values()
            ->all();

        $salesConsultants = collect();

        if (!empty($areaManagerIds)) {
            $salesConsultants = User::query()
                ->where('role', User::ROLE_SALES_CONSULTANT)
                ->whereIn('manager_id', $areaManagerIds)
                ->orderBy('name')
                ->get();
        }

        $salesConsultantsByArea = $salesConsultants->groupBy(fn(User $salesConsultant): int => (int) $salesConsultant->manager_id);

        $hierarchy = $areaManagers->map(function (User $areaManager) use ($salesConsultantsByArea): array {
            $consultants = ($salesConsultantsByArea->get((int) $areaManager->id) ?? collect())
                ->map(fn(User $salesConsultant): array => [
                    'id' => (int) $salesConsultant->id,
                    'name' => $salesConsultant->name,
                    'email' => $salesConsultant->email,
                    'phone' => $salesConsultant->phone,
                ])
                ->values()
                ->all();

            return [
                'id' => (int) $areaManager->id,
                'name' => $areaManager->name,
                'email' => $areaManager->email,
                'phone' => $areaManager->phone,
                'sales_consultants_count' => count($consultants),
                'sales_consultants' => $consultants,
            ];
        })
            ->values()
            ->all();

        $hierarchyCounts = [
            'area_managers' => $areaManagers->count(),
            'sales_consultants' => $salesConsultants->count(),
        ];
        $hierarchyCounts['dependent_users'] = $hierarchyCounts['area_managers']
            + $hierarchyCounts['sales_consultants'];

        $analytics = $this->buildAnalytics($user, $request);
        $followupEscalations = $this->buildFollowupEscalations($user);
        
        $dashboardEpds = $this->getDashboardEpData($user);
        
        $districtEpData = $this->getDistrictEpData($user);

        return view('dashboards.head-of-sales', compact('hierarchy', 'hierarchyCounts', 'analytics', 'dashboardEpds', 'districtEpData', 'followupEscalations'));
    }

    public function areaManager(Request $request): View
    {
        $user = $request->user();
        $salesConsultants = User::query()
            ->where('role', User::ROLE_SALES_CONSULTANT)
            ->where('manager_id', $user->id)
            ->orderBy('name')
            ->get();
        $pendingTransferRequestCount = LeadTransferRequest::query()
            ->where('area_manager_id', $user->id)
            ->where('status', LeadTransferRequest::STATUS_PENDING)
            ->count();
        $analytics = $this->buildAnalytics($user, $request);
        
        $dashboardEpds = $this->getDashboardEpData($user);
        
        $districtEpData = $this->getDistrictEpData($user);

        return view('dashboards.area-manager', compact('salesConsultants', 'pendingTransferRequestCount', 'analytics', 'dashboardEpds', 'districtEpData'));
    }

    public function analytics(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user?->role === User::ROLE_SALES_CONSULTANT) {
            return redirect()->route('dashboard.main');
        }

        $analytics = $this->buildAnalytics($user, $request);

        return view('dashboards.analytics', compact('analytics'));
    }

    public function followupSummary(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (!in_array($user?->role, [User::ROLE_SUPER_ADMIN, User::ROLE_HEAD_OF_SALES], true)) {
            return redirect()->route('dashboard.home');
        }

        $followupEscalations = $this->buildFollowupEscalations($user);

        return view('dashboards.followup-summary', compact('followupEscalations'));
    }

    public function followupTracker(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (!$this->canViewFollowupTracker($user)) {
            return redirect()->route('dashboard.home');
        }

        return view('dashboards.followup-tracker');
    }

    public function followupTrackerSection(Request $request, string $section): View|RedirectResponse
    {
        $user = $request->user();

        if (!$this->canViewFollowupTracker($user)) {
            return redirect()->route('dashboard.home');
        }

        $report = $this->buildFollowupTrackerReport($user, $section, $request);

        return view('dashboards.followup-tracker-section', compact('report'));
    }

    public function analyticsDetail(Request $request, string $section): View|RedirectResponse
    {
        $user = $request->user();

        if ($user?->role === User::ROLE_SALES_CONSULTANT) {
            return redirect()->route('dashboard.main');
        }

        $sections = [
            'active' => 'Active Analytics',
            'booking' => 'Booking Analytics',
            'lost' => 'Lost Analytics',
            'closed' => 'Closed Lead Analytics',
        ];

        if (!array_key_exists($section, $sections)) {
            abort(404);
        }

        $analytics = $this->buildAnalytics($user, $request);
        $sectionTitle = $sections[$section];

        return view('dashboards.analytics-section', compact('analytics', 'section', 'sectionTitle'));
    }

    public function salesConsultant(Request $request): View
    {
        $user = $request->user();
        $pendingTransferRequestCount = LeadTransferRequest::query()
            ->where('requested_by', $user->id)
            ->where('status', LeadTransferRequest::STATUS_PENDING)
            ->count();
        
        $dashboardEpds = $this->getDashboardEpData($user);
        
        $districtEpData = $this->getDistrictEpData($user);

        return view('dashboards.sales-consultant', compact('user', 'pendingTransferRequestCount', 'dashboardEpds', 'districtEpData'));
    }

    /**
     * API endpoint to get EPRs for a specific district
     */
    /**
 * API endpoint to get EPRs for a specific district
 */
public function getDistrictEprs(Request $request, string $district): \Illuminate\Http\JsonResponse
{
    $viewer = $request->user();
    $accessibleUserIds = $this->resolveAccessibleUserIds($viewer);
    
    $normalizedDistrict = User::normalizeDistrictName($district);
    if ($normalizedDistrict === null) {
        return response()->json(['error' => 'Invalid district'], 400);
    }
    
    $enquiries = Enquiry::with(['customer', 'vehicle', 'user'])
        ->whereIn('user_id', $accessibleUserIds)
        ->pendingRegistration()
        ->whereHas('customer', function ($query) use ($normalizedDistrict) {
            $query->whereRaw('LOWER(TRIM(COALESCE(district, \'\'))) = ?', [strtolower($normalizedDistrict)]);
        })
        ->whereRaw("LOWER(COALESCE(followup_status, '')) <> ?", ['done'])
        ->orderBy('follow_date', 'asc')
        ->get();
    
    $mappedEnquiries = $enquiries->map(function ($enquiry) {
        $customer = $enquiry->customer;
        $vehicle = $enquiry->vehicle;
        $mobiles = is_array($customer?->mobile_numbers) ? $customer->mobile_numbers : [];
        $primaryPhone = count($mobiles) ? (string) $mobiles[0] : 'N/A';
        
        // Ensure latitude and longitude are returned as proper numbers
        $latitude = $enquiry->latitude;
        $longitude = $enquiry->longitude;
        
        // Debug log to check values
        \Log::info('EPR Data', [
            'id' => $enquiry->id,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
        
        return [
            'id' => $enquiry->id,
            'customer_name' => trim(($customer?->title ? $customer->title . '. ' : '') . ($customer?->name ?? 'Unknown')),
            'primary_phone' => $primaryPhone,
            'vehicle_name' => trim(($vehicle?->model ?? '') . ' ' . ($vehicle?->variant ?? '')),
            'follow_type' => $enquiry->follow_type,
            'follow_date' => $enquiry->follow_date,
            'follow_time' => $enquiry->follow_time,
            'address' => $customer?->address1 ?: ($customer?->location ?: 'Address not available'),
            'latitude' => $latitude !== null ? (float) $latitude : null,
            'longitude' => $longitude !== null ? (float) $longitude : null,
            'has_location' => $latitude !== null && $longitude !== null && $latitude != 0 && $longitude != 0,
        ];
    });
    
    $count = $mappedEnquiries->count();
    $hasLocationData = $mappedEnquiries->filter(fn($e) => $e['has_location'])->count();
    
    return response()->json([
        'success' => true,
        'district' => $normalizedDistrict,
        'count' => $count,
        'has_location_data' => $hasLocationData,
        'eprs' => $mappedEnquiries,
    ]);
}

    /**
     * Get district EPR data for the map display
     */
    private function getDistrictEpData(User $viewer): array
    {
        $accessibleUserIds = $this->resolveAccessibleUserIds($viewer);
        
        $districtCounts = [];
        
        foreach (User::DISTRICT_OPTIONS as $district) {
            $count = Enquiry::with(['customer'])
                ->whereIn('user_id', $accessibleUserIds)
                ->pendingRegistration()
                ->whereHas('customer', function ($query) use ($district) {
                    $query->whereRaw('LOWER(TRIM(COALESCE(district, \'\'))) = ?', [strtolower($district)]);
                })
                ->whereRaw("LOWER(COALESCE(followup_status, '')) <> ?", ['done'])
                ->count();
            
            $districtCounts[$district] = $count;
        }
        
        $maxCount = max($districtCounts) ?: 1;
        
        $mapData = [];
        foreach ($districtCounts as $district => $count) {
            $mapData[] = [
                'district' => $district,
                'count' => $count,
                'intensity' => $count > 0 ? min(1, $count / $maxCount) : 0,
            ];
        }
        
        return [
            'district_counts' => $districtCounts,
            'max_count' => $maxCount,
            'map_data' => $mapData,
            'total_active_eprs' => array_sum($districtCounts),
        ];
    }

    /**
     * Get dashboard EPR data filtered by followup type
     */
    private function getDashboardEpData(User $viewer): array
    {
        $accessibleUserIds = $this->resolveAccessibleUserIds($viewer);
        $today = Carbon::now('Asia/Colombo')->toDateString();
        
        $baseQuery = Enquiry::with(['customer', 'vehicle'])
            ->whereIn('user_id', $accessibleUserIds)
            ->pendingRegistration()
            ->whereRaw("LOWER(COALESCE(followup_status, '')) <> ?", ['done']);

        $dueFollowupQuery = (clone $baseQuery)
            ->whereDate('follow_date', '<=', $today);

        $callCount = (clone $dueFollowupQuery)
            ->whereRaw('LOWER(COALESCE(follow_type, \'\')) LIKE ?', ['%call%'])
            ->count();

        $showroomCount = (clone $dueFollowupQuery)
            ->whereRaw('LOWER(COALESCE(follow_type, \'\')) LIKE ?', ['%showroom%'])
            ->count();

        $homeCount = (clone $dueFollowupQuery)
            ->whereRaw('LOWER(COALESCE(follow_type, \'\')) LIKE ?', ['%home%'])
            ->count();

        $totalCount = (clone $baseQuery)->count();
        
        $callEpds = (clone $dueFollowupQuery)
            ->whereRaw('LOWER(COALESCE(follow_type, \'\')) LIKE ?', ['%call%'])
            ->orderBy('follow_date', 'asc')
            ->limit(10)
            ->get()
            ->map(function ($enquiry) {
                $customer = $enquiry->customer;
                $vehicle = $enquiry->vehicle;
                $mobiles = is_array($customer?->mobile_numbers) ? $customer->mobile_numbers : [];
                $primaryPhone = count($mobiles) ? (string) $mobiles[0] : 'N/A';
                
                return (object) [
                    'id' => $enquiry->id,
                    'customer_name' => trim(($customer?->title ? $customer->title . '. ' : '') . ($customer?->name ?? 'Unknown')),
                    'primary_phone' => $primaryPhone,
                    'vehicle_name' => trim(($vehicle?->model ?? '') . ' ' . ($vehicle?->variant ?? '')),
                    'follow_type' => $enquiry->follow_type,
                    'follow_date' => $enquiry->follow_date,
                    'follow_time' => $enquiry->follow_time,
                    'created_at' => $enquiry->created_at,
                ];
            });
        
        $showroomEpds = (clone $dueFollowupQuery)
            ->whereRaw('LOWER(COALESCE(follow_type, \'\')) LIKE ?', ['%showroom%'])
            ->orderBy('follow_date', 'asc')
            ->limit(10)
            ->get()
            ->map(function ($enquiry) {
                $customer = $enquiry->customer;
                $vehicle = $enquiry->vehicle;
                $mobiles = is_array($customer?->mobile_numbers) ? $customer->mobile_numbers : [];
                $primaryPhone = count($mobiles) ? (string) $mobiles[0] : 'N/A';
                
                return (object) [
                    'id' => $enquiry->id,
                    'customer_name' => trim(($customer?->title ? $customer->title . '. ' : '') . ($customer?->name ?? 'Unknown')),
                    'primary_phone' => $primaryPhone,
                    'vehicle_name' => trim(($vehicle?->model ?? '') . ' ' . ($vehicle?->variant ?? '')),
                    'follow_type' => $enquiry->follow_type,
                    'follow_date' => $enquiry->follow_date,
                    'follow_time' => $enquiry->follow_time,
                    'created_at' => $enquiry->created_at,
                ];
            });
        
        $homeEpds = (clone $dueFollowupQuery)
            ->whereRaw('LOWER(COALESCE(follow_type, \'\')) LIKE ?', ['%home%'])
            ->orderBy('follow_date', 'asc')
            ->limit(10)
            ->get()
            ->map(function ($enquiry) {
                $customer = $enquiry->customer;
                $vehicle = $enquiry->vehicle;
                $mobiles = is_array($customer?->mobile_numbers) ? $customer->mobile_numbers : [];
                $primaryPhone = count($mobiles) ? (string) $mobiles[0] : 'N/A';
                
                return (object) [
                    'id' => $enquiry->id,
                    'customer_name' => trim(($customer?->title ? $customer->title . '. ' : '') . ($customer?->name ?? 'Unknown')),
                    'primary_phone' => $primaryPhone,
                    'vehicle_name' => trim(($vehicle?->model ?? '') . ' ' . ($vehicle?->variant ?? '')),
                    'follow_type' => $enquiry->follow_type,
                    'follow_date' => $enquiry->follow_date,
                    'follow_time' => $enquiry->follow_time,
                    'created_at' => $enquiry->created_at,
                ];
            });
        
        return [
            'call_epds' => $callEpds,
            'showroom_epds' => $showroomEpds,
            'home_epds' => $homeEpds,
            'call_count' => $callCount,
            'showroom_count' => $showroomCount,
            'home_count' => $homeCount,
            'total_count' => $totalCount,
        ];
    }

    private function canViewFollowupTracker(?User $user): bool
    {
        return $user instanceof User
            && in_array($user->role, [User::ROLE_SUPER_ADMIN, User::ROLE_HEAD_OF_SALES], true);
    }

    private function buildFollowupTrackerReport(User $viewer, string $section, Request $request): array
    {
        $today = Carbon::now('Asia/Colombo')->startOfDay();
        $accessibleUserIds = $this->resolveAccessibleUserIds($viewer);
        $filters = [
            'from_date' => trim((string) $request->query('from_date', '')),
            'to_date' => trim((string) $request->query('to_date', '')),
            'dealer_id' => trim((string) $request->query('dealer_id', '')),
            'sc_id' => trim((string) $request->query('sc_id', '')),
            'model' => trim((string) $request->query('model', '')),
        ];
        $requiresDateFilter = in_array($section, ['total-followed', 'total-attempted'], true);
        $fromDate = $this->parseFilterDate($filters['from_date'], true);
        $toDate = $this->parseFilterDate($filters['to_date'], false);
        $filterError = null;

        if ($requiresDateFilter) {
            if (!$fromDate || !$toDate) {
                $filterError = 'Please select From Date and To Date to view total follow-up data.';
            } elseif ($fromDate->greaterThan($toDate)) {
                $filterError = 'From Date must be before To Date.';
            } elseif ($fromDate->diffInDays($toDate) > 31) {
                $filterError = 'Please select dates within one month.';
            }
        }

        $sectionConfig = [
            'today-due' => [
                'title' => 'No. Of Leads Follow Up Today',
                'subtitle' => 'Today pending follow-ups categorized by type.',
                'notice' => "This dashboard is showing today's pending follow-up data.",
                'date_label' => $today->format('d M Y'),
                'total_label' => 'Leads follow up today',
                'type_source' => 'follow_type',
            ],
            'today-attempted' => [
                'title' => 'No. Of Leads Follow Ups Attempted Today',
                'subtitle' => 'Follow-up attempts saved today categorized by type.',
                'notice' => "This dashboard is showing today's attempted follow-up data.",
                'date_label' => $today->format('d M Y'),
                'total_label' => 'Follow-ups attempted today',
                'type_source' => 'attempted_type',
            ],
            'total-followed' => [
                'title' => 'Total No. Of Leads Followed Up',
                'subtitle' => 'Done follow-ups from the selected filter.',
                'notice' => 'This dashboard is showing filtered done follow-up data.',
                'date_label' => null,
                'total_label' => 'Leads followed up',
                'type_source' => 'attempted_type',
            ],
            'total-attempted' => [
                'title' => 'Total No. Of Follow Ups Attempted',
                'subtitle' => 'Done and Not Done follow-up attempts from the selected filter.',
                'notice' => 'This dashboard is showing filtered attempted follow-up data.',
                'date_label' => null,
                'total_label' => 'Follow-ups attempted',
                'type_source' => 'attempted_type',
            ],
        ][$section] ?? null;

        if ($sectionConfig === null) {
            abort(404);
        }

        $groups = [
            'call' => ['label' => 'Calls', 'count' => 0, 'rows' => []],
            'home_visit' => ['label' => 'Home Visit', 'count' => 0, 'rows' => []],
            'showroom_visit' => ['label' => 'Showroom Visit', 'count' => 0, 'rows' => []],
        ];

        if ($filterError === null) {
            if ($section === 'today-due') {
                $query = Enquiry::query()
                    ->with(['customer', 'vehicle', 'user.manager'])
                    ->whereIn('user_id', $accessibleUserIds);

                $query->whereDate('follow_date', $today->toDateString())
                    ->whereRaw("LOWER(COALESCE(followup_status, '')) <> ?", ['done']);

                $this->applyFollowupTrackerFilters($query, $filters, $accessibleUserIds);
                $query->orderByRaw('follow_time IS NULL, follow_time ASC');

                foreach ($query->get() as $enquiry) {
                    $sourceType = $enquiry->follow_type;
                    $typeKey = match ($this->normalizeFollowupType($sourceType)) {
                        'Call' => 'call',
                        'Home visit' => 'home_visit',
                        'Showroom visit' => 'showroom_visit',
                        default => null,
                    };

                    if ($typeKey === null) {
                        continue;
                    }

                    $groups[$typeKey]['count']++;
                    $groups[$typeKey]['rows'][] = $this->mapFollowupTrackerRow($enquiry, $sourceType);
                }
            } else {
                $attemptQuery = FollowupAttempt::query()
                    ->with(['enquiry.customer', 'enquiry.vehicle', 'enquiry.user.manager'])
                    ->whereHas('enquiry', function ($enquiryQuery) use ($accessibleUserIds, $filters): void {
                        $enquiryQuery->whereIn('user_id', $accessibleUserIds);
                        $this->applyFollowupTrackerFilters($enquiryQuery, $filters, $accessibleUserIds);
                    });

                if ($section === 'today-attempted') {
                    $attemptQuery->whereDate('attempted_at', $today->toDateString())
                        ->whereRaw("LOWER(COALESCE(followup_status, '')) IN (?, ?)", ['done', 'not_done']);
                } elseif ($section === 'total-followed') {
                    $attemptQuery->whereBetween('attempted_at', [$fromDate, $toDate])
                        ->whereRaw("LOWER(COALESCE(followup_status, '')) = ?", ['done']);
                } else {
                    $attemptQuery->whereBetween('attempted_at', [$fromDate, $toDate])
                        ->whereRaw("LOWER(COALESCE(followup_status, '')) IN (?, ?)", ['done', 'not_done']);
                }

                $attempts = $attemptQuery
                    ->orderByDesc('attempted_at')
                    ->get();

                if ($section === 'total-followed') {
                    $attempts = $attempts->unique('enquiry_id')->values();
                }

                foreach ($attempts as $attempt) {
                    if (!$attempt->enquiry instanceof Enquiry) {
                        continue;
                    }

                    $typeKey = match ($this->normalizeFollowupType($attempt->follow_type)) {
                        'Call' => 'call',
                        'Home visit' => 'home_visit',
                        'Showroom visit' => 'showroom_visit',
                        default => null,
                    };

                    if ($typeKey === null) {
                        continue;
                    }

                    $groups[$typeKey]['count']++;
                    $groups[$typeKey]['rows'][] = $this->mapFollowupAttemptTrackerRow($attempt);
                }
            }
        }

        return [
            'section' => $section,
            'title' => $sectionConfig['title'],
            'subtitle' => $sectionConfig['subtitle'],
            'notice' => $sectionConfig['notice'],
            'date_label' => $sectionConfig['date_label'],
            'total_label' => $sectionConfig['total_label'],
            'requires_date_filter' => $requiresDateFilter,
            'filter_error' => $filterError,
            'filters' => $filters,
            'filter_options' => $this->followupTrackerFilterOptions($accessibleUserIds),
            'groups' => array_values($groups),
            'total' => array_sum(array_map(fn(array $group): int => (int) $group['count'], $groups)),
        ];
    }

    private function applyFollowupTrackerFilters($query, array $filters, array $accessibleUserIds): void
    {
        $dealerId = (int) ($filters['dealer_id'] ?: 0);
        if ($dealerId > 0 && in_array($dealerId, $accessibleUserIds, true)) {
            $dealer = User::query()->find($dealerId);
            if ($dealer instanceof User) {
                $dealerUserIds = array_values(array_intersect(
                    $accessibleUserIds,
                    $this->resolveUserAndDescendantIds($dealer)
                ));

                empty($dealerUserIds)
                    ? $query->whereRaw('1 = 0')
                    : $query->whereIn('user_id', $dealerUserIds);
            }
        }

        $salesConsultantId = (int) ($filters['sc_id'] ?: 0);
        if ($salesConsultantId > 0 && in_array($salesConsultantId, $accessibleUserIds, true)) {
            $query->where('user_id', $salesConsultantId);
        }

        if ($filters['model'] !== '') {
            $model = strtolower($filters['model']);
            $query->whereHas('vehicle', function ($vehicleQuery) use ($model): void {
                $vehicleQuery->whereRaw('LOWER(COALESCE(model, \'\')) = ?', [$model]);
            });
        }
    }

    private function followupTrackerFilterOptions(array $accessibleUserIds): array
    {
        return [
            'area_managers' => User::query()
                ->whereIn('id', $accessibleUserIds)
                ->where('role', User::ROLE_AREA_MANAGER)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn(User $user): array => ['id' => (int) $user->id, 'name' => $user->name])
                ->values()
                ->all(),
            'sales_consultants' => User::query()
                ->whereIn('id', $accessibleUserIds)
                ->where('role', User::ROLE_SALES_CONSULTANT)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn(User $user): array => ['id' => (int) $user->id, 'name' => $user->name])
                ->values()
                ->all(),
            'models' => Vehicle::query()
                ->whereNotNull('model')
                ->where('model', '<>', '')
                ->distinct()
                ->orderBy('model')
                ->pluck('model')
                ->values()
                ->all(),
        ];
    }

    private function mapFollowupTrackerRow(Enquiry $enquiry, ?string $sourceType): array
    {
        $customer = $enquiry->customer;
        $vehicle = $enquiry->vehicle;
        $owner = $enquiry->user;
        $mobiles = is_array($customer?->mobile_numbers) ? $customer->mobile_numbers : [];

        return [
            'id' => (int) $enquiry->id,
            'customer_name' => trim(($customer?->title ? $customer->title . '. ' : '') . ($customer?->name ?? 'Unknown')),
            'primary_phone' => count($mobiles) ? (string) $mobiles[0] : 'N/A',
            'vehicle_name' => trim(($vehicle?->model ?? '') . ' ' . ($vehicle?->variant ?? '')) ?: 'N/A',
            'model' => $vehicle?->model ?: 'N/A',
            'follow_type' => $this->normalizeFollowupType($sourceType) ?: ($sourceType ?: 'Not specified'),
            'follow_date' => $this->formatTrackerDate($enquiry->follow_date),
            'follow_time' => !empty($enquiry->follow_time) ? substr((string) $enquiry->follow_time, 0, 5) : '-',
            'attempted_at' => $this->formatTrackerDateTime($enquiry->followup_marked_at),
            'status' => match (strtolower(trim((string) $enquiry->followup_status))) {
                'done' => 'Done',
                'not_done' => 'Not Done',
                default => 'Pending',
            },
            'sales_consultant' => $owner?->name ?: 'Unassigned',
            'area_manager' => $owner?->manager?->name ?: 'Not assigned',
            'url' => route('followup.show', $enquiry->id),
        ];
    }

    private function mapFollowupAttemptTrackerRow(FollowupAttempt $attempt): array
    {
        $enquiry = $attempt->enquiry;
        $customer = $enquiry?->customer;
        $vehicle = $enquiry?->vehicle;
        $owner = $enquiry?->user;
        $mobiles = is_array($customer?->mobile_numbers) ? $customer->mobile_numbers : [];

        return [
            'id' => (int) $attempt->enquiry_id,
            'customer_name' => trim(($customer?->title ? $customer->title . '. ' : '') . ($customer?->name ?? 'Unknown')),
            'primary_phone' => count($mobiles) ? (string) $mobiles[0] : 'N/A',
            'vehicle_name' => trim(($vehicle?->model ?? '') . ' ' . ($vehicle?->variant ?? '')) ?: 'N/A',
            'model' => $vehicle?->model ?: 'N/A',
            'follow_type' => $this->normalizeFollowupType($attempt->follow_type) ?: ($attempt->follow_type ?: 'Not specified'),
            'follow_date' => $this->formatTrackerDate($enquiry?->follow_date),
            'follow_time' => !empty($enquiry?->follow_time) ? substr((string) $enquiry->follow_time, 0, 5) : '-',
            'attempted_at' => $this->formatTrackerDateTime($attempt->attempted_at),
            'status' => match (strtolower(trim((string) $attempt->followup_status))) {
                'done' => 'Done',
                'not_done' => 'Not Done',
                default => 'Pending',
            },
            'sales_consultant' => $owner?->name ?: 'Unassigned',
            'area_manager' => $owner?->manager?->name ?: 'Not assigned',
            'url' => route('followup.show', $attempt->enquiry_id),
        ];
    }

    private function formatTrackerDate($value): string
    {
        if (empty($value)) {
            return '-';
        }

        try {
            return Carbon::parse((string) $value)->format('d M Y');
        } catch (\Throwable $exception) {
            return '-';
        }
    }

    private function formatTrackerDateTime($value): string
    {
        if (empty($value)) {
            return '-';
        }

        try {
            return Carbon::parse((string) $value)->format('d M Y h:i A');
        } catch (\Throwable $exception) {
            return '-';
        }
    }

    private function resolveUserAndDescendantIds(User $root): array
    {
        $resolvedIds = [(int) $root->id];
        $frontier = [(int) $root->id];

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

    private function buildFollowupEscalations(User $viewer): array
    {
        $accessibleUserIds = $this->resolveAccessibleUserIds($viewer);
        $today = Carbon::now('Asia/Colombo')->startOfDay();
        $delayBuckets = [
            'today' => [
                'label' => 'Today',
                'description' => 'Followups due today.',
                'count' => 0,
            ],
            'one_day_delay' => [
                'label' => '1 Day Delay',
                'description' => 'Followups pending for 1 day.',
                'count' => 0,
            ],
            'two_day_delay' => [
                'label' => '2 Day Delay',
                'description' => 'Followups pending for 2 days.',
                'count' => 0,
            ],
            'three_day_delay' => [
                'label' => '3 Day Delay',
                'description' => 'Followups pending for 3 days.',
                'count' => 0,
            ],
            'over_three_day_delay' => [
                'label' => '>3 Days Delay',
                'description' => 'Followups pending for more than 3 days.',
                'count' => 0,
            ],
        ];

        $enquiries = Enquiry::query()
            ->with(['user.manager.manager'])
            ->select(['id', 'user_id', 'follow_type', 'follow_date', 'follow_time', 'followup_status'])
            ->whereIn('user_id', $accessibleUserIds)
            ->whereNotNull('follow_date')
            ->whereDate('follow_date', '<=', $today->toDateString())
            ->whereRaw("LOWER(COALESCE(followup_status, '')) <> ?", ['done'])
            ->orderBy('follow_date')
            ->get();

        $areaManagerRows = [];
        $salesConsultantRows = [];
        $typePendingRows = [
            'call' => [
                'name' => 'Call',
                'role' => 'Followup Type',
                'count' => 0,
                'max_pending_days' => 0,
                'oldest_follow_date' => null,
            ],
            'home_visit' => [
                'name' => 'Home Visit',
                'role' => 'Followup Type',
                'count' => 0,
                'max_pending_days' => 0,
                'oldest_follow_date' => null,
            ],
            'showroom_visit' => [
                'name' => 'Showroom Visit',
                'role' => 'Followup Type',
                'count' => 0,
                'max_pending_days' => 0,
                'oldest_follow_date' => null,
            ],
        ];
        $buckets = [
            'user' => [
                'title' => 'Notify User',
                'description' => 'Followups due today or pending for 1 day.',
                'rows' => [],
            ],
            'area_manager' => [
                'title' => 'Notify Area Manager',
                'description' => 'Followups pending for more than 1 day.',
                'rows' => [],
            ],
        ];

        foreach ($enquiries as $enquiry) {
            if (!$enquiry->user instanceof User) {
                continue;
            }

            try {
                $followDate = Carbon::parse((string) $enquiry->follow_date, 'Asia/Colombo')->startOfDay();
            } catch (\Throwable $exception) {
                continue;
            }

            $pendingDays = (int) $followDate->diffInDays($today, false);
            if ($pendingDays < 0) {
                continue;
            }

            $owner = $enquiry->user;
            $areaManager = $this->findHierarchyRecipient($owner, User::ROLE_AREA_MANAGER);
            $lead = [
                'id' => (int) $enquiry->id,
                'follow_date' => $followDate,
                'pending_days' => (int) $pendingDays,
            ];

            $delayBucketKey = match (true) {
                $pendingDays === 0 => 'today',
                $pendingDays === 1 => 'one_day_delay',
                $pendingDays === 2 => 'two_day_delay',
                $pendingDays === 3 => 'three_day_delay',
                default => 'over_three_day_delay',
            };
            $delayBuckets[$delayBucketKey]['count']++;

            $followupTypeKey = match ($this->normalizeFollowupType($enquiry->follow_type)) {
                'Call' => 'call',
                'Home visit' => 'home_visit',
                'Showroom visit' => 'showroom_visit',
                default => null,
            };

            if ($followupTypeKey !== null) {
                $this->addFollowupPendingAggregateRow(
                    $typePendingRows,
                    $followupTypeKey,
                    $typePendingRows[$followupTypeKey]['name'],
                    'Followup Type',
                    $lead
                );
            }

            $this->addFollowupPendingAggregateRow(
                $areaManagerRows,
                $areaManager instanceof User ? (string) $areaManager->id : 'unassigned',
                $areaManager instanceof User ? $areaManager->name : 'Unassigned Area Manager',
                $areaManager instanceof User ? $areaManager->role_label : 'Area Manager',
                $lead,
                [
                    'sales_consultants' => [],
                ],
                $owner->name
            );

            $this->addFollowupPendingAggregateRow(
                $salesConsultantRows,
                (string) $owner->id,
                $owner->name,
                $owner->role_label,
                $lead,
                [
                    'area_manager_name' => $areaManager instanceof User ? $areaManager->name : 'Not assigned',
                ]
            );

            if ($pendingDays <= 1) {
                $this->addFollowupEscalationRow($buckets['user']['rows'], $owner, $owner, $lead);
            } else {
                if ($areaManager instanceof User) {
                    $this->addFollowupEscalationRow($buckets['area_manager']['rows'], $owner, $areaManager, $lead);
                }
            }
        }

        foreach ($buckets as $bucketKey => $bucket) {
            $rows = array_values($bucket['rows']);
            usort($rows, function (array $left, array $right): int {
                if ($left['max_pending_days'] === $right['max_pending_days']) {
                    return strcmp($left['owner_name'], $right['owner_name']);
                }

                return $right['max_pending_days'] <=> $left['max_pending_days'];
            });

            $buckets[$bucketKey]['rows'] = array_map(function (array $row) use ($bucket): array {
                $row['oldest_follow_date_label'] = $row['oldest_follow_date'] instanceof Carbon
                    ? $row['oldest_follow_date']->format('d M Y')
                    : '-';
                $row['mailto_url'] = $this->buildFollowupNotifyMailto($row, $bucket['title']);
                $row['epr_url'] = route('enquiries.list');

                unset($row['oldest_follow_date']);

                return $row;
            }, $rows);
        }

        return [
            'generated_at' => $today->format('d M Y'),
            'summary' => array_values($delayBuckets),
            'type_pending_rows' => $this->formatFollowupPendingAggregateRows($typePendingRows, false),
            'area_manager_rows' => $this->formatFollowupPendingAggregateRows($areaManagerRows),
            'sales_consultant_rows' => $this->formatFollowupPendingAggregateRows($salesConsultantRows),
            'buckets' => $buckets,
            'total' => array_sum(array_map(fn(array $bucket): int => (int) $bucket['count'], $delayBuckets)),
        ];
    }

    private function addFollowupPendingAggregateRow(
        array &$rows,
        string $key,
        string $name,
        string $role,
        array $lead,
        array $extra = [],
        ?string $consultantName = null
    ): void {
        if (!isset($rows[$key])) {
            $rows[$key] = array_merge([
                'name' => $name,
                'role' => $role,
                'count' => 0,
                'max_pending_days' => 0,
                'oldest_follow_date' => null,
            ], $extra);
        }

        $rows[$key]['count']++;
        $rows[$key]['max_pending_days'] = max((int) $rows[$key]['max_pending_days'], (int) $lead['pending_days']);

        if (!$rows[$key]['oldest_follow_date'] instanceof Carbon
            || $lead['follow_date']->lessThan($rows[$key]['oldest_follow_date'])) {
            $rows[$key]['oldest_follow_date'] = $lead['follow_date']->copy();
        }

        if ($consultantName !== null && isset($rows[$key]['sales_consultants']) && is_array($rows[$key]['sales_consultants'])) {
            $rows[$key]['sales_consultants'][$consultantName] = true;
        }
    }

    private function formatFollowupPendingAggregateRows(array $rows, bool $sort = true): array
    {
        $rows = array_values($rows);
        if ($sort) {
            usort($rows, function (array $left, array $right): int {
                if ((int) $left['count'] === (int) $right['count']) {
                    return strcmp((string) $left['name'], (string) $right['name']);
                }

                return (int) $right['count'] <=> (int) $left['count'];
            });
        }

        return array_map(function (array $row): array {
            $row['oldest_follow_date_label'] = $row['oldest_follow_date'] instanceof Carbon
                ? $row['oldest_follow_date']->format('d M Y')
                : '-';

            if (isset($row['sales_consultants']) && is_array($row['sales_consultants'])) {
                $row['sales_consultants_count'] = count($row['sales_consultants']);
                $row['sales_consultants_label'] = implode(', ', array_keys($row['sales_consultants']));
            }

            unset($row['oldest_follow_date'], $row['sales_consultants']);

            return $row;
        }, $rows);
    }

    private function addFollowupEscalationRow(array &$rows, User $owner, User $recipient, array $lead): void
    {
        $key = (int) $recipient->id . ':' . (int) $owner->id;

        if (!isset($rows[$key])) {
            $rows[$key] = [
                'owner_id' => (int) $owner->id,
                'owner_name' => $owner->name,
                'owner_role' => $owner->role_label,
                'recipient_id' => (int) $recipient->id,
                'recipient_name' => $recipient->name,
                'recipient_role' => $recipient->role_label,
                'recipient_email' => (string) ($recipient->email ?? ''),
                'count' => 0,
                'max_pending_days' => 0,
                'oldest_follow_date' => null,
            ];
        }

        $rows[$key]['count']++;
        $rows[$key]['max_pending_days'] = max((int) $rows[$key]['max_pending_days'], (int) $lead['pending_days']);

        if (!$rows[$key]['oldest_follow_date'] instanceof Carbon
            || $lead['follow_date']->lessThan($rows[$key]['oldest_follow_date'])) {
            $rows[$key]['oldest_follow_date'] = $lead['follow_date']->copy();
        }
    }

    private function findHierarchyRecipient(User $owner, string $role): ?User
    {
        $owner->loadMissing('manager.manager.manager');

        $cursor = $owner;
        $safety = 0;
        while ($cursor instanceof User && $safety < 5) {
            if ($cursor->role === $role) {
                return $cursor;
            }

            $cursor = $cursor->manager;
            $safety++;
        }

        return null;
    }

    private function buildFollowupNotifyMailto(array $row, string $bucketTitle): ?string
    {
        $email = trim((string) ($row['recipient_email'] ?? ''));
        if ($email === '') {
            return null;
        }

        $count = (int) ($row['count'] ?? 0);
        $subject = 'CRM pending followup alert';
        $body = sprintf(
            "%s\n\n%s has %d pending lead followup%s. Oldest followup date: %s. Maximum pending days: %d.\n\nPlease review the EPR records and update the followup status.",
            $bucketTitle,
            (string) ($row['owner_name'] ?? 'Selected user'),
            $count,
            $count === 1 ? '' : 's',
            (string) ($row['oldest_follow_date_label'] ?? '-'),
            (int) ($row['max_pending_days'] ?? 0)
        );

        return 'mailto:' . rawurlencode($email) . '?' . http_build_query([
            'subject' => $subject,
            'body' => $body,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function downloadAnalyticsReport(Request $request)
    {
        $viewer = $request->user();
        $analytics = $this->buildAnalytics($viewer, $request);
        $fileName = 'analytics_report_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($analytics, $viewer): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['Ideal Motors CRM - Analytics Report']);
            fputcsv($handle, ['Generated At', now()->toDateTimeString()]);
            fputcsv($handle, ['Generated By', $viewer->name . ' (' . $viewer->role_label . ')']);
            fputcsv($handle, ['Scope', $analytics['scope_label'] . ' (' . $analytics['scope_user_count'] . ' users)']);
            fputcsv($handle, []);

            fputcsv($handle, ['Applied Filters']);
            $filters = $analytics['filters'] ?? [];
            $filterRows = [
                ['From Date', (string) ($filters['from_date'] ?? '')],
                ['To Date', (string) ($filters['to_date'] ?? '')],
                ['Owner Role', (string) ($filters['owner_role'] ?? '')],
                ['User', (string) ($analytics['selected_filter_user_name'] ?? '')],
                ['User Scope', (string) ($filters['user_scope'] ?? '')],
                ['District', (string) ($filters['district'] ?? '')],
                ['Lead Result', (string) ($filters['lead_result'] ?? '')],
                ['Lead Temperature', (string) ($filters['lead_temperature'] ?? '')],
                ['Followup Type', (string) ($filters['follow_type'] ?? '')],
                ['Followup Status', (string) ($filters['followup_status'] ?? '')],
            ];

            $hasAnyFilter = false;
            foreach ($filterRows as [$label, $value]) {
                if (trim($value) !== '') {
                    $hasAnyFilter = true;
                    fputcsv($handle, [$label, $value]);
                }
            }
            if (!$hasAnyFilter) {
                fputcsv($handle, ['Filter', 'No filters applied']);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['KPI', 'Value']);
            fputcsv($handle, ['Total Leads', (int) ($analytics['kpis']['total_leads'] ?? 0)]);
            fputcsv($handle, ['Active Leads', (int) ($analytics['kpis']['active_leads'] ?? 0)]);
            fputcsv($handle, ['Lost Leads', (int) ($analytics['kpis']['lost_leads'] ?? 0)]);
            fputcsv($handle, ['Closed Leads', (int) ($analytics['kpis']['closed_leads'] ?? 0)]);
            fputcsv($handle, ['Pending Followups', (int) ($analytics['kpis']['pending_followups'] ?? 0)]);
            fputcsv($handle, ['Done Followups', (int) ($analytics['kpis']['done_followups'] ?? 0)]);

            fputcsv($handle, []);
            fputcsv($handle, ['By Hierarchy (Role)']);
            fputcsv($handle, ['Role', 'Users', 'Leads']);
            foreach (($analytics['by_role'] ?? []) as $row) {
                fputcsv($handle, [
                    (string) ($row['label'] ?? ''),
                    (int) ($row['users'] ?? 0),
                    (int) ($row['leads'] ?? 0),
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['By User']);
            fputcsv($handle, ['User', 'Role', 'Manager', 'Total', 'Active', 'Lost', 'Closed', 'Hot', 'Warm', 'Cold', 'Pending', 'Done']);
            foreach (($analytics['by_user'] ?? []) as $row) {
                fputcsv($handle, [
                    (string) ($row['name'] ?? ''),
                    (string) ($row['role'] ?? ''),
                    (string) ($row['manager'] ?? ''),
                    (int) ($row['total'] ?? 0),
                    (int) ($row['active'] ?? 0),
                    (int) ($row['lost'] ?? 0),
                    (int) ($row['closed'] ?? 0),
                    (int) ($row['hot'] ?? 0),
                    (int) ($row['warm'] ?? 0),
                    (int) ($row['cold'] ?? 0),
                    (int) ($row['pending'] ?? 0),
                    (int) ($row['done'] ?? 0),
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function showConsultantTransferForm(Request $request): View
    {
        abort_unless($request->user()?->role === User::ROLE_SUPER_ADMIN, 403);

        $consultants = User::query()
            ->where('role', User::ROLE_SALES_CONSULTANT)
            ->with('manager:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'manager_id']);

        $selectedSourceConsultantIdInput = (string) $request->query('source_consultant_id', '');
        $selectedSourceConsultantId = ctype_digit($selectedSourceConsultantIdInput)
            ? (int) $selectedSourceConsultantIdInput
            : null;

        if ($selectedSourceConsultantId !== null && !$consultants->contains('id', $selectedSourceConsultantId)) {
            $selectedSourceConsultantId = null;
        }

        return view('dashboards.super-admin-consultant-transfer', [
            'consultants' => $consultants,
            'selectedSourceConsultantId' => $selectedSourceConsultantId,
            'leadResultOptions' => ['active' => 'Active', 'lost' => 'Lost', 'closed' => 'Closed'],
            'leadTemperatureOptions' => ['hot' => 'Hot', 'warm' => 'Warm', 'cold' => 'Cold'],
            'followTypeOptions' => ['Home visit', 'Showroom visit', 'Call'],
            'followupStatusOptions' => ['pending' => 'Pending', 'done' => 'Done'],
        ]);
    }

    public function transferConsultantData(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->role === User::ROLE_SUPER_ADMIN, 403);

        $validated = $request->validate([
            'source_consultant_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'target_consultant_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
            'lead_result' => ['nullable', Rule::in(['active', 'lost', 'closed'])],
            'lead_temperature' => ['nullable', Rule::in(['hot', 'warm', 'cold'])],
            'follow_type' => ['nullable', Rule::in(['Home visit', 'Showroom visit', 'Call'])],
            'followup_status' => ['nullable', Rule::in(['pending', 'done'])],
        ]);

        $sourceConsultant = User::query()->find((int) $validated['source_consultant_id']);
        $targetConsultant = User::query()->find((int) $validated['target_consultant_id']);

        if (!$sourceConsultant || $sourceConsultant->role !== User::ROLE_SALES_CONSULTANT) {
            return back()
                ->withErrors(['source_consultant_id' => 'Please select a valid Sales Consultant.'])
                ->withInput();
        }

        if (!$targetConsultant || $targetConsultant->role !== User::ROLE_SALES_CONSULTANT) {
            return back()
                ->withErrors(['target_consultant_id' => 'Please select a valid target Sales Consultant.'])
                ->withInput();
        }

        if ((int) $sourceConsultant->id === (int) $targetConsultant->id) {
            return back()
                ->withErrors(['target_consultant_id' => 'Source and target Sales Consultant must be different.'])
                ->withInput();
        }

        $fromDate = $this->parseFilterDate((string) ($validated['from_date'] ?? ''), true);
        $toDate = $this->parseFilterDate((string) ($validated['to_date'] ?? ''), false);
        if ($fromDate && $toDate && $fromDate->greaterThan($toDate)) {
            [$fromDate, $toDate] = [$toDate->copy()->startOfDay(), $fromDate->copy()->endOfDay()];
        }

        $enquiriesQuery = Enquiry::query()->where('user_id', (int) $sourceConsultant->id);

        if ($fromDate !== null) {
            $enquiriesQuery->where('created_at', '>=', $fromDate);
        }

        if ($toDate !== null) {
            $enquiriesQuery->where('created_at', '<=', $toDate);
        }

        if (!empty($validated['lead_result'])) {
            $enquiriesQuery->whereRaw('LOWER(COALESCE(followup_result, \'\')) = ?', [(string) $validated['lead_result']]);
        }

        if (!empty($validated['lead_temperature'])) {
            $enquiriesQuery->whereRaw('LOWER(COALESCE(followup_lead_temperature, \'\')) = ?', [(string) $validated['lead_temperature']]);
        }

        if (!empty($validated['follow_type'])) {
            $selectedFollowType = (string) $validated['follow_type'];
            if ($selectedFollowType === 'Home visit') {
                $enquiriesQuery->whereRaw('LOWER(COALESCE(follow_type, \'\')) LIKE ?', ['%home%']);
            } elseif ($selectedFollowType === 'Showroom visit') {
                $enquiriesQuery->whereRaw('LOWER(COALESCE(follow_type, \'\')) LIKE ?', ['%showroom%']);
            } elseif ($selectedFollowType === 'Call') {
                $enquiriesQuery->whereRaw('LOWER(COALESCE(follow_type, \'\')) LIKE ?', ['%call%']);
            }
        }

        if (!empty($validated['followup_status'])) {
            if ((string) $validated['followup_status'] === 'done') {
                $enquiriesQuery->whereRaw('LOWER(COALESCE(followup_status, \'\')) = ?', ['done']);
            } else {
                $enquiriesQuery->whereRaw('LOWER(COALESCE(followup_status, \'\')) <> ?', ['done']);
            }
        }

        $transferCount = (clone $enquiriesQuery)->count();
        if ($transferCount === 0) {
            return redirect()
                ->route('dashboard.super_admin.consultant_transfer.form', [
                    'source_consultant_id' => $sourceConsultant->id,
                ])
                ->with('success', 'No leads matched the selected filters. Nothing was transferred.');
        }

        $enquiriesQuery->update([
            'user_id' => (int) $targetConsultant->id,
        ]);

        return redirect()
            ->route('dashboard.super_admin.consultant_transfer.form', [
                'source_consultant_id' => $sourceConsultant->id,
            ])
            ->with('success', $transferCount . ' lead(s) transferred from ' . $sourceConsultant->name . ' to ' . $targetConsultant->name . '.');
    }

    public function editUser(Request $request, User $managedUser): View
    {
        abort_unless($request->user()?->role === User::ROLE_SUPER_ADMIN, 403);

        $managerOptions = User::query()
            ->where('id', '!=', $managedUser->id)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        return view('dashboards.super-admin-user-edit', [
            'managedUser' => $managedUser,
            'roles' => User::ROLE_HIERARCHY,
            'roleLabels' => User::ROLE_LABELS,
            'managerOptions' => $managerOptions,
            'parentRoleByRole' => collect(User::ROLE_HIERARCHY)
                ->mapWithKeys(fn(string $role): array => [$role => User::parentRoleFor($role)])
                ->all(),
        ]);
    }

    public function updateUser(Request $request, User $managedUser): RedirectResponse
    {
        abort_unless($request->user()?->role === User::ROLE_SUPER_ADMIN, 403);

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($managedUser->id)],
            'employee_number' => ['nullable', 'regex:/^M\d{5}$/', Rule::unique('users', 'employee_number')->ignore($managedUser->id)],
            'phone' => ['nullable', 'regex:/^0\d{9}$/'],
            'role' => ['required', Rule::in(User::ROLE_HIERARCHY)],
            'manager_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'phone.regex' => 'Phone number must start with 0 and contain exactly 10 digits.',
            'employee_number.regex' => 'Employee number must start with M followed by exactly 5 digits.',
        ]);

        $resolvedName = trim((string) ($validated['name'] ?? ''));
        if ($resolvedName === '') {
            $resolvedName = (string) $managedUser->name;
        }

        $resolvedEmail = trim((string) ($validated['email'] ?? ''));
        if ($resolvedEmail === '') {
            $resolvedEmail = (string) $managedUser->email;
        }

        $role = (string) $validated['role'];
        $parentRole = User::parentRoleFor($role);
        $managerId = $validated['manager_id'] ?? null;
        $manager = null;

        if ($managerId !== null && (int) $managerId === (int) $managedUser->id) {
            return back()
                ->withErrors(['manager_id' => 'A user cannot be their own manager.'])
                ->withInput();
        }

        if ($parentRole) {
            if (empty($managerId)) {
                return back()
                    ->withErrors(['manager_id' => 'Please select a valid ' . User::ROLE_LABELS[$parentRole] . '.'])
                    ->withInput();
            }

            $manager = User::query()->find((int) $managerId);
            if (!$manager || $manager->role !== $parentRole) {
                return back()
                    ->withErrors(['manager_id' => 'Please select a valid ' . User::ROLE_LABELS[$parentRole] . '.'])
                    ->withInput();
            }
        } else {
            $managerId = null;
        }

        if ((int) $managedUser->id === (int) $request->user()->id && $role !== User::ROLE_SUPER_ADMIN) {
            return back()
                ->withErrors(['role' => 'Super Admin cannot remove their own Super Admin role from this screen.'])
                ->withInput();
        }

        $payload = [
            'name' => $resolvedName,
            'email' => $resolvedEmail,
            'employee_number' => $validated['employee_number'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'role' => $role,
            'manager_id' => $managerId,
            'permitted_districts' => null,
        ];

        if (!empty($validated['password'])) {
            $payload['password'] = $validated['password'];
        }

        $managedUser->update($payload);

        return redirect()
            ->route('dashboard.super_admin')
            ->with('success', 'User details updated successfully for ' . $managedUser->name . '.');
    }

    private function roleCounts(): array
    {
        return [
            'head_of_sales' => User::query()->where('role', User::ROLE_HEAD_OF_SALES)->count(),
            'area_manager' => User::query()->where('role', User::ROLE_AREA_MANAGER)->count(),
            'sales_consultant' => User::query()->where('role', User::ROLE_SALES_CONSULTANT)->count(),
        ];
    }

    private function buildAnalytics(User $viewer, Request $request): array
    {
        $accessibleUserIds = $this->resolveAccessibleUserIds($viewer);
        $users = User::query()
            ->whereIn('id', $accessibleUserIds)
            ->orderBy('name')
            ->get(['id', 'name', 'role', 'manager_id']);
        $filterableUsers = $this->filterUsersForViewerHierarchy($viewer, $users);
        $filterableUserIds = $filterableUsers
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->values()
            ->all();

        $usersById = $users->keyBy('id');

        $enquiriesQuery = Enquiry::query()
            ->leftJoin('customers', 'customers.id', '=', 'enquiries.customer_id')
            ->leftJoin('vehicles', 'vehicles.id', '=', 'enquiries.vehicle_id')
            ->leftJoin('prospect_sheets', 'prospect_sheets.enquiry_id', '=', 'enquiries.id')
            ->leftJoin('bookings', 'bookings.enquiry_id', '=', 'enquiries.id')
            ->select([
                'enquiries.id',
                'enquiries.user_id',
                'enquiries.status as enquiry_status',
                'enquiries.lead_source',
                'enquiries.source_of_information as enquiry_source_of_information',
                'enquiries.followup_result',
                'enquiries.followup_lead_temperature',
                'enquiries.follow_type',
                'enquiries.follow_date',
                'enquiries.followup_status',
                'enquiries.followup_marked_at',
                'enquiries.followup_customer_comment',
                'enquiries.followup_lost_to',
                'enquiries.followup_lost_competition_brand',
                'enquiries.followup_lost_competition_model',
                'enquiries.followup_lost_codealer_name',
                'enquiries.followup_lost_reject_reasons',
                'enquiries.followup_lost_reject_other_text',
                'enquiries.created_at',
                'customers.title as customer_title',
                'customers.name as customer_name',
                'customers.mobile_numbers as customer_mobile_numbers',
                'customers.address1 as customer_address1',
                'customers.address2 as customer_address2',
                'customers.district as customer_district',
                'customers.location as customer_location',
                'customers.state as customer_state',
                'vehicles.model as vehicle_model',
                'vehicles.engine_type as vehicle_engine_type',
                'vehicles.variant as vehicle_variant',
                'prospect_sheets.lead_status as prospect_lead_status',
                'prospect_sheets.current_step as prospect_current_step',
                'prospect_sheets.customer_type as prospect_customer_type',
                'prospect_sheets.corporate_name as prospect_corporate_name',
                'prospect_sheets.profession as prospect_profession',
                'prospect_sheets.date_of_birth as prospect_date_of_birth',
                'prospect_sheets.interested_vehicle_color as prospect_interested_vehicle_color',
                'prospect_sheets.quote_taken as prospect_quote_taken',
                'prospect_sheets.quote_date as prospect_quote_date',
                'prospect_sheets.test_drive_given as prospect_test_drive_given',
                'prospect_sheets.test_drive_date as prospect_test_drive_date',
                'prospect_sheets.test_drive_not_given_reason as prospect_test_drive_not_given_reason',
                'prospect_sheets.purchase_mode as prospect_purchase_mode',
                'prospect_sheets.interested_in_competition as prospect_interested_in_competition',
                'prospect_sheets.competition_brand as prospect_competition_brand',
                'prospect_sheets.competition_model as prospect_competition_model',
                'prospect_sheets.first_time_buyer as prospect_first_time_buyer',
                'prospect_sheets.interested_in_exchange as prospect_interested_in_exchange',
                'prospect_sheets.exchange_vehicle_brand as prospect_exchange_vehicle_brand',
                'prospect_sheets.exchange_vehicle_model as prospect_exchange_vehicle_model',
                'prospect_sheets.exchange_manufacture_year as prospect_exchange_manufacture_year',
                'prospect_sheets.exchange_color as prospect_exchange_color',
                'prospect_sheets.exchange_mileage_km as prospect_exchange_mileage_km',
                'prospect_sheets.exchange_registration_no as prospect_exchange_registration_no',
                'prospect_sheets.exchange_expected_price as prospect_exchange_expected_price',
                'prospect_sheets.exchange_quoted_price as prospect_exchange_quoted_price',
                'prospect_sheets.exchange_price_difference as prospect_exchange_price_difference',
                'prospect_sheets.offer_unit_price as prospect_offer_unit_price',
                'prospect_sheets.offer_unit_price_discount as prospect_offer_unit_price_discount',
                'prospect_sheets.offer_vat_amount as prospect_offer_vat_amount',
                'prospect_sheets.offer_vat_discount as prospect_offer_vat_discount',
                'prospect_sheets.offer_total_cost as prospect_offer_total_cost',
                'prospect_sheets.offer_total_discount as prospect_offer_total_discount',
                'prospect_sheets.offer_final_price as prospect_offer_final_price',
                'prospect_sheets.source_of_information as prospect_source_of_information',
                'bookings.id as booking_id',
                'bookings.created_at as booking_created_at',
                'bookings.interested_model as booking_interested_model',
            ]);

        if ($viewer->role === User::ROLE_SALES_CONSULTANT) {
            $enquiriesQuery->where('user_id', (int) $viewer->id);
        } elseif ($viewer->role !== User::ROLE_SUPER_ADMIN) {
            $enquiriesQuery->whereIn('user_id', $accessibleUserIds);
        }

        $selectedUserIdInput = (string) $request->query('user_id', '');
        $selectedUserId = ctype_digit($selectedUserIdInput) ? (int) $selectedUserIdInput : null;
        if ($selectedUserId !== null && !in_array($selectedUserId, $filterableUserIds, true)) {
            $selectedUserId = null;
        }
        $selectedUserScopeInput = strtolower(trim((string) $request->query('user_scope', 'hierarchy')));
        $selectedUserScope = in_array($selectedUserScopeInput, ['hierarchy', 'self'], true)
            ? $selectedUserScopeInput
            : 'hierarchy';
        $forceHierarchyScope = in_array($viewer->role, [
            User::ROLE_HEAD_OF_SALES,
            User::ROLE_AREA_MANAGER,
        ], true);
        if ($forceHierarchyScope) {
            $selectedUserScope = 'hierarchy';
        }

        $allowedOwnerRoles = array_values(array_filter(
            User::ROLE_HIERARCHY,
            fn(string $role): bool => $role !== User::ROLE_SUPER_ADMIN
                && $filterableUsers->contains(fn(User $user): bool => $user->role === $role)
        ));
        $selectedOwnerRoleInput = strtolower(trim((string) $request->query('owner_role', '')));
        $selectedOwnerRole = null;
        if ($selectedOwnerRoleInput === 'unassigned' && $viewer->role === User::ROLE_SUPER_ADMIN) {
            $selectedOwnerRole = 'unassigned';
        } elseif (in_array($selectedOwnerRoleInput, $allowedOwnerRoles, true)) {
            $selectedOwnerRole = $selectedOwnerRoleInput;
        }

        $selectedFilterUser = $selectedUserId !== null
            ? $filterableUsers->firstWhere('id', $selectedUserId)
            : null;

        if ($selectedOwnerRole === 'unassigned') {
            $selectedUserId = null;
            $selectedFilterUser = null;
        } elseif ($selectedOwnerRole !== null && $selectedFilterUser instanceof User && $selectedFilterUser->role !== $selectedOwnerRole) {
            $selectedUserId = null;
            $selectedFilterUser = null;
        }

        $selectedLeadResult = $this->normalizeLeadResult($request->query('lead_result'));
        $selectedLeadTemperature = $this->normalizeLeadTemperature($request->query('lead_temperature'));
        $selectedFollowType = $this->normalizeFollowupType($request->query('follow_type'));

        $selectedFollowupStatusInput = strtolower(trim((string) $request->query('followup_status', '')));
        $selectedFollowupStatus = in_array($selectedFollowupStatusInput, ['done', 'pending'], true)
            ? $selectedFollowupStatusInput
            : null;

        $fromDate = $this->parseFilterDate((string) $request->query('from_date', ''), true);
        $toDate = $this->parseFilterDate((string) $request->query('to_date', ''), false);
        if ($fromDate && $toDate && $fromDate->greaterThan($toDate)) {
            [$fromDate, $toDate] = [$toDate->copy()->startOfDay(), $fromDate->copy()->endOfDay()];
        }

        $selectedFilterUserName = $selectedFilterUser instanceof User ? $selectedFilterUser->name : null;
        $selectedHierarchyUserIds = null;

        if ($selectedUserId !== null) {
            if ($selectedFilterUser instanceof User && $selectedUserScope === 'hierarchy') {
                $selectedHierarchyUserIds = array_values(array_intersect(
                    $accessibleUserIds,
                    $this->resolveAccessibleUserIds($selectedFilterUser)
                ));
            } else {
                $selectedHierarchyUserIds = [$selectedUserId];
            }

            if (empty($selectedHierarchyUserIds)) {
                $enquiriesQuery->whereRaw('1 = 0');
            } else {
                $enquiriesQuery->whereIn('user_id', $selectedHierarchyUserIds);
            }
        }

        if ($selectedOwnerRole === 'unassigned') {
            $enquiriesQuery->whereNull('user_id');
        } elseif ($selectedOwnerRole !== null) {
            $roleUserIds = $users->where('role', $selectedOwnerRole)
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->values()
                ->all();

            if (empty($roleUserIds)) {
                $enquiriesQuery->whereRaw('1 = 0');
            } else {
                $enquiriesQuery->whereIn('user_id', $roleUserIds);
            }
        }

        if ($selectedLeadResult !== null) {
            $enquiriesQuery->whereRaw('LOWER(COALESCE(followup_result, \'\')) = ?', [$selectedLeadResult]);
        }

        if ($selectedLeadTemperature !== null) {
            $enquiriesQuery->whereRaw('LOWER(COALESCE(followup_lead_temperature, \'\')) = ?', [$selectedLeadTemperature]);
        }

        if ($selectedFollowType !== null) {
            if ($selectedFollowType === 'Home visit') {
                $enquiriesQuery->whereRaw('LOWER(COALESCE(follow_type, \'\')) LIKE ?', ['%home%']);
            } elseif ($selectedFollowType === 'Showroom visit') {
                $enquiriesQuery->whereRaw('LOWER(COALESCE(follow_type, \'\')) LIKE ?', ['%showroom%']);
            } elseif ($selectedFollowType === 'Call') {
                $enquiriesQuery->whereRaw('LOWER(COALESCE(follow_type, \'\')) LIKE ?', ['%call%']);
            }
        }

        if ($selectedFollowupStatus === 'done') {
            $enquiriesQuery->whereRaw('LOWER(COALESCE(followup_status, \'\')) = ?', ['done']);
        } elseif ($selectedFollowupStatus === 'pending') {
            $enquiriesQuery->whereRaw('LOWER(COALESCE(followup_status, \'\')) <> ?', ['done']);
        }

        if ($fromDate !== null) {
            $enquiriesQuery->where('enquiries.created_at', '>=', $fromDate);
        }

        if ($toDate !== null) {
            $enquiriesQuery->where('enquiries.created_at', '<=', $toDate);
        }

        $toDistrictLabel = static function ($value): string {
            $district = trim((string) $value);
            if ($district === '') {
                return 'N/A';
            }

            if (strcasecmp($district, 'na') === 0 || strcasecmp($district, 'n/a') === 0) {
                return 'N/A';
            }

            return ucwords(strtolower($district));
        };

        $districtOptions = (clone $enquiriesQuery)
            ->select('customers.district')
            ->distinct()
            ->pluck('customers.district')
            ->map(fn($district): string => $toDistrictLabel($district))
            ->unique()
            ->sort()
            ->values()
            ->all();

        $selectedDistrictRaw = trim((string) $request->query('district', ''));
        $selectedDistrict = $selectedDistrictRaw !== ''
            ? $toDistrictLabel($selectedDistrictRaw)
            : null;

        if ($selectedDistrict !== null) {
            if ($selectedDistrict === 'N/A') {
                $enquiriesQuery->where(function ($query): void {
                    $query->whereNull('customers.district')
                        ->orWhereRaw('TRIM(customers.district) = \'\'')
                        ->orWhereRaw('LOWER(TRIM(customers.district)) IN (\'na\', \'n/a\')');
                });
            } else {
                $enquiriesQuery->whereRaw('LOWER(TRIM(COALESCE(customers.district, \'\'))) = ?', [
                    strtolower($selectedDistrict),
                ]);
            }
        }

        $enquiries = $enquiriesQuery->get();

        $leadResults = [
            'active' => 0,
            'lost' => 0,
            'closed' => 0,
        ];

        $leadTemperatures = [
            'hot' => 0,
            'warm' => 0,
            'cold' => 0,
        ];

        $followupByType = [
            'Home visit' => ['done' => 0, 'pending' => 0],
            'Showroom visit' => ['done' => 0, 'pending' => 0],
            'Call' => ['done' => 0, 'pending' => 0],
        ];

        $userStats = [];
        foreach ($users as $user) {
            $userStats[(string) $user->id] = $this->emptyAnalyticsStats();
        }

        $doneFollowups = 0;
        $pendingFollowups = 0;
        $districtTotals = [];

        foreach ($enquiries as $enquiry) {
            $ownerKey = $enquiry->user_id === null ? 'unassigned' : (string) ((int) $enquiry->user_id);
            if (!array_key_exists($ownerKey, $userStats)) {
                $userStats[$ownerKey] = $this->emptyAnalyticsStats();
            }

            $districtLabel = $toDistrictLabel($enquiry->customer_district ?? null);
            if (!array_key_exists($districtLabel, $districtTotals)) {
                $districtTotals[$districtLabel] = 0;
            }
            $districtTotals[$districtLabel]++;

            $userStats[$ownerKey]['total']++;
            $status = strtolower(trim((string) $enquiry->followup_status));
            $isDone = $status === 'done';

            if ($isDone) {
                $doneFollowups++;
                $userStats[$ownerKey]['done']++;
            } else {
                $pendingFollowups++;
                $userStats[$ownerKey]['pending']++;
            }

            $leadResult = $this->normalizeLeadResult($enquiry->followup_result);
            if ($leadResult !== null) {
                $leadResults[$leadResult]++;
                $userStats[$ownerKey][$leadResult]++;
            }

            $temperature = $this->normalizeLeadTemperature($enquiry->followup_lead_temperature);
            if ($temperature !== null) {
                $leadTemperatures[$temperature]++;
                $userStats[$ownerKey][$temperature]++;
            }

            $followupType = $this->normalizeFollowupType($enquiry->follow_type);
            if ($followupType !== null) {
                $followupByType[$followupType][$isDone ? 'done' : 'pending']++;
            }
        }

        $lostAnalytics = $this->buildLostAnalytics($enquiries, $usersById);
        $closedAnalytics = $this->buildClosedAnalytics($enquiries, $usersById);
        $activeAnalytics = $this->buildActiveAnalytics($enquiries, $usersById);
        $bookingAnalytics = $this->buildBookingAnalytics($enquiries, $usersById);

        $byUser = [];
        foreach ($users as $user) {
            $stats = $userStats[(string) $user->id] ?? $this->emptyAnalyticsStats();
            $managerName = null;
            if (!empty($user->manager_id) && $usersById->has((int) $user->manager_id)) {
                $managerName = $usersById->get((int) $user->manager_id)?->name;
            }

            $byUser[] = [
                'name' => $user->name,
                'role' => User::ROLE_LABELS[$user->role] ?? ucwords(str_replace('_', ' ', (string) $user->role)),
                'manager' => $managerName ?: '-',
                'total' => $stats['total'],
                'active' => $stats['active'],
                'lost' => $stats['lost'],
                'closed' => $stats['closed'],
                'hot' => $stats['hot'],
                'warm' => $stats['warm'],
                'cold' => $stats['cold'],
                'pending' => $stats['pending'],
                'done' => $stats['done'],
            ];
        }

        if ($viewer->role !== User::ROLE_SALES_CONSULTANT && ($userStats['unassigned']['total'] ?? 0) > 0) {
            $stats = $userStats['unassigned'];
            $byUser[] = [
                'name' => 'Unassigned',
                'role' => 'Unassigned',
                'manager' => '-',
                'total' => $stats['total'],
                'active' => $stats['active'],
                'lost' => $stats['lost'],
                'closed' => $stats['closed'],
                'hot' => $stats['hot'],
                'warm' => $stats['warm'],
                'cold' => $stats['cold'],
                'pending' => $stats['pending'],
                'done' => $stats['done'],
            ];
        }

        usort($byUser, function (array $left, array $right): int {
            if ($left['total'] === $right['total']) {
                return strcmp($left['name'], $right['name']);
            }

            return $right['total'] <=> $left['total'];
        });

        $hasActiveFilters = $selectedUserId !== null
            || $selectedOwnerRole !== null
            || $selectedDistrict !== null
            || $selectedLeadResult !== null
            || $selectedLeadTemperature !== null
            || $selectedFollowType !== null
            || $selectedFollowupStatus !== null
            || $fromDate !== null
            || $toDate !== null;

        if ($hasActiveFilters) {
            $byUser = array_values(array_filter($byUser, fn(array $row): bool => $row['total'] > 0));
        }

        $byRole = [];
        foreach (User::ROLE_HIERARCHY as $role) {
            $byRole[$role] = [
                'label' => User::ROLE_LABELS[$role] ?? ucwords(str_replace('_', ' ', $role)),
                'users' => 0,
                'leads' => 0,
            ];
        }

        foreach ($users as $user) {
            $role = (string) $user->role;
            if (!array_key_exists($role, $byRole)) {
                $byRole[$role] = [
                    'label' => ucwords(str_replace('_', ' ', $role)),
                    'users' => 0,
                    'leads' => 0,
                ];
            }

            $byRole[$role]['users']++;
            $byRole[$role]['leads'] += $userStats[(string) $user->id]['total'] ?? 0;
        }

        $byRoleRows = array_values(array_filter($byRole, function (array $row): bool {
            return $row['users'] > 0 || $row['leads'] > 0;
        }));

        $viewer->loadMissing('manager.manager');
        $currentHierarchy = [];
        $cursor = $viewer;
        $safety = 0;

        while ($cursor !== null && $safety < 8) {
            $currentHierarchy[] = [
                'role' => $cursor->role_label,
                'name' => $cursor->name,
            ];
            $cursor = $cursor->manager;
            $safety++;
        }
        $currentHierarchy = array_reverse($currentHierarchy);

        $sortedByUser = array_values(array_filter($byUser, fn(array $row): bool => $row['total'] > 0));
        $topUsers = array_slice($sortedByUser, 0, 10);
        arsort($districtTotals);
        $districtRows = [];
        $provinceTotals = [];
        foreach ($districtTotals as $district => $total) {
            $districtRows[] = [
                'district' => (string) $district,
                'leads' => (int) $total,
            ];

            $province = User::provinceForDistrict((string) $district) ?? 'N/A';
            if (!array_key_exists($province, $provinceTotals)) {
                $provinceTotals[$province] = 0;
            }
            $provinceTotals[$province] += (int) $total;
        }

        arsort($provinceTotals);
        $provinceRows = [];
        foreach ($provinceTotals as $province => $total) {
            $provinceRows[] = [
                'province' => (string) $province,
                'leads' => (int) $total,
            ];
        }

        return [
            'scope_label' => $viewer->role === User::ROLE_SUPER_ADMIN
                ? 'All users in the organization'
                : 'Your leads and your reporting hierarchy',
            'scope_user_count' => count($accessibleUserIds),
            'has_active_filters' => $hasActiveFilters,
            'filters' => [
                'from_date' => $fromDate ? $fromDate->toDateString() : '',
                'to_date' => $toDate ? $toDate->toDateString() : '',
                'user_id' => $selectedUserId !== null ? (string) $selectedUserId : '',
                'user_scope' => $selectedUserScope,
                'owner_role' => $selectedOwnerRole ?? '',
                'district' => $selectedDistrict ?? '',
                'lead_result' => $selectedLeadResult ?? '',
                'lead_temperature' => $selectedLeadTemperature ?? '',
                'follow_type' => $selectedFollowType ?? '',
                'followup_status' => $selectedFollowupStatus ?? '',
            ],
            'filter_options' => [
                'users' => $filterableUsers->map(function (User $user): array {
                    return [
                        'id' => (int) $user->id,
                        'name' => $user->name,
                        'role' => $user->role_label,
                        'role_key' => $user->role,
                    ];
                })->values()->all(),
                'user_scopes' => $forceHierarchyScope
                    ? [
                        ['value' => 'hierarchy', 'label' => 'Selected user + hierarchy'],
                    ]
                    : [
                        ['value' => 'hierarchy', 'label' => 'Selected user + hierarchy'],
                        ['value' => 'self', 'label' => 'Selected user only'],
                    ],
                'owner_roles' => array_values(array_map(
                    fn(string $role): array => ['value' => $role, 'label' => User::ROLE_LABELS[$role] ?? ucwords(str_replace('_', ' ', $role))],
                    $allowedOwnerRoles
                )),
                'districts' => array_map(
                    fn(string $district): array => ['value' => $district, 'label' => $district],
                    $districtOptions
                ),
                'lead_results' => [
                    ['value' => 'active', 'label' => 'Active'],
                    ['value' => 'lost', 'label' => 'Lost'],
                    ['value' => 'closed', 'label' => 'Closed'],
                ],
                'lead_temperatures' => [
                    ['value' => 'hot', 'label' => 'Hot'],
                    ['value' => 'warm', 'label' => 'Warm'],
                    ['value' => 'cold', 'label' => 'Cold'],
                ],
                'follow_types' => [
                    ['value' => 'Home visit', 'label' => 'Home visit'],
                    ['value' => 'Showroom visit', 'label' => 'Showroom visit'],
                    ['value' => 'Call', 'label' => 'Call'],
                ],
                'followup_statuses' => [
                    ['value' => 'pending', 'label' => 'Pending'],
                    ['value' => 'done', 'label' => 'Done'],
                ],
            ],
            'selected_filter_user_name' => $selectedFilterUserName,
            'selected_hierarchy_count' => is_array($selectedHierarchyUserIds) ? count($selectedHierarchyUserIds) : null,
            'kpis' => [
                'total_leads' => $enquiries->count(),
                'active_leads' => $leadResults['active'],
                'lost_leads' => $leadResults['lost'],
                'closed_leads' => $leadResults['closed'],
                'pending_followups' => $pendingFollowups,
                'done_followups' => $doneFollowups,
            ],
            'charts' => [
                'lead_results' => [
                    'labels' => ['Active', 'Lost', 'Closed'],
                    'values' => [
                        $leadResults['active'],
                        $leadResults['lost'],
                        $leadResults['closed'],
                    ],
                ],
                'lead_temperature' => [
                    'labels' => ['Hot', 'Warm', 'Cold'],
                    'values' => [
                        $leadTemperatures['hot'],
                        $leadTemperatures['warm'],
                        $leadTemperatures['cold'],
                    ],
                ],
                'followup_totals' => [
                    'labels' => ['Home visit', 'Showroom visit', 'Call'],
                    'values' => [
                        $followupByType['Home visit']['done'] + $followupByType['Home visit']['pending'],
                        $followupByType['Showroom visit']['done'] + $followupByType['Showroom visit']['pending'],
                        $followupByType['Call']['done'] + $followupByType['Call']['pending'],
                    ],
                ],
                'followup_by_status' => [
                    'labels' => ['Home visit', 'Showroom visit', 'Call'],
                    'done' => [
                        $followupByType['Home visit']['done'],
                        $followupByType['Showroom visit']['done'],
                        $followupByType['Call']['done'],
                    ],
                    'pending' => [
                        $followupByType['Home visit']['pending'],
                        $followupByType['Showroom visit']['pending'],
                        $followupByType['Call']['pending'],
                    ],
                ],
                'user_totals' => [
                    'labels' => array_map(fn(array $row): string => $row['name'], $topUsers),
                    'values' => array_map(fn(array $row): int => $row['total'], $topUsers),
                ],
                'hierarchy_totals' => [
                    'labels' => array_map(fn(array $row): string => $row['label'], $byRoleRows),
                    'values' => array_map(fn(array $row): int => (int) $row['leads'], $byRoleRows),
                ],
                'district_totals' => [
                    'labels' => array_map(fn(array $row): string => $row['district'], $districtRows),
                    'values' => array_map(fn(array $row): int => (int) $row['leads'], $districtRows),
                ],
                'province_totals' => [
                    'labels' => array_map(fn(array $row): string => $row['province'], $provinceRows),
                    'values' => array_map(fn(array $row): int => (int) $row['leads'], $provinceRows),
                ],
            ],
            'lost_analytics' => $lostAnalytics,
            'closed_analytics' => $closedAnalytics,
            'active_analytics' => $activeAnalytics,
            'booking_analytics' => $bookingAnalytics,
            'by_user' => $byUser,
            'by_role' => $byRoleRows,
            'by_district' => $districtRows,
            'by_province' => $provinceRows,
            'current_hierarchy' => $currentHierarchy,
        ];
    }

    private function buildActiveAnalytics(Collection $enquiries, Collection $usersById): array
    {
        $activeRows = $enquiries
            ->filter(fn(Enquiry $enquiry): bool => $this->normalizeLeadResult($enquiry->followup_result) === 'active')
            ->values();
        $totalActive = $activeRows->count();

        $groups = [
            'registration' => [],
            'month' => [],
            'model' => [],
            'lead_source' => [],
            'source_information' => [],
            'sales_consultant' => [],
            'area_manager' => [],
            'lead_state' => [],
            'age_group' => [],
            'old_vehicle_brand' => [],
            'old_vehicle_model' => [],
            'color' => [],
            'competition_model' => [],
            'exchange_value_difference' => [],
            'no_test_drive_reason' => [],
            'last_followup_discipline' => [],
            'delayed_followup_count' => [],
            'dealer_today_followups' => [],
            'dealer_delayed_followups' => [],
            'sc_today_followups' => [],
            'sc_delayed_followups' => [],
            'successful_home_visit_count' => [],
            'active_aging' => [],
            'quote_given' => [],
            'call_followup_count' => [],
            'successful_call_followup_count' => [],
            'call_status' => [],
            'time_since_last_spoken' => [],
            'city' => [],
            'ro' => [],
        ];
        $monthOrder = [];
        $today = Carbon::now('Asia/Colombo')->startOfDay();
        $activeEnquiryIds = $activeRows
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->filter()
            ->values()
            ->all();
        $attemptsByEnquiryId = empty($activeEnquiryIds)
            ? collect()
            : FollowupAttempt::query()
                ->whereIn('enquiry_id', $activeEnquiryIds)
                ->orderBy('attempted_at')
                ->get(['enquiry_id', 'follow_type', 'followup_status', 'attempted_at'])
                ->groupBy(fn(FollowupAttempt $attempt): int => (int) $attempt->enquiry_id);

        foreach ($activeRows as $enquiry) {
            $createdAt = $this->analyticsDate($enquiry->created_at);
            $monthKey = $createdAt->format('Y-m');
            $monthOrder[$monthKey] = $createdAt->format('M Y');

            $this->addActiveAggregate(
                $groups['registration'],
                $this->isRegisteredAnalyticsLead($enquiry) ? 'REGISTERED' : 'EPR'
            );
            $this->addActiveAggregate($groups['month'], $monthKey);
            $this->addActiveAggregate($groups['model'], $this->displayAnalyticsLabel($enquiry->vehicle_model, 'Not specified'));
            $this->addActiveAggregate($groups['lead_source'], $this->displayAnalyticsLabel($enquiry->lead_source, 'Not specified'));
            $this->addActiveAggregate(
                $groups['source_information'],
                $this->displayAnalyticsLabel($enquiry->prospect_source_of_information ?: $enquiry->enquiry_source_of_information, 'Not specified')
            );
            $this->addActiveAggregate(
                $groups['lead_state'],
                $this->displayAnalyticsLabel($enquiry->prospect_lead_status ?: $enquiry->followup_lead_temperature, 'Not specified')
            );

            $owner = $enquiry->user_id ? $usersById->get((int) $enquiry->user_id) : null;
            $this->addActiveAggregate($groups['sales_consultant'], $owner instanceof User ? $owner->name : 'Unassigned');
            $areaManager = $this->resolveAreaManagerForAnalytics($owner, $usersById);
            $this->addActiveAggregate($groups['area_manager'], $areaManager instanceof User ? $areaManager->name : 'Unassigned');
            $headOfSales = $areaManager instanceof User ? $this->findHierarchyRecipient($areaManager, User::ROLE_HEAD_OF_SALES) : null;

            $attempts = $attemptsByEnquiryId->get((int) $enquiry->id, collect());
            $callAttempts = $attempts->filter(fn(FollowupAttempt $attempt): bool => $this->normalizeFollowupType($attempt->follow_type) === 'Call');
            $successfulCallAttemptCount = $callAttempts
                ->filter(fn(FollowupAttempt $attempt): bool => strtolower(trim((string) $attempt->followup_status)) === 'done')
                ->count();
            $successfulHomeVisitCount = $attempts
                ->filter(fn(FollowupAttempt $attempt): bool => $this->normalizeFollowupType($attempt->follow_type) === 'Home visit'
                    && strtolower(trim((string) $attempt->followup_status)) === 'done')
                ->count();

            $this->addActiveAggregate($groups['age_group'], $this->ageGroupBucket($enquiry->prospect_date_of_birth ?? null));
            $this->addActiveCompositeAggregate($groups['old_vehicle_brand'], $this->displayAnalyticsLabel($enquiry->prospect_exchange_vehicle_brand ?? null, 'NA'), $this->displayAnalyticsLabel($enquiry->prospect_exchange_manufacture_year ?? null, 'NA'));
            $this->addActiveCompositeAggregate($groups['old_vehicle_model'], $this->displayAnalyticsLabel($enquiry->prospect_exchange_vehicle_model ?? null, 'NA'), $this->displayAnalyticsLabel($enquiry->prospect_exchange_manufacture_year ?? null, 'NA'));
            $this->addActiveAggregate($groups['color'], $this->displayAnalyticsLabel($enquiry->prospect_interested_vehicle_color ?? null, 'NA'));
            $this->addActiveAggregate($groups['competition_model'], $this->displayAnalyticsLabel($enquiry->prospect_competition_model ?? null, 'NA'));
            $this->addActiveAggregate($groups['exchange_value_difference'], $this->exchangeDifferenceBucket($enquiry->prospect_exchange_price_difference ?? null));
            $this->addActiveAggregate($groups['no_test_drive_reason'], $this->displayAnalyticsLabel($enquiry->prospect_test_drive_not_given_reason ?? null, 'NA'));
            $this->addActiveAggregate($groups['last_followup_discipline'], $this->lastFollowupDisciplineBucket($enquiry, $attempts));
            $this->addActiveAggregate($groups['delayed_followup_count'], $this->delayedFollowupCountBucket($enquiry, $attempts));
            $this->addActiveAggregate($groups['successful_home_visit_count'], $this->visitCountBucket($successfulHomeVisitCount));
            $this->addActiveAggregate($groups['active_aging'], $this->activeAgingBucket($enquiry));
            $this->addActiveAggregate($groups['quote_given'], $this->yesNoNaLabel($enquiry->prospect_quote_taken ?? null));
            $this->addActiveAggregate($groups['call_followup_count'], $this->followupCountBucket($callAttempts->count()));
            $this->addActiveAggregate($groups['successful_call_followup_count'], $this->followupCountBucket($successfulCallAttemptCount, true));
            $this->addActiveAggregate($groups['time_since_last_spoken'], $this->activeTimeSinceLastSpokenBucket($attempts));
            $this->addActiveAggregate($groups['city'], $this->displayAnalyticsLabel($enquiry->customer_location ?: $enquiry->customer_district, 'NA'));
            $this->addActiveAggregate($groups['ro'], $headOfSales?->name ?? 'Sales Head');

            foreach ($callAttempts as $callAttempt) {
                $this->addActiveAggregate(
                    $groups['call_status'],
                    strtolower(trim((string) $callAttempt->followup_status)) === 'done' ? 'Spoke To Customer' : 'Busy'
                );
            }

            if (!empty($enquiry->follow_date)) {
                try {
                    $followDate = Carbon::parse((string) $enquiry->follow_date)->startOfDay();
                    $isDone = strtolower(trim((string) $enquiry->followup_status)) === 'done';
                    if ($followDate->equalTo($today)) {
                        $this->addActiveAggregate($groups['dealer_today_followups'], $areaManager instanceof User ? $areaManager->name : 'Unassigned');
                        $this->addActiveAggregate($groups['sc_today_followups'], $owner instanceof User ? $owner->name : 'Unassigned');
                    }
                    if (!$isDone && $followDate->lessThan($today)) {
                        $this->addActiveAggregate($groups['dealer_delayed_followups'], $areaManager instanceof User ? $areaManager->name : 'Unassigned');
                        $this->addActiveAggregate($groups['sc_delayed_followups'], $owner instanceof User ? $owner->name : 'Unassigned');
                    }
                } catch (\Throwable $exception) {
                    // Ignore invalid dates for date-bound active follow-up reports.
                }
            }
        }

        $chartTabs = [
            ['key' => 'registration', 'label' => 'EPR Vs Registered', 'title' => 'EPR Vs Registered', 'metric' => 'count', 'rows' => $this->formatActiveRegistrationRows($groups['registration'], $totalActive)],
            ['key' => 'month', 'label' => 'Month Wise', 'title' => 'Month Wise', 'metric' => 'count', 'rows' => $this->formatActiveMonthRows($groups['month'], $monthOrder, $totalActive)],
            ['key' => 'model', 'label' => 'Model Wise', 'title' => 'Model Wise', 'metric' => 'count', 'rows' => $this->formatActiveAggregateRows($groups['model'], $totalActive, 12)],
            ['key' => 'lead_source', 'label' => 'Lead Source Wise', 'title' => 'Lead Source Wise', 'metric' => 'count', 'rows' => $this->formatActiveAggregateRows($groups['lead_source'], $totalActive, 12)],
            ['key' => 'source_information', 'label' => 'Source Of Information Wise', 'title' => 'Source Of Information Wise', 'metric' => 'count', 'rows' => $this->formatActiveAggregateRows($groups['source_information'], $totalActive, 12)],
            ['key' => 'sales_consultant', 'label' => 'Sales Consultant Wise', 'title' => 'Sales Consultant Wise', 'metric' => 'count', 'rows' => $this->formatActiveAggregateRows($groups['sales_consultant'], $totalActive, 12)],
            ['key' => 'area_manager', 'label' => 'Area Manager Wise', 'title' => 'Area Manager Wise', 'metric' => 'count', 'rows' => $this->formatActiveAggregateRows($groups['area_manager'], $totalActive, 12)],
            ['key' => 'lead_state', 'label' => 'Lead State Wise', 'title' => 'Lead State Wise', 'metric' => 'count', 'rows' => $this->formatActiveAggregateRows($groups['lead_state'], $totalActive, 12)],
        ];

        return [
            'total' => $totalActive,
            'tabs' => $chartTabs,
            'export_tabs' => [
                ['title' => 'Age Group Wise', 'export_label' => 'age_group', 'columns' => $this->activeContributionColumns('age_group'), 'rows' => $this->formatActiveBucketRows($groups['age_group'], $totalActive, ['0 - 19', '20 - 25', '26 - 30', '31 - 35', '36 - 40', '41 - 45', '46 - 50', '51 - 55', '56 - 60', '61 - 65', '66 - 70', '71 - 75', '76 - 80', '81 - 85', '86 - 90', '91 - 95', '96 - 100', 'NA']), 'total_row' => $this->activeContributionTotalRow($totalActive)],
                ['title' => 'Old Vehicle Detail:"Brand"', 'export_label' => 'old_vehicle_brand_name', 'columns' => $this->activeDetailColumns('old_vehicle_brand_name'), 'rows' => $this->formatActiveCompositeRows($groups['old_vehicle_brand'], 'old_vehicle_brand_name', 50), 'total_row' => $this->activeDetailTotalRow($totalActive)],
                ['title' => 'Old Vehicle Detail:"Model"', 'export_label' => 'old_vehicle_product_name', 'columns' => $this->activeDetailColumns('old_vehicle_product_name'), 'rows' => $this->formatActiveCompositeRows($groups['old_vehicle_model'], 'old_vehicle_product_name', 50), 'total_row' => $this->activeDetailTotalRow($totalActive)],
                ['title' => 'Color Wise', 'export_label' => 'enquired_color', 'columns' => $this->activeContributionColumns('enquired_color'), 'rows' => $this->formatActiveAggregateRows($groups['color'], $totalActive, 50), 'total_row' => $this->activeContributionTotalRow($totalActive)],
                ['title' => 'Interested in Competition Model', 'export_label' => 'lead_interested_competition_model_name', 'columns' => $this->activeContributionColumns('lead_interested_competition_model_name'), 'rows' => $this->formatActiveAggregateRows($groups['competition_model'], $totalActive, 50), 'total_row' => $this->activeContributionTotalRow($totalActive)],
                ['title' => 'Exchange Value Difference', 'export_label' => 'exchange_groups', 'columns' => $this->activeContributionColumns('exchange_groups'), 'rows' => $this->formatActiveBucketRows($groups['exchange_value_difference'], $totalActive, ['(-) <1000', '(-)10000-5001', '(-)5000-1', '0-5000', '5001-10000', '10001-20000', '>20000', 'NA']), 'total_row' => $this->activeContributionTotalRow($totalActive)],
                ['title' => "Reason For 'NO' Test Drive", 'export_label' => 'reason_for_not_given', 'columns' => $this->activeContributionColumns('reason_for_not_given'), 'rows' => $this->formatActiveAggregateRows($groups['no_test_drive_reason'], $totalActive, 50), 'total_row' => $this->activeContributionTotalRow($totalActive)],
                ['title' => 'Analysis of Last Completed Follow Up', 'export_label' => 'Last_Followup_Done', 'columns' => $this->activeContributionColumns('Last_Followup_Done'), 'rows' => $this->formatActiveBucketRows($groups['last_followup_discipline'], $totalActive, ['On Time', '1 day delay', '2 days delay', '3 days delay', '>3 days delay', 'No Follow Ups']), 'total_row' => $this->activeContributionTotalRow($totalActive)],
                ['title' => 'Delay Analysis of All Follow Ups Completed', 'export_label' => 'No_of_Delayed_Followups', 'columns' => $this->activeContributionColumns('No_of_Delayed_Followups'), 'rows' => $this->formatActiveBucketRows($groups['delayed_followup_count'], $totalActive, ['0', '1', '2', '3', '4', '5', '>5', 'No Follow Up Done']), 'total_row' => $this->activeContributionTotalRow($totalActive)],
                ['title' => 'Area Manager Wise Todays Follow Ups', 'export_label' => 'area_manager_name', 'columns' => $this->activeCountColumns('area_manager_name'), 'rows' => $this->formatActiveCountRows($groups['dealer_today_followups'], 50), 'total_row' => $this->activeCountTotalRow($totalActive)],
                ['title' => 'Area Manager Wise No of Delayed Follow Ups', 'export_label' => 'area_manager_name', 'columns' => $this->activeCountColumns('area_manager_name'), 'rows' => $this->formatActiveCountRows($groups['dealer_delayed_followups'], 50), 'total_row' => $this->activeCountTotalRow($totalActive)],
                ['title' => 'SC Wise Todays Follow Ups', 'export_label' => 'assigned_to_name', 'columns' => $this->activeCountColumns('assigned_to_name'), 'rows' => $this->formatActiveCountRows($groups['sc_today_followups'], 50), 'total_row' => $this->activeCountTotalRow($totalActive)],
                ['title' => 'SC Wise No of Delayed Follow Ups', 'export_label' => 'assigned_to_name', 'columns' => $this->activeCountColumns('assigned_to_name'), 'rows' => $this->formatActiveCountRows($groups['sc_delayed_followups'], 50), 'total_row' => $this->activeCountTotalRow($totalActive)],
                ['title' => 'No Of Successful Home Visits', 'export_label' => 'No_Of_Home_Visits_Done', 'columns' => $this->activeContributionColumns('No_Of_Home_Visits_Done'), 'rows' => $this->formatActiveBucketRows($groups['successful_home_visit_count'], $totalActive, ['0', '1', '2', '>2']), 'total_row' => $this->activeContributionTotalRow($totalActive)],
                ['title' => 'Lead State Wise', 'export_label' => 'lead_state', 'columns' => $this->activeContributionColumns('lead_state'), 'rows' => $this->formatActiveAggregateRows($groups['lead_state'], $totalActive, 50), 'total_row' => $this->activeContributionTotalRow($totalActive)],
                ['title' => 'Aging of Leads', 'export_label' => 'aging_of_leads', 'columns' => $this->activeCountColumns('aging_of_leads'), 'rows' => $this->formatActiveBucketCountRows($groups['active_aging'], ['0-5 Days', '6-10 Days', '11-15 Days', '16-20 Days', '21-30 Days', '31-60 Days', '> 60 Days']), 'total_row' => $this->activeCountTotalRow($totalActive)],
                ['title' => 'Price Quote Given', 'export_label' => 'Price_Quote_Given', 'columns' => $this->activeContributionColumns('Price_Quote_Given'), 'rows' => $this->formatActiveBucketRows($groups['quote_given'], $totalActive, ['Yes', 'No', 'NA']), 'total_row' => $this->activeContributionTotalRow($totalActive)],
                ['title' => 'No of Call Follow Ups Completed', 'export_label' => 'No_of_Call_FollowUps', 'columns' => $this->activeContributionColumns('No_of_Call_FollowUps'), 'rows' => $this->formatActiveBucketRows($groups['call_followup_count'], $totalActive, ['0', '1', '2', '3', '4', '5', '6-9', '>= 10']), 'total_row' => $this->activeContributionTotalRow($totalActive)],
                ['title' => 'No of Successful Call Follow Ups', 'export_label' => 'No_Of_Successful_Call_Followups', 'columns' => $this->activeContributionColumns('No_Of_Successful_Call_Followups'), 'rows' => $this->formatActiveBucketRows($groups['successful_call_followup_count'], $totalActive, ['0', '1', '2', '3', '4', '5', '>5', '>= 10']), 'total_row' => $this->activeContributionTotalRow($totalActive)],
                ['title' => 'Status Of Call Follow Up', 'export_label' => 'Status', 'columns' => $this->activeCallStatusColumns(), 'rows' => $this->formatActiveCallStatusRows($groups['call_status'], $totalActive), 'total_row' => null],
                ['title' => 'Time Since Last Spoken With Customer', 'export_label' => 'Time_Interval', 'columns' => $this->activeCountColumns('Time_Interval'), 'rows' => $this->formatActiveBucketCountRows($groups['time_since_last_spoken'], ['-', '<5 days', '5-7 Days', '8-10 Days', '11-15 Days', '16-20 Days', '21-30 Days', '>30 Days']), 'total_row' => $this->activeCountTotalRow($totalActive)],
                ['title' => 'City Wise', 'export_label' => 'customer_city_name', 'columns' => $this->activeContributionColumns('customer_city_name'), 'rows' => $this->formatActiveAggregateRows($groups['city'], $totalActive, 50), 'total_row' => $this->activeContributionTotalRow($totalActive)],
                ['title' => 'Source Wise', 'export_label' => 'lead_source', 'columns' => $this->activeContributionColumns('lead_source'), 'rows' => $this->formatActiveAggregateRows($groups['lead_source'], $totalActive, 50), 'total_row' => $this->activeContributionTotalRow($totalActive)],
                ['title' => 'SC Wise', 'export_label' => 'assigned_to_name', 'columns' => $this->activeContributionColumns('assigned_to_name'), 'rows' => $this->formatActiveAggregateRows($groups['sales_consultant'], $totalActive, 50), 'total_row' => $this->activeContributionTotalRow($totalActive)],
                ['title' => 'Area Manager Wise', 'export_label' => 'area_manager_name', 'columns' => $this->activeContributionColumns('area_manager_name'), 'rows' => $this->formatActiveAggregateRows($groups['area_manager'], $totalActive, 50), 'total_row' => $this->activeContributionTotalRow($totalActive)],
                ['title' => 'RO Wise', 'export_label' => 'RO_Name', 'columns' => $this->activeContributionColumns('RO_Name'), 'rows' => $this->formatActiveAggregateRows($groups['ro'], $totalActive, 50), 'total_row' => $this->activeContributionTotalRow($totalActive)],
            ],
        ];
    }

    private function addActiveAggregate(array &$groups, ?string $label): void
    {
        $label = $this->displayAnalyticsLabel($label, 'Not specified');
        $groups[$label] = ($groups[$label] ?? 0) + 1;
    }

    private function addActiveCompositeAggregate(array &$groups, ?string $firstValue, ?string $secondValue): void
    {
        $firstValue = $this->displayAnalyticsLabel($firstValue, 'NA');
        $secondValue = $this->displayAnalyticsLabel($secondValue, 'NA');
        $key = $firstValue . '||' . $secondValue;
        $groups[$key] = ($groups[$key] ?? 0) + 1;
    }

    private function formatActiveAggregateRows(array $groups, int $totalActive, int $limit): array
    {
        arsort($groups);

        return array_values(array_map(
            fn(string $label, int $count): array => [
                'label' => $label,
                'count' => $count,
                'contribution' => $totalActive > 0 ? round(($count / $totalActive) * 100, 2) : 0,
            ],
            array_keys(array_slice($groups, 0, $limit, true)),
            array_values(array_slice($groups, 0, $limit, true))
        ));
    }

    private function formatActiveBucketRows(array $groups, int $totalActive, array $order): array
    {
        return array_map(
            fn(string $label): array => [
                'label' => $label,
                'count' => (int) ($groups[$label] ?? 0),
                'contribution' => $totalActive > 0 ? round(((int) ($groups[$label] ?? 0) / $totalActive) * 100, 2) : 0,
            ],
            $order
        );
    }

    private function formatActiveBucketCountRows(array $groups, array $order): array
    {
        return array_map(
            fn(string $label): array => [
                'label' => $label,
                'count' => (int) ($groups[$label] ?? 0),
            ],
            $order
        );
    }

    private function formatActiveCountRows(array $groups, int $limit): array
    {
        arsort($groups);

        return array_values(array_map(
            fn(string $label, int $count): array => [
                'label' => $label,
                'count' => $count,
            ],
            array_keys(array_slice($groups, 0, $limit, true)),
            array_values(array_slice($groups, 0, $limit, true))
        ));
    }

    private function formatActiveCompositeRows(array $groups, string $firstColumnKey, int $limit): array
    {
        arsort($groups);

        return array_values(array_map(function (string $key, int $count) use ($firstColumnKey): array {
            [$firstValue, $modelYear] = array_pad(explode('||', $key, 2), 2, 'NA');

            return [
                $firstColumnKey => $firstValue,
                'Model_Year' => $modelYear,
                'count' => $count,
            ];
        }, array_keys(array_slice($groups, 0, $limit, true)), array_values(array_slice($groups, 0, $limit, true))));
    }

    private function activeContributionColumns(string $firstColumn): array
    {
        return [
            ['key' => 'label', 'heading' => $firstColumn],
            ['key' => 'count', 'heading' => 'No_of_Leads'],
            ['key' => 'contribution', 'heading' => 'Contribution'],
        ];
    }

    private function activeCountColumns(string $firstColumn): array
    {
        return [
            ['key' => 'label', 'heading' => $firstColumn],
            ['key' => 'count', 'heading' => 'No_of_Leads'],
        ];
    }

    private function activeDetailColumns(string $firstColumn): array
    {
        return [
            ['key' => $firstColumn, 'heading' => $firstColumn],
            ['key' => 'Model_Year', 'heading' => 'Model_Year'],
            ['key' => 'count', 'heading' => 'No_Of_Leads'],
        ];
    }

    private function activeCallStatusColumns(): array
    {
        return [
            ['key' => 'label', 'heading' => 'Status'],
            ['key' => 'lead_count', 'heading' => 'No_Of_Leads'],
            ['key' => 'time_count', 'heading' => 'No_Of_Times'],
            ['key' => 'avg_per_lead', 'heading' => 'Avg_No_Per_Lead'],
        ];
    }

    private function activeContributionTotalRow(int $totalActive): array
    {
        return [
            'label' => 'Total',
            'count' => $totalActive,
            'contribution' => $totalActive > 0 ? '100.00%' : '0.00%',
        ];
    }

    private function activeCountTotalRow(int $totalActive): array
    {
        return [
            'label' => 'Total',
            'count' => $totalActive,
        ];
    }

    private function activeDetailTotalRow(int $totalActive): array
    {
        return [
            'Model_Year' => '',
            'count' => $totalActive,
        ];
    }

    private function formatActiveCallStatusRows(array $groups, int $totalActive): array
    {
        arsort($groups);

        return array_values(array_map(
            fn(string $label, int $count): array => [
                'label' => $label,
                'lead_count' => $totalActive,
                'time_count' => $count,
                'avg_per_lead' => $totalActive > 0 ? (string) max(1, (int) round($count / $totalActive)) : '0',
            ],
            array_keys($groups),
            array_values($groups)
        ));
    }

    private function activeAgingBucket(Enquiry $enquiry): string
    {
        if (empty($enquiry->created_at)) {
            return '> 60 Days';
        }

        try {
            $createdAt = Carbon::parse((string) $enquiry->created_at)->startOfDay();
            $today = Carbon::now('Asia/Colombo')->startOfDay();
        } catch (\Throwable $exception) {
            return '> 60 Days';
        }

        $days = max(0, (int) $createdAt->diffInDays($today, false));

        return match (true) {
            $days <= 5 => '0-5 Days',
            $days <= 10 => '6-10 Days',
            $days <= 15 => '11-15 Days',
            $days <= 20 => '16-20 Days',
            $days <= 30 => '21-30 Days',
            $days <= 60 => '31-60 Days',
            default => '> 60 Days',
        };
    }

    private function yesNoNaLabel($value): string
    {
        return match (strtolower(trim((string) $value))) {
            'yes', 'y', '1', 'true' => 'Yes',
            'no', 'n', '0', 'false' => 'No',
            default => 'NA',
        };
    }

    private function activeTimeSinceLastSpokenBucket(Collection $attempts): string
    {
        $doneAttempts = $attempts
            ->filter(fn(FollowupAttempt $attempt): bool => strtolower(trim((string) $attempt->followup_status)) === 'done'
                && !empty($attempt->attempted_at))
            ->values();

        if ($doneAttempts->isEmpty()) {
            return '-';
        }

        try {
            $lastSpokenAt = Carbon::parse((string) $doneAttempts->last()?->attempted_at)->startOfDay();
            $today = Carbon::now('Asia/Colombo')->startOfDay();
        } catch (\Throwable $exception) {
            return '-';
        }

        $days = max(0, (int) $lastSpokenAt->diffInDays($today, false));

        return match (true) {
            $days < 5 => '<5 days',
            $days <= 7 => '5-7 Days',
            $days <= 10 => '8-10 Days',
            $days <= 15 => '11-15 Days',
            $days <= 20 => '16-20 Days',
            $days <= 30 => '21-30 Days',
            default => '>30 Days',
        };
    }

    private function formatActiveRegistrationRows(array $groups, int $totalActive): array
    {
        if ($totalActive <= 0) {
            return [];
        }

        $ordered = [
            'REGISTERED' => (int) ($groups['REGISTERED'] ?? 0),
            'EPR' => (int) ($groups['EPR'] ?? 0),
        ];

        return array_values(array_map(
            fn(string $label, int $count): array => [
                'label' => $label,
                'count' => $count,
                'contribution' => $totalActive > 0 ? round(($count / $totalActive) * 100, 2) : 0,
            ],
            array_keys($ordered),
            array_values($ordered)
        ));
    }

    private function formatActiveMonthRows(array $groups, array $monthOrder, int $totalActive): array
    {
        ksort($groups);

        return array_values(array_map(
            fn(string $monthKey, int $count): array => [
                'label' => $monthOrder[$monthKey] ?? $monthKey,
                'count' => $count,
                'contribution' => $totalActive > 0 ? round(($count / $totalActive) * 100, 2) : 0,
            ],
            array_keys($groups),
            array_values($groups)
        ));
    }

    private function isRegisteredAnalyticsLead(Enquiry $enquiry): bool
    {
        $leadState = strtolower(trim((string) $enquiry->prospect_lead_status));

        return (int) ($enquiry->prospect_current_step ?? 0) >= 5
            && in_array($leadState, ['hot', 'warm', 'cold'], true);
    }

    private function buildBookingAnalytics(Collection $enquiries, Collection $usersById): array
    {
        $bookingRows = $enquiries
            ->filter(fn(Enquiry $enquiry): bool => !empty($enquiry->booking_id))
            ->values();
        $totalBookings = $bookingRows->count();
        $totalEnquired = $enquiries->count();

        $groups = [
            'type' => [],
            'month_booked' => [],
            'model' => [],
            'lead_source' => [],
            'source_information' => [],
            'sales_consultant' => [],
            'area_manager' => [],
            'lead_state' => [],
        ];
        $bookedMonthOrder = [];

        foreach ($bookingRows as $enquiry) {
            $bookingDate = $this->analyticsDate($enquiry->booking_created_at ?: $enquiry->created_at);
            $monthKey = $bookingDate->format('Y-m');
            $bookedMonthOrder[$monthKey] = $bookingDate->format('M Y');

            $this->addBookingAggregate($groups['type'], $this->bookingTypeAnalyticsLabel($enquiry));
            $this->addBookingAggregate($groups['month_booked'], $monthKey);
            $this->addBookingAggregate($groups['model'], $this->displayAnalyticsLabel($enquiry->booking_interested_model ?: $enquiry->vehicle_model, 'Not specified'));
            $this->addBookingAggregate($groups['lead_source'], $this->displayAnalyticsLabel($enquiry->lead_source, 'Not specified'));
            $this->addBookingAggregate(
                $groups['source_information'],
                $this->displayAnalyticsLabel($enquiry->prospect_source_of_information ?: $enquiry->enquiry_source_of_information, 'Not specified')
            );
            $this->addBookingAggregate(
                $groups['lead_state'],
                $this->displayAnalyticsLabel($enquiry->prospect_lead_status ?: $enquiry->followup_lead_temperature, 'Not specified')
            );

            $owner = $enquiry->user_id ? $usersById->get((int) $enquiry->user_id) : null;
            $this->addBookingAggregate($groups['sales_consultant'], $owner instanceof User ? $owner->name : 'Unassigned');
            $areaManager = $this->resolveAreaManagerForAnalytics($owner, $usersById);
            $this->addBookingAggregate($groups['area_manager'], $areaManager instanceof User ? $areaManager->name : 'Unassigned');
        }

        return [
            'total' => $totalBookings,
            'tabs' => [
                ['key' => 'type', 'label' => 'Type of Booking', 'title' => 'Type of Booking', 'metric' => 'count', 'rows' => $this->formatBookingTypeRows($groups['type'], $totalBookings)],
                ['key' => 'month_booked', 'label' => 'Month Wise - Booked', 'title' => 'Month Wise - Booked', 'metric' => 'count', 'rows' => $this->formatBookingMonthRows($groups['month_booked'], $bookedMonthOrder, $totalBookings)],
                ['key' => 'model', 'label' => 'Model Wise', 'title' => 'Model Wise', 'metric' => 'count', 'rows' => $this->formatBookingAggregateRows($groups['model'], $totalBookings, 12)],
                ['key' => 'lead_source', 'label' => 'Lead Source Wise', 'title' => 'Lead Source Wise', 'metric' => 'count', 'rows' => $this->formatBookingAggregateRows($groups['lead_source'], $totalBookings, 12)],
                ['key' => 'source_information', 'label' => 'Source Of Information Wise', 'title' => 'Source Of Information Wise', 'metric' => 'count', 'rows' => $this->formatBookingAggregateRows($groups['source_information'], $totalBookings, 12)],
                ['key' => 'sales_consultant', 'label' => 'Sales Consultant Wise', 'title' => 'Sales Consultant Wise', 'metric' => 'count', 'rows' => $this->formatBookingAggregateRows($groups['sales_consultant'], $totalBookings, 12)],
                ['key' => 'area_manager', 'label' => 'Area Manager Wise', 'title' => 'Area Manager Wise', 'metric' => 'count', 'rows' => $this->formatBookingAggregateRows($groups['area_manager'], $totalBookings, 12)],
                ['key' => 'lead_state', 'label' => 'Lead State Wise', 'title' => 'Lead State Wise', 'metric' => 'count', 'rows' => $this->formatBookingAggregateRows($groups['lead_state'], $totalBookings, 12)],
                ['key' => 'month_enquired', 'label' => 'Month Wise - Enquired', 'title' => 'Month Wise - Enquired', 'metric' => 'enquired_leads', 'total' => $totalEnquired, 'rows' => $this->formatBookingEnquiredMonthRows($enquiries, $totalEnquired)],
            ],
        ];
    }

    private function addBookingAggregate(array &$groups, ?string $label): void
    {
        $label = $this->displayAnalyticsLabel($label, 'Not specified');
        $groups[$label] = ($groups[$label] ?? 0) + 1;
    }

    private function formatBookingAggregateRows(array $groups, int $totalBookings, int $limit): array
    {
        arsort($groups);

        return array_values(array_map(
            fn(string $label, int $count): array => [
                'label' => $label,
                'count' => $count,
                'contribution' => $totalBookings > 0 ? round(($count / $totalBookings) * 100, 2) : 0,
            ],
            array_keys(array_slice($groups, 0, $limit, true)),
            array_values(array_slice($groups, 0, $limit, true))
        ));
    }

    private function formatBookingTypeRows(array $groups, int $totalBookings): array
    {
        if ($totalBookings <= 0) {
            return [];
        }

        $ordered = [
            'BOOKED' => (int) ($groups['BOOKED'] ?? 0),
            'CANCELLED' => (int) ($groups['CANCELLED'] ?? 0),
            'INACTIVE' => (int) ($groups['INACTIVE'] ?? 0),
        ];

        return array_values(array_map(
            fn(string $label, int $count): array => [
                'label' => $label,
                'count' => $count,
                'contribution' => $totalBookings > 0 ? round(($count / $totalBookings) * 100, 2) : 0,
            ],
            array_keys($ordered),
            array_values($ordered)
        ));
    }

    private function formatBookingMonthRows(array $groups, array $monthOrder, int $totalBookings): array
    {
        ksort($groups);

        return array_values(array_map(
            fn(string $monthKey, int $count): array => [
                'label' => $monthOrder[$monthKey] ?? $monthKey,
                'count' => $count,
                'contribution' => $totalBookings > 0 ? round(($count / $totalBookings) * 100, 2) : 0,
            ],
            array_keys($groups),
            array_values($groups)
        ));
    }

    private function formatBookingEnquiredMonthRows(Collection $enquiries, int $totalEnquired): array
    {
        $groups = [];
        $monthOrder = [];

        foreach ($enquiries as $enquiry) {
            $createdAt = $this->analyticsDate($enquiry->created_at);
            $monthKey = $createdAt->format('Y-m');
            $monthOrder[$monthKey] = $createdAt->format('M Y');
            $groups[$monthKey] = ($groups[$monthKey] ?? 0) + 1;
        }

        ksort($groups);

        return array_values(array_map(
            fn(string $monthKey, int $count): array => [
                'label' => $monthOrder[$monthKey] ?? $monthKey,
                'enquired_leads' => $count,
                'contribution' => $totalEnquired > 0 ? round(($count / $totalEnquired) * 100, 2) : 0,
            ],
            array_keys($groups),
            array_values($groups)
        ));
    }

    private function bookingTypeAnalyticsLabel(Enquiry $enquiry): string
    {
        $status = strtolower(trim((string) $enquiry->enquiry_status));

        if (in_array($status, ['cancelled', 'canceled'], true)) {
            return 'CANCELLED';
        }

        if (in_array($status, ['inactive', 'closed', 'lost'], true)) {
            return 'INACTIVE';
        }

        return 'BOOKED';
    }

    private function buildLostAnalytics(Collection $enquiries, Collection $usersById): array
    {
        $lostRows = $enquiries
            ->filter(fn(Enquiry $enquiry): bool => $this->normalizeLeadResult($enquiry->followup_result) === 'lost')
            ->values();
        $totalLost = $lostRows->count();

        $groups = [
            'lost_to' => [],
            'lead_source' => [],
            'month' => [],
            'lost_model_own' => [],
            'area_manager' => [],
            'sales_consultant' => [],
            'city' => [],
            'lost_to_model' => [],
            'lost_to_brand' => [],
            'reasons_competition' => [],
            'lost_to_co_dealer' => [],
            'reasons_co_dealer' => [],
            'lead_status' => [],
            'month_wise_enquired' => [],
            'days_to_lost' => [],
            'followup_count' => [],
            'call_followup_count' => [],
            'successful_call_followup_count' => [],
            'successful_home_visit_count' => [],
            'successful_showroom_visit_count' => [],
            'last_followup_discipline' => [],
            'delayed_followup_count' => [],
            'lost_to_today_interval' => [],
            'lost_followup_type' => [],
            'lead_aging' => [],
            'profession' => [],
            'customer_type' => [],
            'age_group' => [],
            'competition_model' => [],
            'exchange_value_difference' => [],
            'district' => [],
            'province' => [],
        ];

        $monthOrder = [];
        $lostEnquiryIds = $lostRows
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->filter()
            ->values()
            ->all();
        $attemptsByEnquiryId = empty($lostEnquiryIds)
            ? collect()
            : FollowupAttempt::query()
                ->whereIn('enquiry_id', $lostEnquiryIds)
                ->orderBy('attempted_at')
                ->get(['enquiry_id', 'follow_type', 'followup_status', 'attempted_at'])
                ->groupBy(fn(FollowupAttempt $attempt): int => (int) $attempt->enquiry_id);
        $lostDataRows = [];

        foreach ($lostRows as $enquiry) {
            $lostTo = strtolower(trim((string) $enquiry->followup_lost_to));
            $lostToLabel = match ($lostTo) {
                'competitor' => 'Competitor',
                'co_dealer', 'codealer', 'co-dealer' => 'Co-Dealer',
                default => 'Not specified',
            };

            $this->addLostAggregate($groups['lost_to'], $lostToLabel);
            $this->addLostAggregate($groups['lead_source'], $this->displayAnalyticsLabel($enquiry->lead_source, 'Not specified'));
            $this->addLostAggregate($groups['lost_model_own'], $this->displayAnalyticsLabel($enquiry->vehicle_model, 'Not specified'));
            $this->addLostAggregate($groups['city'], $this->displayAnalyticsLabel($enquiry->customer_location ?: $enquiry->customer_district, 'Not specified'));
            $this->addLostAggregate($groups['lost_to_model'], $this->displayAnalyticsLabel($enquiry->followup_lost_competition_model, 'Not specified'));
            $this->addLostAggregate($groups['lost_to_brand'], $this->displayAnalyticsLabel($enquiry->followup_lost_competition_brand, 'Not specified'));
            $this->addLostAggregate($groups['lost_to_co_dealer'], $this->displayAnalyticsLabel($enquiry->followup_lost_codealer_name, 'Not specified'));
            $this->addLostAggregate($groups['lead_status'], $this->displayAnalyticsLabel($enquiry->prospect_lead_status ?: $enquiry->followup_lead_temperature, 'Not specified'));

            $owner = $enquiry->user_id ? $usersById->get((int) $enquiry->user_id) : null;
            $this->addLostAggregate($groups['sales_consultant'], $owner instanceof User ? $owner->name : 'Unassigned');
            $areaManager = $this->resolveAreaManagerForAnalytics($owner, $usersById);
            $this->addLostAggregate($groups['area_manager'], $areaManager instanceof User ? $areaManager->name : 'Unassigned');

            $monthKey = $enquiry->created_at instanceof Carbon
                ? $enquiry->created_at->format('Y-m')
                : Carbon::parse($enquiry->created_at)->format('Y-m');
            $monthLabel = $enquiry->created_at instanceof Carbon
                ? $enquiry->created_at->format('M Y')
                : Carbon::parse($enquiry->created_at)->format('M Y');
            $monthOrder[$monthKey] = $monthLabel;
            $this->addLostAggregate($groups['month'], $monthKey);
            $this->addLostAggregate($groups['month_wise_enquired'], $monthKey);

            $attempts = $attemptsByEnquiryId->get((int) $enquiry->id, collect());
            $attemptCount = $attempts->count();
            if ($attemptCount === 0 && !empty($enquiry->followup_marked_at)) {
                $attemptCount = 1;
            }

            $callAttemptCount = $attempts
                ->filter(fn(FollowupAttempt $attempt): bool => $this->normalizeFollowupType($attempt->follow_type) === 'Call')
                ->count();
            $successfulCallAttemptCount = $attempts
                ->filter(fn(FollowupAttempt $attempt): bool => $this->normalizeFollowupType($attempt->follow_type) === 'Call'
                    && strtolower(trim((string) $attempt->followup_status)) === 'done')
                ->count();
            $successfulHomeVisitCount = $attempts
                ->filter(fn(FollowupAttempt $attempt): bool => $this->normalizeFollowupType($attempt->follow_type) === 'Home visit'
                    && strtolower(trim((string) $attempt->followup_status)) === 'done')
                ->count();
            $successfulShowroomVisitCount = $attempts
                ->filter(fn(FollowupAttempt $attempt): bool => $this->normalizeFollowupType($attempt->follow_type) === 'Showroom visit'
                    && strtolower(trim((string) $attempt->followup_status)) === 'done')
                ->count();

            if ($attempts->isEmpty() && $this->normalizeFollowupType($enquiry->follow_type) === 'Call' && !empty($enquiry->followup_marked_at)) {
                $callAttemptCount = 1;
                $successfulCallAttemptCount = strtolower(trim((string) $enquiry->followup_status)) === 'done' ? 1 : 0;
            }
            if ($attempts->isEmpty() && $this->normalizeFollowupType($enquiry->follow_type) === 'Home visit' && !empty($enquiry->followup_marked_at)) {
                $successfulHomeVisitCount = strtolower(trim((string) $enquiry->followup_status)) === 'done' ? 1 : 0;
            }
            if ($attempts->isEmpty() && $this->normalizeFollowupType($enquiry->follow_type) === 'Showroom visit' && !empty($enquiry->followup_marked_at)) {
                $successfulShowroomVisitCount = strtolower(trim((string) $enquiry->followup_status)) === 'done' ? 1 : 0;
            }

            $this->addLostAggregate($groups['days_to_lost'], $this->daysToLostBucket($enquiry));
            $this->addLostAggregate($groups['followup_count'], $this->followupCountBucket($attemptCount));
            $this->addLostAggregate($groups['call_followup_count'], $this->followupCountBucket($callAttemptCount));
            $this->addLostAggregate($groups['successful_call_followup_count'], $this->followupCountBucket($successfulCallAttemptCount, true));
            $this->addLostAggregate($groups['successful_home_visit_count'], $this->visitCountBucket($successfulHomeVisitCount));
            $this->addLostAggregate($groups['successful_showroom_visit_count'], $this->visitCountBucket($successfulShowroomVisitCount));
            $this->addLostAggregate($groups['last_followup_discipline'], $this->lastFollowupDisciplineBucket($enquiry, $attempts));
            $this->addLostAggregate($groups['delayed_followup_count'], $this->delayedFollowupCountBucket($enquiry, $attempts));
            $this->addLostAggregate($groups['lost_to_today_interval'], $this->lostToTodayIntervalBucket($enquiry));
            $this->addLostAggregate($groups['lost_followup_type'], $this->lostFollowupTypeLabel($enquiry));
            $this->addLostAggregate($groups['lead_aging'], $this->leadAgingBucket($enquiry));
            $this->addLostAggregate($groups['profession'], $this->professionAnalyticsLabel($enquiry->prospect_profession ?? null));
            $this->addLostAggregate($groups['customer_type'], $this->customerTypeAnalyticsLabel($enquiry->prospect_customer_type ?? null));
            $this->addLostAggregate($groups['age_group'], $this->ageGroupBucket($enquiry->prospect_date_of_birth ?? null));
            $this->addLostAggregate($groups['competition_model'], $this->displayAnalyticsLabel($enquiry->prospect_competition_model ?? null, 'NA'));
            $this->addLostAggregate($groups['exchange_value_difference'], $this->exchangeDifferenceBucket($enquiry->prospect_exchange_price_difference ?? null));
            $districtLabel = $this->districtAnalyticsLabel($enquiry->customer_district ?? null);
            $this->addLostAggregate($groups['district'], $districtLabel);
            $this->addLostAggregate($groups['province'], $this->provinceAnalyticsLabel($districtLabel));
            $lostDataRows[] = $this->mapLostDataExportRow($enquiry, $owner, $areaManager, $attempts);

            $reasons = $this->formatLostRejectReasons($enquiry->followup_lost_reject_reasons, $enquiry->followup_lost_reject_other_text);
            foreach ($reasons as $reason) {
                if ($lostTo === 'competitor') {
                    $this->addLostAggregate($groups['reasons_competition'], $reason);
                } elseif (in_array($lostTo, ['co_dealer', 'codealer', 'co-dealer'], true)) {
                    $this->addLostAggregate($groups['reasons_co_dealer'], $reason);
                }
            }
        }

        $monthRows = $this->formatLostMonthRows($groups['month'], $monthOrder, $totalLost);
        $monthWiseRows = $this->formatLostMonthRows($groups['month_wise_enquired'], $monthOrder, $totalLost);

        return [
            'total' => $totalLost,
            'lost_data_headers' => $this->lostDataExportHeaders(),
            'lost_data_rows' => $lostDataRows,
            'tabs' => [
                ['key' => 'lost_to', 'label' => 'Lost To', 'title' => 'Lost To', 'rows' => $this->formatLostAggregateRows($groups['lost_to'], $totalLost, 12)],
                ['key' => 'lead_source', 'label' => 'Lead Source', 'title' => 'Lead Source', 'rows' => $this->formatLostAggregateRows($groups['lead_source'], $totalLost, 12)],
                ['key' => 'month', 'label' => 'Month', 'title' => 'Month', 'rows' => $monthRows],
                ['key' => 'lost_model_own', 'label' => 'Lost Model Own', 'title' => 'Lost Model Own', 'rows' => $this->formatLostAggregateRows($groups['lost_model_own'], $totalLost, 12)],
                ['key' => 'area_manager', 'label' => 'Area Manager', 'title' => 'Area Manager', 'rows' => $this->formatLostAggregateRows($groups['area_manager'], $totalLost, 12)],
                ['key' => 'sales_consultant', 'label' => 'Sales Consultant', 'title' => 'Sales Consultant', 'rows' => $this->formatLostAggregateRows($groups['sales_consultant'], $totalLost, 12)],
                ['key' => 'city', 'label' => 'City', 'title' => 'City', 'rows' => $this->formatLostAggregateRows($groups['city'], $totalLost, 12)],
                ['key' => 'lost_to_model', 'label' => 'Lost To Model', 'title' => 'Lost To Model', 'rows' => $this->formatLostAggregateRows($groups['lost_to_model'], $totalLost, 12)],
                ['key' => 'lost_to_brand', 'label' => 'Lost To Brand', 'title' => 'Lost To Brand', 'rows' => $this->formatLostAggregateRows($groups['lost_to_brand'], $totalLost, 12)],
                ['key' => 'reasons_competition', 'label' => 'Reasons-Competition', 'title' => 'Reasons-Competition', 'rows' => $this->formatLostAggregateRows($groups['reasons_competition'], $totalLost, 12)],
                ['key' => 'lost_to_co_dealer', 'label' => 'Lost To Co-Dealer', 'title' => 'Lost To Co-Dealer', 'rows' => $this->formatLostAggregateRows($groups['lost_to_co_dealer'], $totalLost, 12)],
                ['key' => 'reasons_co_dealer', 'label' => 'Reasons-Co-Dealer', 'title' => 'Reasons-Co-Dealer', 'rows' => $this->formatLostAggregateRows($groups['reasons_co_dealer'], $totalLost, 12)],
                ['key' => 'lead_status', 'label' => 'Lead Status', 'title' => 'Lead Status', 'rows' => $this->formatLostAggregateRows($groups['lead_status'], $totalLost, 12)],
                ['key' => 'month_wise_enquired', 'label' => 'Month Wise Enquired', 'title' => 'Month Wise Enquired', 'rows' => $monthWiseRows],
                ['key' => 'days_to_lost', 'label' => 'Days To Lost', 'title' => 'No of days from date of inquiry to date of lost', 'rows' => $this->formatLostBucketRows($groups['days_to_lost'], $totalLost, ['1 day', '2-3 days', '4-6 days', '7-10 days', '11-15 days', '16-20 days', '>20 days', 'NA'])],
                ['key' => 'followup_count', 'label' => 'No of Follow Ups', 'title' => 'No of Follow Ups', 'rows' => $this->formatLostBucketRows($groups['followup_count'], $totalLost, ['0', '1', '2', '3', '4', '5', '6-9', '>= 10'])],
                ['key' => 'call_followup_count', 'label' => 'No of Calls Follow Ups', 'title' => 'No of Calls Follow Ups', 'rows' => $this->formatLostBucketRows($groups['call_followup_count'], $totalLost, ['0', '1', '2', '3', '4', '5', '6-9', '>= 10'])],
                ['key' => 'successful_call_followup_count', 'label' => 'No of Successful Calls Follow Ups', 'title' => 'No of Successful Calls Follow Ups', 'rows' => $this->formatLostBucketRows($groups['successful_call_followup_count'], $totalLost, ['0', '1', '2', '3', '4', '5', '>5', '>= 10'])],
                ['key' => 'successful_home_visit_count', 'label' => 'No of Successful Home Visits', 'title' => 'No of Successful Home Visits', 'export_label' => 'No_Suc_Home_Visits', 'rows' => $this->formatLostBucketRows($groups['successful_home_visit_count'], $totalLost, ['0', '1', '2', '>2'])],
                ['key' => 'successful_showroom_visit_count', 'label' => 'No of Successful Showroom Visits', 'title' => 'No of Successful Showroom Visits', 'export_label' => 'No_Of_Suc_Showroom_Visits', 'rows' => $this->formatLostBucketRows($groups['successful_showroom_visit_count'], $totalLost, ['0', '1', '2', '>2'])],
                ['key' => 'last_followup_discipline', 'label' => 'Last Follow up Done: Discipline', 'title' => 'last Follow up Done: Discipline', 'export_label' => 'aging_follow_up', 'rows' => $this->formatLostBucketRows($groups['last_followup_discipline'], $totalLost, ['On Time', '1 day delay', '2 days delay', '3 days delay', '>3 days delay', 'No Follow Ups'])],
                ['key' => 'delayed_followup_count', 'label' => 'No of Delayed Followups', 'title' => 'No of Delayed Followups', 'export_label' => 'No_of_Delayed_Followups', 'rows' => $this->formatLostBucketRows($groups['delayed_followup_count'], $totalLost, ['0', '1', '2', '3', '4', '5', '>5', 'No Follow Up Done'])],
                ['key' => 'lost_to_today_interval', 'label' => 'No Of Days Between Lost and Last days', 'title' => 'No Of Days Between Lost and Last days', 'export_label' => 'Time_Interval', 'rows' => $this->formatLostBucketRows($groups['lost_to_today_interval'], $totalLost, ['<5 days', '5-7 Days', '8-10 Days', '11-15 Days', '16-20 Days', '21-30 Days', '>30 Days', 'NA'])],
                ['key' => 'lost_followup_type', 'label' => 'Lead Lost-Follow up Type', 'title' => 'Lead Lost-Follow up Type', 'export_label' => 'Lead_Lost_Follow_Up_Type', 'rows' => $this->formatLostBucketRows($groups['lost_followup_type'], $totalLost, ['Call', 'Home visit', 'Dealer Visit', 'NA'])],
                ['key' => 'lead_aging', 'label' => 'Aging Of Leads', 'title' => 'Aging Of Leads', 'export_label' => 'aging', 'rows' => $this->formatLostBucketRows($groups['lead_aging'], $totalLost, ['<= 15 Days', '16-30 Days', '31-60 Days', '> 60 Days', 'NA'])],
                ['key' => 'profession', 'label' => 'Profession Wise Leads', 'title' => 'Profession Wise Leads', 'export_label' => 'profession', 'rows' => $this->formatLostAggregateRows($groups['profession'], $totalLost, 12)],
                ['key' => 'customer_type', 'label' => 'Customer Type', 'title' => 'Customer Type', 'export_label' => 'customer_type', 'rows' => $this->formatLostBucketRows($groups['customer_type'], $totalLost, ['Individual', 'Corporate', 'NA'])],
                ['key' => 'age_group', 'label' => 'Age Group', 'title' => 'Age Group', 'export_label' => 'age_group', 'rows' => $this->formatLostBucketRows($groups['age_group'], $totalLost, ['0 - 19', '20 - 25', '26 - 30', '31 - 35', '36 - 40', '41 - 45', '46 - 50', '51 - 55', '56 - 60', '61 - 65', '66 - 70', '71 - 75', '76 - 80', '81 - 85', '86 - 90', '91 - 95', '96 - 100', 'NA'])],
                ['key' => 'competition_model', 'label' => 'Interested in Competition Model', 'title' => 'Interested in Competition Model', 'export_label' => 'lead_interested_competition_model_name', 'rows' => $this->formatLostAggregateRows($groups['competition_model'], $totalLost, 20)],
                ['key' => 'exchange_value_difference', 'label' => 'Difference in Exchange value', 'title' => 'Difference in Exchange value', 'export_label' => 'exchange_groups', 'rows' => $this->formatLostBucketRows($groups['exchange_value_difference'], $totalLost, ['(-) <1000', '(-)10000-5001', '(-)5000-1', '0-5000', '5001-10000', '10001-20000', '>20000', 'NA'])],
                ['key' => 'district', 'label' => 'District Wise', 'title' => 'District Wise', 'export_label' => 'district', 'rows' => $this->formatLostAggregateRows($groups['district'], $totalLost, 25)],
                ['key' => 'province', 'label' => 'Province Wise', 'title' => 'Province Wise', 'export_label' => 'province', 'rows' => $this->formatLostAggregateRows($groups['province'], $totalLost, 9)],
            ],
        ];
    }

    private function addLostAggregate(array &$groups, ?string $label): void
    {
        $label = $this->displayAnalyticsLabel($label, 'Not specified');
        $groups[$label] = ($groups[$label] ?? 0) + 1;
    }

    private function lostDataExportHeaders(): array
    {
        return [
            'Lead_Status',
            'State_of_lead',
            'Epr_Creation_Date',
            'Date_Of_Enquiry',
            'No_of_days_from_Enquiry_to_Lost',
            'Date_Of_Lost',
            'lead_id',
            'DMS_ID',
            'DealershipName',
            'Dealership_City',
            'SCName',
            'assigned_to_name',
            'created_by_name',
            'Team_Leader_Name',
            'SM_Name',
            'Lost_to',
            'Competition_Brand',
            'Competition_Model',
            'Co_Dealer_Name',
            'Reason_Lost',
            'Reason_Closed',
            'Lead_source',
            'source_of_information',
            'Salutation',
            'FirstName',
            'LastName',
            'Address',
            'Customer_City',
            'Locality',
            'MOB1',
            'MOB2',
            'Email',
            'Enquired_Model',
            'Enquired_EngineType',
            'Enquired_variant',
            'Enquired_Color',
            'CustomerType',
            'Profession',
            'DOB',
            'AgeGroup',
            'DOA',
            'Purchase_Mode',
            'Bank',
            'Interested_in_competition',
            'Brand',
            'Model',
            'First_Time_Buyer',
            'Interested_In_Exchange',
            'Exchange_Brand',
            'Exchange_Model',
            'Model_year',
            'Ownership',
            'Insurance_validity',
            'Tyre_replacement',
            'Color',
            'Mileage',
            'Registration_no',
            'Expected_price',
            'Quoted_price',
            'Difference',
            'Elements_of_price',
            'Elements_of_price_discount',
            'Elements_accessories',
            'Elements_of_accessories_discount',
            'Elements_scheme',
            'Elements_scheme_discount',
            'Total_Cost',
            'Total_Offer',
            'Total_Final_Price',
            'Dealer_Outflow',
            'Dealer_Savings',
            'Test_Drive_Given',
            'Test_drive_given_date',
            'Reason_for_not_given',
            'No_of_home_visits',
            'Last_home_visit_date',
            'No_of_calls',
            'No_of_busy_off_out_of_network',
            'No_of_spoke_to_customer',
            'No_of_successful_showroom_visit',
            'Last_showroom_visit_date',
            'Last_followup_date',
            'Last_followup_type',
            'Last_followup_status',
            'Last_followup_remarks',
            'Next_scheduled_follow_up_date',
            'Delayed_Follow_Up',
            'Last_sucessful_follow_up_date',
            'Type_of_follow_up',
            'Remarks',
        ];
    }

    private function mapLostDataExportRow(Enquiry $enquiry, ?User $owner, ?User $areaManager, Collection $attempts): array
    {
        $mobiles = $this->analyticsMobileNumbers($enquiry->customer_mobile_numbers ?? null);
        $headOfSales = $areaManager instanceof User ? $this->findHierarchyRecipient($areaManager, User::ROLE_HEAD_OF_SALES) : null;
        $doneAttempts = $attempts
            ->filter(fn(FollowupAttempt $attempt): bool => strtolower(trim((string) $attempt->followup_status)) === 'done')
            ->values();
        $lastAttempt = $attempts->last();
        $lastDoneAttempt = $doneAttempts->last();
        $homeAttempts = $attempts->filter(fn(FollowupAttempt $attempt): bool => $this->normalizeFollowupType($attempt->follow_type) === 'Home visit');
        $callAttempts = $attempts->filter(fn(FollowupAttempt $attempt): bool => $this->normalizeFollowupType($attempt->follow_type) === 'Call');
        $showroomDoneAttempts = $doneAttempts->filter(fn(FollowupAttempt $attempt): bool => $this->normalizeFollowupType($attempt->follow_type) === 'Showroom visit');
        $lostReasons = implode(', ', $this->formatLostRejectReasons($enquiry->followup_lost_reject_reasons, $enquiry->followup_lost_reject_other_text));
        $lostTo = match (strtolower(trim((string) $enquiry->followup_lost_to))) {
            'competitor' => 'Competitor',
            'co_dealer', 'codealer', 'co-dealer' => 'Co-Dealer',
            default => 'NA',
        };
        $customerName = trim((string) ($enquiry->customer_name ?? ''));
        $nameParts = preg_split('/\s+/', $customerName, 2) ?: [];
        $address = trim(implode(' ', array_filter([
            (string) ($enquiry->customer_address1 ?? ''),
            (string) ($enquiry->customer_address2 ?? ''),
        ])));

        return [
            'Lost',
            $this->displayAnalyticsLabel($enquiry->prospect_lead_status ?: $enquiry->followup_lead_temperature, 'NA'),
            $this->formatAnalyticsExportDate($enquiry->created_at ?? null),
            $this->formatAnalyticsExportDate($enquiry->created_at ?? null),
            $this->daysToLostExportValue($enquiry),
            $this->formatAnalyticsExportDate($enquiry->followup_marked_at ?? null),
            (string) ($enquiry->id ?? ''),
            '',
            $areaManager?->name ?? 'NA',
            $this->districtAnalyticsLabel($enquiry->customer_district ?? null),
            $owner?->name ?? 'Unassigned',
            $owner?->name ?? 'Unassigned',
            $owner?->name ?? 'Unassigned',
            $areaManager?->name ?? 'NA',
            $headOfSales?->name ?? 'NA',
            $lostTo,
            (string) ($enquiry->followup_lost_competition_brand ?? ''),
            (string) ($enquiry->followup_lost_competition_model ?? ''),
            (string) ($enquiry->followup_lost_codealer_name ?? ''),
            $lostReasons,
            '',
            (string) ($enquiry->lead_source ?? ''),
            (string) (($enquiry->prospect_source_of_information ?? '') ?: ($enquiry->enquiry_source_of_information ?? '')),
            (string) ($enquiry->customer_title ?? ''),
            $nameParts[0] ?? '',
            $nameParts[1] ?? '',
            $address,
            (string) (($enquiry->customer_location ?? '') ?: ($enquiry->customer_district ?? '')),
            (string) ($enquiry->customer_state ?? ''),
            $mobiles[0] ?? '',
            $mobiles[1] ?? '',
            '',
            (string) ($enquiry->vehicle_model ?? ''),
            (string) ($enquiry->vehicle_engine_type ?? ''),
            (string) ($enquiry->vehicle_variant ?? ''),
            (string) ($enquiry->prospect_interested_vehicle_color ?? ''),
            $this->customerTypeAnalyticsLabel($enquiry->prospect_customer_type ?? null),
            $this->professionAnalyticsLabel($enquiry->prospect_profession ?? null),
            $this->formatAnalyticsExportDate($enquiry->prospect_date_of_birth ?? null),
            $this->ageGroupBucket($enquiry->prospect_date_of_birth ?? null),
            '',
            (string) ($enquiry->prospect_purchase_mode ?? ''),
            '',
            (string) ($enquiry->prospect_interested_in_competition ?? ''),
            (string) ($enquiry->prospect_competition_brand ?? ''),
            (string) ($enquiry->prospect_competition_model ?? ''),
            (string) ($enquiry->prospect_first_time_buyer ?? ''),
            (string) ($enquiry->prospect_interested_in_exchange ?? ''),
            (string) ($enquiry->prospect_exchange_vehicle_brand ?? ''),
            (string) ($enquiry->prospect_exchange_vehicle_model ?? ''),
            (string) ($enquiry->prospect_exchange_manufacture_year ?? ''),
            '',
            '',
            '',
            (string) ($enquiry->prospect_exchange_color ?? ''),
            (string) ($enquiry->prospect_exchange_mileage_km ?? ''),
            (string) ($enquiry->prospect_exchange_registration_no ?? ''),
            $this->formatAnalyticsExportNumber($enquiry->prospect_exchange_expected_price ?? null),
            $this->formatAnalyticsExportNumber($enquiry->prospect_exchange_quoted_price ?? null),
            $this->formatAnalyticsExportNumber($enquiry->prospect_exchange_price_difference ?? null),
            $this->formatAnalyticsExportNumber($enquiry->prospect_offer_unit_price ?? null),
            $this->formatAnalyticsExportNumber($enquiry->prospect_offer_unit_price_discount ?? null),
            '',
            '',
            '',
            '',
            $this->formatAnalyticsExportNumber($enquiry->prospect_offer_total_cost ?? null),
            $this->formatAnalyticsExportNumber($enquiry->prospect_offer_total_discount ?? null),
            $this->formatAnalyticsExportNumber($enquiry->prospect_offer_final_price ?? null),
            '',
            '',
            (string) ($enquiry->prospect_test_drive_given ?? ''),
            $this->formatAnalyticsExportDate($enquiry->prospect_test_drive_date ?? null),
            (string) ($enquiry->prospect_test_drive_not_given_reason ?? ''),
            (string) $homeAttempts->count(),
            $this->formatAnalyticsExportDate($homeAttempts->last()?->attempted_at ?? null),
            (string) $callAttempts->count(),
            '',
            (string) $doneAttempts->filter(fn(FollowupAttempt $attempt): bool => $this->normalizeFollowupType($attempt->follow_type) === 'Call')->count(),
            (string) $showroomDoneAttempts->count(),
            $this->formatAnalyticsExportDate($showroomDoneAttempts->last()?->attempted_at ?? null),
            $this->formatAnalyticsExportDate($lastAttempt?->attempted_at ?? $enquiry->followup_marked_at ?? null),
            $this->normalizeFollowupType($lastAttempt?->follow_type ?? $enquiry->follow_type ?? null) ?? '',
            $this->displayAnalyticsLabel($lastAttempt?->followup_status ?? $enquiry->followup_status ?? null, 'NA'),
            (string) ($enquiry->followup_customer_comment ?? ''),
            $this->formatAnalyticsExportDate($enquiry->follow_date ?? null),
            $this->delayedFollowupCountBucket($enquiry, $attempts),
            $this->formatAnalyticsExportDate($lastDoneAttempt?->attempted_at ?? null),
            $this->normalizeFollowupType($lastDoneAttempt?->follow_type ?? null) ?? '',
            (string) ($enquiry->followup_customer_comment ?? ''),
        ];
    }

    private function daysToLostBucket(Enquiry $enquiry): string
    {
        if (empty($enquiry->created_at) || empty($enquiry->followup_marked_at)) {
            return 'NA';
        }

        try {
            $createdAt = Carbon::parse((string) $enquiry->created_at)->startOfDay();
            $lostAt = Carbon::parse((string) $enquiry->followup_marked_at)->startOfDay();
        } catch (\Throwable $exception) {
            return 'NA';
        }

        $days = max(1, (int) $createdAt->diffInDays($lostAt, false) + 1);

        return match (true) {
            $days === 1 => '1 day',
            $days <= 3 => '2-3 days',
            $days <= 6 => '4-6 days',
            $days <= 10 => '7-10 days',
            $days <= 15 => '11-15 days',
            $days <= 20 => '16-20 days',
            default => '>20 days',
        };
    }

    private function daysToLostExportValue(Enquiry $enquiry): string
    {
        if (empty($enquiry->created_at) || empty($enquiry->followup_marked_at)) {
            return '';
        }

        try {
            $createdAt = Carbon::parse((string) $enquiry->created_at)->startOfDay();
            $lostAt = Carbon::parse((string) $enquiry->followup_marked_at)->startOfDay();
        } catch (\Throwable $exception) {
            return '';
        }

        return (string) max(0, (int) $createdAt->diffInDays($lostAt, false));
    }

    private function daysToClosedBucket(Enquiry $enquiry): string
    {
        if (empty($enquiry->created_at) || empty($enquiry->followup_marked_at)) {
            return 'NA';
        }

        try {
            $createdAt = Carbon::parse((string) $enquiry->created_at)->startOfDay();
            $closedAt = Carbon::parse((string) $enquiry->followup_marked_at)->startOfDay();
        } catch (\Throwable $exception) {
            return 'NA';
        }

        $days = max(1, (int) $createdAt->diffInDays($closedAt, false) + 1);

        return match (true) {
            $days === 1 => '1 day',
            $days <= 3 => '2-3 days',
            $days <= 6 => '4-6 days',
            $days <= 10 => '7-10 days',
            $days <= 15 => '11-15 days',
            $days <= 20 => '16-20 days',
            default => '>20 days',
        };
    }

    private function followupCountBucket(int $count, bool $successfulCallBuckets = false): string
    {
        if ($count <= 5) {
            return (string) max(0, $count);
        }

        if ($count >= 10) {
            return '>= 10';
        }

        return $successfulCallBuckets ? '>5' : '6-9';
    }

    private function visitCountBucket(int $count): string
    {
        if ($count <= 2) {
            return (string) max(0, $count);
        }

        return '>2';
    }

    private function lastFollowupDisciplineBucket(Enquiry $enquiry, Collection $attempts): string
    {
        $doneAttempts = $attempts
            ->filter(fn(FollowupAttempt $attempt): bool => strtolower(trim((string) $attempt->followup_status)) === 'done'
                && !empty($attempt->attempted_at))
            ->values();

        $lastDoneAt = null;
        if ($doneAttempts->isNotEmpty()) {
            $lastDoneAt = $doneAttempts->last()?->attempted_at;
        } elseif (strtolower(trim((string) $enquiry->followup_status)) === 'done' && !empty($enquiry->followup_marked_at)) {
            $lastDoneAt = $enquiry->followup_marked_at;
        }

        if (empty($lastDoneAt)) {
            return 'No Follow Ups';
        }

        if (empty($enquiry->follow_date)) {
            return 'On Time';
        }

        try {
            $scheduledAt = Carbon::parse((string) $enquiry->follow_date)->startOfDay();
            $completedAt = Carbon::parse((string) $lastDoneAt)->startOfDay();
        } catch (\Throwable $exception) {
            return 'On Time';
        }

        $delayDays = (int) $scheduledAt->diffInDays($completedAt, false);

        return match (true) {
            $delayDays <= 0 => 'On Time',
            $delayDays === 1 => '1 day delay',
            $delayDays === 2 => '2 days delay',
            $delayDays === 3 => '3 days delay',
            default => '>3 days delay',
        };
    }

    private function delayedFollowupCountBucket(Enquiry $enquiry, Collection $attempts): string
    {
        $doneAttempts = $attempts
            ->filter(fn(FollowupAttempt $attempt): bool => strtolower(trim((string) $attempt->followup_status)) === 'done'
                && !empty($attempt->attempted_at))
            ->values();

        if ($doneAttempts->isEmpty() && strtolower(trim((string) $enquiry->followup_status)) !== 'done') {
            return 'No Follow Up Done';
        }

        if (empty($enquiry->follow_date)) {
            return '0';
        }

        try {
            $scheduledAt = Carbon::parse((string) $enquiry->follow_date)->startOfDay();
        } catch (\Throwable $exception) {
            return '0';
        }

        if ($doneAttempts->isEmpty() && !empty($enquiry->followup_marked_at)) {
            $doneAttempts = collect([(object) ['attempted_at' => $enquiry->followup_marked_at]]);
        }

        $delayedCount = $doneAttempts
            ->filter(function ($attempt) use ($scheduledAt): bool {
                try {
                    return Carbon::parse((string) $attempt->attempted_at)->startOfDay()->greaterThan($scheduledAt);
                } catch (\Throwable $exception) {
                    return false;
                }
            })
            ->count();

        return $delayedCount > 5 ? '>5' : (string) $delayedCount;
    }

    private function lostToTodayIntervalBucket(Enquiry $enquiry): string
    {
        if (empty($enquiry->followup_marked_at)) {
            return 'NA';
        }

        try {
            $lostAt = Carbon::parse((string) $enquiry->followup_marked_at)->startOfDay();
            $today = Carbon::now('Asia/Colombo')->startOfDay();
        } catch (\Throwable $exception) {
            return 'NA';
        }

        $days = max(0, (int) $lostAt->diffInDays($today, false));

        return match (true) {
            $days < 5 => '<5 days',
            $days <= 7 => '5-7 Days',
            $days <= 10 => '8-10 Days',
            $days <= 15 => '11-15 Days',
            $days <= 20 => '16-20 Days',
            $days <= 30 => '21-30 Days',
            default => '>30 Days',
        };
    }

    private function lostFollowupTypeLabel(Enquiry $enquiry): string
    {
        return match ($this->normalizeFollowupType($enquiry->follow_type)) {
            'Call' => 'Call',
            'Home visit' => 'Home visit',
            'Showroom visit' => 'Dealer Visit',
            default => 'NA',
        };
    }

    private function closedLastSpokenIntervalBucket(Enquiry $enquiry, Collection $attempts): string
    {
        $doneAttempts = $attempts
            ->filter(fn(FollowupAttempt $attempt): bool => strtolower(trim((string) $attempt->followup_status)) === 'done'
                && !empty($attempt->attempted_at))
            ->values();

        if ($doneAttempts->isEmpty()) {
            return 'Never Spoken';
        }

        if (empty($enquiry->followup_marked_at)) {
            return 'NA';
        }

        try {
            $lastSpokenAt = Carbon::parse((string) $doneAttempts->last()?->attempted_at)->startOfDay();
            $closedAt = Carbon::parse((string) $enquiry->followup_marked_at)->startOfDay();
        } catch (\Throwable $exception) {
            return 'NA';
        }

        $days = max(0, (int) $lastSpokenAt->diffInDays($closedAt, false));

        return match (true) {
            $days < 5 => '<5 days',
            $days <= 7 => '5-7 Days',
            $days <= 10 => '8-10 Days',
            $days <= 15 => '11-15 Days',
            $days <= 20 => '16-20 Days',
            $days <= 30 => '21-30 Days',
            default => '>30 Days',
        };
    }

    private function closedFollowupTypeLabel(Enquiry $enquiry): string
    {
        return match ($this->normalizeFollowupType($enquiry->follow_type)) {
            'Call' => 'Call',
            'Home visit' => 'Home visit',
            'Showroom visit' => 'Dealer Visit',
            default => 'NA',
        };
    }

    private function leadAgingBucket(Enquiry $enquiry): string
    {
        if (empty($enquiry->created_at)) {
            return 'NA';
        }

        try {
            $createdAt = Carbon::parse((string) $enquiry->created_at)->startOfDay();
            $today = Carbon::now('Asia/Colombo')->startOfDay();
        } catch (\Throwable $exception) {
            return 'NA';
        }

        $days = max(0, (int) $createdAt->diffInDays($today, false));

        return match (true) {
            $days <= 15 => '<= 15 Days',
            $days <= 30 => '16-30 Days',
            $days <= 60 => '31-60 Days',
            default => '> 60 Days',
        };
    }

    private function professionAnalyticsLabel($profession): string
    {
        $normalized = strtolower(trim((string) $profession));

        return match ($normalized) {
            'salaried' => 'Salaried',
            'self_employed' => 'Self Employed',
            'other' => 'Other',
            'not_asked' => 'Not Asked',
            default => 'NA',
        };
    }

    private function customerTypeAnalyticsLabel($customerType): string
    {
        $normalized = strtolower(trim((string) $customerType));

        return match ($normalized) {
            'individual' => 'Individual',
            'corporate' => 'Corporate',
            default => 'NA',
        };
    }

    private function ageGroupBucket($dateOfBirth): string
    {
        if (empty($dateOfBirth)) {
            return 'NA';
        }

        try {
            $age = Carbon::parse((string) $dateOfBirth)->age;
        } catch (\Throwable $exception) {
            return 'NA';
        }

        return match (true) {
            $age <= 19 => '0 - 19',
            $age <= 25 => '20 - 25',
            $age <= 30 => '26 - 30',
            $age <= 35 => '31 - 35',
            $age <= 40 => '36 - 40',
            $age <= 45 => '41 - 45',
            $age <= 50 => '46 - 50',
            $age <= 55 => '51 - 55',
            $age <= 60 => '56 - 60',
            $age <= 65 => '61 - 65',
            $age <= 70 => '66 - 70',
            $age <= 75 => '71 - 75',
            $age <= 80 => '76 - 80',
            $age <= 85 => '81 - 85',
            $age <= 90 => '86 - 90',
            $age <= 95 => '91 - 95',
            $age <= 100 => '96 - 100',
            default => 'NA',
        };
    }

    private function exchangeDifferenceBucket($difference): string
    {
        if ($difference === null || trim((string) $difference) === '') {
            return 'NA';
        }

        $value = (float) $difference;

        return match (true) {
            $value < -10000 => '(-) <1000',
            $value <= -5001 => '(-)10000-5001',
            $value <= -1 => '(-)5000-1',
            $value <= 5000 => '0-5000',
            $value <= 10000 => '5001-10000',
            $value <= 20000 => '10001-20000',
            default => '>20000',
        };
    }

    private function districtAnalyticsLabel($district): string
    {
        return User::normalizeDistrictName($district) ?? 'NA';
    }

    private function provinceAnalyticsLabel(?string $district): string
    {
        if ($district === null || $district === 'NA') {
            return 'NA';
        }

        return User::provinceForDistrict($district) ?? 'NA';
    }

    private function analyticsMobileNumbers($value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('strval', $value)));
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return array_values(array_filter(array_map('strval', $decoded)));
        }

        return array_values(array_filter(array_map('trim', preg_split('/[,|]/', $raw) ?: [])));
    }

    private function formatAnalyticsExportDate($value): string
    {
        if (empty($value)) {
            return '';
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable $exception) {
            return '';
        }
    }

    private function formatAnalyticsExportNumber($value): string
    {
        if ($value === null || trim((string) $value) === '') {
            return '';
        }

        return number_format((float) $value, 2, '.', '');
    }

    private function formatLostBucketRows(array $groups, int $totalLost, array $order): array
    {
        return array_map(
            fn(string $label): array => [
                'label' => $label,
                'lost_leads' => (int) ($groups[$label] ?? 0),
                'contribution' => $totalLost > 0 ? round(((int) ($groups[$label] ?? 0) / $totalLost) * 100, 2) : 0,
            ],
            $order
        );
    }

    private function formatLostAggregateRows(array $groups, int $totalLost, int $limit): array
    {
        arsort($groups);

        return array_values(array_map(
            fn(string $label, int $count): array => [
                'label' => $label,
                'lost_leads' => $count,
                'contribution' => $totalLost > 0 ? round(($count / $totalLost) * 100, 2) : 0,
            ],
            array_keys(array_slice($groups, 0, $limit, true)),
            array_values(array_slice($groups, 0, $limit, true))
        ));
    }

    private function formatLostMonthRows(array $groups, array $monthOrder, int $totalLost): array
    {
        ksort($groups);

        return array_values(array_map(
            fn(string $monthKey, int $count): array => [
                'label' => $monthOrder[$monthKey] ?? $monthKey,
                'lost_leads' => $count,
                'contribution' => $totalLost > 0 ? round(($count / $totalLost) * 100, 2) : 0,
            ],
            array_keys($groups),
            array_values($groups)
        ));
    }

    private function formatLostRejectReasons($reasons, ?string $otherText): array
    {
        $reasonLabels = [
            'issue_with_product' => 'Issue with product',
            'got_better_discount' => 'Got better discount',
            'other' => trim((string) $otherText) !== '' ? trim((string) $otherText) : 'Other',
        ];

        if (!is_array($reasons)) {
            $reasons = [];
        }

        return array_values(array_filter(array_map(
            fn($reason): string => $reasonLabels[(string) $reason] ?? $this->displayAnalyticsLabel($reason, ''),
            $reasons
        )));
    }

    private function buildClosedAnalytics(Collection $enquiries, Collection $usersById): array
    {
        $closedRows = $enquiries
            ->filter(fn(Enquiry $enquiry): bool => $this->normalizeLeadResult($enquiry->followup_result) === 'closed')
            ->values();
        $totalClosed = $closedRows->count();
        $totalEnquired = $enquiries->count();

        $groups = [
            'days_to_closed' => [],
            'followup_count' => [],
            'call_followup_count' => [],
            'successful_call_followup_count' => [],
            'successful_home_visit_count' => [],
            'successful_showroom_visit_count' => [],
            'last_followup_discipline' => [],
            'delayed_followup_count' => [],
            'closed_last_spoken_interval' => [],
            'closed_followup_type' => [],
            'lead_aging' => [],
            'competition_model' => [],
            'exchange_value_difference' => [],
            'month_closed' => [],
            'model' => [],
            'area_manager' => [],
            'sales_consultant' => [],
            'city' => [],
            'source' => [],
            'lead_state' => [],
            'reason' => [],
        ];
        $closedMonthOrder = [];
        $closedEnquiryIds = $closedRows
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->filter()
            ->values()
            ->all();
        $attemptsByEnquiryId = empty($closedEnquiryIds)
            ? collect()
            : FollowupAttempt::query()
                ->whereIn('enquiry_id', $closedEnquiryIds)
                ->orderBy('attempted_at')
                ->get(['enquiry_id', 'follow_type', 'followup_status', 'attempted_at'])
                ->groupBy(fn(FollowupAttempt $attempt): int => (int) $attempt->enquiry_id);

        foreach ($closedRows as $enquiry) {
            $createdAt = $this->analyticsDate($enquiry->created_at);
            $monthKey = $createdAt->format('Y-m');
            $closedMonthOrder[$monthKey] = $createdAt->format('M Y');

            $this->addClosedAggregate($groups['month_closed'], $monthKey);
            $this->addClosedAggregate($groups['model'], $this->displayAnalyticsLabel($enquiry->vehicle_model, 'Not specified'));
            $this->addClosedAggregate($groups['city'], $this->displayAnalyticsLabel($enquiry->customer_location ?: $enquiry->customer_district, 'Not specified'));
            $this->addClosedAggregate($groups['source'], $this->displayAnalyticsLabel($enquiry->lead_source, 'Not specified'));
            $this->addClosedAggregate($groups['lead_state'], $this->displayAnalyticsLabel($enquiry->prospect_lead_status ?: $enquiry->followup_lead_temperature, 'Not specified'));
            $this->addClosedAggregate($groups['reason'], $this->displayAnalyticsLabel($enquiry->followup_customer_comment, 'Not specified'));

            $owner = $enquiry->user_id ? $usersById->get((int) $enquiry->user_id) : null;
            $this->addClosedAggregate($groups['sales_consultant'], $owner instanceof User ? $owner->name : 'Unassigned');
            $areaManager = $this->resolveAreaManagerForAnalytics($owner, $usersById);
            $this->addClosedAggregate($groups['area_manager'], $areaManager instanceof User ? $areaManager->name : 'Unassigned');

            $attempts = $attemptsByEnquiryId->get((int) $enquiry->id, collect());
            $attemptCount = $attempts->count();
            if ($attemptCount === 0 && !empty($enquiry->followup_marked_at)) {
                $attemptCount = 1;
            }

            $callAttemptCount = $attempts
                ->filter(fn(FollowupAttempt $attempt): bool => $this->normalizeFollowupType($attempt->follow_type) === 'Call')
                ->count();
            $successfulCallAttemptCount = $attempts
                ->filter(fn(FollowupAttempt $attempt): bool => $this->normalizeFollowupType($attempt->follow_type) === 'Call'
                    && strtolower(trim((string) $attempt->followup_status)) === 'done')
                ->count();
            $successfulHomeVisitCount = $attempts
                ->filter(fn(FollowupAttempt $attempt): bool => $this->normalizeFollowupType($attempt->follow_type) === 'Home visit'
                    && strtolower(trim((string) $attempt->followup_status)) === 'done')
                ->count();
            $successfulShowroomVisitCount = $attempts
                ->filter(fn(FollowupAttempt $attempt): bool => $this->normalizeFollowupType($attempt->follow_type) === 'Showroom visit'
                    && strtolower(trim((string) $attempt->followup_status)) === 'done')
                ->count();

            if ($attempts->isEmpty() && $this->normalizeFollowupType($enquiry->follow_type) === 'Call' && !empty($enquiry->followup_marked_at)) {
                $callAttemptCount = 1;
                $successfulCallAttemptCount = strtolower(trim((string) $enquiry->followup_status)) === 'done' ? 1 : 0;
            }
            if ($attempts->isEmpty() && $this->normalizeFollowupType($enquiry->follow_type) === 'Home visit' && !empty($enquiry->followup_marked_at)) {
                $successfulHomeVisitCount = strtolower(trim((string) $enquiry->followup_status)) === 'done' ? 1 : 0;
            }
            if ($attempts->isEmpty() && $this->normalizeFollowupType($enquiry->follow_type) === 'Showroom visit' && !empty($enquiry->followup_marked_at)) {
                $successfulShowroomVisitCount = strtolower(trim((string) $enquiry->followup_status)) === 'done' ? 1 : 0;
            }

            $this->addClosedAggregate($groups['days_to_closed'], $this->daysToClosedBucket($enquiry));
            $this->addClosedAggregate($groups['followup_count'], $this->followupCountBucket($attemptCount));
            $this->addClosedAggregate($groups['call_followup_count'], $this->followupCountBucket($callAttemptCount));
            $this->addClosedAggregate($groups['successful_call_followup_count'], $this->followupCountBucket($successfulCallAttemptCount, true));
            $this->addClosedAggregate($groups['successful_home_visit_count'], $this->visitCountBucket($successfulHomeVisitCount));
            $this->addClosedAggregate($groups['successful_showroom_visit_count'], $this->visitCountBucket($successfulShowroomVisitCount));
            $this->addClosedAggregate($groups['last_followup_discipline'], $this->lastFollowupDisciplineBucket($enquiry, $attempts));
            $this->addClosedAggregate($groups['delayed_followup_count'], $this->delayedFollowupCountBucket($enquiry, $attempts));
            $this->addClosedAggregate($groups['closed_last_spoken_interval'], $this->closedLastSpokenIntervalBucket($enquiry, $attempts));
            $this->addClosedAggregate($groups['closed_followup_type'], $this->closedFollowupTypeLabel($enquiry));
            $this->addClosedAggregate($groups['lead_aging'], $this->leadAgingBucket($enquiry));
            $this->addClosedAggregate($groups['competition_model'], $this->displayAnalyticsLabel($enquiry->prospect_competition_model ?? null, 'NA'));
            $this->addClosedAggregate($groups['exchange_value_difference'], $this->exchangeDifferenceBucket($enquiry->prospect_exchange_price_difference ?? null));
        }

        $chartTabs = [
            ['key' => 'month_closed', 'label' => 'Month Wise - Closed', 'title' => 'Month Wise - Closed', 'metric' => 'closed_leads', 'rows' => $this->formatClosedMonthRows($groups['month_closed'], $closedMonthOrder, $totalClosed)],
            ['key' => 'model', 'label' => 'Model Wise', 'title' => 'Model Wise', 'metric' => 'closed_leads', 'rows' => $this->formatClosedAggregateRows($groups['model'], $totalClosed, 12)],
            ['key' => 'area_manager', 'label' => 'Area Manager Wise', 'title' => 'Area Manager Wise', 'metric' => 'closed_leads', 'rows' => $this->formatClosedAggregateRows($groups['area_manager'], $totalClosed, 12)],
            ['key' => 'sales_consultant', 'label' => 'Sales Consultant Wise', 'title' => 'Sales Consultant Wise', 'metric' => 'closed_leads', 'rows' => $this->formatClosedAggregateRows($groups['sales_consultant'], $totalClosed, 12)],
            ['key' => 'city', 'label' => 'City Wise', 'title' => 'City Wise', 'metric' => 'closed_leads', 'rows' => $this->formatClosedAggregateRows($groups['city'], $totalClosed, 12)],
            ['key' => 'source', 'label' => 'Source Wise', 'title' => 'Source Wise', 'metric' => 'closed_leads', 'rows' => $this->formatClosedAggregateRows($groups['source'], $totalClosed, 12)],
            ['key' => 'lead_state', 'label' => 'Lead State Wise', 'title' => 'Lead State Wise', 'metric' => 'closed_leads', 'rows' => $this->formatClosedAggregateRows($groups['lead_state'], $totalClosed, 12)],
            ['key' => 'reason', 'label' => 'Reason Wise', 'title' => 'Reason Wise', 'metric' => 'closed_leads', 'rows' => $this->formatClosedAggregateRows($groups['reason'], $totalClosed, 12)],
            ['key' => 'month_enquired', 'label' => 'Month Wise - Enquired', 'title' => 'Month Wise - Enquired', 'metric' => 'enquired_leads', 'total' => $totalEnquired, 'rows' => $this->formatClosedEnquiredMonthRows($enquiries, $totalEnquired)],
        ];

        return [
            'total' => $totalClosed,
            'tabs' => $chartTabs,
            'export_tabs' => [
                ['key' => 'days_to_closed', 'label' => 'Days To Closed', 'title' => 'No of days from date of Inquiry to date of Closed', 'export_label' => 'No_of_days_from_date_of_inquiry_to_date_of_Closed', 'rows' => $this->formatClosedBucketRows($groups['days_to_closed'], $totalClosed, ['1 day', '2-3 days', '4-6 days', '7-10 days', '11-15 days', '16-20 days', '>20 days', 'NA'])],
                ['key' => 'followup_count', 'label' => 'No of Follow Ups', 'title' => 'No of Follow Ups', 'export_label' => 'No_of_Follow_Ups', 'rows' => $this->formatClosedBucketRows($groups['followup_count'], $totalClosed, ['0', '1', '2', '3', '4', '5', '6-9', '>= 10'])],
                ['key' => 'call_followup_count', 'label' => 'No of Call Follow Ups', 'title' => 'No of Call Follow Ups', 'export_label' => 'No_of_Call_FollowUps', 'rows' => $this->formatClosedBucketRows($groups['call_followup_count'], $totalClosed, ['0', '1', '2', '3', '4', '5', '6-9', '>= 10'])],
                ['key' => 'successful_call_followup_count', 'label' => 'No of Successful call follow ups', 'title' => 'No of Successful call follow ups', 'export_label' => 'No_Of_Successful_Call_Followups', 'rows' => $this->formatClosedBucketRows($groups['successful_call_followup_count'], $totalClosed, ['0', '1', '2', '3', '4', '5', '>5', '>= 10'])],
                ['key' => 'successful_home_visit_count', 'label' => 'No of Successful Home Visits', 'title' => 'No of Successful Home Visits', 'export_label' => 'No_Suc_Home_Visits', 'rows' => $this->formatClosedBucketRows($groups['successful_home_visit_count'], $totalClosed, ['0', '1', '2', '>2'])],
                ['key' => 'successful_showroom_visit_count', 'label' => 'No of Successful Showroom Visits', 'title' => 'No of Successful Showroom Visits', 'export_label' => 'No_Of_Suc_Showroom_Visits', 'rows' => $this->formatClosedBucketRows($groups['successful_showroom_visit_count'], $totalClosed, ['0', '1', '2', '>2'])],
                ['key' => 'last_followup_discipline', 'label' => 'Last Follow Up Done', 'title' => 'Last Follow Up Done', 'export_label' => 'Last_Followup_Done', 'rows' => $this->formatClosedBucketRows($groups['last_followup_discipline'], $totalClosed, ['On Time', '1 day delay', '2 days delay', '3 days delay', '>3 days delay', 'No Follow Ups'])],
                ['key' => 'delayed_followup_count', 'label' => 'No of Delayed Followups', 'title' => 'No of Delayed Followups', 'export_label' => 'No_of_Delayed_Followups', 'rows' => $this->formatClosedBucketRows($groups['delayed_followup_count'], $totalClosed, ['0', '1', '2', '3', '4', '5', '>5', 'No Follow Up Done'])],
                ['key' => 'closed_last_spoken_interval', 'label' => 'No of Days Between Closed and Last Spoken', 'title' => 'No of Days Between Closed and Last Spoken', 'export_label' => 'Time_Interval', 'rows' => $this->formatClosedBucketRows($groups['closed_last_spoken_interval'], $totalClosed, ['Never Spoken', '<5 days', '5-7 Days', '8-10 Days', '11-15 Days', '16-20 Days', '21-30 Days', '>30 Days', 'NA'])],
                ['key' => 'closed_followup_type', 'label' => 'Lead Closed-Follow Up Type', 'title' => 'Lead Closed-Follow Up Type', 'export_label' => 'Lead_Closed_Follow_Up_Type', 'rows' => $this->formatClosedBucketRows($groups['closed_followup_type'], $totalClosed, ['Call', 'Home visit', 'Dealer Visit', 'NA'])],
                ['key' => 'lead_aging', 'label' => 'Aging of leads', 'title' => 'Aging of leads', 'export_label' => 'aging', 'rows' => $this->formatClosedBucketRows($groups['lead_aging'], $totalClosed, ['<= 15 Days', '16-30 Days', '31-60 Days', '> 60 Days', 'NA'])],
                ['key' => 'competition_model', 'label' => 'Interested In Competition Model', 'title' => 'Interested In Competition Model', 'export_label' => 'lead_interested_competition_model_name', 'rows' => $this->formatClosedAggregateRows($groups['competition_model'], $totalClosed, 50)],
                ['key' => 'exchange_value_difference', 'label' => 'Difference In Exchange Value', 'title' => 'Difference In Exchange Value', 'export_label' => 'exchange_groups', 'rows' => $this->formatClosedBucketRows($groups['exchange_value_difference'], $totalClosed, ['(-) <1000', '(-)10000-5001', '(-)5000-1', '0-5000', '5001-10000', '10001-20000', '>20000', 'NA'])],
                ['key' => 'customer_city', 'label' => 'Customer City Wise', 'title' => 'Customer City Wise', 'export_label' => 'customer_city_name', 'columns' => $this->closedComparisonColumns('customer_city_name'), 'rows' => $this->formatClosedComparisonRows($groups['city'], 50), 'total_row' => $this->closedComparisonTotalRow($totalClosed)],
                ['key' => 'area_manager', 'label' => 'Area Manager Wise', 'title' => 'Area Manager Wise', 'export_label' => 'area_manager_name', 'columns' => $this->closedComparisonColumns('area_manager_name'), 'rows' => $this->formatClosedComparisonRows($groups['area_manager'], 50), 'total_row' => $this->closedComparisonTotalRow($totalClosed)],
                ['key' => 'sc', 'label' => 'SC Wise', 'title' => 'SC Wise', 'export_label' => 'assigned_to_name', 'columns' => $this->closedComparisonColumns('assigned_to_name'), 'rows' => $this->formatClosedComparisonRows($groups['sales_consultant'], 50), 'total_row' => $this->closedComparisonTotalRow($totalClosed)],
            ],
        ];
    }

    private function addClosedAggregate(array &$groups, ?string $label): void
    {
        $label = $this->displayAnalyticsLabel($label, 'Not specified');
        $groups[$label] = ($groups[$label] ?? 0) + 1;
    }

    private function formatClosedAggregateRows(array $groups, int $totalClosed, int $limit): array
    {
        arsort($groups);

        return array_values(array_map(
            fn(string $label, int $count): array => [
                'label' => $label,
                'closed_leads' => $count,
                'contribution' => $totalClosed > 0 ? round(($count / $totalClosed) * 100, 2) : 0,
            ],
            array_keys(array_slice($groups, 0, $limit, true)),
            array_values(array_slice($groups, 0, $limit, true))
        ));
    }

    private function formatClosedMonthRows(array $groups, array $monthOrder, int $totalClosed): array
    {
        ksort($groups);

        return array_values(array_map(
            fn(string $monthKey, int $count): array => [
                'label' => $monthOrder[$monthKey] ?? $monthKey,
                'closed_leads' => $count,
                'contribution' => $totalClosed > 0 ? round(($count / $totalClosed) * 100, 2) : 0,
            ],
            array_keys($groups),
            array_values($groups)
        ));
    }

    private function formatClosedBucketRows(array $groups, int $totalClosed, array $order): array
    {
        return array_map(
            fn(string $label): array => [
                'label' => $label,
                'closed_leads' => (int) ($groups[$label] ?? 0),
                'contribution' => $totalClosed > 0 ? round(((int) ($groups[$label] ?? 0) / $totalClosed) * 100, 2) : 0,
            ],
            $order
        );
    }

    private function closedComparisonColumns(string $firstColumn): array
    {
        return [
            ['key' => 'label', 'heading' => $firstColumn],
            ['key' => 'count', 'heading' => 'Count'],
            ['key' => 'lmtd_percentage', 'heading' => 'LMTD_Percentage'],
            ['key' => 'lmtd', 'heading' => 'LMTD'],
            ['key' => 'lymtd_percentage', 'heading' => 'LYMTD_Percentage'],
            ['key' => 'lymtd', 'heading' => 'LYMTD'],
        ];
    }

    private function formatClosedComparisonRows(array $groups, int $limit): array
    {
        arsort($groups);

        return array_values(array_map(
            fn(string $label, int $count): array => [
                'label' => $label,
                'count' => $count,
                'lmtd_percentage' => 'NA',
                'lmtd' => 'NA',
                'lymtd_percentage' => 'NA',
                'lymtd' => 'NA',
            ],
            array_keys(array_slice($groups, 0, $limit, true)),
            array_values(array_slice($groups, 0, $limit, true))
        ));
    }

    private function closedComparisonTotalRow(int $totalClosed): array
    {
        return [
            'label' => 'Total',
            'count' => $totalClosed,
            'lmtd_percentage' => 'NA',
            'lmtd' => 'NA',
            'lymtd_percentage' => 'NA',
            'lymtd' => 'NA',
        ];
    }

    private function formatClosedEnquiredMonthRows(Collection $enquiries, int $totalEnquired): array
    {
        $months = [];

        foreach ($enquiries as $enquiry) {
            $createdAt = $this->analyticsDate($enquiry->created_at);
            $monthKey = $createdAt->format('Y-m');
            $weekKey = 'wk' . $createdAt->weekOfMonth;
            $dateKey = $createdAt->format('Y-m-d');

            if (!isset($months[$monthKey])) {
                $months[$monthKey] = [
                    'label' => $createdAt->format('M Y'),
                    'count' => 0,
                    'weeks' => [],
                ];
            }

            if (!isset($months[$monthKey]['weeks'][$weekKey])) {
                $months[$monthKey]['weeks'][$weekKey] = [
                    'label' => $weekKey,
                    'count' => 0,
                    'dates' => [],
                ];
            }

            $months[$monthKey]['count']++;
            $months[$monthKey]['weeks'][$weekKey]['count']++;
            $months[$monthKey]['weeks'][$weekKey]['dates'][$dateKey] = ($months[$monthKey]['weeks'][$weekKey]['dates'][$dateKey] ?? 0) + 1;
        }

        ksort($months);

        return array_values(array_map(function (array $month) use ($totalEnquired): array {
            ksort($month['weeks']);
            $weekRows = array_values(array_map(function (array $week) use ($month): array {
                ksort($week['dates']);
                $dateRows = array_values(array_map(
                    fn(string $date, int $count): array => [
                        'label' => $date,
                        'enquired_leads' => $count,
                        'contribution' => $week['count'] > 0 ? round(($count / $week['count']) * 100, 2) : 0,
                    ],
                    array_keys($week['dates']),
                    array_values($week['dates'])
                ));

                return [
                    'label' => $week['label'],
                    'enquired_leads' => $week['count'],
                    'contribution' => $month['count'] > 0 ? round(($week['count'] / $month['count']) * 100, 2) : 0,
                    'drilldown' => [
                        'title' => 'Date Wise - Enquired',
                        'metric' => 'enquired_leads',
                        'total' => $week['count'],
                        'rows' => $dateRows,
                    ],
                ];
            }, $month['weeks']));

            return [
                'label' => $month['label'],
                'enquired_leads' => $month['count'],
                'contribution' => $totalEnquired > 0 ? round(($month['count'] / $totalEnquired) * 100, 2) : 0,
                'drilldown' => [
                    'title' => 'Week Wise - Enquired',
                    'metric' => 'enquired_leads',
                    'total' => $month['count'],
                    'rows' => $weekRows,
                ],
            ];
        }, $months));
    }

    private function analyticsDate($value): Carbon
    {
        return $value instanceof Carbon ? $value : Carbon::parse($value);
    }

    private function displayAnalyticsLabel($value, string $fallback): string
    {
        $label = trim((string) $value);
        if ($label === '') {
            return $fallback;
        }

        return ucwords(str_replace('_', ' ', $label));
    }

    private function resolveAreaManagerForAnalytics(?User $owner, Collection $usersById): ?User
    {
        if (!$owner instanceof User) {
            return null;
        }

        if ($owner->role === User::ROLE_AREA_MANAGER) {
            return $owner;
        }

        $manager = $owner->manager_id ? $usersById->get((int) $owner->manager_id) : null;
        if ($manager instanceof User && $manager->role === User::ROLE_AREA_MANAGER) {
            return $manager;
        }

        return null;
    }

    private function resolveAccessibleUserIds(User $viewer): array
    {
        if ($viewer->role === User::ROLE_SUPER_ADMIN) {
            return User::query()
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->values()
                ->all();
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

    private function filterUsersForViewerHierarchy(User $viewer, Collection $users): Collection
    {
        $nonSuperUsers = $users
            ->filter(fn(User $user): bool => $user->role !== User::ROLE_SUPER_ADMIN)
            ->values();

        return match ($viewer->role) {
            User::ROLE_AREA_MANAGER => $nonSuperUsers
                ->filter(fn(User $user): bool => $user->role === User::ROLE_SALES_CONSULTANT)
                ->values(),
            User::ROLE_SALES_CONSULTANT => $nonSuperUsers
                ->filter(fn(User $user): bool => (int) $user->id === (int) $viewer->id)
                ->values(),
            default => $nonSuperUsers,
        };
    }

    private function normalizeLeadResult($value): ?string
    {
        $normalized = strtolower(trim((string) $value));
        return in_array($normalized, ['active', 'lost', 'closed'], true) ? $normalized : null;
    }

    private function normalizeLeadTemperature($value): ?string
    {
        $normalized = strtolower(trim((string) $value));
        return in_array($normalized, ['hot', 'warm', 'cold'], true) ? $normalized : null;
    }

    private function normalizeFollowupType($value): ?string
    {
        $normalized = strtolower(trim((string) $value));
        if ($normalized === '') {
            return null;
        }

        if (str_contains($normalized, 'home')) {
            return 'Home visit';
        }

        if (str_contains($normalized, 'showroom')) {
            return 'Showroom visit';
        }

        if (str_contains($normalized, 'call')) {
            return 'Call';
        }

        return null;
    }

    private function emptyAnalyticsStats(): array
    {
        return [
            'total' => 0,
            'active' => 0,
            'lost' => 0,
            'closed' => 0,
            'hot' => 0,
            'warm' => 0,
            'cold' => 0,
            'pending' => 0,
            'done' => 0,
        ];
    }

    private function parseFilterDate(string $value, bool $startOfDay): ?Carbon
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        try {
            $date = Carbon::parse($trimmed);
        } catch (\Throwable $exception) {
            return null;
        }

        return $startOfDay ? $date->startOfDay() : $date->endOfDay();
    }
}
