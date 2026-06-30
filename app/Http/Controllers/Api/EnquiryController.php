<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Enquiry;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EnquiryController extends Controller
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
            ->paginate(20);

        return response()->json($enquiries);
    }

    public function store(Request $request)
    {
        $viewer = $request->user();
        $permittedDistricts = $viewer instanceof User
            ? $viewer->resolvePermittedDistricts()
            : User::DISTRICT_OPTIONS;

        $validated = $request->validate([
            'model' => ['required', 'string'],
            'engine' => ['required', 'string'],
            'variant' => ['required', 'string'],
            'title' => ['nullable', 'string', 'max:10'],
            'name' => ['required', 'string', 'max:150'],
            'mobiles' => ['required', 'array', 'min:1'],
            'mobiles.*' => ['nullable', 'regex:/^0\d{9}$/'],
            'district' => ['required', 'string', 'max:100', Rule::in($permittedDistricts)],
            'location' => ['nullable', 'string', 'max:150'],
            'state' => ['nullable', 'string', 'max:100'],
            'address1' => ['nullable', 'string', 'max:255'],
            'address2' => ['nullable', 'string', 'max:255'],
            'lead_source' => ['required', Rule::in(['Walk-In', 'Tele-In', 'Activity', 'Digital', 'Referral', 'Press'])],
            'follow_type' => ['required', Rule::in(['Home Visit', 'Showroom Visit', 'Call'])],
            'follow_date' => ['required', 'date'],
            'follow_time' => ['required', 'date_format:H:i'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'exchange' => ['nullable', 'boolean'],
            'finance' => ['nullable', 'boolean'],
        ]);

        $mobileNumbers = collect($request->input('mobiles', []))
            ->map(fn($mobile) => trim((string) $mobile))
            ->filter()
            ->values()
            ->all();

        if (count($mobileNumbers) === 0) {
            return response()->json(['message' => 'At least one contact number is required.'], 422);
        }

        $vehicle = Vehicle::where('model', $validated['model'])
            ->where('engine_type', $validated['engine'])
            ->where('variant', $validated['variant'])
            ->first();

        if (!$vehicle) {
            return response()->json(['message' => 'Invalid vehicle selection'], 422);
        }

        $latitude = is_numeric($request->input('latitude')) ? (float) $request->input('latitude') : null;
        $longitude = is_numeric($request->input('longitude')) ? (float) $request->input('longitude') : null;

        $locationCapturedAt = null;
        if ($request->filled('location_captured_at')) {
            try {
                $locationCapturedAt = Carbon::parse($request->input('location_captured_at'));
            } catch (\Throwable $e) {
                $locationCapturedAt = null;
            }
        }

        $enquiry = DB::transaction(function () use ($request, $vehicle, $mobileNumbers, $latitude, $longitude, $locationCapturedAt, $viewer) {
            $customer = Customer::create([
                'title' => $request->title,
                'name' => trim((string) $request->name),
                'mobile_numbers' => $mobileNumbers,
                'district' => $request->district,
                'location' => $request->location,
                'state' => $request->filled('state') ? $request->state : null,
                'address1' => $request->filled('address1') ? $request->address1 : null,
                'address2' => $request->filled('address2') ? $request->address2 : null,
            ]);

            return Enquiry::create([
                'user_id' => $viewer->id,
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

        return response()->json([
            'message' => 'Enquiry created successfully',
            'enquiry' => $enquiry->load(['customer', 'vehicle']),
        ], 201);
    }

    public function show(Enquiry $enquiry)
    {
        $enquiry->load(['customer', 'vehicle', 'user', 'prospectSheet', 'booking']);
        return response()->json($enquiry);
    }
}