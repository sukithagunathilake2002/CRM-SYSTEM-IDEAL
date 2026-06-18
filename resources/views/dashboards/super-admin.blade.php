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
}

.dashboard-header-card h1,
.dashboard-header-card p {
    color: white;
}

.dashboard-header-card .stat {
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.3);
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

.stats-card-enhanced {
    background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
    border: none;
    border-radius: 16px;
}

.stats-card-enhanced .stat {
    background: white;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
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

html.theme-dark .stats-card-enhanced {
    background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
}

html.theme-dark .stats-card-enhanced .stat {
    background: #374151;
    border-color: #4b5563;
}

html.theme-dark .stats-card-enhanced .stat strong,
html.theme-dark .stats-card-enhanced .stat span {
    color: #f3f4f6;
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
        <a class="btn-link alt" href="{{ url('/epr') }}">Open EPR</a>
    </div>
</section>

<section class="card stats-card-enhanced">
    <div class="card-title-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M3 7a4 4 0 0 0 4 4h4a4 4 0 0 0 4-4M19 7a4 4 0 0 0-4-4h-4a4 4 0 0 0-4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <h2>Head Of Sales Hierarchy Summary</h2>
    </div>
    <div class="stats-grid">
        <div class="stat"><strong>{{ $dependentCounts['dependent_users'] }}</strong><span>Total Dependent Users</span></div>
        <div class="stat"><strong>{{ $dependentCounts['area_managers'] }}</strong><span>Area Managers</span></div>
        <div class="stat"><strong>{{ $dependentCounts['sales_consultants'] }}</strong><span>Sales Consultants</span></div>
    </div>
</section>

<section class="card users-card">
    <div class="card-title-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M12 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <h2>Manage All Users</h2>
    </div>
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
                    <th>Employee Number</th>
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
                    <td colspan="6">No users found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
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
            <strong>{{ $head['name'] }} (Head Of Sales)</strong>
            <span>{{ $head['email'] }}</span>
            <span>
                Dependent Users: {{ $head['dependent_users_count'] }} |
                Area Managers: {{ $head['area_managers_count'] }} |
                Sales Consultants: {{ $head['sales_consultants_count'] }}
            </span>

            @if(!empty($head['area_managers']))
            <div class="hierarchy-children">
                @foreach($head['area_managers'] as $areaManager)
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
                    @else
                    <span>No Sales Consultants assigned under this Area Manager yet.</span>
                    @endif
                </div>
                @endforeach
            </div>
            @else
            <span>No Area Managers assigned under this Head Of Sales yet.</span>
            @endif
        </li>
        @empty
        <li>No Head Of Sales users yet.</li>
        @endforelse
    </ul>
</section>

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

@include('dashboards.partials.followup-escalations', ['followupEscalations' => $followupEscalations])

@include('dashboards.partials.analytics', ['analytics' => $analytics])

<script>
(function() {
    var mount = document.getElementById('districtLeadMap');
    if (!mount) return;

    var infoName = document.getElementById('districtLeadInfoName');
    var infoCount = document.getElementById('districtLeadInfoCount');
    var tableRows = document.querySelectorAll('.district-summary-row');
    
    var svgElement = null;
    var svgWrapper = null;
    var currentDistrictGroup = null;
    var currentDistrictName = null;
    var isZoomed = false;
    var isProcessing = false;
    var originalOrder = [];
    
    function normalize(str) {
        return String(str || '').trim().toLowerCase().replace(/[^a-z]/g, '');
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
    
    function zoomOutDistrict(callback) {
        if (!currentDistrictGroup) {
            if (callback) callback();
            return;
        }
        
        var group = currentDistrictGroup;
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
    
    function zoomInDistrict(group, callback) {
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
        if (currentDistrictGroup) {
            currentDistrictGroup.classList.remove('district-zoomed', 'district-zoom-in', 'district-zoom-out');
        }
        restoreOrder();
        currentDistrictGroup = null;
        currentDistrictName = null;
        isZoomed = false;
        if (infoName) infoName.textContent = 'Click a district';
        if (infoCount) infoCount.textContent = '0';
    }
    
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
    
    function updateDistrictInfo(districtName, count) {
        if (infoName) infoName.textContent = districtName;
        if (infoCount) infoCount.textContent = count;
        
        var infoCard = document.getElementById('districtLeadInfoCard');
        if (infoCard) {
            infoCard.classList.add('animate');
            setTimeout(function() { infoCard.classList.remove('animate'); }, 300);
        }
    }
    
    function onDistrictClick(districtName, groupElement, eprCount) {
        if (isProcessing) return;
        isProcessing = true;
        
        if (isZoomed && currentDistrictGroup === groupElement) {
            zoomOutDistrict(function() {
                resetToNormal();
                isProcessing = false;
            });
            return;
        }
        
        if (isZoomed && currentDistrictGroup !== groupElement) {
            zoomOutDistrict(function() {
                restoreOrder();
                currentDistrictGroup = null;
                isZoomed = false;
                
                bringToFront(groupElement);
                zoomInDistrict(groupElement, function() {
                    currentDistrictGroup = groupElement;
                    currentDistrictName = districtName;
                    isZoomed = true;
                    
                    updateDistrictInfo(districtName, eprCount);
                    
                    groupElement.classList.add('district-pulse');
                    setTimeout(function() {
                        groupElement.classList.remove('district-pulse');
                    }, 400);
                    
                    isProcessing = false;
                });
            });
            return;
        }
        
        bringToFront(groupElement);
        zoomInDistrict(groupElement, function() {
            currentDistrictGroup = groupElement;
            currentDistrictName = districtName;
            isZoomed = true;
            
            updateDistrictInfo(districtName, eprCount);
            
            groupElement.classList.add('district-pulse');
            setTimeout(function() {
                groupElement.classList.remove('district-pulse');
            }, 400);
            
            isProcessing = false;
        });
    }
    
    function getPathCenter(path) {
        var bbox = path.getBBox();
        return {
            x: bbox.x + bbox.width / 2,
            y: bbox.y + bbox.height / 2
        };
    }
    
    var districtCounts = {};
    var analyticsDistricts = @json($analytics['by_district'] ?? []);
    
    analyticsDistricts.forEach(function(row) {
        var key = normalize(row.district);
        if (key && key !== 'na') {
            districtCounts[key] = Number(row.leads) || 0;
        }
    });
    
    for (var i = 0; i < tableRows.length; i++) {
        var row = tableRows[i];
        var district = row.getAttribute('data-district');
        if (district) {
            row.addEventListener('click', (function(d) {
                return function() {
                    var group = document.querySelector('.district-group[data-district="' + d + '"]');
                    var count = districtCounts[normalize(d)] || 0;
                    if (group) onDistrictClick(d, group, count);
                };
            })(district));
        }
    }
    
    var maxCount = 0;
    for (var key in districtCounts) {
        if (districtCounts[key] > maxCount) maxCount = districtCounts[key];
    }
    if (maxCount === 0) maxCount = 1;
    
    function getFillColor(count) {
        if (count <= 0) return '#eef2ff';
        var ratio = count / maxCount;
        if (ratio > 0.8) return '#1d4ed8';
        if (ratio > 0.6) return '#2563eb';
        if (ratio > 0.4) return '#3b82f6';
        if (ratio > 0.2) return '#60a5fa';
        return '#93c5fd';
    }
    
    function getMarkerColor(count) {
        if (count <= 0) return '#9ca3af';
        var ratio = count / maxCount;
        if (ratio > 0.8) return '#c53030';
        if (ratio > 0.6) return '#dd6b20';
        if (ratio > 0.4) return '#d69e2e';
        if (ratio > 0.2) return '#38a169';
        return '#3182ce';
    }
    
    var mapUrl = @json(asset('data/sri-lanka-districts-map.json'));
    fetch(mapUrl)
        .then(function(response) { return response.json(); })
        .then(function(mapData) {
            var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            svg.setAttribute('viewBox', mapData.viewBox || '0 0 450 793');
            svg.classList.add('district-map-svg');
            svg.style.overflow = 'visible';
            
            var wrapper = document.createElementNS('http://www.w3.org/2000/svg', 'g');
            wrapper.classList.add('district-wrapper-group');
            
            var groups = [];
            var locations = mapData.locations || [];
            
            locations.forEach(function(location) {
                var name = String(location.name || '');
                var key = normalize(name);
                var count = districtCounts[key] || 0;
                var fillColor = getFillColor(count);
                
                var group = document.createElementNS('http://www.w3.org/2000/svg', 'g');
                group.classList.add('district-group');
                group.setAttribute('data-district', name);
                group.setAttribute('data-count', count);
                group.style.transformOrigin = 'center';
                group.style.cursor = 'pointer';
                
                var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path.setAttribute('d', String(location.path || ''));
                path.setAttribute('fill', fillColor);
                path.setAttribute('stroke', '#c7d2fe');
                path.setAttribute('stroke-width', '1.2');
                path.classList.add('district-map-path');
                group.appendChild(path);
                
                groups.push({ group: group, name: name, count: count, path: path });
            });
            
            groups.forEach(function(item) {
                wrapper.appendChild(item.group);
            });
            
            svg.appendChild(wrapper);
            mount.innerHTML = '';
            mount.appendChild(svg);
            svgElement = svg;
            svgWrapper = wrapper;
            
            setTimeout(function() {
                groups.forEach(function(item) {
                    var group = item.group;
                    var name = item.name;
                    var count = item.count;
                    var path = item.path;
                    
                    group.addEventListener('click', (function(n, g, c) {
                        return function() { onDistrictClick(n, g, c); };
                    })(name, group, count));
                    
                    if (count > 0) {
                        var center = getPathCenter(path);
                        var markerColor = getMarkerColor(count);
                        
                        var markerGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
                        markerGroup.classList.add('district-number-marker');
                        markerGroup.setAttribute('transform', 'translate(' + center.x + ',' + center.y + ')');
                        markerGroup.style.pointerEvents = 'none';
                        
                        var circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                        circle.setAttribute('cx', '0');
                        circle.setAttribute('cy', '0');
                        circle.setAttribute('r', '16');
                        circle.setAttribute('fill', markerColor);
                        circle.setAttribute('stroke', '#ffffff');
                        circle.setAttribute('stroke-width', '2.5');
                        markerGroup.appendChild(circle);
                        
                        var text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                        text.setAttribute('x', '0');
                        text.setAttribute('y', '6');
                        text.setAttribute('text-anchor', 'middle');
                        text.setAttribute('fill', '#ffffff');
                        text.setAttribute('font-size', '13');
                        text.setAttribute('font-weight', 'bold');
                        text.textContent = String(count);
                        markerGroup.appendChild(text);
                        
                        group.appendChild(markerGroup);
                    }
                });
            }, 100);
        })
        .catch(function(error) {
            console.error('Error loading map:', error);
            mount.innerHTML = '<p>Unable to load district map data.</p>';
        });
})();
</script>
@endsection
