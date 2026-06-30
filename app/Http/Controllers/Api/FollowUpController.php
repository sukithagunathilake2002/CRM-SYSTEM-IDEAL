<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FollowUpController extends Controller
{
    private function canAccessEnquiry(User $viewer, Enquiry $enquiry): bool
    {
        if ($viewer->role === User::ROLE_SUPER_ADMIN) {
            return true;
        }

        if ($enquiry->user_id === null) {
            return true;
        }

        $accessibleUserIds = $this->resolveAccessibleUserIds($viewer);
        return in_array((int) $enquiry->user_id, $accessibleUserIds, true);
    }

    private function resolveAccessibleUserIds(User $viewer): array
    {
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

    public function show(Enquiry $enquiry)
    {
        $viewer = request()->user();
        abort_unless($this->canAccessEnquiry($viewer, $enquiry), 403);

        $enquiry->load(['customer', 'vehicle', 'user']);
        $customer = $enquiry->customer;
        $vehicle = $enquiry->vehicle;
        $mobileNumbers = is_array($customer?->mobile_numbers)
            ? array_values(array_filter($customer->mobile_numbers))
            : [];
        $primaryPhone = count($mobileNumbers) > 0 ? (string) $mobileNumbers[0] : 'N/A';

        $customerName = trim(($customer?->title ? $customer->title . ' ' : '') . ($customer?->name ?? 'N/A'));
        $interestedIn = trim(($vehicle?->model ?? '') . ' ' . ($vehicle?->variant ?? ''));
        $totalPrice = (float) (($vehicle?->unit_price ?? 0) + ($vehicle?->vat_amount ?? 0));
        $followDateLabel = $enquiry->follow_date
            ? Carbon::parse($enquiry->follow_date)->format('d-M-Y')
            : 'No followup date';

        return response()->json([
            'enquiry' => $enquiry,
            'customer_name' => $customerName,
            'interested_in' => $interestedIn ?: 'N/A',
            'primary_phone' => $primaryPhone,
            'total_price' => $totalPrice,
            'follow_date_label' => $followDateLabel,
        ]);
    }

    public function updateStatus(Request $request, Enquiry $enquiry)
    {
        $viewer = $request->user();
        abort_unless($this->canAccessEnquiry($viewer, $enquiry), 403);

        $isHomeVisit = stripos($enquiry->follow_type ?? '', 'home') !== false;

        $validated = $request->validate([
            'followup_status' => ['required', Rule::in(['done', 'not_done'])],
            'followup_visit_date' => ['nullable', 'date'],
            'followup_met_whom' => ['nullable', 'string', 'max:255'],
            'followup_result' => ['nullable', Rule::in(['active', 'lost', 'closed'])],
            'followup_customer_comment' => ['nullable', 'string', 'max:1000'],
            'followup_conversion_year' => ['nullable', 'integer', 'between:2000,2100'],
            'followup_conversion_month' => ['nullable', 'integer', 'between:1,12'],
            'followup_test_drive_given' => ['nullable', Rule::in(['yes', 'no'])],
            'followup_test_drive_not_given_reason' => ['nullable', 'string', 'max:255'],
            'followup_test_drive_when' => ['nullable', 'date'],
            'followup_test_drive_vehicle_used' => ['nullable', 'string', 'max:255'],
            'followup_test_drive_to_whom' => ['nullable', 'string', 'max:255'],
            'followup_first_time_buyer' => ['nullable', Rule::in(['yes', 'no'])],
            'followup_first_time_buyer_reason' => ['nullable', 'string', 'max:255'],
            'followup_lead_temperature' => ['nullable', Rule::in(['hot', 'warm', 'cold'])],
            'followup_next_type' => ['nullable', Rule::in(['Home visit', 'Showroom visit', 'Call'])],
            'followup_next_date' => ['nullable', 'date'],
            'followup_next_time' => ['nullable', 'date_format:H:i'],
            'followup_lost_to' => ['nullable', Rule::in(['competitor', 'co_dealer'])],
            'followup_lost_competition_brand' => ['nullable', 'string', 'max:255'],
            'followup_lost_competition_model' => ['nullable', 'string', 'max:255'],
            'followup_lost_codealer_name' => ['nullable', 'string', 'max:255'],
            'followup_lost_reject_reasons' => ['nullable', 'array'],
            'followup_lost_reject_reasons.*' => [Rule::in(['issue_with_product', 'got_better_discount', 'other'])],
            'followup_lost_reject_other_text' => ['nullable', 'string', 'max:255'],
            'followup_not_done_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $enquiry->followup_status = $validated['followup_status'];
        $enquiry->followup_marked_at = now();

        if ($validated['followup_status'] === 'done') {
            if ($isHomeVisit) {
                $enquiry->followup_visit_date = $validated['followup_visit_date'] ?? null;
                $enquiry->followup_met_whom = $validated['followup_met_whom'] ?? null;
            } else {
                $enquiry->followup_visit_date = null;
                $enquiry->followup_met_whom = null;
            }

            $enquiry->followup_result = $validated['followup_result'] ?? null;

            if (($validated['followup_result'] ?? null) === 'active') {
                $enquiry->followup_customer_comment = $validated['followup_customer_comment'] ?? null;
                $enquiry->followup_conversion_year = $validated['followup_conversion_year'] ?? null;
                $enquiry->followup_conversion_month = $validated['followup_conversion_month'] ?? null;
                $enquiry->followup_test_drive_given = $validated['followup_test_drive_given'] ?? null;
                $enquiry->followup_test_drive_not_given_reason = ($validated['followup_test_drive_given'] ?? null) === 'no'
                    ? ($validated['followup_test_drive_not_given_reason'] ?? null)
                    : null;
                $enquiry->followup_test_drive_when = ($validated['followup_test_drive_given'] ?? null) === 'yes'
                    ? ($validated['followup_test_drive_when'] ?? null)
                    : null;
                $enquiry->followup_test_drive_vehicle_used = ($validated['followup_test_drive_given'] ?? null) === 'yes'
                    ? ($validated['followup_test_drive_vehicle_used'] ?? null)
                    : null;
                $enquiry->followup_test_drive_to_whom = ($validated['followup_test_drive_given'] ?? null) === 'yes'
                    ? ($validated['followup_test_drive_to_whom'] ?? null)
                    : null;
                $enquiry->followup_first_time_buyer = $validated['followup_first_time_buyer'] ?? null;
                $enquiry->followup_first_time_buyer_reason = ($validated['followup_first_time_buyer'] ?? null) === 'no'
                    ? ($validated['followup_first_time_buyer_reason'] ?? null)
                    : null;
                $enquiry->followup_lead_temperature = $validated['followup_lead_temperature'] ?? null;
                $enquiry->followup_next_type = $validated['followup_next_type'] ?? null;
                $enquiry->followup_next_date = $validated['followup_next_date'] ?? null;
                $enquiry->followup_next_time = $validated['followup_next_time'] ?? null;

                $enquiry->follow_type = $validated['followup_next_type'] ?? $enquiry->follow_type;
                $enquiry->follow_date = $validated['followup_next_date'] ?? $enquiry->follow_date;
                $enquiry->follow_time = $validated['followup_next_time'] ?? $enquiry->follow_time;
            } elseif (($validated['followup_result'] ?? null) === 'lost') {
                $lostReasons = array_values(array_unique(array_filter((array) ($validated['followup_lost_reject_reasons'] ?? []))));
                $enquiry->followup_lost_to = $validated['followup_lost_to'] ?? null;
                $enquiry->followup_lost_competition_brand = ($validated['followup_lost_to'] ?? null) === 'competitor'
                    ? ($validated['followup_lost_competition_brand'] ?? null)
                    : null;
                $enquiry->followup_lost_competition_model = ($validated['followup_lost_to'] ?? null) === 'competitor'
                    ? ($validated['followup_lost_competition_model'] ?? null)
                    : null;
                $enquiry->followup_lost_codealer_name = ($validated['followup_lost_to'] ?? null) === 'co_dealer'
                    ? ($validated['followup_lost_codealer_name'] ?? null)
                    : null;
                $enquiry->followup_lost_reject_reasons = $lostReasons;
                $enquiry->followup_lost_reject_other_text = in_array('other', $lostReasons, true)
                    ? ($validated['followup_lost_reject_other_text'] ?? null)
                    : null;
            }
        } else {
            $enquiry->followup_visit_date = null;
            $enquiry->followup_met_whom = null;
            $enquiry->followup_not_done_reason = $validated['followup_not_done_reason'] ?? null;
        }

        $enquiry->save();

        return response()->json([
            'message' => 'Followup status updated successfully',
            'enquiry' => $enquiry,
        ]);
    }
}