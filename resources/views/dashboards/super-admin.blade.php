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

<section class="card">
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
                            <tr>
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
        if (!mount) {
            return;
        }

        const districtRows = @json($analytics['by_district'] ?? []);
        const normalize = (value) => String(value || '')
            .trim()
            .toLowerCase()
            .replace(/[^a-z]/g, '');
        const aliases = {
            monaragala: 'moneragala',
            mullativu: 'mullaitivu',
        };
        const countByDistrict = {};

        districtRows.forEach((row) => {
            const rawKey = normalize(row?.district);
            const key = aliases[rawKey] || rawKey;
            if (key === '' || key === 'na') {
                return;
            }
            countByDistrict[key] = Number(row?.leads || 0);
        });

        const values = Object.values(countByDistrict).filter((value) => Number.isFinite(value));
        const maxCount = values.length ? Math.max(...values) : 0;

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
                    path.setAttribute('data-count', String(count));

                    const title = document.createElementNS('http://www.w3.org/2000/svg', 'title');
                    title.textContent = `${districtName}: ${count} lead${count === 1 ? '' : 's'}`;
                    path.appendChild(title);
                    districtLayer.appendChild(path);
                });

                svg.appendChild(districtLayer);
                svg.appendChild(labelLayer);
                mount.innerHTML = '';
                mount.appendChild(svg);

                const paths = Array.from(svg.querySelectorAll('.district-map-path'));
                paths.forEach((path) => {
                    const count = Number(path.getAttribute('data-count') || '0');
                    if (count <= 0) {
                        return;
                    }

                    const box = path.getBBox();
                    const cx = box.x + (box.width / 2);
                    const cy = box.y + (box.height / 2);

                    const marker = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                    marker.setAttribute('cx', String(cx));
                    marker.setAttribute('cy', String(cy));
                    marker.setAttribute('r', '8');
                    marker.classList.add('district-map-marker');

                    const label = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                    label.setAttribute('x', String(cx));
                    label.setAttribute('y', String(cy + 3));
                    label.setAttribute('text-anchor', 'middle');
                    label.classList.add('district-map-count-label');
                    label.textContent = String(count);

                    labelLayer.appendChild(marker);
                    labelLayer.appendChild(label);
                });
            })
            .catch(() => {
                mount.innerHTML = '<p class="district-map-error">Unable to load district map data.</p>';
            });
    })();
</script>

@include('dashboards.partials.analytics', ['analytics' => $analytics])
@endsection
