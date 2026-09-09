<?php

namespace App\Support;

use App\Models\Enquiry;
use App\Models\User;
use App\Models\Vehicle;

class HeadOfSalesOverview
{
    public function build(User $viewer): array
    {
        // Aggregate before returning rows: the landing page needs totals, not lead models or reports.
        $rows = Enquiry::query()
            ->leftJoin('customers', 'customers.id', '=', 'enquiries.customer_id')
            ->whereIn('enquiries.user_id', $viewer->accessibleUserIds())
            ->whereIn('enquiries.vehicle_id', Vehicle::visibleTo($viewer)->select('id'))
            ->toBase()
            ->selectRaw("customers.district, COUNT(*) as total_leads,
                SUM(CASE WHEN LOWER(TRIM(COALESCE(followup_result, ''))) = 'active' THEN 1 ELSE 0 END) as active_leads,
                SUM(CASE WHEN LOWER(TRIM(COALESCE(followup_result, ''))) = 'lost' THEN 1 ELSE 0 END) as lost_leads,
                SUM(CASE WHEN LOWER(TRIM(COALESCE(followup_result, ''))) = 'closed' THEN 1 ELSE 0 END) as closed_leads,
                SUM(CASE WHEN LOWER(TRIM(COALESCE(followup_status, ''))) = 'done' THEN 1 ELSE 0 END) as done_followups")
            ->groupBy('customers.district')
            ->get();

        $kpis = array_fill_keys(['total_leads', 'active_leads', 'lost_leads', 'closed_leads', 'done_followups'], 0);
        $districts = [];
        foreach ($rows as $row) {
            foreach ($kpis as $key => $total) {
                $kpis[$key] += (int) $row->$key;
            }
            $district = trim((string) $row->district);
            $district = $district === '' || in_array(strtolower($district), ['na', 'n/a'], true)
                ? 'N/A' : ucwords(strtolower($district));
            $districts[$district] = ($districts[$district] ?? 0) + (int) $row->total_leads;
        }
        $doneFollowups = $kpis['done_followups'];
        unset($kpis['done_followups']);
        $kpis['pending_followups'] = $kpis['total_leads'] - $doneFollowups;
        $kpis['done_followups'] = $doneFollowups;
        arsort($districts);
        $provinces = [];
        foreach ($districts as $district => $total) {
            $province = User::provinceForDistrict($district) ?? 'N/A';
            $provinces[$province] = ($provinces[$province] ?? 0) + $total;
        }
        arsort($provinces);

        return [
            'kpis' => $kpis,
            'by_district' => collect($districts)->map(fn ($total, $district) => ['district' => $district, 'leads' => $total])->values()->all(),
            'by_province' => collect($provinces)->map(fn ($total, $province) => ['province' => $province, 'leads' => $total])->values()->all(),
        ];
    }
}
