<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
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
        $todayDate = $today->toDateString();

        // Total leads
        $totalLeads = Enquiry::whereIn('user_id', $accessibleUserIds)->count();
        
        // Today's leads
        $todayLeads = Enquiry::whereIn('user_id', $accessibleUserIds)
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->count();

        // Pending followups (due today)
        $pendingFollowups = Enquiry::whereIn('user_id', $accessibleUserIds)
            ->whereRaw("LOWER(COALESCE(followup_status, '')) <> ?", ['done'])
            ->whereDate('follow_date', $todayDate)
            ->count();

        // EPR counts by follow type (matches PC version dashboard)
        $baseQuery = Enquiry::with(['customer', 'vehicle'])
            ->whereIn('user_id', $accessibleUserIds)
            ->pendingRegistration()
            ->whereRaw("LOWER(COALESCE(followup_status, '')) <> ?", ['done']);

        $dueFollowupQuery = (clone $baseQuery)
            ->whereDate('follow_date', '<=', $todayDate);

        $callCount = (clone $dueFollowupQuery)
            ->whereRaw('LOWER(COALESCE(follow_type, \'\')) LIKE ?', ['%call%'])
            ->count();

        $showroomCount = (clone $dueFollowupQuery)
            ->whereRaw('LOWER(COALESCE(follow_type, \'\')) LIKE ?', ['%showroom%'])
            ->count();

        $homeCount = (clone $dueFollowupQuery)
            ->whereRaw('LOWER(COALESCE(follow_type, \'\')) LIKE ?', ['%home%'])
            ->count();

        $totalEprCount = (clone $baseQuery)->count();

        // Today's followups for the logged-in user (only their own)
        $todayFollowups = Enquiry::with(['customer:id,title,name'])
            ->where('user_id', $viewer->id)
            ->whereDate('follow_date', $todayDate)
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

        // Lead status counts - Only count leads with completed prospect sheets
        $leadStatusCounts = [
            'hot' => 0,
            'warm' => 0,
            'cold' => 0,
        ];

        $enquiries = Enquiry::whereIn('user_id', $accessibleUserIds)
            ->whereHas('prospectSheet', function ($query) {
                $query->where('current_step', '>=', 5);
            })
            ->get();

        foreach ($enquiries as $enquiry) {
            $status = strtolower(trim((string) $enquiry->prospectSheet?->lead_status));
            if (isset($leadStatusCounts[$status])) {
                $leadStatusCounts[$status]++;
            }
        }

        // Active bookings - MATCHES WEB VERSION EXACTLY
        // Count enquiries that have a completed booking and are not terminal
        $activeBookings = Enquiry::query()
            ->whereIn('user_id', $accessibleUserIds)
            ->whereHas('booking', function ($query) {
                $query->whereNotNull('booking_completed_at');
            })
            ->whereRaw("LOWER(COALESCE(status, 'open')) NOT IN ('closed', 'cancelled', 'canceled', 'lost')")
            ->whereRaw("LOWER(COALESCE(followup_result, '')) NOT IN ('lost', 'closed')")
            ->count();

        // Active inquiries - MATCHES WEB VERSION EXACTLY
        // Count enquiries that are not terminal
        $activeInquiries = Enquiry::query()
            ->whereIn('user_id', $accessibleUserIds)
            ->whereRaw("LOWER(COALESCE(status, 'open')) NOT IN ('closed', 'cancelled', 'canceled', 'lost')")
            ->whereRaw("LOWER(COALESCE(followup_result, '')) NOT IN ('lost', 'closed')")
            ->count();

        return response()->json([
            'total_leads' => $totalLeads,
            'today_leads' => $todayLeads,
            'pending_followups' => $pendingFollowups,
            'lead_status_counts' => $leadStatusCounts,
            'today_followups' => $todayFollowups,
            'active_bookings' => $activeBookings,
            'active_inquiries' => $activeInquiries,
            // EPR counts by follow type (matches PC version)
            'call_count' => $callCount,
            'showroom_count' => $showroomCount,
            'home_count' => $homeCount,
            'total_epr_count' => $totalEprCount,
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