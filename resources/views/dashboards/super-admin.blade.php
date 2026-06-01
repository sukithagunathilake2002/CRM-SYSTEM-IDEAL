@extends('layouts.portal')

@section('content')
<section class="card">
    <h1>Super Admin Dashboard</h1>
    <p>Manage full organization hierarchy and access all CRM modules.</p>

    <div class="stats-grid">
        <div class="stat"><strong>{{ $counts['head_of_sales'] }}</strong><span>Heads Of Sales</span></div>
        <div class="stat"><strong>{{ $counts['regional_manager'] }}</strong><span>Regional Managers</span></div>
        <div class="stat"><strong>{{ $counts['area_manager'] }}</strong><span>Area Managers</span></div>
        <div class="stat"><strong>{{ $counts['sales_consultant'] }}</strong><span>Sales Consultants</span></div>
    </div>

    <div class="quick-links">
        <a class="btn-link" href="{{ route('auth.register.form', 'head-of-sales') }}">Register Head Of Sales</a>
        <a class="btn-link" href="{{ route('auth.register.form', 'regional-manager') }}">Register Regional Manager</a>
        <a class="btn-link" href="{{ route('auth.register.form', 'area-manager') }}">Register Area Manager</a>
        <a class="btn-link" href="{{ route('auth.register.form', 'sales-consultant') }}">Register Sales Consultant</a>
        <a class="btn-link alt" href="{{ url('/epr') }}">Open EPR</a>
        <a class="btn-link alt" href="{{ route('enquiries.map', ['date' => now()->toDateString()]) }}">Open Day Map</a>
    </div>
</section>

<section class="card">
    <h2>Head Of Sales Hierarchy Summary</h2>
    <div class="stats-grid">
        <div class="stat"><strong>{{ $dependentCounts['dependent_users'] }}</strong><span>Total Dependent Users</span></div>
        <div class="stat"><strong>{{ $dependentCounts['regional_managers'] }}</strong><span>Regional Managers</span></div>
        <div class="stat"><strong>{{ $dependentCounts['area_managers'] }}</strong><span>Area Managers</span></div>
        <div class="stat"><strong>{{ $dependentCounts['sales_consultants'] }}</strong><span>Sales Consultants</span></div>
    </div>
</section>

<section class="card">
    <h2>Manage All Users</h2>
    <p>Edit user details, role assignment, reporting manager, and password.</p>
    <div class="quick-links manage-users-actions">
        <a class="btn-link" href="{{ route('dashboard.super_admin.consultant_transfer.form') }}">Transfer Consultant Data</a>
    </div>

    <div class="analytics-table-wrap">
        <table class="analytics-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Manager</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($manageableUsers as $managedUser)
                    <tr>
                        <td>{{ $managedUser->name }}</td>
                        <td>{{ $managedUser->email }}</td>
                        <td>{{ $managedUser->role_label }}</td>
                        <td>{{ $managedUser->manager?->name ?? '-' }}</td>
                        <td>
                            <div class="quick-links user-table-actions">
                                <a class="btn-link alt" href="{{ route('dashboard.super_admin.users.edit', $managedUser) }}">Edit</a>
                                @if($managedUser->role === \App\Models\User::ROLE_SALES_CONSULTANT)
                                    <a class="btn-link" href="{{ route('dashboard.super_admin.consultant_transfer.form', ['source_consultant_id' => $managedUser->id]) }}">Transfer Data</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="card">
    <h2>Head Of Sales Team Hierarchy</h2>
    <ul class="list hierarchy-list">
        @forelse($headHierarchy as $head)
            <li>
                <strong>{{ $head['name'] }} (Head Of Sales)</strong>
                <span>{{ $head['email'] }}</span>
                <span>
                    Dependent Users: {{ $head['dependent_users_count'] }} |
                    Regional Managers: {{ $head['regional_managers_count'] }} |
                    Area Managers: {{ $head['area_managers_count'] }} |
                    Sales Consultants: {{ $head['sales_consultants_count'] }}
                </span>

                @if(!empty($head['regional_managers']))
                    <div class="hierarchy-children">
                        @foreach($head['regional_managers'] as $regionalManager)
                            <div class="hierarchy-child">
                                <strong>{{ $regionalManager['name'] }} (Regional Manager)</strong>
                                <span>{{ $regionalManager['email'] }}</span>
                                <span>Area Managers: {{ $regionalManager['area_managers_count'] }} | Sales Consultants: {{ $regionalManager['sales_consultants_count'] }}</span>

                                @if(!empty($regionalManager['area_managers']))
                                    <div class="hierarchy-children">
                                        @foreach($regionalManager['area_managers'] as $areaManager)
                                            <div class="hierarchy-child">
                                                <strong>{{ $areaManager['name'] }} (Area Manager)</strong>
                                                <span>{{ $areaManager['email'] }}</span>
                                                <span>Sales Consultants: {{ $areaManager['sales_consultants_count'] }}</span>

                                                @if(!empty($areaManager['sales_consultants']))
                                                    <div class="hierarchy-leaf-wrap">
                                                        @foreach($areaManager['sales_consultants'] as $salesConsultant)
                                                            <span class="hierarchy-pill">{{ $salesConsultant['name'] }}</span>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span>No Area Managers assigned under this Regional Manager yet.</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <span>No Regional Managers assigned under this Head Of Sales yet.</span>
                @endif
            </li>
        @empty
            <li>No Head Of Sales users yet.</li>
        @endforelse
    </ul>
</section>

<section id="districtOverviewCard" class="card">
    <h2>Sri Lanka District Lead Overview</h2>
    <p>District map with current filtered lead totals.</p>
    <div class="district-overview-grid">
        <div class="district-map-card">
            <div id="districtLeadMap" class="district-lead-map" aria-label="Sri Lanka district lead map"></div>
            <div class="district-map-scale">
                <span class="district-map-scale-title">Lead density</span>
                <div class="district-map-scale-bar"></div>
                <div class="district-map-scale-labels">
                    <span>Low</span>
                    <span>High</span>
                </div>
            </div>
        </div>
        <div class="district-summary-card">
            <div id="districtLeadInfoCard" class="district-lead-info-card" aria-live="polite">
                <span class="district-lead-info-label">Total Leads</span>
                <h3 id="districtLeadInfoName" class="district-lead-info-name">-</h3>
                <p class="district-lead-info-value"><span id="districtLeadInfoCount">0</span> Leads</p>
            </div>
            <div class="analytics-table-wrap">
                <table class="analytics-table district-summary-table">
                    <thead>
                        <tr>
                            <th>District</th>
                            <th>Leads</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($analytics['by_district'] ?? []) as $row)
                            <tr class="district-summary-row"
                                data-district="{{ $row['district'] }}"
                                data-leads="{{ (int) ($row['leads'] ?? 0) }}">
                                <td>{{ $row['district'] }}</td>
                                <td>{{ $row['leads'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2">No district data available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<script>
    (() => {
        const mount = document.getElementById('districtLeadMap');
        const overviewCard = document.getElementById('districtOverviewCard');
        if (!mount || !overviewCard || overviewCard.dataset.initialized === '1') {
            return;
        }
        overviewCard.dataset.initialized = '1';

        const infoCard = document.getElementById('districtLeadInfoCard');
        const infoName = document.getElementById('districtLeadInfoName');
        const infoCount = document.getElementById('districtLeadInfoCount');
        const tableRows = Array.from(document.querySelectorAll('.district-summary-row'));

        const districtRows = @json($analytics['by_district'] ?? []);
        const normalize = (value) => String(value || '')
            .trim()
            .toLowerCase()
            .replace(/[^a-z]/g, '');
        const aliases = {
            monaragala: 'moneragala',
            mullativu: 'mullaitivu',
        };
        const nameByDistrictKey = {};
        const countByDistrict = {};
        const rowByDistrictKey = {};

        districtRows.forEach((row) => {
            const rawKey = normalize(row?.district);
            const key = aliases[rawKey] || rawKey;
            if (key === '' || key === 'na') {
                return;
            }
            nameByDistrictKey[key] = String(row?.district || '');
            countByDistrict[key] = Number(row?.leads || 0);
        });

        tableRows.forEach((row) => {
            const districtName = row.getAttribute('data-district') || '';
            const leads = Number(row.getAttribute('data-leads') || '0');
            const districtKey = aliases[normalize(districtName)] || normalize(districtName);
            if (!districtKey) {
                return;
            }

            rowByDistrictKey[districtKey] = row;
            row.setAttribute('data-district-key', districtKey);
            row.setAttribute('tabindex', '0');
            if (!nameByDistrictKey[districtKey]) {
                nameByDistrictKey[districtKey] = districtName;
            }
            if (!Number.isFinite(countByDistrict[districtKey])) {
                countByDistrict[districtKey] = leads;
            }
        });

        const values = Object.values(countByDistrict).filter((value) => Number.isFinite(value));
        const maxCount = values.length ? Math.max(...values) : 0;
        let selectedDistrictKey = '';
        let countAnimationFrame = null;
        const pathByDistrictKey = {};
        const markerByDistrictKey = {};
        const labelByDistrictKey = {};

        const getFillColor = (count) => {
            if (count <= 0 || maxCount <= 0) {
                return '#eef2ff';
            }

            const ratio = count / maxCount;
            if (ratio > 0.8) return '#1d4ed8';
            if (ratio > 0.6) return '#2563eb';
            if (ratio > 0.4) return '#3b82f6';
            if (ratio > 0.2) return '#60a5fa';
            return '#93c5fd';
        };

        const animateCountUp = (target) => {
            const maxTarget = Math.max(0, Number(target) || 0);
            const duration = 700;
            const start = performance.now();
            if (countAnimationFrame) {
                cancelAnimationFrame(countAnimationFrame);
            }
            infoCount.textContent = '0';

            const step = (now) => {
                const progress = Math.min((now - start) / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 3);
                infoCount.textContent = String(Math.round(maxTarget * eased));
                if (progress < 1) {
                    countAnimationFrame = requestAnimationFrame(step);
                } else {
                    countAnimationFrame = null;
                }
            };

            countAnimationFrame = requestAnimationFrame(step);
        };

        const pulseInfoCard = () => {
            if (!infoCard) {
                return;
            }
            infoCard.classList.remove('is-animating');
            void infoCard.offsetWidth;
            infoCard.classList.add('is-animating');
            setTimeout(() => infoCard.classList.remove('is-animating'), 360);
        };

        const selectDistrict = (districtKey) => {
            if (!districtKey || !nameByDistrictKey[districtKey]) {
                return;
            }

            selectedDistrictKey = districtKey;
            const leads = Number(countByDistrict[districtKey] || 0);

            Object.entries(pathByDistrictKey).forEach(([key, path]) => {
                path.classList.toggle('is-selected', key === districtKey);
            });

            Object.entries(markerByDistrictKey).forEach(([key, marker]) => {
                marker.classList.toggle('is-selected', key === districtKey);
            });

            Object.entries(labelByDistrictKey).forEach(([key, label]) => {
                label.classList.toggle('is-selected', key === districtKey);
            });

            Object.entries(rowByDistrictKey).forEach(([key, row]) => {
                row.classList.toggle('is-selected', key === districtKey);
            });

            if (infoName) {
                infoName.textContent = nameByDistrictKey[districtKey];
            }

            pulseInfoCard();
            animateCountUp(leads);
        };

        const bindRowInteraction = (row) => {
            const districtKey = row.getAttribute('data-district-key');
            if (!districtKey) {
                return;
            }

            row.addEventListener('click', () => selectDistrict(districtKey));
            row.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    selectDistrict(districtKey);
                }
            });
        };

        tableRows.forEach(bindRowInteraction);

        fetch(@json(asset('data/sri-lanka-districts-map.json')))
            .then((response) => response.json())
            .then((mapData) => {
                const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                svg.setAttribute('viewBox', mapData.viewBox || '0 0 450 793');
                svg.setAttribute('role', 'img');
                svg.setAttribute('aria-label', mapData.label || 'Sri Lanka district map');
                svg.classList.add('district-map-svg');

                const districtLayer = document.createElementNS('http://www.w3.org/2000/svg', 'g');
                const labelLayer = document.createElementNS('http://www.w3.org/2000/svg', 'g');
                labelLayer.classList.add('district-map-label-layer');

                (mapData.locations || []).forEach((location) => {
                    const districtName = String(location.name || '');
                    const rawKey = normalize(districtName);
                    const districtKey = aliases[rawKey] || rawKey;
                    const count = Number(countByDistrict[districtKey] || 0);

                    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                    path.setAttribute('d', String(location.path || ''));
                    path.setAttribute('fill', getFillColor(count));
                    path.setAttribute('stroke', '#c7d2fe');
                    path.setAttribute('stroke-width', '1.2');
                    path.classList.add('district-map-path');
                    path.setAttribute('data-district', districtName);
                    path.setAttribute('data-district-key', districtKey);
                    path.setAttribute('data-count', String(count));
                    path.setAttribute('role', 'button');
                    path.setAttribute('tabindex', '0');

                    const title = document.createElementNS('http://www.w3.org/2000/svg', 'title');
                    title.textContent = `${districtName}: ${count} lead${count === 1 ? '' : 's'}`;
                    path.appendChild(title);
                    districtLayer.appendChild(path);
                    pathByDistrictKey[districtKey] = path;
                    if (!nameByDistrictKey[districtKey]) {
                        nameByDistrictKey[districtKey] = districtName;
                    }
                });

                svg.appendChild(districtLayer);
                svg.appendChild(labelLayer);
                mount.innerHTML = '';
                mount.appendChild(svg);

                const paths = Array.from(svg.querySelectorAll('.district-map-path'));
                const pathMetrics = paths.map((path) => {
                    const box = path.getBBox();
                    const cx = box.x + (box.width / 2);
                    const cy = box.y + (box.height / 2);
                    const radius = Math.max(1, Math.hypot(box.width, box.height) / 2);
                    return { path, box, cx, cy, radius };
                });
                const metricsByPath = new Map(pathMetrics.map((item) => [item.path, item]));
                const viewBoxParts = String(mapData.viewBox || '0 0 450 793').trim().split(/\s+/).map(Number);
                const mapCenterX = Number.isFinite(viewBoxParts[0]) && Number.isFinite(viewBoxParts[2])
                    ? viewBoxParts[0] + (viewBoxParts[2] / 2)
                    : 225;
                const mapCenterY = Number.isFinite(viewBoxParts[1]) && Number.isFinite(viewBoxParts[3])
                    ? viewBoxParts[1] + (viewBoxParts[3] / 2)
                    : 396.5;

                const clampScaleWithoutOverlap = (currentPath, desiredScale) => {
                    const current = metricsByPath.get(currentPath);
                    if (!current) {
                        return desiredScale;
                    }

                    let maxAllowed = desiredScale;
                    const safeGap = 0.7;

                    pathMetrics.forEach((other) => {
                        if (other.path === current.path) {
                            return;
                        }

                        const dx = current.cx - other.cx;
                        const dy = current.cy - other.cy;
                        const centerDistance = Math.hypot(dx, dy);
                        if (!Number.isFinite(centerDistance) || centerDistance <= 0) {
                            return;
                        }

                        const candidateMax = (centerDistance - other.radius - safeGap) / current.radius;
                        if (Number.isFinite(candidateMax)) {
                            maxAllowed = Math.min(maxAllowed, candidateMax);
                        }
                    });

                    return Math.max(1.02, Math.min(desiredScale, maxAllowed));
                };

                paths.forEach((path) => {
                    const districtKey = path.getAttribute('data-district-key') || '';
                    const count = Number(path.getAttribute('data-count') || '0');
                    const metric = metricsByPath.get(path);
                    if (!metric) {
                        return;
                    }
                    const { box, cx, cy } = metric;
                    const area = box.width * box.height;

                    let selectScale = 2.18;
                    if (area > 30000) {
                        selectScale = 1.82;
                    } else if (area > 18000) {
                        selectScale = 1.94;
                    } else if (area > 10000) {
                        selectScale = 2.04;
                    } else if (area > 4500) {
                        selectScale = 2.12;
                    } else {
                        selectScale = 2.24;
                    }
                    selectScale = clampScaleWithoutOverlap(path, selectScale);
                    path.style.setProperty('--district-select-scale', String(selectScale));

                    const centerDx = cx - mapCenterX;
                    const centerDy = cy - mapCenterY;
                    const centerDistance = Math.hypot(centerDx, centerDy);
                    const normX = centerDistance > 0 ? centerDx / centerDistance : 0;
                    const normY = centerDistance > 0 ? centerDy / centerDistance : -1;
                    let popDistance = 24;
                    if (area > 30000) {
                        popDistance = 8;
                    } else if (area > 18000) {
                        popDistance = 12;
                    } else if (area > 10000) {
                        popDistance = 16;
                    } else if (area > 4500) {
                        popDistance = 20;
                    }
                    if (selectScale < 1.12) {
                        popDistance = Math.min(popDistance, 2);
                    } else if (selectScale < 1.22) {
                        popDistance = Math.min(popDistance, 4);
                    }
                    path.style.setProperty('--district-pop-x', `${(normX * popDistance).toFixed(2)}px`);
                    path.style.setProperty('--district-pop-y', `${(normY * popDistance).toFixed(2)}px`);

                    if (count <= 0) {
                        path.addEventListener('click', () => selectDistrict(districtKey));
                        path.addEventListener('keydown', (event) => {
                            if (event.key === 'Enter' || event.key === ' ') {
                                event.preventDefault();
                                selectDistrict(districtKey);
                            }
                        });
                        return;
                    }

                    const marker = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                    marker.setAttribute('cx', String(cx));
                    marker.setAttribute('cy', String(cy));
                    marker.setAttribute('r', '8');
                    marker.classList.add('district-map-marker');
                    marker.setAttribute('data-district-key', districtKey);

                    const label = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                    label.setAttribute('x', String(cx));
                    label.setAttribute('y', String(cy + 3));
                    label.setAttribute('text-anchor', 'middle');
                    label.classList.add('district-map-count-label');
                    label.setAttribute('data-district-key', districtKey);
                    label.textContent = String(count);

                    labelLayer.appendChild(marker);
                    labelLayer.appendChild(label);
                    markerByDistrictKey[districtKey] = marker;
                    labelByDistrictKey[districtKey] = label;

                    path.addEventListener('click', () => selectDistrict(districtKey));
                    path.addEventListener('keydown', (event) => {
                        if (event.key === 'Enter' || event.key === ' ') {
                            event.preventDefault();
                            selectDistrict(districtKey);
                        }
                    });
                });

                const fallbackKey = Object.keys(countByDistrict)
                    .sort((a, b) => Number(countByDistrict[b] || 0) - Number(countByDistrict[a] || 0))[0]
                    || '';
                const defaultKey = selectedDistrictKey || fallbackKey;
                if (defaultKey) {
                    selectDistrict(defaultKey);
                }
            })
            .catch(() => {
                mount.innerHTML = '<p class="district-map-error">Unable to load district map data.</p>';
                const fallbackKey = Object.keys(countByDistrict)
                    .sort((a, b) => Number(countByDistrict[b] || 0) - Number(countByDistrict[a] || 0))[0]
                    || '';
                if (fallbackKey) {
                    selectDistrict(fallbackKey);
                }
            });
    })();
</script>

@include('dashboards.partials.analytics', ['analytics' => $analytics])
@endsection
