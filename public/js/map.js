document.addEventListener('DOMContentLoaded', function () {
    if (typeof L === 'undefined' || !document.getElementById('map')) {
        return;
    }

    const colors = {
        service_center: '#e53935',
        residential: '#1D9E75',
        commercial: '#fb8c00',
    };
    const barangayData = Array.isArray(window.barangayData) ? window.barangayData : [];
    const mapConfig = window.cleanflowMapConfig || {};
    const mapCenter = mapConfig.center || { lat: 7.9047, lng: 125.0940 };
    const mapZoom = mapConfig.zoom ?? 12;
    const minZoom = mapConfig.minZoom ?? 11;
    const maxZoom = mapConfig.maxZoom ?? 17;
    const maxBounds = mapConfig.maxBounds ?? [[7.75, 124.95], [8.05, 125.25]];

    function makeIcon(type) {
        const color = colors[type] || '#1D9E75';

        return L.divIcon({
            className: '',
            html: `<div style="width:18px;height:18px;border-radius:50%;background:${color};border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,0.4)"></div>`,
            iconSize: [18, 18],
            iconAnchor: [9, 9],
            popupAnchor: [0, -12],
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

    const markers = [];
    barangayData.forEach(function (barangay) {
        const serviceList = (barangay.services || []).map(function (service) {
            return `<li>&#10003; ${service}</li>`;
        }).join('');
        const typeName = (barangay.type || 'residential')
            .replace('_', ' ')
            .replace(/\b\w/g, function (char) {
                return char.toUpperCase();
            });
        const popup = `
            <div style="min-width:180px">
                <strong style="font-size:1rem">${barangay.name}</strong><br>
                <span style="color:${colors[barangay.type] || colors.residential};font-size:0.85rem;font-weight:600">${typeName}</span>
                <ul style="margin-top:8px;padding-left:0;list-style:none;font-size:0.85rem">${serviceList}</ul>
            </div>`;
        const marker = L.marker([barangay.lat, barangay.lng], { icon: makeIcon(barangay.type) })
            .addTo(map)
            .bindPopup(popup);

        markers.push({ marker, data: barangay });
    });

    document.querySelectorAll('#barangayList li').forEach(function (listItem) {
        listItem.addEventListener('click', function () {
            const lat = parseFloat(listItem.dataset.lat);
            const lng = parseFloat(listItem.dataset.lng);
            const name = listItem.dataset.name;

            map.setView([lat, lng], 16);

            const foundMarker = markers.find(function (entry) {
                return entry.data.name === name;
            });

            if (foundMarker) {
                foundMarker.marker.openPopup();
            }
        });
    });

    const searchInput = document.getElementById('barangaySearch');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = searchInput.value.toLowerCase();

            document.querySelectorAll('#barangayList li').forEach(function (listItem) {
                const name = listItem.dataset.name.toLowerCase();
                listItem.classList.toggle('hidden', query.length > 0 && !name.includes(query));
            });

            if (query.length > 1) {
                const match = markers.find(function (entry) {
                    return entry.data.name.toLowerCase().includes(query);
                });

                if (match) {
                    map.setView([match.data.lat, match.data.lng], 15);
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

            markers.forEach(function ({ marker, data }) {
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
