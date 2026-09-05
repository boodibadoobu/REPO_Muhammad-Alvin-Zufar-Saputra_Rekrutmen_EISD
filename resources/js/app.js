import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const tileLayerUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
const indonesiaCenter = [-2.5, 118];
const pinIcon = L.divIcon({
    className: 'location-pin-wrapper',
    html: '<span class="location-pin" aria-hidden="true"></span>',
    iconAnchor: [18, 38],
    iconSize: [36, 40],
});

function addTiles(map) {
    L.tileLayer(tileLayerUrl, {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>',
        maxZoom: 19,
    }).addTo(map);
}

function initializeLocationPickers() {
    document.querySelectorAll('[data-location-picker]').forEach((container) => {
        const mapElement = container.querySelector('[data-map-canvas]');
        const latitudeInput = container.querySelector('input[name="latitude"]');
        const longitudeInput = container.querySelector('input[name="longitude"]');
        const coordinateText = container.querySelector('[data-coordinate-text]');
        const locateButton = container.querySelector('[data-use-current-location]');

        if (!mapElement || !latitudeInput || !longitudeInput) {
            return;
        }

        const initialLatitude = Number.parseFloat(latitudeInput.value);
        const initialLongitude = Number.parseFloat(longitudeInput.value);
        const hasInitialPosition = Number.isFinite(initialLatitude) && Number.isFinite(initialLongitude);
        const map = L.map(mapElement, { scrollWheelZoom: false }).setView(
            hasInitialPosition ? [initialLatitude, initialLongitude] : indonesiaCenter,
            hasInitialPosition ? 17 : 5,
        );
        let marker = null;

        addTiles(map);

        const setPosition = (latitude, longitude, shouldCenter = true) => {
            const position = [latitude, longitude];

            if (marker) {
                marker.setLatLng(position);
            } else {
                marker = L.marker(position, { draggable: true, icon: pinIcon, title: 'Geser titik lokasi laporan', alt: 'Titik lokasi laporan' }).addTo(map);
                marker.on('dragend', () => {
                    const draggedPosition = marker.getLatLng();
                    setPosition(draggedPosition.lat, draggedPosition.lng, false);
                });
            }

            latitudeInput.value = latitude.toFixed(7);
            longitudeInput.value = longitude.toFixed(7);
            coordinateText.textContent = latitude.toFixed(6) + ', ' + longitude.toFixed(6);

            if (shouldCenter) {
                map.setView(position, Math.max(map.getZoom(), 17));
            }
        };

        if (hasInitialPosition) {
            setPosition(initialLatitude, initialLongitude, false);
        }

        map.on('click', (event) => setPosition(event.latlng.lat, event.latlng.lng));

        if (locateButton) {
            locateButton.addEventListener('click', () => {
                if (!navigator.geolocation) {
                    coordinateText.textContent = 'Perangkat ini tidak mendukung GPS. Pilih titik pada peta.';
                    return;
                }

                locateButton.disabled = true;
                coordinateText.textContent = 'Mencari lokasi perangkat...';

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        setPosition(position.coords.latitude, position.coords.longitude);
                        locateButton.disabled = false;
                    },
                    () => {
                        coordinateText.textContent = 'Lokasi tidak dapat diakses. Izinkan GPS atau pilih titik pada peta.';
                        locateButton.disabled = false;
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 30000 },
                );
            });
        }

        window.setTimeout(() => map.invalidateSize(), 100);
    });
}

function initializeLocationDisplays() {
    document.querySelectorAll('[data-location-display]').forEach((element) => {
        const latitude = Number.parseFloat(element.dataset.latitude);
        const longitude = Number.parseFloat(element.dataset.longitude);

        if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
            return;
        }

        const map = L.map(element, {
            dragging: true,
            scrollWheelZoom: false,
        }).setView([latitude, longitude], 17);

        addTiles(map);
        L.marker([latitude, longitude], { icon: pinIcon, title: 'Titik lokasi laporan', alt: 'Titik lokasi laporan' }).addTo(map);
        window.setTimeout(() => map.invalidateSize(), 100);
    });
}

function initializePublicMaps() {
    document.querySelectorAll('[data-public-map]').forEach((element) => {
        const dataElement = element.parentElement?.querySelector('[data-map-markers]');
        const reports = dataElement ? JSON.parse(dataElement.textContent || '[]') : [];
        const map = L.map(element, { scrollWheelZoom: false }).setView(indonesiaCenter, 5);

        addTiles(map);

        const markers = reports.map((report) => {
            const marker = L.marker([report.latitude, report.longitude], { icon: pinIcon, title: report.title, alt: report.title }).addTo(map);
            const popup = document.createElement('div');
            const title = document.createElement('strong');
            const meta = document.createElement('small');
            const link = document.createElement('a');

            title.textContent = report.title;
            meta.textContent = report.status;
            link.textContent = 'Lihat detail →';
            link.href = report.url;
            popup.className = 'map-popup';
            popup.append(title, meta, link);
            marker.bindPopup(popup);

            return marker;
        });

        if (markers.length > 0) {
            const bounds = L.featureGroup(markers).getBounds();
            map.fitBounds(bounds.pad(0.18), { maxZoom: 16 });
        }

        window.setTimeout(() => map.invalidateSize(), 100);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initializeLocationPickers();
    initializeLocationDisplays();
    initializePublicMaps();
});
