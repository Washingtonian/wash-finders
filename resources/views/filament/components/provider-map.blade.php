@php
    $data = $getState();
    $latitude = $data['latitude'] ?? null;
    $longitude = $data['longitude'] ?? null;
    $address = $data['address'] ?? null;

    // Debug output
    $debugInfo = "Lat: " . ($latitude ?? 'null') . ", Lng: " . ($longitude ?? 'null') . ", Addr: " . ($address ?? 'null');
@endphp

<div class="provider-map-container">
    <!-- Debug info -->
    <div class="mb-2 p-2 bg-yellow-100 text-xs">
        Debug: {{ $debugInfo }}
    </div>

    @if($latitude && $longitude)
        <div class="mb-2">
            <span class="text-sm text-gray-600">📍 {{ $address ?? 'Provider Location' }}</span>
        </div>
        <div id="provider-map-{{ $latitude }}-{{ $longitude }}" class="w-full h-64 border border-gray-300 rounded-lg bg-gray-100"></div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const mapId = 'provider-map-{{ $latitude }}-{{ $longitude }}';
                const mapElement = document.getElementById(mapId);

                console.log('Map element found:', mapElement);
                console.log('Coordinates:', {{ $latitude }}, {{ $longitude }});

                if (mapElement) {
                    // Load Leaflet if not already loaded
                    if (typeof L === 'undefined') {
                        const link = document.createElement('link');
                        link.rel = 'stylesheet';
                        link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                        document.head.appendChild(link);

                        const script = document.createElement('script');
                        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                        script.onload = function() {
                            initMap();
                        };
                        document.head.appendChild(script);
                    } else {
                        initMap();
                    }

                    function initMap() {
                        const map = L.map(mapId).setView([{{ $latitude }}, {{ $longitude }}], 15);

                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '© OpenStreetMap contributors'
                        }).addTo(map);

                        const marker = L.marker([{{ $latitude }}, {{ $longitude }}]).addTo(map);

                        @if($address)
                            marker.bindPopup('{{ addslashes($address) }}').openPopup();
                        @endif

                        console.log('Map initialized successfully');
                    }
                }
            });
        </script>
    @else
        <div class="text-center py-8 text-gray-500">
            <div class="text-4xl mb-2">🗺️</div>
            <p>No location data available</p>
            <p class="text-sm">Address geocoding required</p>
            <p class="text-xs mt-2">Debug: {{ $debugInfo }}</p>
        </div>
    @endif
</div>
