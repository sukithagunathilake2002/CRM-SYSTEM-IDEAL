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
    <p>Click on any district to zoom in and view EPR count. Click again to reset.</p>
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
                <p class="district-lead-info-value"><span id="districtLeadInfoCount">0</span> Active EPRs</p>
            </div>
            <div class="analytics-table-wrap">
                <table class="analytics-table district-summary-table">
                    <thead>
                        <tr>
                            <th>District</th>
                            <th>EPRs</th>
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

@include('dashboards.partials.analytics', ['analytics' => $analytics])

<style>
.district-map-card {
    overflow: visible !important;
}

.district-lead-map {
    overflow: visible !important;
    min-height: 500px;
    position: relative;
}

.district-map-svg {
    width: 100%;
    height: auto;
    overflow: visible !important;
}

.district-group {
    cursor: pointer;
    transform-origin: center;
    transform-box: fill-box;
    transition: transform 0.5s cubic-bezier(0.34, 1.2, 0.64, 1), filter 0.3s ease-out;
}

/* Hover effect - pastel green */
.district-group:hover .district-map-path {
    fill: #a8e6cf !important;
    stroke: #a8e6cf !important;
    transition: fill 0.2s ease, stroke 0.2s ease;
}

.district-group.district-zoomed {
    transform: scale(1.6);
    filter: drop-shadow(0 20px 40px rgba(0,0,0,0.5));
}

.district-group.district-zoom-in {
    animation: zoomInBounce 0.5s cubic-bezier(0.34, 1.2, 0.64, 1) forwards;
}

.district-group.district-zoom-out {
    animation: zoomOutBounce 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55) forwards;
}

@keyframes zoomInBounce {
    0% {
        transform: scale(1);
        filter: drop-shadow(0 0 0 rgba(0,0,0,0));
    }
    40% {
        transform: scale(1.75);
        filter: drop-shadow(0 10px 20px rgba(0,0,0,0.3));
    }
    70% {
        transform: scale(1.55);
        filter: drop-shadow(0 15px 30px rgba(0,0,0,0.4));
    }
    100% {
        transform: scale(1.6);
        filter: drop-shadow(0 20px 40px rgba(0,0,0,0.5));
    }
}

@keyframes zoomOutBounce {
    0% {
        transform: scale(1.6);
        filter: drop-shadow(0 20px 40px rgba(0,0,0,0.5));
    }
    50% {
        transform: scale(0.92);
        filter: drop-shadow(0 5px 10px rgba(0,0,0,0.1));
    }
    100% {
        transform: scale(1);
        filter: drop-shadow(0 0 0 rgba(0,0,0,0));
    }
}

.district-group.district-pulse {
    animation: pulseGlow 0.4s ease-in-out;
}

@keyframes pulseGlow {
    0% { filter: drop-shadow(0 20px 40px rgba(0,0,0,0.5)); }
    50% { filter: drop-shadow(0 0 20px rgba(168, 230, 207, 0.8)); }
    100% { filter: drop-shadow(0 20px 40px rgba(0,0,0,0.5)); }
}

.district-map-path {
    transform-origin: center;
    transform-box: fill-box;
    transition: fill 0.2s ease, stroke 0.2s ease;
}

.district-number-marker {
    pointer-events: none;
    transform-origin: center;
}

.district-number-marker circle {
    transition: all 0.2s ease;
}

/* Hide stroke on number marker during hover of parent group */
.district-group:hover .district-number-marker circle {
    stroke: #a8e6cf;
}

.district-no-data-msg {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(0,0,0,0.85);
    color: white;
    padding: 12px 24px;
    border-radius: 8px;
    z-index: 1000;
    font-size: 14px;
    white-space: nowrap;
    animation: fadeOutMsg 2.5s ease forwards;
}

@keyframes fadeOutMsg {
    0% { opacity: 0; transform: translate(-50%, -50%) scale(0.9); }
    15% { opacity: 1; transform: translate(-50%, -50%) scale(1.05); }
    30% { transform: translate(-50%, -50%) scale(1); }
    80% { opacity: 1; }
    100% { opacity: 0; visibility: hidden; transform: translate(-50%, -50%) scale(0.95); }
}

.district-summary-row {
    cursor: pointer;
    transition: all 0.2s ease;
}

.district-summary-row:hover {
    background: rgba(168, 230, 207, 0.3);
    transform: translateX(5px);
}

.district-lead-info-card {
    transition: all 0.3s cubic-bezier(0.34, 1.2, 0.64, 1);
}

.district-lead-info-card.animate {
    transform: scale(1.03);
    background: linear-gradient(135deg, #f0f4ff 0%, #e8eeff 100%);
}

.district-map-scale-bar {
    height: 8px;
    border-radius: 4px;
    background: linear-gradient(90deg, #93c5fd, #1d4ed8);
}

.district-map-scale-labels {
    display: flex;
    justify-content: space-between;
    font-size: 11px;
    color: #64748b;
    margin-top: 5px;
}
</style>

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
        
        // If clicking the same district that is zoomed - zoom out
        if (isZoomed && currentDistrictGroup === groupElement) {
            zoomOutDistrict(function() {
                resetToNormal();
                isProcessing = false;
            });
            return;
        }
        
        // If another district is zoomed - reset it first, then zoom new one
        if (isZoomed && currentDistrictGroup !== groupElement) {
            zoomOutDistrict(function() {
                restoreOrder();
                currentDistrictGroup = null;
                isZoomed = false;
                
                // Now zoom in the new district
                bringToFront(groupElement);
                zoomInDistrict(groupElement, function() {
                    currentDistrictGroup = groupElement;
                    currentDistrictName = districtName;
                    isZoomed = true;
                    
                    updateDistrictInfo(districtName, eprCount);
                    
                    // Add pulse effect
                    groupElement.classList.add('district-pulse');
                    setTimeout(function() {
                        groupElement.classList.remove('district-pulse');
                    }, 400);
                    
                    isProcessing = false;
                });
            });
            return;
        }
        
        // No district zoomed - zoom in new district
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
    
    // Function to calculate center of a path
    function getPathCenter(path) {
        var bbox = path.getBBox();
        return {
            x: bbox.x + bbox.width / 2,
            y: bbox.y + bbox.height / 2
        };
    }
    
    // District data processing
    var districtCounts = {};
    var analyticsDistricts = @json($analytics['by_district'] ?? []);
    
    analyticsDistricts.forEach(function(row) {
        var key = normalize(row.district);
        if (key && key !== 'na') {
            districtCounts[key] = Number(row.leads) || 0;
        }
    });
    
    // Add click handlers for table rows
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
    
    // Keep original blue color scheme for districts
    function getFillColor(count) {
        if (count <= 0) return '#eef2ff';
        var ratio = count / maxCount;
        if (ratio > 0.8) return '#1d4ed8';
        if (ratio > 0.6) return '#2563eb';
        if (ratio > 0.4) return '#3b82f6';
        if (ratio > 0.2) return '#60a5fa';
        return '#93c5fd';
    }
    
    // Darker, high-contrast colors for number marker circles
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
            
            // Create groups for ALL districts (including those with 0 EPRs)
            locations.forEach(function(location) {
                var name = String(location.name || '');
                var key = normalize(name);
                var count = districtCounts[key] || 0;
                var fillColor = getFillColor(count);
                
                // Create group for this district
                var group = document.createElementNS('http://www.w3.org/2000/svg', 'g');
                group.classList.add('district-group');
                group.setAttribute('data-district', name);
                group.setAttribute('data-count', count);
                group.style.transformOrigin = 'center';
                group.style.cursor = 'pointer';
                
                // Add the path with blue border
                var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path.setAttribute('d', String(location.path || ''));
                path.setAttribute('fill', fillColor);
                path.setAttribute('stroke', '#c7d2fe');
                path.setAttribute('stroke-width', '1.2');
                path.classList.add('district-map-path');
                group.appendChild(path);
                
                groups.push({ group: group, name: name, count: count, path: path });
            });
            
            // Add groups to wrapper first to calculate centers
            groups.forEach(function(item) {
                wrapper.appendChild(item.group);
            });
            
            svg.appendChild(wrapper);
            mount.innerHTML = '';
            mount.appendChild(svg);
            svgElement = svg;
            svgWrapper = wrapper;
            
            // After DOM is rendered, calculate centers and add markers (only for districts with EPRs)
            setTimeout(function() {
                groups.forEach(function(item) {
                    var group = item.group;
                    var name = item.name;
                    var count = item.count;
                    var path = item.path;
                    
                    // Add click handler to EVERY group (including zero EPR districts)
                    group.addEventListener('click', (function(n, g, c) {
                        return function() { onDistrictClick(n, g, c); };
                    })(name, group, count));
                    
                    // Only add number marker if count > 0
                    if (count > 0) {
                        var center = getPathCenter(path);
                        var markerColor = getMarkerColor(count);
                        
                        // Create number marker group with high-contrast circle
                        var markerGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
                        markerGroup.classList.add('district-number-marker');
                        markerGroup.setAttribute('transform', 'translate(' + center.x + ',' + center.y + ')');
                        markerGroup.style.pointerEvents = 'none';
                        
                        // Background circle - high contrast color
                        var circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                        circle.setAttribute('cx', '0');
                        circle.setAttribute('cy', '0');
                        circle.setAttribute('r', '16');
                        circle.setAttribute('fill', markerColor);
                        circle.setAttribute('stroke', '#ffffff');
                        circle.setAttribute('stroke-width', '2.5');
                        markerGroup.appendChild(circle);
                        
                        // Number text
                        var text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                        text.setAttribute('x', '0');
                        text.setAttribute('y', '6');
                        text.setAttribute('text-anchor', 'middle');
                        text.setAttribute('fill', '#ffffff');
                        text.setAttribute('font-size', '13');
                        text.setAttribute('font-weight', 'bold');
                        text.textContent = String(count);
                        markerGroup.appendChild(text);
                        
                        // Add marker to the same group so it zooms with the path
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