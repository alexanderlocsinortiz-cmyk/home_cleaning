(function () {
    'use strict';

    let unavailableTimer = null;

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function showUnavailable() {
        const element = document.getElementById('map');

        if (!element || element.dataset.googleMapsMessage === 'true' || window.google?.maps) {
            return;
        }

        element.dataset.googleMapsMessage = 'true';
        element.classList.add('flex', 'items-center', 'justify-center', 'p-6', 'text-center');
        element.innerHTML = '<div class="max-w-md text-sm font-semibold leading-6 text-slate-600"><div class="mb-2 text-base font-black text-slate-900">Google Maps is not available</div><div>Add a valid <code class="rounded bg-slate-200 px-1.5 py-0.5 text-xs">GOOGLE_MAPS_API_KEY</code> in Laravel Cloud and enable the Maps JavaScript API, then reload this page.</div></div>';
    }

    function markerIcon(color, scale = 8) {
        return {
            path: window.google.maps.SymbolPath.CIRCLE,
            scale,
            fillColor: color,
            fillOpacity: 1,
            strokeColor: '#ffffff',
            strokeWeight: 2,
        };
    }

    function providerMarkerIcon() {
        return {
            path: window.google.maps.SymbolPath.CIRCLE,
            scale: 12,
            fillColor: '#7c3aed',
            fillOpacity: 1,
            strokeColor: '#ffffff',
            strokeWeight: 3,
        };
    }

    function init() {
        const element = document.getElementById('map');

        if (!element || element.dataset.initialized === 'true') {
            return;
        }

        if (!window.google?.maps) {
            showUnavailable();
            return;
        }

        const colors = {
            service_center: '#1e3a8a',
            residential: '#2563eb',
            commercial: '#f97316',
            office: '#60a5fa',
            municipality: '#0f766e',
        };
        const barangayData = Array.isArray(window.barangayData) ? window.barangayData : [];
        const coverageData = Array.isArray(window.cleanflowCoverageData) ? window.cleanflowCoverageData : [];
        const providerCoverageData = Array.isArray(window.providerCoverageData) ? window.providerCoverageData : [];
        const config = window.cleanflowMapConfig || {};
        const center = config.center || { lat: 7.95, lng: 124.95 };
        const boundsConfig = config.maxBounds;
        const map = new window.google.maps.Map(element, {
            center: { lat: Number(center.lat), lng: Number(center.lng) },
            zoom: Number(config.zoom || 9),
            minZoom: Number(config.minZoom || 8),
            maxZoom: Number(config.maxZoom || 17),
            mapTypeId: 'roadmap',
            scaleControl: true,
            streetViewControl: false,
            mapTypeControl: false,
            fullscreenControl: true,
            gestureHandling: 'greedy',
            restriction: Array.isArray(boundsConfig) && boundsConfig.length >= 2
                ? {
                    latLngBounds: {
                        south: Number(boundsConfig[0][0]),
                        west: Number(boundsConfig[0][1]),
                        north: Number(boundsConfig[1][0]),
                        east: Number(boundsConfig[1][1]),
                    },
                    strictBounds: false,
                }
                : undefined,
        });
        const infoWindow = new window.google.maps.InfoWindow();
        const barangayMarkers = [];
        const coverageMarkers = [];

        function addMarker(data, type, content, isProvider = false) {
            const lat = Number(data.lat);
            const lng = Number(data.lng);

            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                return null;
            }

            const marker = new window.google.maps.Marker({
                map,
                position: { lat, lng },
                title: data.name || 'Service area',
                icon: isProvider ? providerMarkerIcon() : markerIcon(colors[type] || '#2563eb'),
                zIndex: isProvider ? 500 : undefined,
            });

            marker.set('cleanflowPopupContent', content);
            marker.addListener('click', () => {
                infoWindow.setContent(content);
                infoWindow.open({ map, anchor: marker, shouldFocus: false });
            });

            return { marker, data, providerMarker: isProvider };
        }

        barangayData.forEach((barangay) => {
            const services = (barangay.services || [])
                .map((service) => `<li>${escapeHtml(service)}</li>`)
                .join('');
            const type = barangay.type || 'residential';
            const typeName = type.replace('_', ' ').replace(/\b\w/g, (char) => char.toUpperCase());
            const content = `
                <div class="cleanflow-map-popup">
                    <strong class="cleanflow-map-popup__title">${escapeHtml(barangay.name)}</strong>
                    <span class="cleanflow-map-popup__type">${escapeHtml(typeName)}</span>
                    <ul class="cleanflow-map-popup__services">${services}</ul>
                </div>`;
            const entry = addMarker(barangay, type, content);

            if (entry) {
                barangayMarkers.push(entry);
            }
        });

        coverageData.forEach((area) => {
            const content = `
                <div class="cleanflow-map-popup">
                    <strong class="cleanflow-map-popup__title">${escapeHtml(area.name)}</strong>
                    <span class="cleanflow-map-popup__type">Bukidnon provider coverage</span>
                    <ul class="cleanflow-map-popup__services"><li>Approved providers may serve this city/municipality</li></ul>
                </div>`;
            const entry = addMarker(area, 'municipality', content);

            if (entry) {
                coverageMarkers.push(entry);
            }
        });

        providerCoverageData.forEach((area) => {
            const providerList = (area.providers || [])
                .map((provider) => {
                    const services = provider.services?.length
                        ? ` <span>(${provider.services.map(escapeHtml).join(', ')})</span>`
                        : '';

                    return `<li><strong>${escapeHtml(provider.name)}</strong>${services}<br><small>${escapeHtml(provider.availability)}</small></li>`;
                })
                .join('');
            const totalCount = Number(area.provider_count || 0);
            const availableCount = Number(area.available_provider_count || 0);
            const content = `
                <div class="cleanflow-map-popup">
                    <strong class="cleanflow-map-popup__title">Provider coverage: ${escapeHtml(area.name)}</strong>
                    <span class="cleanflow-map-popup__type">${totalCount} approved provider${totalCount === 1 ? '' : 's'} · ${availableCount} available</span>
                    <p class="mt-2 text-xs text-slate-500">This marker shows the service-area center. Provider exact base locations are private.</p>
                    <ul class="cleanflow-map-popup__services">${providerList}</ul>
                </div>`;
            const entry = addMarker(area, 'municipality', content, true);

            if (entry) {
                coverageMarkers.push(entry);
            }
        });

        function focusMarker(entry, zoom) {
            const position = entry.marker.getPosition();

            if (!position) {
                return;
            }

            map.setCenter(position);
            map.setZoom(zoom);
            infoWindow.setContent(entry.marker.get('cleanflowPopupContent'));
            infoWindow.open({ map, anchor: entry.marker, shouldFocus: false });
        }

        function findEntry(collection, name, includeProvider) {
            return collection.find((entry) => entry.data.name === name && (includeProvider || !entry.providerMarker));
        }

        document.querySelectorAll('#barangayList li').forEach((listItem) => {
            listItem.addEventListener('click', () => {
                const entry = findEntry(barangayMarkers, listItem.dataset.name, true);

                if (entry) {
                    focusMarker(entry, 15);
                }
            });
        });

        document.querySelectorAll('#coverageAreaList li').forEach((listItem) => {
            listItem.addEventListener('click', () => {
                const entry = findEntry(coverageMarkers, listItem.dataset.name, false);

                if (entry) {
                    focusMarker(entry, 11);
                }
            });
        });

        const searchInput = document.getElementById('barangaySearch');

        if (searchInput) {
            searchInput.addEventListener('input', () => {
                const query = searchInput.value.toLowerCase();
                document.querySelectorAll('#barangayList li, #coverageAreaList li').forEach((listItem) => {
                    listItem.classList.toggle('hidden', query.length > 0 && !listItem.dataset.name.toLowerCase().includes(query));
                });

                if (query.length > 1) {
                    const match = barangayMarkers.find((entry) => entry.data.name.toLowerCase().includes(query))
                        || coverageMarkers.find((entry) => !entry.providerMarker && entry.data.name.toLowerCase().includes(query));

                    if (match) {
                        map.setCenter(match.marker.getPosition());
                        map.setZoom(match.providerMarker ? 11 : 13);
                    }
                } else if (query.length === 0) {
                    map.setCenter({ lat: Number(center.lat), lng: Number(center.lng) });
                    map.setZoom(Number(config.zoom || 9));
                }
            });
        }

        document.querySelectorAll('.filter-btn').forEach((button) => {
            button.addEventListener('click', () => {
                document.querySelectorAll('.filter-btn').forEach((filterButton) => filterButton.classList.remove('active'));
                button.classList.add('active');
                const activeFilter = button.dataset.filter;

                barangayMarkers.forEach(({ marker, data }) => {
                    const visible = activeFilter === 'all'
                        || data.type === activeFilter
                        || (activeFilter === 'residential' && data.type === 'service_center');
                    marker.setMap(visible ? map : null);
                });

                document.querySelectorAll('#barangayList li').forEach((listItem) => {
                    const visible = activeFilter === 'all'
                        || listItem.dataset.type === activeFilter
                        || (activeFilter === 'residential' && listItem.dataset.type === 'service_center');
                    listItem.classList.toggle('hidden', !visible);
                });
            });
        });

        element.dataset.initialized = 'true';
        window.setTimeout(() => window.google.maps.event.trigger(map, 'resize'), 100);
    }

    window.initCleanflowCoverageMap = init;

    document.addEventListener('DOMContentLoaded', () => {
        init();

        if (!window.google?.maps && !window.cleanflowGoogleMapsEnabled) {
            showUnavailable();
        } else if (!window.google?.maps) {
            unavailableTimer = window.setTimeout(showUnavailable, 8000);
        }
    });
}());
