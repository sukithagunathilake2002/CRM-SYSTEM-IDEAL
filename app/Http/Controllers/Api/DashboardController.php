<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
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

    public function stats(Request $request)
    {
        $viewer = $request->user();
        $accessibleUserIds = $this->resolveAccessibleUserIds($viewer);

        $today = now('Asia/Colombo');
        $todayStart = $today->copy()->startOfDay();
        $todayEnd = $today->copy()->endOfDay();

        $totalLeads = Enquiry::whereIn('user_id', $accessibleUserIds)->count();
        $todayLeads = Enquiry::whereIn('user_id', $accessibleUserIds)
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->count();

        $pendingFollowups = Enquiry::whereIn('user_id', $accessibleUserIds)
            ->whereRaw("LOWER(COALESCE(followup_status, '')) <> ?", ['done'])
            ->whereDate('follow_date', $today->toDateString())
            ->count();

        $todayFollowups = Enquiry::with(['customer:id,title,name'])
            ->where('user_id', $viewer->id)
            ->whereDate('follow_date', $today->toDateString())
            ->whereRaw("LOWER(COALESCE(followup_status, '')) <> ?", ['done'])
            ->orderBy('follow_time')
            ->limit(10)
            ->get()
            ->map(function ($enquiry) {
                return [
                    'id' => $enquiry->id,
                    'customer_name' => trim(($enquiry->customer?->title ? $enquiry->customer->title . ' ' : '') . ($enquiry->customer?->name ?? 'Unknown')),
                    'follow_type' => $enquiry->follow_type,
                    'follow_time' => $enquiry->follow_time,
                ];
            });

        $leadStatusCounts = [
            'hot' => 0,
            'warm' => 0,
            'cold' => 0,
        ];

        $enquiries = Enquiry::whereIn('user_id', $accessibleUserIds)
            ->whereHas('prospectSheet')
            ->get();

        foreach ($enquiries as $enquiry) {
            $status = strtolower(trim((string) $enquiry->prospectSheet?->lead_status));
            if (isset($leadStatusCounts[$status])) {
                $leadStatusCounts[$status]++;
            }
        }

        return response()->json([
            'total_leads' => $totalLeads,
            'today_leads' => $todayLeads,
            'pending_followups' => $pendingFollowups,
            'lead_status_counts' => $leadStatusCounts,
            'today_followups' => $todayFollowups,
            'user' => [
                'name' => $viewer->name,
                'role_label' => $viewer->role_label,
            ],
        ]);
    }

    public function getDistrictData(Request $request)
    {
        $viewer = $request->user();
        $accessibleUserIds = $this->resolveAccessibleUserIds($viewer);

        $districtData = [];
        foreach (User::DISTRICT_OPTIONS as $district) {
            $count = Enquiry::with(['customer'])
                ->whereIn('user_id', $accessibleUserIds)
                ->whereHas('customer', function ($query) use ($district) {
                    $query->whereRaw('LOWER(TRIM(COALESCE(district, \'\'))) = ?', [strtolower($district)]);
                })
                ->whereRaw("LOWER(COALESCE(followup_status, '')) <> ?", ['done'])
                ->count();

            $districtData[] = [
                'district' => $district,
                'count' => $count,
            ];
        }

        return response()->json($districtData);
    }
}