(function() {
    var configRoot = window.IdealLeadMapConfig || {};
    var mapUrl = configRoot.mapUrl;
    var analyticsDistricts = configRoot.districts || [];
    var analyticsProvinces = configRoot.provinces || [];
    var provinceDistrictMap = configRoot.provinceDistrictMap || {};
    var svgNamespace = 'http://www.w3.org/2000/svg';

    if (!mapUrl) {
        return;
    }

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
            if (counts[key] > max) {
                max = counts[key];
            }
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

    function parseViewBox(viewBox) {
        var parts = String(viewBox || '').trim().split(/[\s,]+/).map(Number);
        if (parts.length !== 4 || parts.some(function(value) { return !Number.isFinite(value); })) {
            return { x: 0, y: 0, width: 450, height: 793 };
        }

        return {
            x: parts[0],
            y: parts[1],
            width: parts[2],
            height: parts[3]
        };
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
            return {
                name: name,
                count: counts[normalize(name)] || 0,
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
        if (!mount) {
            return;
        }

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
            if (!svgWrapper) {
                return;
            }
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
            if (isProcessing) {
                return;
            }
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
            if (count <= 0) {
                return;
            }

            var bbox = group.getBBox();
            var centerX = bbox.x + bbox.width / 2;
            var centerY = bbox.y + bbox.height / 2;
            var markerGroup = document.createElementNS(svgNamespace, 'g');
            var digits = String(count).length;
            var radius = Math.max(18, 12 + (digits * 3));
            var markerPadding = radius + 4;

            if (mapViewBox) {
                centerX = Math.min(
                    Math.max(centerX, mapViewBox.x + markerPadding),
                    mapViewBox.x + mapViewBox.width - markerPadding
                );
                centerY = Math.min(
                    Math.max(centerY, mapViewBox.y + markerPadding),
                    mapViewBox.y + mapViewBox.height - markerPadding
                );
            }

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
            text.setAttribute('font-size', digits > 3 ? '11' : '14');
            text.setAttribute('font-weight', '800');
            text.textContent = String(count);
            markerGroup.appendChild(text);

            group.appendChild(markerGroup);
        }

        var svg = document.createElementNS(svgNamespace, 'svg');
        var mapViewBoxValue = mapData.viewBox || '0 0 450 793';
        var mapViewBox = parseViewBox(mapViewBoxValue);

        svg.setAttribute('viewBox', mapViewBoxValue);
        svg.classList.add('district-map-svg');
        svg.style.overflow = 'hidden';

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
            if (!name) {
                return;
            }

            row.addEventListener('click', function() {
                var item = groupByKey[normalize(name)];
                if (item) {
                    onEntityClick(item.name, item.group, item.count);
                }
            });
        });
    }

    function setupLeadOverviewTabs() {
        var tabs = document.querySelectorAll('[data-lead-overview-tab]');
        var panels = document.querySelectorAll('[data-lead-overview-panel]');

        if (!tabs.length || !panels.length) {
            return;
        }

        function activate(panelId) {
            Array.prototype.forEach.call(tabs, function(tab) {
                var isActive = tab.getAttribute('data-lead-overview-tab') === panelId;
                tab.classList.toggle('is-active', isActive);
                tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });

            Array.prototype.forEach.call(panels, function(panel) {
                panel.classList.toggle('is-hidden', panel.id !== panelId);
            });
        }

        Array.prototype.forEach.call(tabs, function(tab) {
            tab.addEventListener('click', function() {
                activate(tab.getAttribute('data-lead-overview-tab'));
            });
        });

        var activeTab = document.querySelector('[data-lead-overview-tab].is-active') || tabs[0];
        activate(activeTab.getAttribute('data-lead-overview-tab'));
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

            setTimeout(setupLeadOverviewTabs, 180);
        })
        .catch(function(error) {
            console.error('Error loading map:', error);
            var districtMount = document.getElementById('districtLeadMap');
            var provinceMount = document.getElementById('provinceLeadMap');
            if (districtMount) districtMount.innerHTML = '<p>Unable to load district map data.</p>';
            if (provinceMount) provinceMount.innerHTML = '<p>Unable to load province map data.</p>';
        });
})();
