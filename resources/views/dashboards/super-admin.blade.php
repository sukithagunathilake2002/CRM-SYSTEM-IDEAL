@extends('layouts.portal')

@section('content')
<style>
/* Adjust portal-main for balanced spacing */
.portal-main {
    max-width: 1400px !important;
    margin: 0 auto !important;
    padding-left: 16px !important;
    padding-right: 16px !important;
}

/* Additional dashboard styling using existing color palette */
.dashboard-header-card {
    background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
    border: none;
    color: white;
    border-radius: 16px;
    text-align: center;
}

.dashboard-header-card h1,
.dashboard-header-card p {
    color: white;
}

.dashboard-header-card .card-title-icon,
.dashboard-header-card .quick-links {
    justify-content: center;
}

.dashboard-header-card .stats-grid {
    grid-template-columns: repeat(3, minmax(180px, 1fr));
    max-width: 980px;
    margin-left: auto;
    margin-right: auto;
}

.dashboard-header-card .stat {
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.3);
    text-align: center;
}

.dashboard-header-card .stat strong,
.dashboard-header-card .stat span {
    color: white;
}

.dashboard-header-card .btn-link {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.dashboard-header-card .btn-link:hover {
    background: rgba(255, 255, 255, 0.3);
}

.dashboard-header-card .btn-link.alt {
    background: white;
    color: #1e3a8a;
}

.users-card {
    background: linear-gradient(135deg, #ffffff 0%, #f3f4f6 100%);
    border-radius: 16px;
}

.hierarchy-card {
    background: linear-gradient(135deg, #fefce8 0%, #fef3c7 100%);
    border-radius: 16px;
}

.district-card {
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
    border-radius: 16px;
}

.analytics-card-enhanced {
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    border-radius: 16px;
}

.card-title-icon {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.card-title-icon svg {
    width: 24px;
    height: 24px;
    stroke: currentColor;
    stroke-width: 1.5;
}

/* Card styling */
section.card {
    border-radius: 16px;
    margin-bottom: 20px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

/* Dark mode adjustments */
html.theme-dark .dashboard-header-card {
    background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 100%);
}

html.theme-dark .users-card {
    background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
}

html.theme-dark .hierarchy-card {
    background: linear-gradient(135deg, #422006 0%, #292524 100%);
}

html.theme-dark .district-card {
    background: linear-gradient(135deg, #064e3b 0%, #042f2e 100%);
}

html.theme-dark .analytics-card-enhanced {
    background: linear-gradient(135deg, #172554 0%, #111827 100%);
}

@media (max-width: 760px) {
    .dashboard-header-card .stats-grid {
        grid-template-columns: 1fr;
        max-width: 420px;
    }
}
</style>

<section class="card dashboard-header-card">
    <div class="card-title-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path d="M3 12h2l3-9 3 9h2M5 21v-6M19 13V7M15 13V9M21 21H3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <h1>Super Admin Dashboard</h1>
    </div>
    <p>Manage full organization hierarchy and access all CRM modules.</p>

    <div class="stats-grid">
        <div class="stat"><strong>{{ $counts['head_of_sales'] }}</strong><span>Heads Of Sales</span></div>
        <div class="stat"><strong>{{ $counts['area_manager'] }}</strong><span>Area Managers</span></div>
        <div class="stat"><strong>{{ $counts['sales_consultant'] }}</strong><span>Sales Consultants</span></div>
    </div>

    <div class="quick-links">
        <a class="btn-link" href="{{ route('auth.register.form', 'head-of-sales') }}">Register Head Of Sales</a>
        <a class="btn-link" href="{{ route('auth.register.form', 'area-manager') }}">Register Area Manager</a>
        <a class="btn-link" href="{{ route('auth.register.form', 'sales-consultant') }}">Register Sales Consultant</a>
        <a class="btn-link" href="{{ route('dashboard.analytics') }}">Analytics Filters</a>
        <a class="btn-link alt" href="{{ url('/epr') }}">Open EPR</a>
    </div>
</section>

<section class="card users-card">
    @php
        $manageableUserGroups = collect($manageableUsers)->groupBy('role');
        $manageableRoleOrder = [
            \App\Models\User::ROLE_HEAD_OF_SALES,
            \App\Models\User::ROLE_AREA_MANAGER,
            \App\Models\User::ROLE_SALES_CONSULTANT,
        ];
    @endphp
    <details class="manage-users-toggle">
        <summary>
            <span class="card-title-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M12 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <span class="manage-users-heading">Manage All Users</span>
            </span>
            <span class="manage-users-summary-count">{{ count($manageableUsers) }} users</span>
        </summary>

        <p>Edit user details, role assignment, reporting manager, and password.</p>
        <div class="quick-links manage-users-actions">
            <a class="btn-link" href="{{ route('dashboard.super_admin.consultant_transfer.form') }}">Transfer Consultant Data</a>
        </div>

        <div class="manage-users-groups">
            @forelse($manageableRoleOrder as $roleKey)
                @php
                    $roleUsers = $manageableUserGroups->get($roleKey, collect());
                    $roleLabel = \App\Models\User::ROLE_LABELS[$roleKey] ?? ucwords(str_replace('_', ' ', $roleKey));
                @endphp
                <details class="manage-users-group">
                    <summary>
                        <strong>{{ $roleLabel }}</strong>
                        <span>{{ $roleUsers->count() }} user{{ $roleUsers->count() === 1 ? '' : 's' }}</span>
                    </summary>
                    <div class="analytics-table-wrap">
                        <table class="analytics-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Employee Number</th>
                                    <th>Role</th>
                                    <th>Manager</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($roleUsers as $managedUser)
                                <tr>
                                    <td>{{ $managedUser->name }}</td>
                                    <td>{{ $managedUser->email }}</td>
                                    <td>{{ $managedUser->employee_number ?: '-' }}</td>
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
                                    <td colspan="6">No {{ $roleLabel }} users found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </details>
            @empty
                <p>No users found.</p>
            @endforelse
        </div>
    </details>
</section>

<section class="card hierarchy-card">
    <div class="card-title-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path d="M12 3v18M3 12h18M12 3L3 12M12 3l9 9M12 21l-9-9M12 21l9-9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <h2>Head Of Sales Team Hierarchy</h2>
    </div>
    <ul class="list hierarchy-list">
        @forelse($headHierarchy as $head)
        <li>
            <details class="hierarchy-toggle hierarchy-head-toggle">
                <summary>
                    <span class="hierarchy-summary-main">
                        <strong>{{ $head['name'] }} (Head Of Sales)</strong>
                        <span>{{ $head['email'] }}</span>
                    </span>
                    <span class="hierarchy-summary-counts">
                        Dependent Users: {{ $head['dependent_users_count'] }} |
                        Area Managers: {{ $head['area_managers_count'] }} |
                        Sales Consultants: {{ $head['sales_consultants_count'] }}
                    </span>
                </summary>

                @if(!empty($head['area_managers']))
                <div class="hierarchy-children">
                    @foreach($head['area_managers'] as $areaManager)
                    <details class="hierarchy-child hierarchy-toggle">
                        <summary>
                            <span class="hierarchy-summary-main">
                                <strong>{{ $areaManager['name'] }} (Area Manager)</strong>
                                <span>{{ $areaManager['email'] }}</span>
                            </span>
                            <span class="hierarchy-summary-counts">Sales Consultants: {{ $areaManager['sales_consultants_count'] }}</span>
                        </summary>

                        @if(!empty($areaManager['sales_consultants']))
                        <div class="hierarchy-leaf-wrap">
                            @foreach($areaManager['sales_consultants'] as $salesConsultant)
                            <span class="hierarchy-pill">{{ $salesConsultant['name'] }}</span>
                            @endforeach
                        </div>
                        @else
                        <span>No Sales Consultants assigned under this Area Manager yet.</span>
                        @endif
                    </details>
                    @endforeach
                </div>
                @else
                <span>No Area Managers assigned under this Head Of Sales yet.</span>
                @endif
            </details>
        </li>
        @empty
        <li>No Head Of Sales users yet.</li>
        @endforelse
    </ul>
</section>

<div class="hierarchy-metric-actions" aria-label="Lead and followup analytics shortcuts">
    <a class="hierarchy-metric-btn analytics" href="{{ route('dashboard.analytics') }}">Analytics Filters</a>
    <a class="hierarchy-metric-btn active" href="{{ route('dashboard.analytics.detail', 'active') }}">Active</a>
    <a class="hierarchy-metric-btn booking" href="{{ route('dashboard.analytics.detail', 'booking') }}">Booking</a>
    <a class="hierarchy-metric-btn lost" href="{{ route('dashboard.analytics.detail', 'lost') }}">Lost</a>
    <a class="hierarchy-metric-btn closed" href="{{ route('dashboard.analytics.detail', 'closed') }}">Closed Lead</a>
    <a class="hierarchy-metric-btn followup" href="{{ route('dashboard.followup_tracker') }}">FollowUp</a>
</div>

<section id="districtOverviewCard" class="card district-card">
    <div class="card-title-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            <circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="2"/>
        </svg>
        <h2>Sri Lanka District Lead Overview</h2>
    </div>
    <p>Click on any district to zoom in and view lead count. Click again to reset.</p>
    <div class="district-overview-grid">
        <div class="district-map-card">
            <div id="districtLeadMap" class="district-lead-map"></div>
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
            <div id="districtLeadInfoCard" class="district-lead-info-card">
                <span class="district-lead-info-label">Selected District</span>
                <h3 id="districtLeadInfoName" class="district-lead-info-name">Click a district</h3>
                <p class="district-lead-info-value"><span id="districtLeadInfoCount">0</span> Active Leads</p>
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
                        <tr class="district-summary-row" data-district="{{ $row['district'] }}">
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

<section id="provinceOverviewCard" class="card district-card">
    <div class="card-title-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path d="M3 6l6-3 6 3 6-3v15l-6 3-6-3-6 3V6z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
            <path d="M9 3v15M15 6v15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <h2>Sri Lanka Province Lead Overview</h2>
    </div>
    <p>Click on any province to zoom in and view lead count. Click again to reset.</p>
    <div class="district-overview-grid">
        <div class="district-map-card">
            <div id="provinceLeadMap" class="district-lead-map"></div>
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
            <div id="provinceLeadInfoCard" class="district-lead-info-card">
                <span class="district-lead-info-label">Selected Province</span>
                <h3 id="provinceLeadInfoName" class="district-lead-info-name">Click a province</h3>
                <p class="district-lead-info-value"><span id="provinceLeadInfoCount">0</span> Active Leads</p>
            </div>
            <div class="analytics-table-wrap">
                <table class="analytics-table district-summary-table">
                    <thead>
                        <tr>
                            <th>Province</th>
                            <th>Leads</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($analytics['by_province'] ?? []) as $row)
                        <tr class="district-summary-row province-summary-row" data-province="{{ $row['province'] }}">
                            <td>{{ $row['province'] }}</td>
                            <td>{{ $row['leads'] }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2">No province data available.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<script>
(function() {
    var mapUrl = @json(asset('data/sri-lanka-districts-map.json'));
    var analyticsDistricts = @json($analytics['by_district'] ?? []);
    var analyticsProvinces = @json($analytics['by_province'] ?? []);
    var provinceDistrictMap = @json(\App\Models\User::PROVINCE_DISTRICT_MAP);
    var svgNamespace = 'http://www.w3.org/2000/svg';

    function normalize(str) {
        var key = String(str || '').trim().toLowerCase().replace(/[^a-z]/g, '');
        if (key === 'moneragala') {
            return 'monaragala';
        }
        return key;
    }

    function buildCountMap(rows, nameField) {
        var counts = {};
        (rows || []).forEach(function(row) {
            var key = normalize(row[nameField]);
            if (key && key !== 'na') {
                counts[key] = Number(row.leads) || 0;
            }
        });
        return counts;
    }

    function maxCount(counts) {
        var max = 0;
        Object.keys(counts).forEach(function(key) {
            if (counts[key] > max) max = counts[key];
        });
        return max || 1;
    }

    function fillColor(count, max) {
        if (count <= 0) return '#eef2ff';
        var ratio = count / max;
        if (ratio > 0.8) return '#1d4ed8';
        if (ratio > 0.6) return '#2563eb';
        if (ratio > 0.4) return '#3b82f6';
        if (ratio > 0.2) return '#60a5fa';
        return '#93c5fd';
    }

    function markerColor(count, max) {
        if (count <= 0) return '#9ca3af';
        var ratio = count / max;
        if (ratio > 0.8) return '#c53030';
        if (ratio > 0.6) return '#dd6b20';
        if (ratio > 0.4) return '#d69e2e';
        if (ratio > 0.2) return '#38a169';
        return '#3182ce';
    }

    function buildProvinceLookup() {
        var lookup = {};
        Object.keys(provinceDistrictMap || {}).forEach(function(province) {
            (provinceDistrictMap[province] || []).forEach(function(district) {
                lookup[normalize(district)] = province;
            });
        });
        return lookup;
    }

    function districtItems(mapData, counts) {
        return (mapData.locations || []).map(function(location) {
            var name = String(location.name || '');
            var count = counts[normalize(name)] || 0;
            return {
                name: name,
                count: count,
                paths: [String(location.path || '')]
            };
        });
    }

    function provinceItems(mapData, counts, provinceLookup) {
        var grouped = {};
        Object.keys(provinceDistrictMap || {}).forEach(function(province) {
            grouped[province] = {
                name: province,
                count: counts[normalize(province)] || 0,
                paths: []
            };
        });

        (mapData.locations || []).forEach(function(location) {
            var province = provinceLookup[normalize(location.name)];
            if (province && grouped[province]) {
                grouped[province].paths.push(String(location.path || ''));
            }
        });

        return Object.keys(grouped).map(function(province) {
            return grouped[province];
        }).filter(function(item) {
            return item.paths.length > 0;
        });
    }

    function renderLeadMap(config, mapData) {
        var mount = document.getElementById(config.mountId);
        if (!mount) return;

        var infoName = document.getElementById(config.infoNameId);
        var infoCount = document.getElementById(config.infoCountId);
        var infoCard = document.getElementById(config.infoCardId);
        var tableRows = document.querySelectorAll(config.rowSelector);
        var counts = config.counts;
        var max = maxCount(counts);
        var groupByKey = {};
        var currentGroup = null;
        var isZoomed = false;
        var isProcessing = false;
        var originalOrder = [];
        var svgWrapper = null;

        function restoreOrder() {
            if (svgWrapper && originalOrder.length > 0) {
                originalOrder.forEach(function(child) {
                    if (child.parentNode === svgWrapper) {
                        svgWrapper.removeChild(child);
                        svgWrapper.appendChild(child);
                    }
                });
            }
        }

        function bringToFront(element) {
            if (!svgWrapper) return;
            if (originalOrder.length === 0) {
                originalOrder = Array.from(svgWrapper.children);
            }
            if (element.parentNode === svgWrapper) {
                svgWrapper.removeChild(element);
                svgWrapper.appendChild(element);
            }
        }

        function clearSelectedRows() {
            Array.prototype.forEach.call(tableRows, function(row) {
                row.classList.remove('is-selected');
            });
        }

        function markSelectedRow(name) {
            clearSelectedRows();
            Array.prototype.forEach.call(tableRows, function(row) {
                if (normalize(row.getAttribute(config.rowAttribute)) === normalize(name)) {
                    row.classList.add('is-selected');
                }
            });
        }

        function zoomOut(callback) {
            if (!currentGroup) {
                if (callback) callback();
                return;
            }

            var group = currentGroup;
            var animationEnded = false;

            function onAnimationEnd() {
                if (animationEnded) return;
                animationEnded = true;
                group.classList.remove('district-zoom-out');
                group.removeEventListener('animationend', onAnimationEnd);
                if (callback) callback();
            }

            group.classList.remove('district-zoomed', 'district-zoom-in');
            group.classList.add('district-zoom-out');
            group.addEventListener('animationend', onAnimationEnd);
            setTimeout(onAnimationEnd, 500);
        }

        function zoomIn(group, callback) {
            var animationEnded = false;

            function onAnimationEnd() {
                if (animationEnded) return;
                animationEnded = true;
                group.classList.remove('district-zoom-in');
                group.classList.add('district-zoomed');
                group.removeEventListener('animationend', onAnimationEnd);
                if (callback) callback();
            }

            group.classList.add('district-zoom-in');
            group.addEventListener('animationend', onAnimationEnd);
            setTimeout(onAnimationEnd, 500);
        }

        function resetToNormal() {
            if (currentGroup) {
                currentGroup.classList.remove('district-zoomed', 'district-zoom-in', 'district-zoom-out');
            }
            restoreOrder();
            clearSelectedRows();
            currentGroup = null;
            isZoomed = false;
            if (infoName) infoName.textContent = config.defaultText;
            if (infoCount) infoCount.textContent = '0';
        }

        function updateInfo(name, count) {
            if (infoName) infoName.textContent = name;
            if (infoCount) infoCount.textContent = count;
            markSelectedRow(name);

            if (infoCard) {
                infoCard.classList.add('animate');
                setTimeout(function() { infoCard.classList.remove('animate'); }, 300);
            }
        }

        function onEntityClick(name, groupElement, leadCount) {
            if (isProcessing) return;
            isProcessing = true;

            if (isZoomed && currentGroup === groupElement) {
                zoomOut(function() {
                    resetToNormal();
                    isProcessing = false;
                });
                return;
            }

            function completeZoomIn() {
                currentGroup = groupElement;
                isZoomed = true;
                updateInfo(name, leadCount);

                groupElement.classList.add('district-pulse');
                setTimeout(function() {
                    groupElement.classList.remove('district-pulse');
                }, 400);

                isProcessing = false;
            }

            if (isZoomed && currentGroup !== groupElement) {
                zoomOut(function() {
                    restoreOrder();
                    currentGroup = null;
                    isZoomed = false;
                    bringToFront(groupElement);
                    zoomIn(groupElement, completeZoomIn);
                });
                return;
            }

            bringToFront(groupElement);
            zoomIn(groupElement, completeZoomIn);
        }

        function addMarker(group, count) {
            if (count <= 0) return;

            var bbox = group.getBBox();
            var centerX = bbox.x + bbox.width / 2;
            var centerY = bbox.y + bbox.height / 2;
            var markerGroup = document.createElementNS(svgNamespace, 'g');
            var digits = String(count).length;
            var radius = Math.max(16, 10 + (digits * 3));

            markerGroup.classList.add('district-number-marker');
            markerGroup.setAttribute('transform', 'translate(' + centerX + ',' + centerY + ')');
            markerGroup.style.pointerEvents = 'none';

            var circle = document.createElementNS(svgNamespace, 'circle');
            circle.setAttribute('cx', '0');
            circle.setAttribute('cy', '0');
            circle.setAttribute('r', String(radius));
            circle.setAttribute('fill', markerColor(count, max));
            circle.setAttribute('stroke', '#ffffff');
            circle.setAttribute('stroke-width', '2.5');
            markerGroup.appendChild(circle);

            var text = document.createElementNS(svgNamespace, 'text');
            text.setAttribute('x', '0');
            text.setAttribute('y', '5');
            text.setAttribute('text-anchor', 'middle');
            text.setAttribute('fill', '#ffffff');
            text.setAttribute('font-size', digits > 3 ? '10' : '13');
            text.setAttribute('font-weight', 'bold');
            text.textContent = String(count);
            markerGroup.appendChild(text);

            group.appendChild(markerGroup);
        }

        var svg = document.createElementNS(svgNamespace, 'svg');
        svg.setAttribute('viewBox', mapData.viewBox || '0 0 450 793');
        svg.classList.add('district-map-svg');
        svg.style.overflow = 'visible';

        svgWrapper = document.createElementNS(svgNamespace, 'g');
        svgWrapper.classList.add('district-wrapper-group');

        config.items.forEach(function(item) {
            var group = document.createElementNS(svgNamespace, 'g');
            group.classList.add('district-group');
            group.setAttribute(config.entityAttribute, item.name);
            group.setAttribute('data-count', item.count);
            group.style.transformOrigin = 'center';
            group.style.cursor = 'pointer';

            item.paths.forEach(function(pathValue) {
                var path = document.createElementNS(svgNamespace, 'path');
                path.setAttribute('d', pathValue);
                path.setAttribute('fill', fillColor(item.count, max));
                path.setAttribute('stroke', '#c7d2fe');
                path.setAttribute('stroke-width', '1.2');
                path.classList.add('district-map-path');
                group.appendChild(path);
            });

            svgWrapper.appendChild(group);
            groupByKey[normalize(item.name)] = {
                group: group,
                name: item.name,
                count: item.count
            };
        });

        svg.appendChild(svgWrapper);
        mount.innerHTML = '';
        mount.appendChild(svg);

        setTimeout(function() {
            Object.keys(groupByKey).forEach(function(key) {
                var item = groupByKey[key];
                item.group.addEventListener('click', function() {
                    onEntityClick(item.name, item.group, item.count);
                });
                addMarker(item.group, item.count);
            });
        }, 100);

        Array.prototype.forEach.call(tableRows, function(row) {
            var name = row.getAttribute(config.rowAttribute);
            if (!name) return;

            row.addEventListener('click', function() {
                var item = groupByKey[normalize(name)];
                if (item) {
                    onEntityClick(item.name, item.group, item.count);
                }
            });
        });
    }

    fetch(mapUrl)
        .then(function(response) { return response.json(); })
        .then(function(mapData) {
            var districtCounts = buildCountMap(analyticsDistricts, 'district');
            var provinceCounts = buildCountMap(analyticsProvinces, 'province');
            var provinceLookup = buildProvinceLookup();

            renderLeadMap({
                mountId: 'districtLeadMap',
                infoNameId: 'districtLeadInfoName',
                infoCountId: 'districtLeadInfoCount',
                infoCardId: 'districtLeadInfoCard',
                rowSelector: '.district-summary-row[data-district]',
                rowAttribute: 'data-district',
                entityAttribute: 'data-district',
                defaultText: 'Click a district',
                counts: districtCounts,
                items: districtItems(mapData, districtCounts)
            }, mapData);

            renderLeadMap({
                mountId: 'provinceLeadMap',
                infoNameId: 'provinceLeadInfoName',
                infoCountId: 'provinceLeadInfoCount',
                infoCardId: 'provinceLeadInfoCard',
                rowSelector: '.province-summary-row[data-province]',
                rowAttribute: 'data-province',
                entityAttribute: 'data-province',
                defaultText: 'Click a province',
                counts: provinceCounts,
                items: provinceItems(mapData, provinceCounts, provinceLookup)
            }, mapData);
        })
        .catch(function(error) {
            console.error('Error loading map:', error);
            var districtMount = document.getElementById('districtLeadMap');
            var provinceMount = document.getElementById('provinceLeadMap');
            if (districtMount) districtMount.innerHTML = '<p>Unable to load district map data.</p>';
            if (provinceMount) provinceMount.innerHTML = '<p>Unable to load province map data.</p>';
        });
})();
</script>
<script>
(() => {
    const buttons = Array.from(document.querySelectorAll('.hierarchy-metric-btn[data-analytics-toggle][data-analytics-card], .hierarchy-metric-btn[data-scroll-target]'));
    if (!buttons.length) {
        return;
    }

    buttons.forEach((button) => {
        button.addEventListener('click', () => {
            const scrollTarget = document.getElementById(button.dataset.scrollTarget || '');
            if (scrollTarget) {
                scrollTarget.hidden = false;
                button.setAttribute('aria-expanded', 'true');
                window.requestAnimationFrame(() => {
                    scrollTarget.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    window.dispatchEvent(new Event('resize'));
                });
                return;
            }

            const toggle = document.getElementById(button.dataset.analyticsToggle || '');
            const card = document.getElementById(button.dataset.analyticsCard || '');

            if (!toggle || !card) {
                return;
            }

            if (card.hidden) {
                toggle.click();
                return;
            }

            card.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
})();
</script>
@endsection
