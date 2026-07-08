<?php

namespace App\Http\Controllers;

use App\Models\Enquiry;
use App\Models\LeadTransferRequest;
use App\Models\User;
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
                'enquiries.followup_status',
                'enquiries.followup_customer_comment',
                'enquiries.followup_lost_to',
                'enquiries.followup_lost_competition_brand',
                'enquiries.followup_lost_competition_model',
                'enquiries.followup_lost_codealer_name',
                'enquiries.followup_lost_reject_reasons',
                'enquiries.followup_lost_reject_other_text',
                'enquiries.created_at',
                'customers.district as customer_district',
                'customers.location as customer_location',
                'vehicles.model as vehicle_model',
                'prospect_sheets.lead_status as prospect_lead_status',
                'prospect_sheets.current_step as prospect_current_step',
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
        foreach ($districtTotals as $district => $total) {
            $districtRows[] = [
                'district' => (string) $district,
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
            ],
            'lost_analytics' => $lostAnalytics,
            'closed_analytics' => $closedAnalytics,
            'active_analytics' => $activeAnalytics,
            'booking_analytics' => $bookingAnalytics,
            'by_user' => $byUser,
            'by_role' => $byRoleRows,
            'by_district' => $districtRows,
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
        ];
        $monthOrder = [];

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
        }

        return [
            'total' => $totalActive,
            'tabs' => [
                ['key' => 'registration', 'label' => 'EPR Vs Registered', 'title' => 'EPR Vs Registered', 'metric' => 'count', 'rows' => $this->formatActiveRegistrationRows($groups['registration'], $totalActive)],
                ['key' => 'month', 'label' => 'Month Wise', 'title' => 'Month Wise', 'metric' => 'count', 'rows' => $this->formatActiveMonthRows($groups['month'], $monthOrder, $totalActive)],
                ['key' => 'model', 'label' => 'Model Wise', 'title' => 'Model Wise', 'metric' => 'count', 'rows' => $this->formatActiveAggregateRows($groups['model'], $totalActive, 12)],
                ['key' => 'lead_source', 'label' => 'Lead Source Wise', 'title' => 'Lead Source Wise', 'metric' => 'count', 'rows' => $this->formatActiveAggregateRows($groups['lead_source'], $totalActive, 12)],
                ['key' => 'source_information', 'label' => 'Source Of Information Wise', 'title' => 'Source Of Information Wise', 'metric' => 'count', 'rows' => $this->formatActiveAggregateRows($groups['source_information'], $totalActive, 12)],
                ['key' => 'sales_consultant', 'label' => 'Sales Consultant Wise', 'title' => 'Sales Consultant Wise', 'metric' => 'count', 'rows' => $this->formatActiveAggregateRows($groups['sales_consultant'], $totalActive, 12)],
                ['key' => 'area_manager', 'label' => 'Area Manager Wise', 'title' => 'Area Manager Wise', 'metric' => 'count', 'rows' => $this->formatActiveAggregateRows($groups['area_manager'], $totalActive, 12)],
                ['key' => 'lead_state', 'label' => 'Lead State Wise', 'title' => 'Lead State Wise', 'metric' => 'count', 'rows' => $this->formatActiveAggregateRows($groups['lead_state'], $totalActive, 12)],
            ],
        ];
    }

    private function addActiveAggregate(array &$groups, ?string $label): void
    {
        $label = $this->displayAnalyticsLabel($label, 'Not specified');
        $groups[$label] = ($groups[$label] ?? 0) + 1;
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
        ];

        $monthOrder = [];

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
            ],
        ];
    }

    private function addLostAggregate(array &$groups, ?string $label): void
    {
        $label = $this->displayAnalyticsLabel($label, 'Not specified');
        $groups[$label] = ($groups[$label] ?? 0) + 1;
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
        }

        return [
            'total' => $totalClosed,
            'tabs' => [
                ['key' => 'month_closed', 'label' => 'Month Wise - Closed', 'title' => 'Month Wise - Closed', 'metric' => 'closed_leads', 'rows' => $this->formatClosedMonthRows($groups['month_closed'], $closedMonthOrder, $totalClosed)],
                ['key' => 'model', 'label' => 'Model Wise', 'title' => 'Model Wise', 'metric' => 'closed_leads', 'rows' => $this->formatClosedAggregateRows($groups['model'], $totalClosed, 12)],
                ['key' => 'area_manager', 'label' => 'Area Manager Wise', 'title' => 'Area Manager Wise', 'metric' => 'closed_leads', 'rows' => $this->formatClosedAggregateRows($groups['area_manager'], $totalClosed, 12)],
                ['key' => 'sales_consultant', 'label' => 'Sales Consultant Wise', 'title' => 'Sales Consultant Wise', 'metric' => 'closed_leads', 'rows' => $this->formatClosedAggregateRows($groups['sales_consultant'], $totalClosed, 12)],
                ['key' => 'city', 'label' => 'City Wise', 'title' => 'City Wise', 'metric' => 'closed_leads', 'rows' => $this->formatClosedAggregateRows($groups['city'], $totalClosed, 12)],
                ['key' => 'source', 'label' => 'Source Wise', 'title' => 'Source Wise', 'metric' => 'closed_leads', 'rows' => $this->formatClosedAggregateRows($groups['source'], $totalClosed, 12)],
                ['key' => 'lead_state', 'label' => 'Lead State Wise', 'title' => 'Lead State Wise', 'metric' => 'closed_leads', 'rows' => $this->formatClosedAggregateRows($groups['lead_state'], $totalClosed, 12)],
                ['key' => 'reason', 'label' => 'Reason Wise', 'title' => 'Reason Wise', 'metric' => 'closed_leads', 'rows' => $this->formatClosedAggregateRows($groups['reason'], $totalClosed, 12)],
                ['key' => 'month_enquired', 'label' => 'Month Wise - Enquired', 'title' => 'Month Wise - Enquired', 'metric' => 'enquired_leads', 'total' => $totalEnquired, 'rows' => $this->formatClosedEnquiredMonthRows($enquiries, $totalEnquired)],
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
