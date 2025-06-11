<?php
session_start();
require_once '../includes/db.php';

// Fetch evacuation centers
$evacuation_centers = [];
$sql = "SELECT ec.*, 
        GROUP_CONCAT(CONCAT(r.type, ':', r.quantity) SEPARATOR '|') as resources
        FROM evacuation_centers ec
        LEFT JOIN resources r ON ec.center_id = r.center_id
        WHERE ec.status = 'open'
        GROUP BY ec.center_id";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $evacuation_centers[] = $row;
    }
}

// Encode data for JavaScript
$centersJson = json_encode($evacuation_centers);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Incident - PROTEQ</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
     integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
     crossorigin=""/>
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/g_user.css">
    <style>
        .center-marker {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: 2px solid white;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        .center-marker.open { background-color: #28a745; }
        .center-marker.full { background-color: #dc3545; }
        .center-marker.closed { background-color: #6c757d; }
        .user-location-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1000;
            background: white;
            border: none;
            border-radius: 4px;
            padding: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            cursor: pointer;
        }
        .user-location-btn:hover {
            background: #f8f9fa;
        }
        .distance-badge {
            background-color: #6c757d;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8em;
        }
        .center-card {
            transition: all 0.3s ease;
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 10px;
        }
        .center-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .center-list {
            max-height: calc(100vh - 300px);
            overflow-y: auto;
            padding: 10px;
        }
        .center-list::-webkit-scrollbar {
            width: 8px;
        }
        .center-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        .center-list::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        .center-list::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        .map-container {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .resource-badge {
            font-size: 0.8em;
            padding: 4px 8px;
            margin: 2px;
            border-radius: 12px;
            background-color: #e9ecef;
            color: #495057;
        }
        .directions-btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            margin-top: 5px;
        }
        .directions-btn:hover {
            background-color: #0056b3;
            color: white;
        }
        
        @media (max-width: 991.98px) {
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'components/_sidebar.php'; ?>

    <main class="main-content">
        <?php include 'components/topbar.php'; ?>

        <div class="container-fluid p-4">
            <div class="row">
                <!-- Map Container -->
                <div class="col-md-8 mb-4">
                    <div class="card">
                        <div class="card-body p-0">
                            <div class="map-container">
                                <div id="map" style="height: calc(100vh - 200px);"></div>
                                <button class="user-location-btn" id="locateUser" title="Find Nearest Centers">
                                    <i class="bi bi-geo-alt"></i> Find Nearest
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Centers List -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-building"></i> Nearest Evacuation Centers</h5>
                        </div>
                        <div class="center-list" id="centersList">
                            <!-- Centers will be populated here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  
    <script src="../assets/js/user-menu.js"></script>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const centers = <?php echo $centersJson; ?>;
        const defaultCenter = [12.8797, 121.7740]; // Philippines center
        const defaultZoom = 6;
        let map;
        let markers = {};
        let userMarker;
        let userLatLng;
        let centerList = document.getElementById('centersList');

        // Calculate distance between two points using Haversine formula
        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371; // Earth's radius in km
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = 
                Math.sin(dLat/2) * Math.sin(dLat/2) +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * 
                Math.sin(dLon/2) * Math.sin(dLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return R * c;
        }

        // Sort centers by distance
        function sortCentersByDistance() {
            if (!userLatLng) return centers;
            
            return [...centers].sort((a, b) => {
                const distA = calculateDistance(userLatLng.lat, userLatLng.lng, a.latitude, a.longitude);
                const distB = calculateDistance(userLatLng.lat, userLatLng.lng, b.latitude, b.longitude);
                return distA - distB;
            });
        }

        // Create custom marker
        function createMarkerIcon(center) {
            const status = center.status.toLowerCase();
            const iconHtml = `
                <div class="center-marker ${status}">
                    <i class="bi bi-building"></i>
                </div>
            `;
            return L.divIcon({
                html: iconHtml,
                className: 'custom-marker',
                iconSize: [30, 30]
            });
        }

        // Add marker to map
        function addMarker(center) {
            const marker = L.marker([center.latitude, center.longitude], {
                icon: createMarkerIcon(center)
            }).addTo(map);

            marker.bindPopup(createPopupContent(center));
            markers[center.center_id] = marker;
        }

        // Create popup content
        function createPopupContent(center) {
            const resources = center.resources ? center.resources.split('|').map(r => {
                const [type, quantity] = r.split(':');
                return `<span class="resource-badge">${type}: ${quantity}</span>`;
            }).join(' ') : 'No resources listed';

            let distanceHtml = '';
            if (userLatLng) {
                const distance = calculateDistance(userLatLng.lat, userLatLng.lng, center.latitude, center.longitude);
                distanceHtml = `<p class="mb-1">Distance: ${distance.toFixed(1)} km</p>`;
            }

            return `
                <div class="text-center">
                    <h6>${center.name}</h6>
                    ${distanceHtml}
                    <p class="mb-1">Status: <span class="badge bg-${getStatusColor(center.status)}">${center.status}</span></p>
                    <p class="mb-1">Capacity: ${center.current_occupancy}/${center.capacity}</p>
                    <p class="mb-1">Contact: ${center.contact_person}<br>${center.contact_number}</p>
                    <hr>
                    <p class="mb-2"><strong>Resources:</strong><br>${resources}</p>
                    <a href="https://www.google.com/maps/dir/?api=1&destination=${center.latitude},${center.longitude}" 
                       class="directions-btn" target="_blank">
                       <i class="bi bi-arrow-right-circle"></i> Get Directions
                    </a>
                </div>
            `;
        }

        // Add center to list
        function addToList(center) {
            const item = document.createElement('div');
            item.className = 'card center-card';
            
            let distanceHtml = '';
            if (userLatLng) {
                const distance = calculateDistance(userLatLng.lat, userLatLng.lng, center.latitude, center.longitude);
                distanceHtml = `
                    <span class="distance-badge">
                        <i class="bi bi-arrow-right-circle"></i> ${distance.toFixed(1)} km
                    </span>
                `;
            }

            item.innerHTML = `
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <h6 class="card-title mb-1">${center.name}</h6>
                        <div class="d-flex gap-2">
                            ${distanceHtml}
                            <span class="badge bg-${getStatusColor(center.status)}">${center.status}</span>
                        </div>
                    </div>
                    <p class="card-text mb-1">
                        <i class="bi bi-people"></i> Capacity: ${center.current_occupancy}/${center.capacity}
                    </p>
                    <p class="card-text mb-1">
                        <i class="bi bi-telephone"></i> ${center.contact_number}
                    </p>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <button class="btn btn-sm btn-primary" onclick="centerMap(${center.latitude}, ${center.longitude})">
                            <i class="bi bi-map"></i> View
                        </button>
                        <a href="https://www.google.com/maps/dir/?api=1&destination=${center.latitude},${center.longitude}" 
                           class="btn btn-sm btn-outline-primary" target="_blank">
                           <i class="bi bi-arrow-right-circle"></i> Directions
                        </a>
                    </div>
                </div>
            `;

            centerList.appendChild(item);
        }

        // Center map on specific location
        function centerMap(lat, lng) {
            map.setView([lat, lng], 13);
            markers[Object.keys(markers).find(key => 
                markers[key].getLatLng().lat === lat && 
                markers[key].getLatLng().lng === lng
            )].openPopup();
        }

        // Get status color
        function getStatusColor(status) {
            switch(status.toLowerCase()) {
                case 'open': return 'success';
                case 'full': return 'danger';
                case 'closed': return 'secondary';
                default: return 'primary';
            }
        }

        // Update center list with distances
        function updateCenterList() {
            centerList.innerHTML = '';
            const sortedCenters = sortCentersByDistance();
            sortedCenters.forEach(center => {
                addToList(center);
            });
        }

        // Locate user and find nearest centers
        function locateUser() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(position => {
                    const { latitude, longitude } = position.coords;
                    userLatLng = { lat: latitude, lng: longitude };
                    
                    // Remove existing user marker if any
                    if (userMarker) {
                        map.removeLayer(userMarker);
                    }

                    // Add new user marker
                    userMarker = L.marker([latitude, longitude], {
                        icon: L.divIcon({
                            html: '<div style="background-color: #007bff; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.2);"></div>',
                            className: 'user-location-marker',
                            iconSize: [20, 20]
                        })
                    }).addTo(map);

                    userMarker.bindPopup("📍 You are here").openPopup();
                    
                    // Update popups with distances
                    centers.forEach(center => {
                        markers[center.center_id].setPopupContent(createPopupContent(center));
                    });

                    // Update list with sorted centers
                    updateCenterList();

                    // Center map on user location
                    map.setView([latitude, longitude], 13);
                }, error => {
                    alert('Unable to get your location. Please check your location settings.');
                });
            } else {
                alert('Geolocation is not supported by your browser.');
            }
        }

        // Initialize map
        function initMap() {
            map = L.map('map').setView(defaultCenter, defaultZoom);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            // Add markers for each center
            centers.forEach(center => {
                addMarker(center);
                addToList(center);
            });

            // Add event listener for locate button
            document.getElementById('locateUser').addEventListener('click', locateUser);
        }

        // Initialize map when page loads
        window.addEventListener('load', initMap);
    </script>

</body>
</html>