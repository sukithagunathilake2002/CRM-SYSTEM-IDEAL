@php
    $notificationClass = $notificationClass ?? 'crm-notifications';
    $summaryClass = $summaryClass ?? 'crm-notify-btn';
    $badgeClass = $badgeClass ?? 'crm-notify-badge';
    $menuClass = $menuClass ?? 'crm-notify-menu';
    $titleClass = $titleClass ?? 'crm-notify-title';
    $itemClass = $itemClass ?? 'crm-notify-item';
    $emptyClass = $emptyClass ?? 'crm-notify-empty';
    $reminderClass = $reminderClass ?? 'crm-reminder-item';

    $notificationCount = (int) ($globalNotificationCount ?? 0);
    $systemReminders = $globalSystemReminders ?? collect();
    $todayFollowups = $globalTodayFollowups ?? collect();
@endphp

<details class="{{ $notificationClass }}">
    <summary class="{{ $summaryClass }}" aria-label="Open notifications" title="Notifications">
        <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
            <path d="M15 18H5l1.2-1.6A2 2 0 0 0 6.6 15V11a5.4 5.4 0 0 1 10.8 0v4a2 2 0 0 0 .4 1.4L19 18h-4" stroke-linecap="round" stroke-linejoin="round"></path>
            <path d="M10 20a2 2 0 0 0 4 0" stroke-linecap="round"></path>
        </svg>
        @if($notificationCount > 0)
            <span class="{{ $badgeClass }}">{{ $notificationCount }}</span>
        @endif
    </summary>
    <div class="{{ $menuClass }}">
        <p class="{{ $titleClass }}">System Reminders</p>
        @forelse($systemReminders as $reminder)
            <div class="{{ trim($itemClass . ' ' . $reminderClass) }}">
                <span>{{ $reminder->sender?->name ?? 'Manager' }} sent a reminder</span>
                <small>
                    Registration {{ $reminder->pending_registration_count }},
                    Follow Up {{ $reminder->pending_followup_count }},
                    Booking {{ $reminder->pending_booking_count }},
                    Delivery {{ $reminder->pending_delivery_count }}
                </small>
                <form method="POST" action="{{ route('dashboard.reminders.read', $reminder->id) }}">
                    @csrf
                    <button type="submit">Mark Read</button>
                </form>
            </div>
        @empty
            <p class="{{ $emptyClass }}">No system reminders.</p>
        @endforelse

        <p class="{{ $titleClass }}">Due Followups</p>
        @forelse($todayFollowups as $followup)
            @php
                $customerTitle = trim((string) ($followup->customer?->title ?? ''));
                $customerName = trim((string) ($followup->customer?->name ?? 'Customer'));
                $customerLabel = trim($customerTitle . ' ' . $customerName);
                $followupTime = $followup->follow_time ? substr((string) $followup->follow_time, 0, 5) : '--:--';
                $followupType = trim((string) ($followup->follow_type ?? 'Followup'));
            @endphp
            <a href="{{ route('followup.show', $followup->id) }}" class="{{ $itemClass }}">
                <span>{{ $customerLabel !== '' ? $customerLabel : 'Customer' }}</span>
                <small>{{ $followupType }} at {{ $followupTime }}</small>
            </a>
        @empty
            <p class="{{ $emptyClass }}">No due followups.</p>
        @endforelse
    </div>
</details>
