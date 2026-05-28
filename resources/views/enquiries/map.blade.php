@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/enquiry-map.css') }}">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
@php
    $resolvedDate = $selectedDate ?? now()->toDateString();
    $resolvedMapDateLabel = $mapDateLabel ?? $resolvedDate;
    $resolvedDateSlash = \Carbon\Carbon::parse($resolvedDate)->format('Y/m/d');
    $resolvedListHeading = $listHeading ?? ('Enquiries on ' . $resolvedDateSlash);
@endphp

<div class="enquiry-map-page">
    <header class="map-topbar">
        <a href="{{ route('dashboard.main') }}" class="brand-logo-link" aria-label="Go to dashboard">
            <img src="{{ asset('icons/logo.png') }}" alt="Ideal Motors" class="brand-logo">
        </a>
        <div class="map-topbar-actions">
            <a href="{{ url('/epr') }}" class="map-link-btn">EPR List</a>
        </div>
    </header>

    <main class="map-shell">
        <form method="GET" action="{{ route('enquiries.map') }}" class="map-filter-form">
            <div class="map-filter-grid">
                <label class="map-field" for="mapFromDate">
                    <span>From Date</span>
                    <input type="date" id="mapFromDate" name="from_date" value="{{ $selectedFromDate ?? '' }}">
                </label>

                <label class="map-field" for="mapToDate">
                    <span>To Date</span>
                    <input type="date" id="mapToDate" name="to_date" value="{{ $selectedToDate ?? '' }}">
                </label>

                <label class="map-field map-field-user" for="mapUser">
                    <span>User</span>
                    <select id="mapUser" name="user_id">
                        <option value="">All visible hierarchy users</option>
                        @foreach($availableUsers as $mapUser)
                            <option value="{{ $mapUser->id }}" @selected((string) $selectedUserId === (string) $mapUser->id)>
                                {{ $mapUser->name }} ({{ $mapUser->role_label }})
                            </option>
                        @endforeach
                    </select>
                </label>

                <button type="submit" class="map-apply-btn">
                    <span aria-hidden="true">⏷</span>
                    Apply Filter
                </button>
            </div>
        </form>

        @if(!empty($selectedFilterUserName))
            <div class="map-filter-note">
                Showing locations for <strong>{{ $selectedFilterUserName }}</strong> and hierarchy users
                @if(!empty($selectedHierarchyCount))
                    ({{ $selectedHierarchyCount }} user{{ $selectedHierarchyCount === 1 ? '' : 's' }}).
                @endif
            </div>
        @endif

        <section class="map-panel">
            <div class="map-summary">
                <strong>{{ $resolvedMapDateLabel }}</strong>
                <span>{{ $mapPoints->count() }} Enquiry location(s)</span>
            </div>

            <div id="dayMap" class="day-map" aria-label="Day wise enquiry location map"></div>
        </section>

        <p class="map-list-footer">{{ $resolvedListHeading }}</p>

        <section class="map-list">
            @forelse($mapPoints as $point)
                <article class="map-list-card">
                    <p><strong>{{ $point['name'] }}</strong> ({{ $point['phone'] }})</p>
                    <p>{{ $point['vehicle'] ?: 'Vehicle not set' }}</p>
                    <p>Created by {{ $point['owner'] }}</p>
                    <p>{{ $point['location'] }} | {{ $point['captured_at_label'] ?? $point['time'] }}</p>
                </article>
            @empty
                <p class="map-empty">No captured enquiry locations for the selected filter.</p>
            @endforelse
        </section>
    </main>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    function initMap() {
        // Google Maps callback placeholder. Leaflet map rendering is handled below.
    }
</script>
<script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCv4YJf305Z2rZY_DfmlmBtJ4L-iPtdf-A&callback=initMap"></script>
<script>
    (function () {
        const rawPoints = @json($mapPoints ?? []);
        const points = Array.isArray(rawPoints) ? rawPoints : [];
        const mapEl = document.getElementById('dayMap');

        if (!mapEl || typeof L === 'undefined') {
            return;
        }

        const defaultCenter = [7.8731, 80.7718];
        const normalizedPoints = points
            .map((point) => ({
                ...point,
                lat: Number(point?.lat),
                lng: Number(point?.lng)
            }))
            .filter((point) => Number.isFinite(point.lat) && Number.isFinite(point.lng));
        const initialCenter = normalizedPoints.length
            ? [normalizedPoints[0].lat, normalizedPoints[0].lng]
            : defaultCenter;

        const map = L.map(mapEl).setView(initialCenter, normalizedPoints.length ? 11 : 7);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        const bounds = [];
        const escapeHtml = (value) => String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

        normalizedPoints.forEach((point) => {
            bounds.push([point.lat, point.lng]);

            const popup = `
                <div>
                    <strong>${escapeHtml(point.name)}</strong><br>
                    ${escapeHtml(point.phone)}<br>
                    ${escapeHtml(point.vehicle || 'Vehicle not set')}<br>
                    Created By: ${escapeHtml(point.owner)}<br>
                    ${escapeHtml(point.location)} | ${escapeHtml(point.captured_at_label || point.time)}
                </div>
            `;

            L.marker([point.lat, point.lng]).addTo(map).bindPopup(popup);
        });

        if (bounds.length > 1) {
            map.fitBounds(bounds, { padding: [30, 30] });
        }
    })();
</script>
@endsection
