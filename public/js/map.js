document.addEventListener('DOMContentLoaded', function () {
    if (typeof L === 'undefined' || !document.getElementById('map')) {
        return;
    }

    const colors = {
        service_center: '#1E3A8A',
        residential: '#2563EB',
        commercial: '#3B82F6',
        office: '#60A5FA',
        municipality: '#0F766E',
    };
    const barangayData = Array.isArray(window.barangayData) ? window.barangayData : [];
    const coverageData = Array.isArray(window.cleanflowCoverageData) ? window.cleanflowCoverageData : [];
    const providerCoverageData = Array.isArray(window.providerCoverageData) ? window.providerCoverageData : [];
    const mapConfig = window.cleanflowMapConfig || {};
    const mapCenter = mapConfig.center || { lat: 7.95, lng: 124.95 };
    const mapZoom = mapConfig.zoom ?? 9;
    const minZoom = mapConfig.minZoom ?? 8;
    const maxZoom = mapConfig.maxZoom ?? 17;
    const maxBounds = mapConfig.maxBounds ?? [[7.35, 124.40], [8.65, 125.55]];

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function makeIcon(type) {
        const color = colors[type] || '#2563EB';

        return L.divIcon({
            className: '',
            html: `<div style="width:18px;height:18px;border-radius:50%;background:${color};border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,0.4)"></div>`,
            iconSize: [18, 18],
            iconAnchor: [9, 9],
            popupAnchor: [0, -12],
        });
    }

    function makeProviderIcon() {
        return L.divIcon({
            className: '',
            html: '<div style="width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#7c3aed;color:#fff;border:3px solid #fff;box-shadow:0 2px 10px rgba(0,0,0,0.45);font-size:13px"><i class="fas fa-users"></i></div>',
            iconSize: [30, 30],
            iconAnchor: [15, 15],
            popupAnchor: [0, -17],
        });
    }

    const map = L.map('map', {
        center: [mapCenter.lat, mapCenter.lng],
        zoom: mapZoom,
        minZoom: minZoom,
        maxZoom: maxZoom,
        maxBounds: maxBounds,
        maxBoundsViscosity: 0.8,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19,
    }).addTo(map);

    const barangayMarkers = [];
    const coverageMarkers = [];

    barangayData.forEach(function (barangay) {
        const serviceList = (barangay.services || [])
            .map((service) => `<li>${escapeHtml(service)}</li>`)
            .join('');
        const typeName = (barangay.type || 'residential')
            .replace('_', ' ')
            .replace(/\b\w/g, function (char) {
                return char.toUpperCase();
            });
        const popup = `
            <div class="cleanflow-map-popup">
                <strong class="cleanflow-map-popup__title">${escapeHtml(barangay.name)}</strong>
                <span class="cleanflow-map-popup__type" style="--popup-accent:${colors[barangay.type] || colors.residential};">${escapeHtml(typeName)}</span>
                <ul class="cleanflow-map-popup__services">${serviceList}</ul>
            </div>`;
        const marker = L.marker([barangay.lat, barangay.lng], { icon: makeIcon(barangay.type) })
            .addTo(map)
            .bindPopup(popup);

        barangayMarkers.push({ marker, data: barangay });
    });

    coverageData.forEach(function (area) {
        const popup = `
            <div class="cleanflow-map-popup">
                <strong class="cleanflow-map-popup__title">${escapeHtml(area.name)}</strong>
                <span class="cleanflow-map-popup__type" style="--popup-accent:${colors.municipality};">Bukidnon provider coverage</span>
                <ul class="cleanflow-map-popup__services"><li>Approved providers may serve this city/municipality</li></ul>
            </div>`;
        const marker = L.marker([area.lat, area.lng], { icon: makeIcon('municipality') })
            .addTo(map)
            .bindPopup(popup);

        coverageMarkers.push({ marker, data: area });
    });

    providerCoverageData.forEach(function (area) {
        const providerList = (area.providers || [])
            .map(function (provider) {
                const services = provider.services?.length
                    ? ` <span>(${provider.services.map(escapeHtml).join(', ')})</span>`
                    : '';

                return `<li><strong>${escapeHtml(provider.name)}</strong>${services}<br><small>${escapeHtml(provider.availability)}</small></li>`;
            })
            .join('');
        const availableCount = Number(area.available_provider_count || 0);
        const totalCount = Number(area.provider_count || 0);
        const popup = `
            <div class="cleanflow-map-popup">
                <strong class="cleanflow-map-popup__title">Provider coverage: ${escapeHtml(area.name)}</strong>
                <span class="cleanflow-map-popup__type" style="--popup-accent:#7c3aed;">${totalCount} approved provider${totalCount === 1 ? '' : 's'} · ${availableCount} available</span>
                <p class="mt-2 text-xs text-slate-500">This marker shows the service area center. Provider exact base locations are private.</p>
                <ul class="cleanflow-map-popup__services">${providerList}</ul>
            </div>`;
        const marker = L.marker([area.lat, area.lng], { icon: makeProviderIcon(), zIndexOffset: 500 })
            .addTo(map)
            .bindPopup(popup);

        coverageMarkers.push({ marker, data: area, providerMarker: true });
    });

    function focusMarker(marker, zoom = 13) {
        map.setView([marker.data.lat, marker.data.lng], zoom);
        marker.marker.openPopup();
    }

    document.querySelectorAll('#barangayList li').forEach(function (listItem) {
        listItem.addEventListener('click', function () {
            const name = listItem.dataset.name;
            const foundMarker = barangayMarkers.find(function (entry) {
                return entry.data.name === name;
            });

            if (foundMarker) {
                focusMarker(foundMarker, 15);
            }
        });
    });

    document.querySelectorAll('#coverageAreaList li').forEach(function (listItem) {
        listItem.addEventListener('click', function () {
            const name = listItem.dataset.name;
            const foundMarker = coverageMarkers.find(function (entry) {
                return entry.data.name === name && !entry.providerMarker;
            });

            if (foundMarker) {
                focusMarker(foundMarker, 11);
            }
        });
    });

    const searchInput = document.getElementById('barangaySearch');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = searchInput.value.toLowerCase();
            const barangayItems = document.querySelectorAll('#barangayList li');
            const coverageItems = document.querySelectorAll('#coverageAreaList li');

            barangayItems.forEach(function (listItem) {
                const name = listItem.dataset.name.toLowerCase();
                listItem.classList.toggle('hidden', query.length > 0 && !name.includes(query));
            });
            coverageItems.forEach(function (listItem) {
                const name = listItem.dataset.name.toLowerCase();
                listItem.classList.toggle('hidden', query.length > 0 && !name.includes(query));
            });

            if (query.length > 1) {
                const match = barangayMarkers.find((entry) => entry.data.name.toLowerCase().includes(query))
                    || coverageMarkers.find((entry) => !entry.providerMarker && entry.data.name.toLowerCase().includes(query));

                if (match) {
                    map.setView([match.data.lat, match.data.lng], match.providerMarker ? 11 : 13);
                }
            } else if (query.length === 0) {
                map.setView([mapCenter.lat, mapCenter.lng], mapZoom);
            }
        });
    }

    let activeFilter = 'all';
    document.querySelectorAll('.filter-btn').forEach(function (button) {
        button.addEventListener('click', function () {
            document.querySelectorAll('.filter-btn').forEach(function (filterButton) {
                filterButton.classList.remove('active');
            });

            button.classList.add('active');
            activeFilter = button.dataset.filter;

            barangayMarkers.forEach(function ({ marker, data }) {
                if (activeFilter === 'all' || data.type === activeFilter || (activeFilter === 'residential' && data.type === 'service_center')) {
                    marker.addTo(map);
                } else {
                    map.removeLayer(marker);
                }
            });

            document.querySelectorAll('#barangayList li').forEach(function (listItem) {
                const type = listItem.dataset.type;

                if (activeFilter === 'all' || type === activeFilter || (activeFilter === 'residential' && type === 'service_center')) {
                    listItem.classList.remove('hidden');
                } else {
                    listItem.classList.add('hidden');
                }
            });
        });
    });
});
