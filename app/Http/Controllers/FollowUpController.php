<?php

namespace App\Http\Controllers;

use App\Models\CompetitionVehicle;
use App\Models\Enquiry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FollowUpController extends Controller
{
    public function show(Request $request, Enquiry $enquiry): View
    {
        $enquiry->load(['customer', 'vehicle', 'user']);

        abort_unless($this->canAccessEnquiry($request->user(), $enquiry), 403);

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
        $followTypeLabel = $enquiry->follow_type ?: 'Followup not set';
        $competitionMap = CompetitionVehicle::query()
            ->orderBy('brand')
            ->orderBy('model')
            ->get()
            ->groupBy('brand')
            ->map(function ($items) {
                return $items->pluck('model')->unique()->values()->all();
            })
            ->toArray();
        $competitionBrands = array_keys($competitionMap);
        $followupStatus = $enquiry->followup_status ?: 'pending';
        $selectedFollowupStatus = old('followup_status', $followupStatus);
        $selectedVisitDate = old(
            'followup_visit_date',
            $enquiry->followup_visit_date ? Carbon::parse($enquiry->followup_visit_date)->format('Y-m-d') : ''
        );
        $selectedMetWhom = old('followup_met_whom', $enquiry->followup_met_whom ?? '');
        $selectedResult = old('followup_result', $enquiry->followup_result ?? '');
        $selectedCustomerComment = old('followup_customer_comment', $enquiry->followup_customer_comment ?? '');
        $selectedConversionYear = old(
            'followup_conversion_year',
            $enquiry->followup_conversion_year ?: now()->year
        );
        $selectedConversionMonth = old(
            'followup_conversion_month',
            $enquiry->followup_conversion_month ?: now()->month
        );
        $selectedTestDriveGiven = old('followup_test_drive_given', $enquiry->followup_test_drive_given ?? '');
        $selectedTestDriveNoReason = old('followup_test_drive_not_given_reason', $enquiry->followup_test_drive_not_given_reason ?? '');
        $selectedTestDriveWhen = old(
            'followup_test_drive_when',
            $enquiry->followup_test_drive_when ? Carbon::parse($enquiry->followup_test_drive_when)->format('Y-m-d') : ''
        );
        $selectedTestDriveVehicleUsed = old('followup_test_drive_vehicle_used', $enquiry->followup_test_drive_vehicle_used ?? '');
        $selectedTestDriveToWhom = old('followup_test_drive_to_whom', $enquiry->followup_test_drive_to_whom ?? '');
        $selectedFirstTimeBuyer = old('followup_first_time_buyer', $enquiry->followup_first_time_buyer ?? '');
        $selectedFirstTimeBuyerReason = old('followup_first_time_buyer_reason', $enquiry->followup_first_time_buyer_reason ?? '');
        $selectedLeadTemperature = old('followup_lead_temperature', $enquiry->followup_lead_temperature ?? '');
        $selectedNextType = old('followup_next_type', $enquiry->followup_next_type ?? '');
        $selectedNextDate = old(
            'followup_next_date',
            $enquiry->followup_next_date ? Carbon::parse($enquiry->followup_next_date)->format('Y-m-d') : ''
        );
        $selectedNextTime = old(
            'followup_next_time',
            !empty($enquiry->followup_next_time) ? substr((string) $enquiry->followup_next_time, 0, 5) : ''
        );
        $selectedLostTo = old('followup_lost_to', $enquiry->followup_lost_to ?? '');
        $selectedLostCompetitionBrand = old('followup_lost_competition_brand', $enquiry->followup_lost_competition_brand ?? '');
        $selectedLostCompetitionModel = old('followup_lost_competition_model', $enquiry->followup_lost_competition_model ?? '');
        $selectedLostCodealerName = old('followup_lost_codealer_name', $enquiry->followup_lost_codealer_name ?? '');
        $selectedLostRejectReasons = old(
            'followup_lost_reject_reasons',
            is_array($enquiry->followup_lost_reject_reasons ?? null) ? $enquiry->followup_lost_reject_reasons : []
        );
        if (!is_array($selectedLostRejectReasons)) {
            $selectedLostRejectReasons = [];
        }
        $selectedLostRejectOtherText = old('followup_lost_reject_other_text', $enquiry->followup_lost_reject_other_text ?? '');

        $statusLabel = match ($followupStatus) {
            'done' => 'Done',
            'not_done' => 'Not Done',
            default => 'Pending',
        };
        
        // Determine if this is a Home Visit EPR (only these should show physical visit fields)
        // Call and Showroom Visit EPRs should NOT show Visit Date, Met Whom, and image upload fields
        $isHomeVisit = stripos($enquiry->follow_type ?? '', 'home') !== false;
        $showPhysicalVisitFields = $isHomeVisit;

        return view('followup.show', [
            'enquiry' => $enquiry,
            'customerName' => $customerName,
            'interestedIn' => $interestedIn ?: 'N/A',
            'primaryPhone' => $primaryPhone,
            'totalPrice' => $totalPrice,
            'followDateLabel' => $followDateLabel,
            'followTypeLabel' => $followTypeLabel,
            'competitionMap' => $competitionMap,
            'competitionBrands' => $competitionBrands,
            'followupStatus' => $followupStatus,
            'selectedFollowupStatus' => $selectedFollowupStatus,
            'selectedVisitDate' => $selectedVisitDate,
            'selectedMetWhom' => $selectedMetWhom,
            'selectedResult' => $selectedResult,
            'selectedCustomerComment' => $selectedCustomerComment,
            'selectedConversionYear' => $selectedConversionYear,
            'selectedConversionMonth' => $selectedConversionMonth,
            'selectedTestDriveGiven' => $selectedTestDriveGiven,
            'selectedTestDriveNoReason' => $selectedTestDriveNoReason,
            'selectedTestDriveWhen' => $selectedTestDriveWhen,
            'selectedTestDriveVehicleUsed' => $selectedTestDriveVehicleUsed,
            'selectedTestDriveToWhom' => $selectedTestDriveToWhom,
            'selectedFirstTimeBuyer' => $selectedFirstTimeBuyer,
            'selectedFirstTimeBuyerReason' => $selectedFirstTimeBuyerReason,
            'selectedLeadTemperature' => $selectedLeadTemperature,
            'selectedNextType' => $selectedNextType,
            'selectedNextDate' => $selectedNextDate,
            'selectedNextTime' => $selectedNextTime,
            'selectedLostTo' => $selectedLostTo,
            'selectedLostCompetitionBrand' => $selectedLostCompetitionBrand,
            'selectedLostCompetitionModel' => $selectedLostCompetitionModel,
            'selectedLostCodealerName' => $selectedLostCodealerName,
            'selectedLostRejectReasons' => $selectedLostRejectReasons,
            'selectedLostRejectOtherText' => $selectedLostRejectOtherText,
            'statusLabel' => $statusLabel,
            'showPhysicalVisitFields' => $showPhysicalVisitFields,
            'isHomeVisit' => $isHomeVisit,
        ]);
    }

    public function updateStatus(Request $request, Enquiry $enquiry): RedirectResponse
    {
        abort_unless($this->canAccessEnquiry($request->user(), $enquiry), 403);
        
        // Determine if this is a Home Visit EPR (only these require physical visit fields)
        $isHomeVisit = stripos($enquiry->follow_type ?? '', 'home') !== false;

        $validated = $request->validate([
            'followup_status' => ['required', Rule::in(['done', 'not_done'])],
            'followup_visit_date' => ['nullable', 'date', Rule::requiredIf(fn() => $request->input('followup_status') === 'done' && $isHomeVisit)],
            'followup_met_whom' => ['nullable', 'string', 'max:255', Rule::requiredIf(fn() => $request->input('followup_status') === 'done' && $isHomeVisit)],
            'followup_result' => ['nullable', Rule::in(['active', 'lost', 'closed']), 'required_if:followup_status,done'],
            'followup_picture_1' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'followup_picture_2' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'followup_customer_comment' => ['nullable', 'string', 'max:1000'],
            'followup_conversion_year' => ['nullable', 'integer', 'between:2000,2100'],
            'followup_conversion_month' => ['nullable', 'integer', 'between:1,12'],
            'followup_test_drive_given' => ['nullable', Rule::in(['yes', 'no'])],
            'followup_test_drive_not_given_reason' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(
                    fn() => $request->input('followup_status') === 'done'
                        && $request->input('followup_result') === 'active'
                        && $request->input('followup_test_drive_given') === 'no'
                ),
            ],
            'followup_test_drive_when' => [
                'nullable',
                'date',
                Rule::requiredIf(
                    fn() => $request->input('followup_status') === 'done'
                        && $request->input('followup_result') === 'active'
                        && $request->input('followup_test_drive_given') === 'yes'
                ),
            ],
            'followup_test_drive_vehicle_used' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(
                    fn() => $request->input('followup_status') === 'done'
                        && $request->input('followup_result') === 'active'
                        && $request->input('followup_test_drive_given') === 'yes'
                ),
            ],
            'followup_test_drive_to_whom' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(
                    fn() => $request->input('followup_status') === 'done'
                        && $request->input('followup_result') === 'active'
                        && $request->input('followup_test_drive_given') === 'yes'
                ),
            ],
            'followup_first_time_buyer' => ['nullable', Rule::in(['yes', 'no'])],
            'followup_first_time_buyer_reason' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(
                    fn() => $request->input('followup_status') === 'done'
                        && $request->input('followup_result') === 'active'
                        && $request->input('followup_first_time_buyer') === 'no'
                ),
            ],
            'followup_lead_temperature' => ['nullable', Rule::in(['hot', 'warm', 'cold'])],
            'followup_next_type' => ['nullable', Rule::in(['Home visit', 'Showroom visit', 'Call'])],
            'followup_next_date' => ['nullable', 'date'],
            'followup_next_time' => ['nullable', 'date_format:H:i'],
            'followup_lost_to' => [
                'nullable',
                Rule::in(['competitor', 'co_dealer']),
                Rule::requiredIf(
                    fn() => $request->input('followup_status') === 'done'
                        && $request->input('followup_result') === 'lost'
                ),
            ],
            'followup_lost_competition_brand' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(
                    fn() => $request->input('followup_status') === 'done'
                        && $request->input('followup_result') === 'lost'
                        && $request->input('followup_lost_to') === 'competitor'
                ),
            ],
            'followup_lost_competition_model' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(
                    fn() => $request->input('followup_status') === 'done'
                        && $request->input('followup_result') === 'lost'
                        && $request->input('followup_lost_to') === 'competitor'
                ),
            ],
            'followup_lost_codealer_name' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(
                    fn() => $request->input('followup_status') === 'done'
                        && $request->input('followup_result') === 'lost'
                        && $request->input('followup_lost_to') === 'co_dealer'
                ),
            ],
            'followup_lost_reject_reasons' => [
                'nullable',
                'array',
                Rule::requiredIf(
                    fn() => $request->input('followup_status') === 'done'
                        && $request->input('followup_result') === 'lost'
                ),
            ],
            'followup_lost_reject_reasons.*' => [Rule::in(['issue_with_product', 'got_better_discount', 'other'])],
            'followup_lost_reject_other_text' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(
                    fn() => $request->input('followup_status') === 'done'
                        && $request->input('followup_result') === 'lost'
                        && in_array('other', (array) $request->input('followup_lost_reject_reasons', []), true)
                ),
            ],
        ]);

        $enquiry->followup_status = $validated['followup_status'];
        $enquiry->followup_marked_at = now();

        if ($validated['followup_status'] === 'done') {
            // Only set visit date and met whom for Home Visit followups
            if ($isHomeVisit) {
                $enquiry->followup_visit_date = $validated['followup_visit_date'] ?? null;
                $enquiry->followup_met_whom = $validated['followup_met_whom'] ?? null;
            } else {
                $enquiry->followup_visit_date = null;
                $enquiry->followup_met_whom = null;
            }
            
            $enquiry->followup_result = $validated['followup_result'] ?? null;

            if (($validated['followup_result'] ?? null) === 'active') {
                $requiredErrors = [];

                if (empty($validated['followup_conversion_year'])) {
                    $requiredErrors['followup_conversion_year'] = 'Expected conversion year is required for active leads.';
                }

                if (empty($validated['followup_conversion_month'])) {
                    $requiredErrors['followup_conversion_month'] = 'Expected conversion month is required for active leads.';
                }

                if (empty($validated['followup_test_drive_given'])) {
                    $requiredErrors['followup_test_drive_given'] = 'Please select whether test drive was given.';
                }

                if (empty($validated['followup_first_time_buyer'])) {
                    $requiredErrors['followup_first_time_buyer'] = 'Please select whether this is a first time buyer.';
                }

                if (empty($validated['followup_lead_temperature'])) {
                    $requiredErrors['followup_lead_temperature'] = 'Lead status is required for active leads.';
                }

                if (empty($validated['followup_next_type'])) {
                    $requiredErrors['followup_next_type'] = 'Next follow up type is required for active leads.';
                }

                if (empty($validated['followup_next_date'])) {
                    $requiredErrors['followup_next_date'] = 'Scheduled date is required for active leads.';
                }

                if (empty($validated['followup_next_time'])) {
                    $requiredErrors['followup_next_time'] = 'Scheduled time is required for active leads.';
                }

                if (!empty($requiredErrors)) {
                    return back()->withErrors($requiredErrors)->withInput();
                }

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
                $this->clearLostFollowupFields($enquiry);
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
                $this->clearActiveFollowupFields($enquiry);
            } else {
                $this->clearActiveFollowupFields($enquiry);
                $this->clearLostFollowupFields($enquiry);
            }

            if ($request->hasFile('followup_picture_1')) {
                $enquiry->followup_picture_1 = $request->file('followup_picture_1')->store('followups', 'public');
            }

            if ($request->hasFile('followup_picture_2')) {
                $enquiry->followup_picture_2 = $request->file('followup_picture_2')->store('followups', 'public');
            }
        } else {
            $enquiry->followup_visit_date = null;
            $enquiry->followup_met_whom = null;
            $this->clearActiveFollowupFields($enquiry);
            $this->clearLostFollowupFields($enquiry);
        }

        $enquiry->save();

        return redirect()
            ->route('followup.show', $enquiry->id)
            ->with('success', 'Followup status updated.');
    }

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

    private function clearActiveFollowupFields(Enquiry $enquiry): void
    {
        $enquiry->followup_customer_comment = null;
        $enquiry->followup_conversion_year = null;
        $enquiry->followup_conversion_month = null;
        $enquiry->followup_test_drive_given = null;
        $enquiry->followup_test_drive_not_given_reason = null;
        $enquiry->followup_test_drive_when = null;
        $enquiry->followup_test_drive_vehicle_used = null;
        $enquiry->followup_test_drive_to_whom = null;
        $enquiry->followup_first_time_buyer = null;
        $enquiry->followup_first_time_buyer_reason = null;
        $enquiry->followup_lead_temperature = null;
        $enquiry->followup_next_type = null;
        $enquiry->followup_next_date = null;
        $enquiry->followup_next_time = null;
    }

    private function clearLostFollowupFields(Enquiry $enquiry): void
    {
        $enquiry->followup_lost_to = null;
        $enquiry->followup_lost_competition_brand = null;
        $enquiry->followup_lost_competition_model = null;
        $enquiry->followup_lost_codealer_name = null;
        $enquiry->followup_lost_reject_reasons = null;
        $enquiry->followup_lost_reject_other_text = null;
    }
}