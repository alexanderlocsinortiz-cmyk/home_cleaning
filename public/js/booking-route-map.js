(function () {
    'use strict';

    const mapStates = {};
    let currentPosition = null;
    let locationPromise = null;
    let unavailableTimer = null;

    function mapElements() {
        return document.querySelectorAll('.provider-list-map, .staff-booking-map, .provider-booking-map');
    }

    function status(mapId, message) {
        const element = document.getElementById(mapId + '-status');

        if (element) {
            element.textContent = message;
        }
    }

    function showUnavailable() {
        mapElements().forEach((element) => {
            if (element.dataset.googleMapsMessage === 'true' || window.google?.maps) {
                return;
            }

            element.dataset.googleMapsMessage = 'true';
            element.classList.add('flex', 'items-center', 'justify-center', 'p-6', 'text-center');
            element.innerHTML = '<div class="max-w-md text-sm font-semibold leading-6 text-slate-600"><div class="mb-2 text-base font-black text-slate-900">Google Maps is not available</div><div>Add a valid <code class="rounded bg-slate-200 px-1.5 py-0.5 text-xs">GOOGLE_MAPS_API_KEY</code> in Laravel Cloud and enable the Maps JavaScript API, then reload this page.</div></div>';
        });
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

    function initMap(mapElement) {
        if (!mapElement || mapStates[mapElement.id]) {
            return mapStates[mapElement?.id] || null;
        }

        if (!window.google?.maps) {
            status(mapElement.id, 'Google Maps is still loading. Please try again shortly.');
            return null;
        }

        const destLat = Number(mapElement.dataset.destinationLat);
        const destLng = Number(mapElement.dataset.destinationLng);

        if (!Number.isFinite(destLat) || !Number.isFinite(destLng)) {
            status(mapElement.id, 'Client pin is invalid');
            return null;
        }

        const destination = { lat: destLat, lng: destLng };
        const map = new window.google.maps.Map(mapElement, {
            center: destination,
            zoom: 16,
            mapTypeId: 'roadmap',
            scaleControl: true,
            streetViewControl: false,
            mapTypeControl: false,
            fullscreenControl: true,
            gestureHandling: 'greedy',
        });
        const infoWindow = new window.google.maps.InfoWindow();
        const clientMarker = new window.google.maps.Marker({
            map,
            position: destination,
            title: mapElement.dataset.client || 'Client',
            label: { text: 'C', color: '#ffffff', fontWeight: '700' },
            icon: markerIcon('#2563eb'),
        });

        clientMarker.addListener('click', () => {
            infoWindow.setContent(`<strong>${mapElement.dataset.client || 'Client'}</strong><br>${mapElement.dataset.address || 'Pinned service address'}`);
            infoWindow.open({ map, anchor: clientMarker, shouldFocus: false });
        });

        mapStates[mapElement.id] = {
            map,
            destination,
            infoWindow,
            clientMarker,
            providerMarker: null,
            routeRenderer: null,
            directLine: null,
        };
        window.setTimeout(() => window.google.maps.event.trigger(map, 'resize'), 100);

        return mapStates[mapElement.id];
    }

    function getCurrentPosition() {
        if (currentPosition) {
            return Promise.resolve(currentPosition);
        }

        if (locationPromise) {
            return locationPromise;
        }

        locationPromise = new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error('Geolocation unavailable'));
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    currentPosition = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude,
                    };
                    resolve(currentPosition);
                },
                reject,
                { enableHighAccuracy: true, timeout: 12000, maximumAge: 60000 }
            );
        }).finally(() => {
            locationPromise = null;
        });

        return locationPromise;
    }

    function clearRoute(state) {
        if (state.routeRenderer) {
            state.routeRenderer.setMap(null);
            state.routeRenderer = null;
        }

        if (state.directLine) {
            state.directLine.setMap(null);
            state.directLine = null;
        }
    }

    function drawDirectLine(state, origin) {
        clearRoute(state);
        state.directLine = new window.google.maps.Polyline({
            map: state.map,
            path: [origin, state.destination],
            strokeColor: '#2563eb',
            strokeOpacity: 0.8,
            strokeWeight: 4,
            icons: [{
                icon: { path: 'M 0,-1 0,1', strokeOpacity: 1, scale: 3 },
                offset: '0',
                repeat: '12px',
            }],
        });
        const bounds = new window.google.maps.LatLngBounds();
        bounds.extend(origin);
        bounds.extend(state.destination);
        state.map.fitBounds(bounds, 24);
    }

    function drawRoute(mapId) {
        const state = mapStates[mapId] || initMap(document.getElementById(mapId));

        if (!state) {
            return;
        }

        status(mapId, 'Getting your current location...');

        getCurrentPosition().then((origin) => {
            if (state.providerMarker) {
                state.providerMarker.setPosition(origin);
            } else {
                state.providerMarker = new window.google.maps.Marker({
                    map: state.map,
                    position: origin,
                    title: 'Your current location',
                    label: { text: 'P', color: '#ffffff', fontWeight: '700' },
                    icon: markerIcon('#059669'),
                });
                state.providerMarker.addListener('click', () => {
                    state.infoWindow.setContent('Your current location');
                    state.infoWindow.open({ map: state.map, anchor: state.providerMarker, shouldFocus: false });
                });
            }

            const directionsService = new window.google.maps.DirectionsService();
            directionsService.route({
                origin,
                destination: state.destination,
                travelMode: window.google.maps.TravelMode.DRIVING,
            }, (result, responseStatus) => {
                if (responseStatus === 'OK' && result?.routes?.[0]?.legs?.[0]) {
                    clearRoute(state);
                    state.routeRenderer = new window.google.maps.DirectionsRenderer({
                        map: state.map,
                        directions: result,
                        suppressMarkers: true,
                        preserveViewport: false,
                        polylineOptions: {
                            strokeColor: '#2563eb',
                            strokeOpacity: 0.85,
                            strokeWeight: 5,
                        },
                    });
                    const leg = result.routes[0].legs[0];
                    const distance = leg.distance?.text || '';
                    const duration = leg.duration?.text || '';
                    status(mapId, distance && duration ? `${distance} - about ${duration}` : 'Route ready');
                } else {
                    drawDirectLine(state, origin);
                    status(mapId, 'Google route service unavailable; showing direct line');
                }
            });
        }).catch(() => {
            state.map.setCenter(state.destination);
            state.map.setZoom(16);
            status(mapId, 'Allow location access to draw your route');
        });
    }

    function mapTarget(button) {
        return button.dataset.providerListMapTarget
            || button.dataset.mapTarget
            || button.dataset.providerRouteMap
            || '';
    }

    function initAll() {
        if (!window.google?.maps) {
            showUnavailable();
            return;
        }

        mapElements().forEach(initMap);
    }

    window.initCleanflowBookingRouteMaps = initAll;

    document.addEventListener('DOMContentLoaded', () => {
        initAll();

        document.querySelectorAll('[data-provider-list-map-toggle], [data-route-panel][data-map-target], [data-provider-route-map]').forEach((button) => {
            button.addEventListener('click', () => {
                const panelId = button.dataset.providerListMapToggle || button.dataset.routePanel;
                const targetId = mapTarget(button);
                const panel = panelId ? document.getElementById(panelId) : null;
                const mapElement = targetId ? document.getElementById(targetId) : null;

                panel?.classList.remove('hidden');
                initMap(mapElement);
                drawRoute(targetId);
                window.setTimeout(() => {
                    const state = mapStates[targetId];

                    if (state && window.google?.maps?.event) {
                        window.google.maps.event.trigger(state.map, 'resize');
                    }
                }, 100);
            });
        });

        document.querySelectorAll('[data-provider-list-map-close], .staff-route-close').forEach((button) => {
            button.addEventListener('click', () => {
                const panelId = button.dataset.providerListMapClose || button.dataset.routePanel;
                document.getElementById(panelId)?.classList.add('hidden');
            });
        });

        if (!window.google?.maps && !window.cleanflowGoogleMapsEnabled) {
            showUnavailable();
        } else if (!window.google?.maps) {
            unavailableTimer = window.setTimeout(showUnavailable, 8000);
        }
    });
}());
