@php
    $leadSidebarUser = $leadSidebarUser ?? auth()->user();
    $leadSidebarLinks = [
        ['href' => route('enquiries.list', ['lead_status' => 'hot']), 'label' => 'Hot Leads', 'icon' => 'flame'],
        ['href' => route('enquiries.list', ['lead_status' => 'warm']), 'label' => 'Warm Leads', 'icon' => 'sun'],
        ['href' => route('enquiries.list', ['lead_status' => 'cold']), 'label' => 'Cold Leads', 'icon' => 'snowflake'],
        ['href' => route('enquiries.list', ['lead_result' => 'active']), 'label' => 'Active Lead', 'icon' => 'activity'],
        ['href' => route('enquiries.list', ['lead_result' => 'lost']), 'label' => 'Lost Lead', 'icon' => 'circle-x'],
        ['href' => route('enquiries.list', ['lead_result' => 'closed']), 'label' => 'Closed Lead', 'icon' => 'circle-check'],
        ['href' => route('enquiries.list', ['registration' => 'pending']), 'label' => 'EPR', 'icon' => 'clipboard'],
        ['href' => route('enquiries.list', ['booking' => 'active']), 'label' => 'Active Booking', 'icon' => 'calendar-check'],
        ['href' => url('/epr'), 'label' => 'Cancelled Booking', 'icon' => 'calendar-x'],
        ['href' => route('enquiries.list', ['delivery' => 'active']), 'label' => 'Deliveries', 'icon' => 'truck'],
        ['href' => route('enquiries.list', ['delivery_approval' => 'pending']), 'label' => 'Pending Delivery', 'icon' => 'clock'],
    ];

    if ($leadSidebarUser?->role === \App\Models\User::ROLE_SALES_CONSULTANT) {
        $leadSidebarLinks[] = ['href' => route('enquiries.list', ['delivery_approval' => 'approved']), 'label' => 'Approved Delivery', 'icon' => 'badge-check'];
    }

    $leadSidebarLinks[] = ['href' => route('enquiries.list', ['view' => 'all']), 'label' => 'All Leads', 'icon' => 'list'];
@endphp

@foreach($leadSidebarLinks as $leadSidebarLink)
    <a href="{{ $leadSidebarLink['href'] }}">
        <span class="lead-sidebar-icon lead-sidebar-icon-{{ $leadSidebarLink['icon'] }}" aria-hidden="true">
            @switch($leadSidebarLink['icon'])
                @case('flame')
                    <svg viewBox="0 0 24 24"><path d="M12 22c4.2 0 7-2.9 7-6.8 0-2.5-1.3-4.8-3.4-6.6-.6 2-1.7 3.1-3.1 3.8.2-3.1-1.1-5.5-3.9-7.9.1 3.2-1.6 5-2.9 6.7A7 7 0 0 0 5 15.2C5 19.1 7.8 22 12 22Z"/><path d="M12.1 18.7c1.7 0 2.9-1.2 2.9-2.8 0-1.1-.6-2-1.5-2.7-.3 1-.9 1.5-1.8 1.8.1-1.3-.4-2.4-1.5-3.4 0 1.4-.8 2.2-1.3 3A3 3 0 0 0 9 15.9c0 1.6 1.2 2.8 3.1 2.8Z"/></svg>
                    @break
                @case('sun')
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                    @break
                @case('snowflake')
                    <svg viewBox="0 0 24 24"><path d="M12 2v20M4 6l16 12M20 6 4 18M8 4l4 3 4-3M8 20l4-3 4 3M3 10l5 2-5 2M21 10l-5 2 5 2"/></svg>
                    @break
                @case('activity')
                    <svg viewBox="0 0 24 24"><path d="M3 12h4l3-7 4 14 3-7h4"/></svg>
                    @break
                @case('circle-x')
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m9 9 6 6M15 9l-6 6"/></svg>
                    @break
                @case('circle-check')
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></svg>
                    @break
                @case('clipboard')
                    <svg viewBox="0 0 24 24"><path d="M9 4h6l1 2h3v14H5V6h3l1-2Z"/><path d="M9 10h6M9 14h6M9 18h4"/></svg>
                    @break
                @case('calendar-check')
                    <svg viewBox="0 0 24 24"><path d="M6 3v3M18 3v3M4 8h16M5 5h14v15H5z"/><path d="m8 14 2.5 2.5L16 11"/></svg>
                    @break
                @case('calendar-x')
                    <svg viewBox="0 0 24 24"><path d="M6 3v3M18 3v3M4 8h16M5 5h14v15H5z"/><path d="m9 12 5 5M14 12l-5 5"/></svg>
                    @break
                @case('truck')
                    <svg viewBox="0 0 24 24"><path d="M3 6h11v10H3zM14 10h4l3 3v3h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/></svg>
                    @break
                @case('clock')
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    @break
                @case('badge-check')
                    <svg viewBox="0 0 24 24"><path d="m12 2 2.4 2 3.1-.2 1 3 2.5 1.8-1.2 2.9 1.2 2.9-2.5 1.8-1 3-3.1-.2-2.4 2-2.4-2-3.1.2-1-3L3 14.4l1.2-2.9L3 8.6l2.5-1.8 1-3 3.1.2L12 2Z"/><path d="m8.5 12 2.2 2.2 4.8-5"/></svg>
                    @break
                @default
                    <svg viewBox="0 0 24 24"><path d="M8 6h13M8 12h13M8 18h13"/><path d="M3 6h.01M3 12h.01M3 18h.01"/></svg>
            @endswitch
        </span>
        <span>{{ $leadSidebarLink['label'] }}</span>
    </a>
@endforeach
