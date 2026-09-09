<?php

namespace App\Support;

use App\Models\Enquiry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AllLeadsReport
{
    public function scope(Builder $query, User $viewer): Builder
    {
        if ($viewer->role !== User::ROLE_SUPER_ADMIN) {
            $query->whereIn('enquiries.user_id', $viewer->accessibleUserIds())
                ->whereIn('enquiries.vehicle_id', \App\Models\Vehicle::visibleTo($viewer)->select('id'));
        }

        return $query;
    }

    public const CHOICES = [
        'test_drive' => 'Test Drive Given',
        'first_buyer' => 'First Time Buyer',
        'interested_exchange' => 'Interested in Exchange',
        'competition' => 'Interested in Competition',
    ];

    public function filters(Request $request): array
    {
        $rules = [
            'from_date' => ['nullable', 'required_with:to_date', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'required_with:from_date', 'date_format:Y-m-d', 'after_or_equal:from_date'],
            'area_managers' => ['sometimes', 'array', 'max:100'],
            'area_managers.*' => ['integer', Rule::exists('users', 'id')->where('role', User::ROLE_AREA_MANAGER)],
            'consultants' => ['sometimes', 'array', 'max:100'],
            'consultants.*' => ['integer', Rule::exists('users', 'id')->where('role', User::ROLE_SALES_CONSULTANT)],
            'vehicle_models' => ['sometimes', 'array', 'max:100'],
            'vehicle_models.*' => ['string', 'max:255'],
            'lead_sources' => ['sometimes', 'array', 'max:100'],
            'lead_sources.*' => ['string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
        foreach (self::CHOICES as $key => $label) {
            $rules[$key] = ['sometimes', 'array', 'max:2'];
            $rules[$key.'.*'] = ['string', Rule::in(['yes', 'no'])];
        }
        if ($request->user() && $request->user()->role !== User::ROLE_SUPER_ADMIN) {
            $ids = $request->user()->accessibleUserIds();
            $rules['area_managers.*'][] = Rule::in($ids);
            $rules['consultants.*'][] = Rule::in($ids);
        }
        $filters = $request->validateWithBag('allLeads', $rules);
        unset($filters['page']);
        foreach ($filters as $key => $value) {
            if (is_array($value)) {
                $filters[$key] = array_values(array_unique($value));
            }
        }
        $now = now('Asia/Colombo');
        $from = ! empty($filters['from_date']) ? Carbon::parse($filters['from_date'], 'Asia/Colombo') : $now->copy()->startOfMonth();
        $to = ! empty($filters['to_date']) ? Carbon::parse($filters['to_date'], 'Asia/Colombo') : $now->copy()->endOfMonth();
        $filters['date_notice'] = '';
        if ($from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1 > 180) {
            $from = $now->copy()->startOfMonth();
            $to = $now->copy()->endOfMonth();
            $filters['date_notice'] = 'The selected range exceeded 180 days. Showing the current month instead.';
        }
        $filters['from_date'] = $from->toDateString();
        $filters['to_date'] = $to->toDateString();

        return $filters;
    }

    public function query(array $filters): Builder
    {
        $query = Enquiry::query()->with(['customer', 'vehicle', 'user.manager', 'prospectSheet'])
            ->where('enquiries.created_at', '>=', $filters['from_date'].' 00:00:00')
            ->where('enquiries.created_at', '<=', $filters['to_date'].' 23:59:59');

        if ($areas = $filters['area_managers'] ?? []) {
            $query->whereHas('user', function ($user) use ($areas) {
                $user->where(function ($scope) use ($areas) {
                    $scope->where(function ($manager) use ($areas) {
                        $manager->where('role', User::ROLE_AREA_MANAGER)->whereIn('id', $areas);
                    })->orWhere(function ($consultant) use ($areas) {
                        $consultant->where('role', User::ROLE_SALES_CONSULTANT)->whereIn('manager_id', $areas);
                    });
                });
            });
        }
        if ($consultants = $filters['consultants'] ?? []) {
            $query->whereIn('user_id', $consultants);
        }
        (new EnquiryListing)->filterVehicleModels($query, $filters['vehicle_models'] ?? []);
        if ($sources = $filters['lead_sources'] ?? []) {
            $query->whereIn($query->getQuery()->raw('LOWER(TRIM(lead_source))'), array_map('strtolower', $sources));
        }
        if (count($filters['competition'] ?? []) === 1) {
            // Mirror terminalLeadResult(): an explicit lost/closed follow-up result takes precedence over status.
            $lostToCompetitor = "LOWER(TRIM(COALESCE(followup_lost_to, ''))) = 'competitor' AND (LOWER(TRIM(COALESCE(followup_result, ''))) = 'lost' OR (LOWER(TRIM(COALESCE(followup_result, ''))) NOT IN ('lost', 'closed') AND LOWER(TRIM(COALESCE(status, ''))) = 'lost'))";
            $query->whereRaw(($filters['competition'][0] === 'yes' ? '' : 'NOT ').'('.$lostToCompetitor.')');
        }

        // Use the prospect answers; fall back to enquiry answers only when the prospect answer is blank.
        foreach (['test_drive' => ['test_drive_given', 'followup_test_drive_given'],
            'first_buyer' => ['first_time_buyer', 'followup_first_time_buyer'],
            'interested_exchange' => ['interested_in_exchange', 'exchange']] as $key => [$column, $fallback]) {
            if (! $values = $filters[$key] ?? []) {
                continue;
            }
            $query->where(function ($answers) use ($column, $fallback, $values) {
                $answers->whereHas('prospectSheet', fn ($p) => $p->whereIn($p->getQuery()->raw("LOWER(TRIM($column))"), $values));
                $answers->orWhere(function ($missing) use ($column, $fallback, $values) {
                    $missing->whereDoesntHave('prospectSheet', fn ($p) => $p->whereRaw("TRIM(COALESCE($column, '')) <> ''"));
                    if ($fallback === 'exchange') {
                        $missing->whereIn('exchange', array_map(fn ($value) => $value === 'yes' ? 1 : 0, $values));
                    } else {
                        $missing->whereIn($missing->getQuery()->raw("LOWER(TRIM($fallback))"), $values);
                    }
                });
            });
        }

        return $query;
    }

    public function row(Enquiry $enquiry): array
    {
        $owner = $enquiry->user;
        $area = $owner?->role === User::ROLE_AREA_MANAGER ? $owner : ($owner?->manager?->role === User::ROLE_AREA_MANAGER ? $owner->manager : null);
        $prospect = $enquiry->prospectSheet;
        $answer = fn ($value) => match (strtolower(trim((string) $value))) {
            'yes', '1' => 'Yes', 'no', '0' => 'No', 'not_asked' => 'Not asked', default => 'Not recorded',
        };
        $preferProspect = fn ($value, $fallback) => trim((string) $value) !== '' ? $value : $fallback;
        $useProspectTestDrive = trim((string) $prospect?->test_drive_given) !== '';
        $testDrive = $answer($useProspectTestDrive ? $prospect->test_drive_given : $enquiry->followup_test_drive_given);
        $testDriveDate = $useProspectTestDrive ? $prospect->test_drive_date : $enquiry->followup_test_drive_when;
        $testDriveReason = $useProspectTestDrive ? $prospect->test_drive_not_given_reason : $enquiry->followup_test_drive_not_given_reason;

        return [
            'id' => $enquiry->id,
            'date' => $enquiry->created_at?->format('Y-m-d H:i'),
            'customer' => trim(($enquiry->customer?->title ? $enquiry->customer->title.'. ' : '').($enquiry->customer?->name ?? 'Unknown')),
            'phone' => implode(', ', $enquiry->customer?->mobile_numbers ?? []),
            'district' => $enquiry->customer?->district ?? '',
            'area_manager' => $area?->name ?? 'Unassigned',
            'consultant' => $owner?->role === User::ROLE_SALES_CONSULTANT ? $owner->name : 'Unassigned',
            'owner' => $owner?->name ?? 'Unassigned',
            'vehicle_model' => collect($enquiry->selectedVehicleItems())->pluck('model')->filter()->unique()->implode(', '),
            'lead_source' => $enquiry->lead_source ?? '',
            'lead_status' => ucfirst($prospect?->lead_status ?? ''),
            'lead_result' => ucfirst($enquiry->terminalLeadResult() ?? ($enquiry->followup_result ?: ($enquiry->status ?: 'Not recorded'))),
            'test_drive' => $testDrive,
            'test_drive_date' => $testDrive === 'Yes' && $testDriveDate ? Carbon::parse($testDriveDate)->format('Y-m-d') : '',
            'test_drive_not_given_reason' => $testDrive === 'No' ? ($testDriveReason ?? '') : '',
            'first_buyer' => $answer($preferProspect($prospect?->first_time_buyer, $enquiry->followup_first_time_buyer)),
            'interested_exchange' => $answer($preferProspect($prospect?->interested_in_exchange, $enquiry->exchange)),
            'competition' => $enquiry->terminalLeadResult() === 'lost' && strtolower(trim((string) $enquiry->followup_lost_to)) === 'competitor' ? 'Yes' : 'No',
            'competition_brand' => $enquiry->followup_lost_competition_brand ?? '',
            'competition_model' => $enquiry->followup_lost_competition_model ?? '',
        ];
    }

    public function headings(): array
    {
        return ['Lead ID', 'Inquiry Date', 'Customer', 'Phone', 'District', 'Area Manager', 'Consultant', 'Created By',
            'Vehicle Model', 'Lead Source', 'Lead Status', 'Lead Result', 'Test Drive Given', 'Test Drive Given Date',
            'Reason for Not Giving Test Drive', 'First Time Buyer',
            'Interested in Exchange', 'Lost to Competitor', 'Competitor Brand', 'Competitor Model'];
    }
}
