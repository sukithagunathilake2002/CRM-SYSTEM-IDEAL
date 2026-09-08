<?php

namespace App\Providers;

use App\Models\Enquiry;
use App\Models\SalesConsultantReminder;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer(['layouts.app', 'layouts.portal'], function ($view): void {
            $view->with([
                'globalSystemReminders' => collect(),
                'globalTodayFollowups' => collect(),
                'globalNotificationCount' => 0,
            ]);

            $user = Auth::user();
            if (!$user) {
                return;
            }

            $viewerId = (int) $user->id;
            $visibleUserIds = collect([$viewerId])->filter()->values();

            if (Schema::hasTable('users')) {
                if ($user->role === User::ROLE_SUPER_ADMIN) {
                    $visibleUserIds = User::query()->pluck('id');
                } elseif ($user->role === User::ROLE_HEAD_OF_SALES) {
                    $areaIds = User::query()
                        ->where('role', User::ROLE_AREA_MANAGER)
                        ->where('manager_id', $viewerId)
                        ->pluck('id');
                    $consultantIds = User::query()
                        ->where('role', User::ROLE_SALES_CONSULTANT)
                        ->whereIn('manager_id', $areaIds)
                        ->pluck('id');

                    $visibleUserIds = $visibleUserIds
                        ->merge($areaIds)
                        ->merge($consultantIds)
                        ->unique()
                        ->values();
                } elseif ($user->role === User::ROLE_AREA_MANAGER) {
                    $consultantIds = User::query()
                        ->where('role', User::ROLE_SALES_CONSULTANT)
                        ->where('manager_id', $viewerId)
                        ->pluck('id');

                    $visibleUserIds = $visibleUserIds
                        ->merge($consultantIds)
                        ->unique()
                        ->values();
                }
            }

            $todayFollowups = collect();
            if (Schema::hasTable('enquiries')) {
                $todayFollowups = Enquiry::query()
                    ->with(['customer:id,title,name'])
                    ->select(['id', 'customer_id', 'follow_type', 'follow_date', 'follow_time', 'followup_status'])
                    ->whereIn('user_id', $visibleUserIds)
                    ->nonTerminalLead()
                    ->whereDate('follow_date', '<=', now('Asia/Colombo')->toDateString())
                    ->whereRaw("LOWER(COALESCE(followup_status, 'pending')) NOT IN (?, ?)", ['done', 'not_done'])
                    ->orderBy('follow_date', 'desc')
                    ->orderBy('follow_time')
                    ->limit(8)
                    ->get();
            }

            $systemReminders = collect();
            if (Schema::hasTable('sales_consultant_reminders')) {
                $systemReminders = SalesConsultantReminder::query()
                    ->with('sender:id,name')
                    ->where('recipient_id', $viewerId)
                    ->whereNull('read_at')
                    ->latest()
                    ->limit(5)
                    ->get();
            }

            $view->with([
                'globalSystemReminders' => $systemReminders,
                'globalTodayFollowups' => $todayFollowups,
                'globalNotificationCount' => $systemReminders->count() + $todayFollowups->count(),
            ]);
        });
    }
}
