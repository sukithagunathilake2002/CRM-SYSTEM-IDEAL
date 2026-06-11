<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Enquiry;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\Request;
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
        $allowedProvinces = collect(User::PROVINCE_DISTRICT_MAP)
            ->filter(function (array $districts) use ($permittedDistricts): bool {
                return !empty(array_intersect($districts, $permittedDistricts));
            })
            ->keys()
            ->values()
            ->all();

        $request->validate([
            'model' => ['required', 'string'],
            'engine' => ['required', 'string'],
            'variant' => ['required', 'string'],
            'title' => ['nullable', 'string', 'max:10'],
            'name' => ['required', 'string', 'max:150'],
            'mobiles' => ['required', 'array', 'min:1'],
            'mobiles.*' => ['nullable', 'regex:/^0\d{9}$/'],
            'province' => ['required', 'string', Rule::in($allowedProvinces)],
            'district' => ['required', 'string', 'max:100', Rule::in($permittedDistricts)],
            'location' => ['nullable', 'string', 'max:150'],
            'state' => ['nullable', 'string', 'max:100'],
            'address1' => ['nullable', 'string', 'max:255'],
            'address2' => ['nullable', 'string', 'max:255'],
            'lead_source' => ['required', Rule::in(['Walk-In', 'Tele-In', 'Activity', 'Digital', 'Referral', 'Press'])],
            'follow_type' => ['required', Rule::in(['Home Visit', 'Showroom Visit', 'Call'])],
            'follow_date' => ['required', 'date'],
            'follow_time' => ['required', 'date_format:H:i'],
        ], [
            'mobiles.*.regex' => 'Contact number must be 10 digits and start with 0.',
        ]);

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

        $location = trim((string) $request->input('location', ''));
        if ($location === '') {
            $location = $district;
        }

        $vehicle = Vehicle::where('model', $request->model)
            ->where('engine_type', $request->engine)
            ->where('variant', $request->variant)
            ->first();

        if (!$vehicle) {
            return back()->with('error', 'Invalid vehicle selection');
        }

        $ownerUserId = $request->user()?->id;

        DB::transaction(function () use ($request, $vehicle, $mobileNumbers, $district, $location, $ownerUserId) {
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
                'status' => 'OPEN',
            ]);
        });

        return redirect()->back()->with('success', 'ERP Enquiry Saved Successfully');
    }

    

public function list(Request $request)
{
    $viewer = $request->user();
    $enquiriesQuery = Enquiry::with(['customer', 'vehicle', 'user']);
    $selectedLeadStatus = strtolower(trim((string) $request->query('lead_status', '')));
    if (!in_array($selectedLeadStatus, ['hot', 'warm', 'cold'], true)) {
        $selectedLeadStatus = null;
    }

    if ($viewer && $viewer->role !== User::ROLE_SUPER_ADMIN) {
        $accessibleUserIds = $this->resolveAccessibleUserIds($viewer);
        $enquiriesQuery->whereIn('user_id', $accessibleUserIds);
    }

    if ($selectedLeadStatus !== null) {
        $enquiriesQuery->whereHas('prospectSheet', function ($query) use ($selectedLeadStatus) {
            $query->whereRaw("LOWER(COALESCE(lead_status, '')) = ?", [$selectedLeadStatus]);
        });
    }

    $enquiries = $enquiriesQuery
        ->latest()
        ->get();

    return view('enquiries.index', compact('enquiries'));
}

public function listCallEpds(Request $request)
{
    $viewer = $request->user();
    $enquiriesQuery = Enquiry::with(['customer', 'vehicle', 'user'])
        ->whereRaw('LOWER(COALESCE(follow_type, \'\')) LIKE ?', ['%call%']);

    if ($viewer && $viewer->role !== User::ROLE_SUPER_ADMIN) {
        $accessibleUserIds = $this->resolveAccessibleUserIds($viewer);
        $enquiriesQuery->whereIn('user_id', $accessibleUserIds);
    }

    $enquiries = $enquiriesQuery
        ->latest()
        ->get();

    return view('enquiries.index', compact('enquiries'));
}

public function listShowroomEpds(Request $request)
{
    $viewer = $request->user();
    $enquiriesQuery = Enquiry::with(['customer', 'vehicle', 'user'])
        ->whereRaw('LOWER(COALESCE(follow_type, \'\')) LIKE ?', ['%showroom%']);

    if ($viewer && $viewer->role !== User::ROLE_SUPER_ADMIN) {
        $accessibleUserIds = $this->resolveAccessibleUserIds($viewer);
        $enquiriesQuery->whereIn('user_id', $accessibleUserIds);
    }

    $enquiries = $enquiriesQuery
        ->latest()
        ->get();

    return view('enquiries.index', compact('enquiries'));
}

public function listHomeEpds(Request $request)
{
    $viewer = $request->user();
    $enquiriesQuery = Enquiry::with(['customer', 'vehicle', 'user'])
        ->whereRaw('LOWER(COALESCE(follow_type, \'\')) LIKE ?', ['%home%']);

    if ($viewer && $viewer->role !== User::ROLE_SUPER_ADMIN) {
        $accessibleUserIds = $this->resolveAccessibleUserIds($viewer);
        $enquiriesQuery->whereIn('user_id', $accessibleUserIds);
    }

    $enquiries = $enquiriesQuery
        ->latest()
        ->get();

    return view('enquiries.index', compact('enquiries'));
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
