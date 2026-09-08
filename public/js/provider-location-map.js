(function () {
    'use strict';

    const providerLocationMaps = new Set();

    function invalidateProviderLocationMaps() {
        providerLocationMaps.forEach((map) => map.invalidateSize());
    }

    window.cleanflowInvalidateProviderLocationMaps = invalidateProviderLocationMaps;

    function numberOrNull(value) {
        const number = Number(value);

        return Number.isFinite(number) ? number : null;
    }

    function initializeProviderLocationMaps() {
        if (!window.L) {
            return;
        }

        const config = window.cleanflowProviderMapConfig || {};
        const centers = window.cleanflowProviderLocationCenters || {};

        document.querySelectorAll('[data-provider-location-map]').forEach((element) => {
            if (element.dataset.initialized === 'true') {
                return;
            }

            element.dataset.initialized = 'true';
            const readOnly = element.dataset.readonly === 'true';
            const mapCenter = config.center || { lat: 7.95, lng: 124.95 };
            const map = L.map(element, {
                minZoom: config.minZoom || 8,
                maxZoom: config.maxZoom || 17,
                maxBounds: config.maxBounds || undefined,
                maxBoundsViscosity: 0.85,
            }).setView([mapCenter.lat, mapCenter.lng], config.zoom || 9);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
                maxZoom: config.maxZoom || 17,
            }).addTo(map);
            L.control.scale({ imperial: false }).addTo(map);
            providerLocationMaps.add(map);

            const shell = element.closest('[data-provider-location-shell]') || element.parentElement;
            const areaInput = document.getElementById(element.dataset.areaInput || 'location_area');
            const latitudeInput = document.getElementById(element.dataset.latitudeInput || 'location_latitude');
            const longitudeInput = document.getElementById(element.dataset.longitudeInput || 'location_longitude');
            const status = shell?.querySelector('[data-provider-location-status]');
            const currentLocationButton = shell?.querySelector('[data-provider-location-current]');
            let marker = null;

            const setStatus = (message, isError = false) => {
                if (!status) {
                    return;
                }

                status.textContent = message;
                status.classList.toggle('text-red-600', isError);
                status.classList.toggle('text-emerald-700', !isError);
            };

            const setPin = (lat, lng, message = 'Location pin saved.') => {
                if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                    return;
                }

                marker?.remove();
                marker = L.marker([lat, lng]).addTo(map);
                latitudeInput && (latitudeInput.value = lat.toFixed(7));
                longitudeInput && (longitudeInput.value = lng.toFixed(7));
                map.panTo([lat, lng]);
                setStatus(message);
            };

            const initialLat = numberOrNull(latitudeInput?.value || element.dataset.latitude);
            const initialLng = numberOrNull(longitudeInput?.value || element.dataset.longitude);

            if (initialLat !== null && initialLng !== null) {
                setPin(initialLat, initialLng, 'Saved provider location.');
            }

            areaInput?.addEventListener('change', () => {
                const center = centers[areaInput.value];

                if (center) {
                    map.setView([center.lat, center.lng], Math.max(config.zoom || 9, 11));
                    setStatus('Map moved to the selected city/municipality. Click the exact base location.');
                }
            });

            if (!readOnly) {
                map.on('click', (event) => setPin(event.latlng.lat, event.latlng.lng));

                currentLocationButton?.addEventListener('click', () => {
                    if (!navigator.geolocation) {
                        setStatus('This browser does not support location detection. Click the map instead.', true);
                        return;
                    }

                    setStatus('Requesting your current location...');
                    navigator.geolocation.getCurrentPosition(
                        (position) => setPin(position.coords.latitude, position.coords.longitude, 'Current location pinned.'),
                        () => setStatus('Location access was unavailable. Click the map to place the pin manually.', true),
                        { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
                    );
                });
            }

            window.setTimeout(() => map.invalidateSize(), 100);
        });
    }

    document.addEventListener('DOMContentLoaded', initializeProviderLocationMaps);
})();
