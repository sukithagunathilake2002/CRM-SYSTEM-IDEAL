<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Enquiry;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
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
        $models = Vehicle::select('model')->distinct()->orderBy('model')->get();
        $districtOptions = $viewer instanceof User
            ? $viewer->resolvePermittedDistricts()
            : User::DISTRICT_OPTIONS;
        $sourceInfoMap = $this->sourceInformationOptions();
        $selectedVehiclesForForm = $this->selectedVehiclesForForm(
            $request->old('selected_vehicle_ids', [])
        );

        return view('new-enquiry', compact('models', 'districtOptions', 'sourceInfoMap', 'selectedVehiclesForForm'));
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
            ->select('id', 'variant', 'unit_price', 'vat_amount')
            ->orderBy('variant')
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
        $sourceInfoMap = $this->sourceInformationOptions();
        $leadSourceOptions = array_keys($sourceInfoMap);
        $selectedLeadSource = (string) $request->input('lead_source', '');
        $sourceInformationOptions = $sourceInfoMap[$selectedLeadSource] ?? [];

        $request->validate([
            'selected_vehicle_ids' => ['required', 'array', 'min:1'],
            'selected_vehicle_ids.*' => ['required', 'integer', 'exists:vehicles,id'],
            'model' => ['nullable', 'string'],
            'engine' => ['nullable', 'string'],
            'variant' => ['nullable', 'string'],
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
            'lead_source' => ['required', Rule::in($leadSourceOptions)],
            'source_of_information' => ['required', 'string', 'max:255', Rule::in($sourceInformationOptions)],
            'source_of_information_other' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(fn() => $request->input('source_of_information') === 'Other'),
            ],
            'follow_type' => ['required', Rule::in(['Home Visit', 'Showroom Visit', 'Call'])],
            'follow_date' => ['required', 'date'],
            'follow_time' => ['required', 'date_format:H:i'],
            'inquiry_date' => ['required', 'date', 'before_or_equal:' . now('Asia/Colombo')->toDateString()],
        ], [
            'mobiles.*.regex' => 'Contact number must be 10 digits and start with 0.',
            'inquiry_date.before_or_equal' => 'Date of Inquiry cannot be a future date.',
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

        $selectedVehicleIds = collect($request->input('selected_vehicle_ids', []))
            ->map(fn($id): int => (int) $id)
            ->filter(fn(int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        $selectedVehicles = Vehicle::query()
            ->whereIn('id', $selectedVehicleIds)
            ->get()
            ->keyBy('id');

        $selectedVehiclePayload = collect($selectedVehicleIds)
            ->map(fn(int $id) => $selectedVehicles->get($id))
            ->filter()
            ->map(fn(Vehicle $selectedVehicle): array => $this->vehicleSelectionPayload($selectedVehicle))
            ->values()
            ->all();

        $vehicle = $selectedVehicles->get($selectedVehicleIds[0] ?? 0);

        if (!$vehicle) {
            return back()->with('error', 'Invalid vehicle selection');
        }

        $ownerUserId = $request->user()?->id;
        $sourceOfInformation = $this->resolveSourceInformation(
            $request->input('source_of_information'),
            $request->input('source_of_information_other')
        );

        $now = now('Asia/Colombo');
        $inquiryAt = Carbon::parse((string) $request->input('inquiry_date'), 'Asia/Colombo')
            ->setTime($now->hour, $now->minute, $now->second);

        $createdEnquiry = DB::transaction(function () use ($request, $vehicle, $selectedVehiclePayload, $mobileNumbers, $district, $location, $ownerUserId, $inquiryAt, $sourceOfInformation) {
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

            $enquiry = new Enquiry([
                'user_id' => $ownerUserId,
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle->id,
                'selected_vehicle_models' => $selectedVehiclePayload,
                'lead_source' => $request->lead_source,
                'source_of_information' => $sourceOfInformation,
                'follow_type' => $request->follow_type,
                'follow_date' => $request->follow_date,
                'follow_time' => $request->follow_time,
                'followup_status' => 'pending',
                'exchange' => $request->exchange ? 1 : 0,
                'finance' => $request->finance ? 1 : 0,
                'status' => 'OPEN',
            ]);

            $enquiry->created_at = $inquiryAt;
            $enquiry->save();

            return $enquiry;
        });

        $createdLeadDetails = [
            'id' => $createdEnquiry->id,
            'customer_name' => trim((string) (($request->title ? $request->title . '. ' : '') . trim((string) $request->name))),
            'mobile_numbers' => $mobileNumbers,
            'district' => $district,
            'location' => $location,
            'lead_source' => $request->lead_source,
            'source_of_information' => $sourceOfInformation,
            'follow_type' => $request->follow_type,
            'follow_date' => $request->follow_date,
            'follow_time' => $request->follow_time,
            'vehicles' => collect($selectedVehiclePayload)
                ->map(fn(array $selectedVehicle): string => trim((string) ($selectedVehicle['label'] ?? '')))
                ->filter()
                ->values()
                ->all(),
        ];

        return redirect()
            ->back()
            ->with('success', 'ERP Enquiry Saved Successfully')
            ->with('created_lead', $createdLeadDetails);
    }

    

public function list(Request $request)
{
    $viewer = $request->user();
    $enquiriesQuery = Enquiry::with(['customer', 'vehicle', 'user', 'prospectSheet', 'booking', 'delivery']);
    $showAllLeads = $request->query('view') === 'all';
    $registrationView = $request->query('registration') === 'pending' ? 'pending' : 'registered';
    $selectedLeadStatus = strtolower(trim((string) $request->query('lead_status', '')));
    if (!in_array($selectedLeadStatus, ['hot', 'warm', 'cold'], true)) {
        $selectedLeadStatus = null;
    }
    $selectedLeadResult = strtolower(trim((string) $request->query('lead_result', '')));
    if (!in_array($selectedLeadResult, ['active', 'lost', 'closed'], true)) {
        $selectedLeadResult = null;
    }
    $selectedBookingView = strtolower(trim((string) $request->query('booking', '')));
    if (!in_array($selectedBookingView, ['active', 'inactive'], true)) {
        $selectedBookingView = null;
    }
    $selectedInquiryView = strtolower(trim((string) $request->query('inquiry', '')));
    if ($selectedInquiryView !== 'active') {
        $selectedInquiryView = null;
    }
    $selectedDeliveryView = strtolower(trim((string) $request->query('delivery', '')));
    if ($selectedDeliveryView !== 'active') {
        $selectedDeliveryView = null;
    }
    $selectedDeliveryApprovalView = strtolower(trim((string) $request->query('delivery_approval', '')));
    if (!in_array($selectedDeliveryApprovalView, [Delivery::APPROVAL_PENDING, Delivery::APPROVAL_APPROVED], true)) {
        $selectedDeliveryApprovalView = null;
    }

    if ($viewer && $viewer->role !== User::ROLE_SUPER_ADMIN) {
        $accessibleUserIds = $this->resolveAccessibleUserIds($viewer);
        $enquiriesQuery->whereIn('user_id', $accessibleUserIds);
    }

    if ($selectedDeliveryApprovalView !== null) {
        $enquiriesQuery->whereHas('delivery', function ($query) use ($selectedDeliveryApprovalView): void {
            $query->where('approval_status', $selectedDeliveryApprovalView);
        });
    } elseif ($selectedDeliveryView === 'active') {
        $enquiriesQuery->activeDeliveryStage();
    } elseif ($selectedBookingView === 'active') {
        $enquiriesQuery->activeBookingStage();
    } elseif ($selectedBookingView === 'inactive') {
        $enquiriesQuery->inactiveBookingStage();
    } elseif ($selectedInquiryView === 'active') {
        $enquiriesQuery->activeInquiryStage();
    } elseif (!$showAllLeads && !in_array($selectedLeadResult, ['lost', 'closed'], true)) {
        if ($registrationView === 'pending') {
            $enquiriesQuery->pendingRegistration();
        } else {
            $enquiriesQuery->registeredLead();
        }

        if ($selectedLeadResult === 'active') {
            $enquiriesQuery
                ->doesntHave('booking')
                ->doesntHave('delivery');
        }
    }

    if ($selectedLeadStatus !== null) {
        $enquiriesQuery->whereHas('prospectSheet', function ($query) use ($selectedLeadStatus) {
            $query->whereRaw("LOWER(COALESCE(lead_status, '')) = ?", [$selectedLeadStatus]);
        });
    }

    if ($selectedLeadResult !== null) {
        $enquiriesQuery->whereRaw("LOWER(COALESCE(followup_result, '')) = ?", [$selectedLeadResult]);
    }

    $enquiries = $enquiriesQuery
        ->orderBy('follow_date')
        ->orderBy('follow_time')
        ->get();

    return view('enquiries.index', compact('enquiries'));
}

public function destroy(Request $request, Enquiry $enquiry)
{
    if ($request->user()?->role !== User::ROLE_SUPER_ADMIN) {
        abort(403);
    }

    $leadLabel = trim((string) ($enquiry->customer?->name ?? 'Lead'));

    DB::transaction(function () use ($enquiry): void {
        DB::table('lead_transfer_requests')
            ->where('enquiry_id', $enquiry->id)
            ->delete();

        $enquiry->delivery()->delete();
        $enquiry->delete();
    });

    return redirect()
        ->route('enquiries.list', ['view' => 'all'])
        ->with('success', $leadLabel . ' deleted successfully.');
}

public function listCallEpds(Request $request)
{
    $viewer = $request->user();
    $today = now('Asia/Colombo')->toDateString();
    $enquiriesQuery = Enquiry::with(['customer', 'vehicle', 'user', 'prospectSheet', 'booking', 'delivery'])
        ->nonTerminalLead()
        ->whereDate('follow_date', '<=', $today)
        ->whereRaw("LOWER(COALESCE(followup_status, 'pending')) NOT IN (?, ?)", ['done', 'not_done'])
        ->whereRaw('LOWER(COALESCE(follow_type, \'\')) LIKE ?', ['%call%']);

    if ($viewer && $viewer->role !== User::ROLE_SUPER_ADMIN) {
        $accessibleUserIds = $this->resolveAccessibleUserIds($viewer);
        $enquiriesQuery->whereIn('user_id', $accessibleUserIds);
    }

    $enquiries = $enquiriesQuery
        ->orderBy('follow_date')
        ->orderBy('follow_time')
        ->get();

    return view('enquiries.index', compact('enquiries'));
}

public function listShowroomEpds(Request $request)
{
    $viewer = $request->user();
    $today = now('Asia/Colombo')->toDateString();
    $enquiriesQuery = Enquiry::with(['customer', 'vehicle', 'user', 'prospectSheet', 'booking', 'delivery'])
        ->nonTerminalLead()
        ->whereDate('follow_date', '<=', $today)
        ->whereRaw("LOWER(COALESCE(followup_status, 'pending')) NOT IN (?, ?)", ['done', 'not_done'])
        ->whereRaw('LOWER(COALESCE(follow_type, \'\')) LIKE ?', ['%showroom%']);

    if ($viewer && $viewer->role !== User::ROLE_SUPER_ADMIN) {
        $accessibleUserIds = $this->resolveAccessibleUserIds($viewer);
        $enquiriesQuery->whereIn('user_id', $accessibleUserIds);
    }

    $enquiries = $enquiriesQuery
        ->orderBy('follow_date')
        ->orderBy('follow_time')
        ->get();

    return view('enquiries.index', compact('enquiries'));
}

public function listHomeEpds(Request $request)
{
    $viewer = $request->user();
    $today = now('Asia/Colombo')->toDateString();
    $enquiriesQuery = Enquiry::with(['customer', 'vehicle', 'user', 'prospectSheet', 'booking', 'delivery'])
        ->nonTerminalLead()
        ->whereDate('follow_date', '<=', $today)
        ->whereRaw("LOWER(COALESCE(followup_status, 'pending')) NOT IN (?, ?)", ['done', 'not_done'])
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

    private function sourceInformationOptions(): array
    {
        return [
            'Walk-In' => ['Showroom Visit', 'Road Show', 'Display', 'Existing Customer', 'Other'],
            'Tele-In' => ['Call Center', 'Hotline', 'Inbound Call', 'Missed Call', 'Other'],
            'Activity' => ['Event', 'Mall Display', 'Corporate Visit', 'Canvasing', 'Other'],
            'Digital' => ['Facebook', 'Instagram', 'Google', 'Website', 'YouTube', 'TikTok', 'Other'],
            'Referral' => ['Customer Referral', 'Employee Referral', 'Dealer Referral', 'Friends/Family', 'Other'],
            'Press' => ['Newspaper', 'Magazine', 'Radio', 'TV', 'Other'],
        ];
    }

    private function resolveSourceInformation(?string $selectedSourceInformation, ?string $otherSourceInformation): ?string
    {
        $selectedSourceInformation = trim((string) $selectedSourceInformation);
        $otherSourceInformation = trim((string) $otherSourceInformation);

        if ($selectedSourceInformation === 'Other') {
            return $otherSourceInformation !== '' ? $otherSourceInformation : null;
        }

        return $selectedSourceInformation !== '' ? $selectedSourceInformation : null;
    }

    private function selectedVehiclesForForm(mixed $vehicleIds): array
    {
        $ids = collect(is_array($vehicleIds) ? $vehicleIds : [])
            ->map(fn($id): int => (int) $id)
            ->filter(fn(int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($ids)) {
            return [];
        }

        $vehicles = Vehicle::query()
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        return collect($ids)
            ->map(fn(int $id) => $vehicles->get($id))
            ->filter()
            ->map(fn(Vehicle $vehicle): array => $this->vehicleSelectionPayload($vehicle))
            ->values()
            ->all();
    }

    private function vehicleSelectionPayload(Vehicle $vehicle): array
    {
        return [
            'vehicle_id' => (int) $vehicle->id,
            'model' => $vehicle->model,
            'engine_type' => $vehicle->engine_type,
            'variant' => $vehicle->variant,
            'label' => trim((string) $vehicle->model . ' ' . (string) $vehicle->engine_type . ' ' . (string) $vehicle->variant),
        ];
    }
}
