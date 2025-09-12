<div class="provider-map-container">
    @if($latitude && $longitude)
        <div class="mb-2">
            <span class="text-sm text-gray-600">📍 {{ $address ?? 'Provider Location' }}</span>
        </div>
        <div id="{{ $mapId }}" class="w-full border border-gray-300 rounded-lg bg-gray-100" style="height: 400px; min-height: 400px;"></div>
    @else
        <div class="text-center py-8 text-gray-500">
            <div class="text-4xl mb-2">🗺️</div>
            <p>{{ $address ?? 'No location data available' }}</p>
            <p class="text-sm">Address geocoding required</p>
        </div>
    @endif
</div>

@if($latitude && $longitude)
    @push('styles')
    <style>
        #{{ $mapId }} {
            height: 400px !important;
            min-height: 400px !important;
            width: 100% !important;
            z-index: 1;
        }
        .leaflet-container {
            height: 100% !important;
            width: 100% !important;
        }
    </style>
    @endpush

    @push('scripts')
    <script>
        // Add a small delay to ensure DOM is ready
        setTimeout(function() {
            const mapElement = document.getElementById('{{ $mapId }}');

            console.log('Map element:', mapElement);
            console.log('Element dimensions:', mapElement ? mapElement.offsetWidth + 'x' + mapElement.offsetHeight : 'not found');

            if (mapElement) {
                // Load Leaflet CSS
                if (!document.querySelector('link[href*="leaflet"]')) {
                    const link = document.createElement('link');
                    link.rel = 'stylesheet';
                    link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                    document.head.appendChild(link);
                }

                // Load Leaflet JS
                if (typeof L === 'undefined') {
                    const script = document.createElement('script');
                    script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                    script.onload = function() {
                        setTimeout(initMap, 100); // Small delay after script loads
                    };
                    document.head.appendChild(script);
                } else {
                    initMap();
                }

                function initMap() {
                    try {
                        console.log('Initializing map for {{ $mapId }}');
                        const map = L.map('{{ $mapId }}', {
                            preferCanvas: false
                        }).setView([{{ $latitude }}, {{ $longitude }}], 15);

                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '© OpenStreetMap contributors'
                        }).addTo(map);

                        const marker = L.marker([{{ $latitude }}, {{ $longitude }}]).addTo(map);

                        @if($address)
                            marker.bindPopup('{{ addslashes($address) }}').openPopup();
                        @endif

                        // Force map to resize after a short delay
                        setTimeout(function() {
                            map.invalidateSize();
                            console.log('Map invalidated and resized');
                        }, 200);

                        console.log('Map initialized successfully for {{ $mapId }}');
                    } catch (error) {
                        console.error('Map initialization failed:', error);
                        mapElement.innerHTML = '<div class="flex items-center justify-center h-full text-red-500">Map failed to load: ' + error.message + '</div>';
                    }
                }
            } else {
                console.error('Map element not found: {{ $mapId }}');
            }
        }, 500); // 500ms delay to ensure everything is ready
    </script>
    @endpush
@endif
