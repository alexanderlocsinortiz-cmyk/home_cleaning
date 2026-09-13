(function () {
    'use strict';

    let unavailableTimer = null;

    function showUnavailable() {
        const element = document.getElementById('provider-directory-map');

        if (!element || element.dataset.googleMapsMessage === 'true' || window.google?.maps) {
            return;
        }

        element.dataset.googleMapsMessage = 'true';
        element.classList.add('flex', 'items-center', 'justify-center', 'p-6', 'text-center');
        element.innerHTML = '<div class="max-w-md text-sm font-semibold leading-6 text-slate-600"><div class="mb-2 text-base font-black text-slate-900">Google Maps is not available</div><div>Add a valid <code class="rounded bg-slate-200 px-1.5 py-0.5 text-xs">GOOGLE_MAPS_API_KEY</code> in Laravel Cloud and enable the Maps JavaScript API, then reload this page.</div></div>';
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function init() {
        const element = document.getElementById('provider-directory-map');

        if (!element || element.dataset.initialized === 'true') {
            return;
        }

        if (!window.google?.maps) {
            showUnavailable();
            return;
        }

        const config = window.cleanflowAdminProviderMapConfig || {};
        const points = Array.isArray(window.cleanflowAdminProviderMapPoints)
            ? window.cleanflowAdminProviderMapPoints
            : [];
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
        const bounds = new window.google.maps.LatLngBounds();
        const markerIcon = {
            path: window.google.maps.SymbolPath.CIRCLE,
            scale: 9,
            fillColor: '#60a5fa',
            fillOpacity: 1,
            strokeColor: '#1d4ed8',
            strokeWeight: 3,
        };

        points.forEach((point) => {
            const lat = Number(point.lat);
            const lng = Number(point.lng);

            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                return;
            }

            const position = { lat, lng };
            const marker = new window.google.maps.Marker({
                map,
                position,
                title: point.name || 'Provider',
                icon: markerIcon,
            });
            const content = `
                <div class="cleanflow-map-popup">
                    <strong>${escapeHtml(point.name)}</strong>
                    <div>${escapeHtml(point.contact)}</div>
                    <div>${escapeHtml(point.area || 'Area not set')}</div>
                    <div>${escapeHtml(point.availability)} · ${escapeHtml(point.status)}</div>
                </div>`;

            marker.addListener('click', () => {
                infoWindow.setContent(content);
                infoWindow.open({ map, anchor: marker, shouldFocus: false });
            });
            bounds.extend(position);
        });

        if (points.length > 0 && !bounds.isEmpty()) {
            map.fitBounds(bounds, 30);
            window.google.maps.event.addListenerOnce(map, 'bounds_changed', () => {
                if (map.getZoom() > 13) {
                    map.setZoom(13);
                }
            });
        }

        element.dataset.initialized = 'true';
        window.setTimeout(() => window.google.maps.event.trigger(map, 'resize'), 100);
    }

    window.initCleanflowAdminProviderMap = init;

    document.addEventListener('DOMContentLoaded', () => {
        init();

        if (!window.google?.maps && !window.cleanflowGoogleMapsEnabled) {
            showUnavailable();
        } else if (!window.google?.maps) {
            unavailableTimer = window.setTimeout(showUnavailable, 8000);
        }
    });
}());
