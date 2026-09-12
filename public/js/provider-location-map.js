(function () {
    'use strict';

    const providerLocationMaps = new Set();
    let googleMapsUnavailableTimer = null;

    function invalidateProviderLocationMaps() {
        providerLocationMaps.forEach(({ map }) => {
            if (window.google?.maps?.event) {
                window.google.maps.event.trigger(map, 'resize');
            }
        });
    }

    window.cleanflowInvalidateProviderLocationMaps = invalidateProviderLocationMaps;

    function numberOrNull(value) {
        const number = Number(value);

        return Number.isFinite(number) ? number : null;
    }

    function normalizeAreaName(value) {
        return String(value || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .replace(/\b(city|municipality)\b/g, '')
            .replace(/[^a-z0-9]+/g, ' ')
            .trim();
    }

    function configuredAreas(centers) {
        return Object.entries(centers || {}).filter(([, center]) => (
            numberOrNull(center?.lat) !== null && numberOrNull(center?.lng) !== null
        ));
    }

    function areaNamesMatch(candidate, configuredName) {
        const normalizedCandidate = normalizeAreaName(candidate);
        const normalizedConfiguredName = normalizeAreaName(configuredName);

        return normalizedCandidate !== '' && (
            normalizedCandidate === normalizedConfiguredName
            || normalizedCandidate.includes(normalizedConfiguredName)
            || normalizedConfiguredName.includes(normalizedCandidate)
        );
    }

    function nearestConfiguredArea(lat, lng, centers) {
        let nearest = null;
        let nearestDistance = Number.POSITIVE_INFINITY;

        configuredAreas(centers).forEach(([name, center]) => {
            const centerLat = numberOrNull(center.lat);
            const centerLng = numberOrNull(center.lng);
            const distance = ((lat - centerLat) ** 2) + ((lng - centerLng) ** 2);

            if (distance < nearestDistance) {
                nearestDistance = distance;
                nearest = name;
            }
        });

        return nearest;
    }

    function isWithinConfiguredBounds(lat, lng, config) {
        const bounds = config.maxBounds;

        if (!Array.isArray(bounds) || bounds.length < 2) {
            return true;
        }

        const southWest = bounds[0];
        const northEast = bounds[1];

        return lat >= Number(southWest[0])
            && lat <= Number(northEast[0])
            && lng >= Number(southWest[1])
            && lng <= Number(northEast[1]);
    }

    function areaFromGeocoderResults(results, centers) {
        const candidates = [];

        (results || []).forEach((result) => {
            (result.address_components || []).forEach((component) => {
                candidates.push(component.long_name, component.short_name);
            });
        });

        return configuredAreas(centers).find(([name]) => (
            candidates.some((candidate) => areaNamesMatch(candidate, name))
        ))?.[0] || null;
    }

    function renderGoogleMapsRequired(element) {
        if (!element || element.dataset.initialized === 'true' || element.dataset.googleMapsMessage === 'true') {
            return;
        }

        element.dataset.googleMapsMessage = 'true';
        element.classList.add('flex', 'items-center', 'justify-center', 'p-6', 'text-center');
        element.innerHTML = '<div class="max-w-md text-sm font-semibold leading-6 text-slate-600"><div class="mb-2 text-base font-black text-slate-900">Google Maps is not available</div><div>Add a valid <code class="rounded bg-slate-200 px-1.5 py-0.5 text-xs">GOOGLE_MAPS_API_KEY</code> in Laravel Cloud and enable the Maps JavaScript API, then reload this page.</div></div>';
    }

    function showGoogleMapsRequiredIfUnavailable() {
        if (window.google?.maps || window.cleanflowGoogleMapsEnabled) {
            return;
        }

        document.querySelectorAll('[data-provider-location-map]').forEach(renderGoogleMapsRequired);
    }

    function initializeProviderLocationMaps() {
        if (!window.google?.maps) {
            return;
        }

        if (googleMapsUnavailableTimer) {
            window.clearTimeout(googleMapsUnavailableTimer);
            googleMapsUnavailableTimer = null;
        }

        const config = window.cleanflowProviderMapConfig || {};
        const centers = window.cleanflowProviderLocationCenters || {};
        const mapCenter = config.center || { lat: 7.95, lng: 124.95 };
        const bounds = config.maxBounds;

        document.querySelectorAll('[data-provider-location-map]').forEach((element) => {
            if (element.dataset.initialized === 'true') {
                return;
            }

            element.dataset.googleMapsMessage = 'false';
            element.dataset.initialized = 'true';
            const readOnly = element.dataset.readonly === 'true';
            const map = new window.google.maps.Map(element, {
                center: mapCenter,
                zoom: config.zoom || 9,
                minZoom: config.minZoom || 8,
                maxZoom: config.maxZoom || 17,
                restriction: Array.isArray(bounds) && bounds.length >= 2
                    ? {
                        latLngBounds: {
                            south: Number(bounds[0][0]),
                            west: Number(bounds[0][1]),
                            north: Number(bounds[1][0]),
                            east: Number(bounds[1][1]),
                        },
                        strictBounds: false,
                    }
                    : undefined,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: true,
                scaleControl: true,
            });

            providerLocationMaps.add({ map });

            const shell = element.closest('[data-provider-location-shell]')
                || element.closest('[data-provider-location-form]')
                || element.parentElement;
            const areaInput = document.getElementById(element.dataset.areaInput || 'location_area');
            const latitudeInput = document.getElementById(element.dataset.latitudeInput || 'location_latitude');
            const longitudeInput = document.getElementById(element.dataset.longitudeInput || 'location_longitude');
            const confirmedInput = document.getElementById(element.dataset.confirmedInput || '');
            const status = shell?.querySelector('[data-provider-location-status]');
            const currentLocationButton = shell?.querySelector('[data-provider-location-current]');
            const confirmButton = shell?.querySelector('[data-provider-location-confirm]');
            const geocoder = window.google.maps.Geocoder ? new window.google.maps.Geocoder() : null;
            let marker = null;
            let locationRequestId = 0;

            const setStatus = (message, isError = false) => {
                if (!status) {
                    return;
                }

                status.textContent = message;
                status.classList.toggle('text-red-600', isError);
                status.classList.toggle('text-emerald-700', !isError);
                status.classList.toggle('text-slate-500', false);
            };

            const clearConfirmation = () => {
                if (confirmedInput) {
                    confirmedInput.value = '';
                }

                if (confirmButton) {
                    confirmButton.disabled = true;
                    confirmButton.classList.add('opacity-60', 'cursor-not-allowed');
                }
            };

            const enableConfirmation = () => {
                if (confirmButton) {
                    confirmButton.disabled = false;
                    confirmButton.classList.remove('opacity-60', 'cursor-not-allowed');
                }
            };

            const confirmLocation = () => {
                if (!areaInput?.value || !latitudeInput?.value || !longitudeInput?.value) {
                    return;
                }

                if (confirmedInput) {
                    confirmedInput.value = '1';
                }

                enableConfirmation();
                setStatus(`Location confirmed for ${areaInput.value}.`);
                element.classList.remove('ring-2', 'ring-red-300');
            };

            const detectMunicipality = (lat, lng) => {
                const requestId = ++locationRequestId;

                setStatus('Detecting municipality from this location...');

                if (!geocoder) {
                    const nearest = nearestConfiguredArea(lat, lng, centers);

                    if (requestId === locationRequestId && nearest) {
                        if (areaInput) {
                            areaInput.value = nearest;
                        }
                        enableConfirmation();
                        setStatus(`Nearest municipality detected: ${nearest}. Confirm to save.`);
                    }

                    return;
                }

                geocoder.geocode({ location: { lat, lng } }, (results, geocoderStatus) => {
                    if (requestId !== locationRequestId) {
                        return;
                    }

                    const detectedArea = geocoderStatus === 'OK'
                        ? areaFromGeocoderResults(results, centers)
                        : null;
                    const area = detectedArea || nearestConfiguredArea(lat, lng, centers);

                    if (!area) {
                        setStatus('The municipality could not be detected. Select it manually, then confirm.', true);
                        return;
                    }

                    if (areaInput) {
                        areaInput.value = area;
                    }

                    enableConfirmation();
                    setStatus(
                        detectedArea
                            ? `Municipality detected: ${area}. Confirm to save.`
                            : `Nearest municipality detected: ${area}. Confirm to save.`
                    );
                });
            };

            const setPin = (lat, lng, { detectArea = true, preserveConfirmation = false } = {}) => {
                if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                    return false;
                }

                if (!isWithinConfiguredBounds(lat, lng, config)) {
                    setStatus('This location is outside the supported Bukidnon service area.', true);
                    return false;
                }

                if (!marker) {
                    marker = new window.google.maps.Marker({
                        map,
                        draggable: !readOnly,
                        title: 'Provider base location',
                    });

                    if (!readOnly) {
                        marker.addListener('dragend', (event) => {
                            setPin(event.latLng.lat(), event.latLng.lng());
                        });
                    }
                }

                marker.setPosition({ lat, lng });
                latitudeInput && (latitudeInput.value = lat.toFixed(7));
                longitudeInput && (longitudeInput.value = lng.toFixed(7));
                map.panTo({ lat, lng });

                if (!preserveConfirmation) {
                    clearConfirmation();
                }

                if (detectArea) {
                    detectMunicipality(lat, lng);
                } else if (confirmedInput?.value === '1') {
                    enableConfirmation();
                    setStatus(`Location confirmed for ${areaInput?.value || 'the selected municipality'}.`);
                } else if (confirmButton) {
                    enableConfirmation();
                    setStatus('Location selected. Confirm this location before continuing.');
                } else {
                    setStatus('Provider location selected.');
                }

                return true;
            };

            const clearPin = () => {
                locationRequestId += 1;
                marker?.setMap(null);
                marker = null;
                if (latitudeInput) {
                    latitudeInput.value = '';
                }
                if (longitudeInput) {
                    longitudeInput.value = '';
                }
                clearConfirmation();
            };

            const initialLat = numberOrNull(latitudeInput?.value || element.dataset.latitude);
            const initialLng = numberOrNull(longitudeInput?.value || element.dataset.longitude);

            if (initialLat !== null && initialLng !== null) {
                setPin(initialLat, initialLng, {
                    detectArea: false,
                    preserveConfirmation: confirmedInput?.value === '1',
                });
            } else if (confirmButton) {
                clearConfirmation();
            }

            areaInput?.addEventListener('change', () => {
                clearPin();
                const center = centers[areaInput.value];

                if (center) {
                    map.setCenter({ lat: Number(center.lat), lng: Number(center.lng) });
                    map.setZoom(Math.max(config.zoom || 9, 11));
                    setStatus('Municipality selected. Click the exact base location, then confirm.');
                }
            });

            confirmButton?.addEventListener('click', confirmLocation);

            if (!readOnly) {
                map.addListener('click', (event) => setPin(event.latLng.lat(), event.latLng.lng()));

                currentLocationButton?.addEventListener('click', () => {
                    if (!navigator.geolocation) {
                        setStatus('This browser does not support location detection. Click the map instead.', true);
                        return;
                    }

                    setStatus('Requesting your current location...');
                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            if (setPin(position.coords.latitude, position.coords.longitude)) {
                                map.setZoom(Math.max(config.zoom || 9, 12));
                            }
                        },
                        () => setStatus('Location access was unavailable. Click the map to place the pin manually.', true),
                        { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
                    );
                });
            }

            window.setTimeout(() => window.google.maps.event.trigger(map, 'resize'), 100);
        });
    }

    window.initCleanflowProviderMap = initializeProviderLocationMaps;

    document.addEventListener('DOMContentLoaded', () => {
        initializeProviderLocationMaps();

        if (!window.google?.maps && !window.cleanflowGoogleMapsEnabled) {
            showGoogleMapsRequiredIfUnavailable();
        } else if (!window.google?.maps) {
            googleMapsUnavailableTimer = window.setTimeout(showGoogleMapsRequiredIfUnavailable, 8000);
        }
    });
})();
