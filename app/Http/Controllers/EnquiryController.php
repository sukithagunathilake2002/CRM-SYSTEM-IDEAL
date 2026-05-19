<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Enquiry;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EnquiryController extends Controller
{
    /**
     * Load New Enquiry Page
     */
    public function create(Request $request)
    {
        $viewer = $request->user();
        $models = Vehicle::select('model')->distinct()->get();
        $districtOptions = $viewer instanceof User
            ? $viewer->resolvePermittedDistricts()
            : User::DISTRICT_OPTIONS;

        return view('new-enquiry', compact('models', 'districtOptions'));
    }

    /**
     * Fetch Engine Types for selected Model
     */
    public function getEngines($model)
    {
        return Vehicle::where('model', $model)
            ->select('engine_type')
            ->distinct()
            ->get();
    }

    /**
     * Fetch Variants for selected Model + Engine
     */
    public function getVariants($model, $engine)
    {
        return Vehicle::where('model', $model)
            ->where('engine_type', $engine)
            ->select('variant')
            ->distinct()
            ->get();
    }

    /**
     * Store Customer + Enquiry in ERP tables
     */
    public function store(Request $request)
    {
        $viewer = $request->user();
        $permittedDistricts = $viewer instanceof User
            ? $viewer->resolvePermittedDistricts()
            : User::DISTRICT_OPTIONS;

        $request->validate([
            'model' => ['required', 'string'],
            'engine' => ['required', 'string'],
            'variant' => ['required', 'string'],
            'title' => ['nullable', 'string', 'max:10'],
            'name' => ['required', 'string', 'max:150'],
            'mobiles' => ['required', 'array', 'min:1'],
            'mobiles.*' => ['nullable', 'string', 'max:30'],
            'province' => ['nullable', 'string', Rule::in(array_keys(User::PROVINCE_DISTRICT_MAP))],
            'district' => ['nullable', 'string', 'max:100', Rule::in($permittedDistricts)],
            'location' => ['nullable', 'string', 'max:150'],
            'state' => ['nullable', 'string', 'max:100'],
            'address1' => ['nullable', 'string', 'max:255'],
            'address2' => ['nullable', 'string', 'max:255'],
            'lead_source' => ['nullable', 'string', 'max:100'],
            'follow_type' => ['nullable', 'string', 'max:100'],
            'follow_date' => ['nullable', 'date'],
            'follow_time' => ['nullable', 'date_format:H:i'],
        ]);

        $latitude = is_numeric($request->input('latitude'))
            ? (float) $request->input('latitude')
            : null;
        $longitude = is_numeric($request->input('longitude'))
            ? (float) $request->input('longitude')
            : null;
        $mobileNumbers = collect($request->input('mobiles', []))
            ->map(fn($mobile) => trim((string) $mobile))
            ->filter()
            ->values()
            ->all();

        if (count($mobileNumbers) === 0) {
            return back()
                ->withErrors(['mobiles' => 'At least one contact number is required.'])
                ->withInput();
        }

        $district = trim((string) $request->input('district', ''));
        $selectedProvince = trim((string) $request->input('province', ''));
        if ($district === '') {
            $district = 'N/A';
        } else {
            $normalizedDistrict = User::normalizeDistrictName($district);
            if ($normalizedDistrict === null || !in_array($normalizedDistrict, $permittedDistricts, true)) {
                return back()
                    ->withErrors(['district' => 'Please select a permitted district.'])
                    ->withInput();
            }

            if ($selectedProvince === '') {
                return back()
                    ->withErrors(['province' => 'Please select a province first.'])
                    ->withInput();
            }

            $districtProvince = User::provinceForDistrict($normalizedDistrict);
            if ($districtProvince !== $selectedProvince) {
                return back()
                    ->withErrors(['district' => 'Selected district does not belong to selected province.'])
                    ->withInput();
            }
            $district = $normalizedDistrict;
        }

        $location = trim((string) $request->input('location', ''));
        if ($location === '') {
            $location = ($latitude !== null && $longitude !== null) ? 'GPS Captured' : $district;
        }

        $locationCapturedAt = null;
        if ($request->filled('location_captured_at')) {
            try {
                $locationCapturedAt = Carbon::parse($request->input('location_captured_at'));
            } catch (\Throwable $e) {
                $locationCapturedAt = null;
            }
        }

        $vehicle = Vehicle::where('model', $request->model)
            ->where('engine_type', $request->engine)
            ->where('variant', $request->variant)
            ->first();

        if (!$vehicle) {
            return back()->with('error', 'Invalid vehicle selection');
        }

        $ownerUserId = $request->user()?->id;

        DB::transaction(function () use ($request, $vehicle, $mobileNumbers, $district, $location, $latitude, $longitude, $locationCapturedAt, $ownerUserId) {
            $customer = Customer::create([
                'title' => $request->title,
                'name' => trim((string) $request->name),
                'mobile_numbers' => $mobileNumbers,
                'district' => $district,
                'location' => $location,
                'state' => $request->filled('state') ? $request->state : null,
                'address1' => $request->filled('address1') ? $request->address1 : null,
                'address2' => $request->filled('address2') ? $request->address2 : null,
            ]);

            Enquiry::create([
                'user_id' => $ownerUserId,
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle->id,
                'lead_source' => $request->lead_source,
                'follow_type' => $request->follow_type,
                'follow_date' => $request->follow_date,
                'follow_time' => $request->follow_time,
                'followup_status' => 'pending',
                'exchange' => $request->exchange ? 1 : 0,
                'finance' => $request->finance ? 1 : 0,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'location_captured_at' => $locationCapturedAt,
                'status' => 'OPEN',
            ]);
        });

        return redirect()->back()->with('success', 'ERP Enquiry Saved Successfully');
    }

    public function list(Request $request)
    {
        $viewer = $request->user();
        $enquiriesQuery = Enquiry::with(['customer', 'vehicle', 'user']);

        if ($viewer && $viewer->role !== User::ROLE_SUPER_ADMIN) {
            $accessibleUserIds = $this->resolveAccessibleUserIds($viewer);

            // Non-super users can only view leads owned by users in their accessible hierarchy.
            $enquiriesQuery->whereIn('user_id', $accessibleUserIds);
        }

        $enquiries = $enquiriesQuery
            ->latest()
            ->get();

        return view('enquiries.index', compact('enquiries'));
    }

    public function map(Request $request)
    {
        $viewer = $request->user();
        $accessibleUserIds = $this->resolveAccessibleUserIds($viewer);
        $scopeUsers = User::query()
            ->whereIn('id', $accessibleUserIds)
            ->where('role', '!=', User::ROLE_SUPER_ADMIN)
            ->orderBy('name')
            ->get(['id', 'name', 'role']);
        $availableUsers = $this->filterUsersForViewerHierarchy($viewer, $scopeUsers);
        $availableUserIds = $availableUsers
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->values()
            ->all();

        $selectedUserIdInput = (string) $request->query('user_id', '');
        $selectedUserId = ctype_digit($selectedUserIdInput) ? (int) $selectedUserIdInput : null;
        if ($selectedUserId !== null && !in_array($selectedUserId, $availableUserIds, true)) {
            $selectedUserId = null;
        }

        $selectedDateInput = (string) $request->query('date', '');
        $selectedFromDateInput = (string) $request->query('from_date', '');
        $selectedToDateInput = (string) $request->query('to_date', '');

        $selectedFromDate = null;
        $selectedToDate = null;

        if (trim($selectedFromDateInput) !== '') {
            try {
                $selectedFromDate = Carbon::parse($selectedFromDateInput)->toDateString();
            } catch (\Throwable $e) {
                $selectedFromDate = null;
            }
        }

        if (trim($selectedToDateInput) !== '') {
            try {
                $selectedToDate = Carbon::parse($selectedToDateInput)->toDateString();
            } catch (\Throwable $e) {
                $selectedToDate = null;
            }
        }

        // Backward compatible support for old single-date map link/filter.
        if ($selectedFromDate === null && $selectedToDate === null && trim($selectedDateInput) !== '') {
            try {
                $legacyDate = Carbon::parse($selectedDateInput)->toDateString();
                $selectedFromDate = $legacyDate;
                $selectedToDate = $legacyDate;
            } catch (\Throwable $e) {
                // Ignore invalid legacy date and continue with defaults.
            }
        }

        if ($selectedFromDate === null && $selectedToDate === null) {
            $today = now()->toDateString();
            $selectedFromDate = $today;
            $selectedToDate = $today;
        }

        if ($selectedFromDate !== null && $selectedToDate !== null && $selectedFromDate > $selectedToDate) {
            [$selectedFromDate, $selectedToDate] = [$selectedToDate, $selectedFromDate];
        }

        $enquiriesQuery = Enquiry::with(['customer', 'vehicle', 'user'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        if ($selectedFromDate !== null && $selectedToDate !== null) {
            $enquiriesQuery->where(function ($query) use ($selectedFromDate, $selectedToDate) {
                $query->where(function ($located) use ($selectedFromDate, $selectedToDate) {
                    $located->whereNotNull('location_captured_at')
                        ->whereDate('location_captured_at', '>=', $selectedFromDate)
                        ->whereDate('location_captured_at', '<=', $selectedToDate);
                })->orWhere(function ($fallback) use ($selectedFromDate, $selectedToDate) {
                    $fallback->whereNull('location_captured_at')
                        ->whereDate('created_at', '>=', $selectedFromDate)
                        ->whereDate('created_at', '<=', $selectedToDate);
                });
            });
        } elseif ($selectedFromDate !== null) {
            $enquiriesQuery->where(function ($query) use ($selectedFromDate) {
                $query->where(function ($located) use ($selectedFromDate) {
                    $located->whereNotNull('location_captured_at')
                        ->whereDate('location_captured_at', '>=', $selectedFromDate);
                })->orWhere(function ($fallback) use ($selectedFromDate) {
                    $fallback->whereNull('location_captured_at')
                        ->whereDate('created_at', '>=', $selectedFromDate);
                });
            });
        } elseif ($selectedToDate !== null) {
            $enquiriesQuery->where(function ($query) use ($selectedToDate) {
                $query->where(function ($located) use ($selectedToDate) {
                    $located->whereNotNull('location_captured_at')
                        ->whereDate('location_captured_at', '<=', $selectedToDate);
                })->orWhere(function ($fallback) use ($selectedToDate) {
                    $fallback->whereNull('location_captured_at')
                        ->whereDate('created_at', '<=', $selectedToDate);
                });
            });
        }

        if ($viewer->role !== User::ROLE_SUPER_ADMIN) {
            $enquiriesQuery->whereIn('user_id', $accessibleUserIds);
        }

        $selectedHierarchyUserIds = null;
        if ($selectedUserId !== null) {
            $selectedUser = $availableUsers->firstWhere('id', $selectedUserId);
            if ($selectedUser instanceof User) {
                $selectedHierarchyUserIds = array_values(array_intersect(
                    $accessibleUserIds,
                    $this->resolveAccessibleUserIds($selectedUser)
                ));
            } else {
                $selectedHierarchyUserIds = [$selectedUserId];
            }

            if (!empty($selectedHierarchyUserIds)) {
                $enquiriesQuery->whereIn('user_id', $selectedHierarchyUserIds);
            } else {
                $enquiriesQuery->whereRaw('1 = 0');
            }
        }

        $enquiries = $enquiriesQuery
            ->orderByDesc('location_captured_at')
            ->orderByDesc('created_at')
            ->get();

        $mapPoints = $enquiries->map(function (Enquiry $enquiry) {
            $customer = $enquiry->customer;
            $vehicle = $enquiry->vehicle;
            $mobileNumbers = is_array($customer?->mobile_numbers)
                ? array_values(array_filter($customer->mobile_numbers))
                : [];
            $primaryPhone = count($mobileNumbers) ? (string) $mobileNumbers[0] : 'N/A';
            $capturedAt = $enquiry->location_captured_at ?: $enquiry->created_at;

            return [
                'id' => $enquiry->id,
                'name' => trim(($customer?->title ? $customer->title . ' ' : '') . ($customer?->name ?? 'Unknown')),
                'phone' => $primaryPhone,
                'vehicle' => trim(($vehicle?->model ?? '') . ' ' . ($vehicle?->variant ?? '')),
                'lat' => (float) $enquiry->latitude,
                'lng' => (float) $enquiry->longitude,
                'time' => $capturedAt ? Carbon::parse($capturedAt)->format('h:i A') : 'N/A',
                'captured_at_label' => $capturedAt ? Carbon::parse($capturedAt)->format('d M Y h:i A') : 'N/A',
                'location' => $customer?->location ?: 'N/A',
                'owner' => $enquiry->user?->name ?: 'Unassigned',
            ];
        })->values();

        if ($selectedFromDate !== null && $selectedToDate !== null) {
            $mapDateLabel = $selectedFromDate === $selectedToDate
                ? $selectedFromDate
                : $selectedFromDate . ' to ' . $selectedToDate;
            $listHeading = $selectedFromDate === $selectedToDate
                ? 'Enquiries on ' . $selectedFromDate
                : 'Enquiries from ' . $selectedFromDate . ' to ' . $selectedToDate;
        } elseif ($selectedFromDate !== null) {
            $mapDateLabel = 'From ' . $selectedFromDate;
            $listHeading = 'Enquiries from ' . $selectedFromDate . ' onwards';
        } else {
            $mapDateLabel = 'Up to ' . $selectedToDate;
            $listHeading = 'Enquiries up to ' . $selectedToDate;
        }

        $selectedFilterUser = $selectedUserId !== null
            ? $availableUsers->firstWhere('id', $selectedUserId)
            : null;
        $selectedFilterUserName = $selectedFilterUser instanceof User ? $selectedFilterUser->name : null;

        return view('enquiries.map', [
            'selectedDate' => $selectedToDate ?? $selectedFromDate ?? now()->toDateString(),
            'selectedFromDate' => $selectedFromDate,
            'selectedToDate' => $selectedToDate,
            'mapPoints' => $mapPoints,
            'selectedUserId' => $selectedUserId,
            'availableUsers' => $availableUsers,
            'mapDateLabel' => $mapDateLabel,
            'listHeading' => $listHeading,
            'selectedHierarchyCount' => is_array($selectedHierarchyUserIds) ? count($selectedHierarchyUserIds) : null,
            'selectedFilterUserName' => $selectedFilterUserName,
        ]);
    }

    private function filterUsersForViewerHierarchy(User $viewer, Collection $users): Collection
    {
        return match ($viewer->role) {
            User::ROLE_AREA_MANAGER => $users
                ->filter(fn(User $user): bool => $user->role === User::ROLE_SALES_CONSULTANT)
                ->values(),
            User::ROLE_REGIONAL_MANAGER => $users
                ->filter(fn(User $user): bool => in_array($user->role, [User::ROLE_AREA_MANAGER, User::ROLE_SALES_CONSULTANT], true))
                ->values(),
            User::ROLE_SALES_CONSULTANT => $users
                ->filter(fn(User $user): bool => (int) $user->id === (int) $viewer->id)
                ->values(),
            default => $users,
        };
    }

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
}
