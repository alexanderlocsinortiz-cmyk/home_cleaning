(function () {
    'use strict';

    const config = window.cleanflowBookingLiveMapConfig || {};
    const bookingId = config.bookingId;
    const bookingStatus = config.bookingStatus;
    const staffName = config.staffName || 'Staff';
    const serviceAddress = config.serviceAddress || 'Pinned service address';
    const destination = {
        lat: Number(config.destinationLat),
        lng: Number(config.destinationLng),
    };
    const travelMinutesPerKm = 5;

    let clientMap = null;
    let clientStaffMarker = null;
    let clientDestMarker = null;
    let clientLine = null;
    let adminMap = null;
    let adminStaffMarker = null;
    let adminDestMarker = null;
    let adminLine = null;
    let clientLocationMap = null;
    let pollTimer = null;

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function markerIcon(color) {
        return {
            path: window.google.maps.SymbolPath.CIRCLE,
            scale: 10,
            fillColor: color,
            fillOpacity: 1,
            strokeColor: '#ffffff',
            strokeWeight: 3,
        };
    }

    function estimateTravelMinutes(distanceKm) {
        const numericDistance = Number.parseFloat(distanceKm);

        if (!Number.isFinite(numericDistance) || numericDistance <= 0) {
            return 1;
        }

        return Math.max(1, Math.round(numericDistance * travelMinutesPerKm));
    }

    function distanceBetweenPoints(a, b) {
        const earthRadiusKm = 6371;
        const lat1 = Number(a.lat) * Math.PI / 180;
        const lat2 = Number(b.lat) * Math.PI / 180;
        const deltaLat = (Number(b.lat) - Number(a.lat)) * Math.PI / 180;
        const deltaLng = (Number(b.lng) - Number(a.lng)) * Math.PI / 180;
        const haversine = Math.sin(deltaLat / 2) ** 2
            + Math.cos(lat1) * Math.cos(lat2) * Math.sin(deltaLng / 2) ** 2;

        return earthRadiusKm * 2 * Math.atan2(Math.sqrt(haversine), Math.sqrt(1 - haversine));
    }

    function clearLine(line) {
        if (line?.setMap) {
            line.setMap(null);
        }
    }

    function directLine(map, origin, color) {
        return new window.google.maps.Polyline({
            map,
            path: [origin, destination],
            strokeColor: color,
            strokeOpacity: 0.8,
            strokeWeight: 4,
            icons: [{
                icon: { path: 'M 0,-1 0,1', strokeOpacity: 1, scale: 3 },
                offset: '0',
                repeat: '12px',
            }],
        });
    }

    function fitRoute(map, origin) {
        const bounds = new window.google.maps.LatLngBounds();
        bounds.extend(origin);
        bounds.extend(destination);
        map.fitBounds(bounds, 40);
    }

    function resizeMap(map) {
        if (map && window.google?.maps?.event) {
            window.google.maps.event.trigger(map, 'resize');
        }
    }

    function setLocationStatus(text, color, background) {
        const status = document.getElementById('location-status');

        if (status) {
            status.textContent = text;
            status.style.color = color;
            status.style.background = background;
        }
    }

    function initClientLocationMap() {
        const mapEl = document.getElementById('client-location-map');

        if (!mapEl || clientLocationMap || !window.google?.maps) {
            return;
        }

        const lat = Number(mapEl.dataset.lat);
        const lng = Number(mapEl.dataset.lng);

        if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
            return;
        }

        clientLocationMap = new window.google.maps.Map(mapEl, {
            center: { lat, lng },
            zoom: 17,
            mapTypeId: 'roadmap',
            scaleControl: true,
            streetViewControl: false,
            mapTypeControl: false,
            fullscreenControl: true,
            gestureHandling: 'greedy',
        });

        const marker = new window.google.maps.Marker({
            map: clientLocationMap,
            position: { lat, lng },
            title: 'Client service location',
            label: { text: 'C', color: '#ffffff', fontWeight: '700' },
            icon: markerIcon('#2563eb'),
        });
        const info = new window.google.maps.InfoWindow({
            content: `<strong>Client service location</strong><br>${escapeHtml(serviceAddress)}`,
        });

        marker.addListener('click', () => info.open({ map: clientLocationMap, anchor: marker, shouldFocus: false }));
        window.setTimeout(() => resizeMap(clientLocationMap), 100);
    }

    function ensureClientMap() {
        if (clientMap || !document.getElementById('google-map-frame')) {
            return clientMap;
        }

        clientMap = new window.google.maps.Map(document.getElementById('google-map-frame'), {
            center: destination,
            zoom: 15,
            mapTypeId: 'roadmap',
            scaleControl: true,
            streetViewControl: false,
            mapTypeControl: false,
            fullscreenControl: true,
            gestureHandling: 'greedy',
        });
        clientDestMarker = new window.google.maps.Marker({
            map: clientMap,
            position: destination,
            title: 'Your service address',
            label: { text: 'C', color: '#ffffff', fontWeight: '700' },
            icon: markerIcon('#2563eb'),
        });
        const info = new window.google.maps.InfoWindow({
            content: `<strong>Your address</strong><br>${escapeHtml(serviceAddress)}`,
        });

        clientDestMarker.addListener('click', () => info.open({ map: clientMap, anchor: clientDestMarker, shouldFocus: false }));
        window.setTimeout(() => resizeMap(clientMap), 100);

        return clientMap;
    }

    function ensureAdminMap() {
        if (adminMap || !document.getElementById('admin-google-map-frame')) {
            return adminMap;
        }

        adminMap = new window.google.maps.Map(document.getElementById('admin-google-map-frame'), {
            center: destination,
            zoom: 15,
            mapTypeId: 'roadmap',
            scaleControl: true,
            streetViewControl: false,
            mapTypeControl: false,
            fullscreenControl: true,
            gestureHandling: 'greedy',
        });
        adminDestMarker = new window.google.maps.Marker({
            map: adminMap,
            position: destination,
            title: 'Client destination',
            label: { text: 'C', color: '#ffffff', fontWeight: '700' },
            icon: markerIcon('#2563eb'),
        });
        const info = new window.google.maps.InfoWindow({
            content: `<strong>Client destination</strong><br>${escapeHtml(serviceAddress)}`,
        });

        adminDestMarker.addListener('click', () => info.open({ map: adminMap, anchor: adminDestMarker, shouldFocus: false }));
        window.setTimeout(() => resizeMap(adminMap), 100);

        return adminMap;
    }

    function updateArrival(distanceKm, durationMinutes) {
        const distance = Number(distanceKm).toFixed(1);
        const duration = Math.max(1, Math.round(Number(durationMinutes) || estimateTravelMinutes(distanceKm)));
        const info = document.getElementById('client-route-info');

        if (info) {
            info.style.display = 'block';
            info.innerHTML = `<strong>${distance} km</strong> away &nbsp;|&nbsp; <strong>${duration} min</strong> estimated arrival`;
        }

        const arrivalText = document.getElementById('arrival-text');
        const arrivalSub = document.getElementById('arrival-sub');

        if (!arrivalText) {
            return;
        }

        if (Number(distanceKm) < 0.3) {
            arrivalText.innerHTML = '<strong>Staff has arrived!</strong>';
            arrivalText.style.color = '#1E40AF';

            if (arrivalSub) {
                arrivalSub.textContent = 'Your cleaner is at your location.';
            }
        } else if (duration <= 5) {
            arrivalText.innerHTML = '<strong>Staff is arriving soon!</strong>';
            arrivalText.style.color = '#2563EB';

            if (arrivalSub) {
                arrivalSub.textContent = `About ${duration} min away - ${distance} km`;
            }
        } else {
            arrivalText.innerHTML = '<strong>Staff is on the way</strong>';
            arrivalText.style.color = '#2563EB';

            if (arrivalSub) {
                arrivalSub.textContent = `About ${duration} min away - ${distance} km`;
            }
        }
    }

    function drawRoute(map, origin, existingLine, color, onRoute) {
        clearLine(existingLine);

        const directionsService = new window.google.maps.DirectionsService();

        directionsService.route({
            origin,
            destination,
            travelMode: window.google.maps.TravelMode.DRIVING,
        }, (result, routeStatus) => {
            if (routeStatus === 'OK' && result?.routes?.[0]?.legs?.[0]) {
                const leg = result.routes[0].legs[0];
                const routeRenderer = new window.google.maps.DirectionsRenderer({
                    map,
                    directions: result,
                    suppressMarkers: true,
                    preserveViewport: false,
                    polylineOptions: {
                        strokeColor: color,
                        strokeOpacity: 0.85,
                        strokeWeight: 5,
                    },
                });
                const distanceKm = Number(leg.distance?.value || 0) / 1000;
                const durationMinutes = Number(leg.duration?.value || 0) / 60;

                onRoute(routeRenderer, distanceKm, durationMinutes, true);
                return;
            }

            const fallback = directLine(map, origin, color);
            onRoute(fallback, distanceBetweenPoints(origin, destination), null, false);
        });
    }

    async function showClientMap(lat, lng, updatedAt) {
        if (!window.google?.maps || !Number.isFinite(lat) || !Number.isFinite(lng)) {
            return;
        }

        const noMsg = document.getElementById('no-location-msg');
        const mapContainer = document.getElementById('map-container');
        const arrivalStatus = document.getElementById('arrival-status');
        const map = ensureClientMap();

        if (!map) {
            return;
        }

        if (noMsg) noMsg.style.display = 'none';
        if (mapContainer) mapContainer.style.display = 'block';
        if (arrivalStatus) arrivalStatus.style.display = 'flex';

        const origin = { lat, lng };

        if (clientStaffMarker) {
            clientStaffMarker.setPosition(origin);
        } else {
            clientStaffMarker = new window.google.maps.Marker({
                map,
                position: origin,
                title: `${staffName} is on the way`,
                label: { text: 'S', color: '#ffffff', fontWeight: '700' },
                icon: markerIcon('#059669'),
            });
        }

        drawRoute(map, origin, clientLine, '#2563EB', (line, distanceKm, durationMinutes, routed) => {
            clientLine = line;

            if (routed) {
                updateArrival(distanceKm, durationMinutes);
            } else {
                updateArrival(distanceKm, estimateTravelMinutes(distanceKm));
                setLocationStatus('Live - Showing direct route estimate', '#2563EB', '#EFF6FF');
            }
        });

        fitRoute(map, origin);
        resizeMap(map);
        setLocationStatus('Live - Updated ' + (updatedAt || 'just now'), '#2563EB', '#EFF6FF');
    }

    async function showAdminMap(lat, lng, updatedAt) {
        if (!window.google?.maps || !Number.isFinite(lat) || !Number.isFinite(lng)) {
            return;
        }

        const noMsg = document.getElementById('admin-no-location');
        const mapContainer = document.getElementById('admin-map-container');
        const info = document.getElementById('admin-location-info');
        const status = document.getElementById('admin-location-status');
        const map = ensureAdminMap();

        if (!map) {
            return;
        }

        if (noMsg) noMsg.style.display = 'none';
        if (mapContainer) mapContainer.style.display = 'block';
        if (info) info.textContent = `📍 Last updated: ${updatedAt || 'just now'} — Coordinates: ${lat.toFixed(5)}, ${lng.toFixed(5)}`;
        if (status) {
            status.textContent = '🟢 Live';
            status.style.color = '#2563EB';
            status.style.background = '#EFF6FF';
        }

        const origin = { lat, lng };

        if (adminStaffMarker) {
            adminStaffMarker.setPosition(origin);
        } else {
            adminStaffMarker = new window.google.maps.Marker({
                map,
                position: origin,
                title: `${staffName} is here`,
                label: { text: 'S', color: '#ffffff', fontWeight: '700' },
                icon: markerIcon('#059669'),
            });
        }

        drawRoute(map, origin, adminLine, '#60A5FA', (line) => {
            adminLine = line;
        });

        fitRoute(map, origin);
        resizeMap(map);
    }

    async function pollLocation() {
        try {
            const response = await fetch(`/bookings/${bookingId}/location/current`, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();

            if (!data.tracking) {
                setLocationStatus('Waiting for location...', '#60A5FA', '#EFF6FF');
                return;
            }

            const lat = Number.parseFloat(data.latitude);
            const lng = Number.parseFloat(data.longitude);

            await Promise.all([
                showClientMap(lat, lng, data.updated_at),
                showAdminMap(lat, lng, data.updated_at),
            ]);
        } catch (error) {
            console.error('Poll error:', error);
        }
    }

    function init() {
        if (!window.google?.maps) {
            return;
        }

        initClientLocationMap();

        if (['confirmed', 'in_progress'].includes(bookingStatus)) {
            pollLocation();

            if (!pollTimer) {
                pollTimer = window.setInterval(pollLocation, 10000);
            }
        }
    }

    window.initCleanflowLiveBookingMaps = init;

    document.addEventListener('DOMContentLoaded', () => {
        if (window.google?.maps) {
            init();
        }
    });
}());
