<?php
require_once '../../includes/db.php';
session_start();

// Check if user is not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../auth/admin-login.php");
    exit();
}
// Fetch evacuation centers
$evacuation_centers = [];
$sql = "SELECT ec.*, 
        GROUP_CONCAT(CONCAT(r.type, ':', r.quantity) SEPARATOR '|') as resources
        FROM evacuation_centers ec
        LEFT JOIN resources r ON ec.center_id = r.center_id
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
    <title>Resource & Evacuation Mapping - PROTEQ Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="../../assets/css/admin_css/admin-styles.css">
    <link rel="stylesheet" href="../../assets/css/notifications.css">
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
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .center-marker.open { background-color: #28a745; }
        .center-marker.full { background-color: #dc3545; }
        .center-marker.closed { background-color: #6c757d; }
        .filter-section {
            background: white;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 15px;
        }
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
            transition: all 0.3s ease;
        }
        .user-location-btn:hover {
            background: #f8f9fa;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .distance-badge {
            background-color: #6c757d;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .list-group-item {
            transition: all 0.3s ease;
        }
        .list-group-item:hover {
            transform: translateX(5px);
            background-color: #f8f9fa;
        }
        .badge {
            font-weight: 500;
            padding: 5px 10px;
        }
    </style>
</head>
<body>
    <div class="d-flex" id="wrapper">
        <?php include '../components/_sidebar.php'; ?>

        <div id="page-content-wrapper">
            <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-primary btn-sm" id="sidebarToggle"><i class="bi bi-list"></i></button>
                    <h4 class="ms-3 mb-0">
                        <i class=" text-primary me-2"></i>
                        Resource & Evacuation Mapping
                    </h4>
                    <div class="ms-auto">
                        
                    </div>
                </div>
            </nav>

            <div class="container-fluid p-4">
                <div class="card mb-3 shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                        <h5 class="mb-0">
                            <i class="bi bi-building text-primary me-2"></i>
                            Evacuation Centers
                        </h5>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCenterModal">
                            <i class="bi bi-plus-circle me-1"></i> Add Center
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Filters Section -->
                            <div class="col-12 mb-4">
                                <div class="card border-0 bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title mb-3">
                                            <i class="bi bi-funnel me-2"></i>
                                            Filter Centers
                                        </h6>
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <label class="form-label">Status</label>
                                                <select class="form-select" id="statusFilter" data-bs-toggle="tooltip" title="Filter by center status">
                                                    <option value="all">All Status</option>
                                                    <option value="open">Open</option>
                                                    <option value="full">Full</option>
                                                    <option value="closed">Closed</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Resource Type</label>
                                                <select class="form-select" id="resourceFilter" data-bs-toggle="tooltip" title="Filter by available resources">
                                                    <option value="all">All Resources</option>
                                                    <option value="food">Food</option>
                                                    <option value="water">Water</option>
                                                    <option value="medical">Medical</option>
                                                    <option value="shelter">Shelter</option>
                                                    <option value="clothing">Clothing</option>
                                                    <option value="blankets">Blankets</option>
                                                    <option value="hygiene">Hygiene Kits</option>
                                                    <option value="first_aid">First Aid Kits</option>
                                                    <option value="flashlights">Flashlights</option>
                                                    <option value="batteries">Batteries</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Capacity</label>
                                                <select class="form-select" id="capacityFilter" data-bs-toggle="tooltip" title="Filter by center capacity">
                                                    <option value="all">All Capacities</option>
                                                    <option value="low">Low (0-50)</option>
                                                    <option value="medium">Medium (51-200)</option>
                                                    <option value="high">High (201+)</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Search</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">
                                                        <i class="bi bi-search"></i>
                                                    </span>
                                                    <input type="text" class="form-control" id="searchFilter" placeholder="Search centers..." data-bs-toggle="tooltip" title="Search in all fields">
                                                    <button class="btn btn-outline-secondary" type="button" id="clearFilters" data-bs-toggle="tooltip" title="Clear all filters">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Map Container -->
                            <div class="col-md-8">
                                <div id="map" style="height: calc(100vh - 300px); border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                    <button class="user-location-btn" id="locateUser" title="Find Nearest Centers">
                                        <i class="bi bi-geo-alt"></i> Find Nearest
                                    </button>
                                </div>
                            </div>

                            <!-- Centers List -->
                            <div class="col-md-4">
                                <div class="list-group list-group-flush" id="centersList" style="max-height: calc(100vh - 300px); overflow-y: auto;">
                                    <!-- Centers will be populated here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Center Details Modal -->
    <div class="modal fade" id="centerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Center Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="centerDetails">
                        <!-- Center details will be populated here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Center Modal -->
    <div class="modal fade" id="addCenterModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title">
                        <i class="bi bi-building-add text-primary me-2"></i>
                        Add New Evacuation Center
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addCenterForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">
                                    <i class="bi bi-building me-1"></i>
                                    Center Name
                                </label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">
                                    <i class="bi bi-toggle-on me-1"></i>
                                    Status
                                </label>
                                <select class="form-select" name="status" required>
                                    <option value="open">Open</option>
                                    <option value="full">Full</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">
                                    <i class="bi bi-geo-alt me-1"></i>
                                    Location
                                </label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="locationSearch" placeholder="Search for a location...">
                                    <button class="btn btn-outline-secondary" type="button" id="searchLocation">
                                        <i class="bi bi-search"></i> Search
                                    </button>
                                </div>
                                <small class="text-muted">Or click on the map below to select location</small>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div id="locationMap" style="height: 200px; border-radius: 5px;"></div>
                                <input type="hidden" name="latitude" id="selectedLat" required>
                                <input type="hidden" name="longitude" id="selectedLng" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">
                                    <i class="bi bi-people me-1"></i>
                                    Capacity
                                </label>
                                <input type="number" class="form-control" name="capacity" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">
                                    <i class="bi bi-person-check me-1"></i>
                                    Current Occupancy
                                </label>
                                <input type="number" class="form-control" name="current_occupancy" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">
                                    <i class="bi bi-person me-1"></i>
                                    Contact Person
                                </label>
                                <input type="text" class="form-control" name="contact_person" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">
                                    <i class="bi bi-telephone me-1"></i>
                                    Contact Number
                                </label>
                                <input type="text" class="form-control" name="contact_number" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">
                                    <i class="bi bi-box-seam me-1"></i>
                                    Resources
                                </label>
                                <div id="resourcesContainer">
                                    <div class="row mb-2 resource-row">
                                        <div class="col-md-5">
                                            <select class="form-select" name="resource_type[]" required>
                                                <option value="">Select Resource Type</option>
                                                <option value="food">Food</option>
                                                <option value="water">Water</option>
                                                <option value="medical">Medical</option>
                                                <option value="shelter">Shelter</option>
                                                <option value="clothing">Clothing</option>
                                                <option value="blankets">Blankets</option>
                                                <option value="hygiene">Hygiene Kits</option>
                                                <option value="first_aid">First Aid Kits</option>
                                                <option value="flashlights">Flashlights</option>
                                                <option value="batteries">Batteries</option>
                                            </select>
                                        </div>
                                        <div class="col-md-5">
                                            <input type="number" class="form-control" name="resource_quantity[]" placeholder="Quantity" required>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-danger remove-resource" style="display: none;">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-secondary btn-sm mt-2" id="addResource">
                                    <i class="bi bi-plus-circle"></i> Add Resource
                                </button>
                            </div>
                        </div>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <strong>Warning:</strong> Adding a new evacuation center will:
                            <ul class="mb-0 mt-2">
                                <li>Make it visible on the emergency map</li>
                                <li>Allow resource allocation and tracking</li>
                                <li>Enable real-time status updates</li>
                            </ul>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-primary" id="saveCenter">
                                <i class="bi bi-save me-1"></i> Save Center
                            </button>
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                <i class="bi bi-x-circle me-1"></i> Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="../../assets/js/admin-script.js"></script>

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
                return `${type}: ${quantity}`;
            }).join('<br>') : 'No resources listed';

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
                    <p class="mb-0"><strong>Resources:</strong><br>${resources}</p>
                    <a href="https://www.google.com/maps/dir/?api=1&destination=${center.latitude},${center.longitude}" 
                       class="btn btn-sm btn-primary mt-2" target="_blank">
                       <i class="bi bi-arrow-right-circle"></i> Get Directions
                    </a>
                </div>
            `;
        }

        // Add center to list
        function addToList(center) {
            const item = document.createElement('a');
            item.href = '#';
            item.className = 'list-group-item list-group-item-action';

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
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1">${center.name}</h6>
                    <div class="d-flex gap-2">
                        ${distanceHtml}
                        <span class="badge bg-${getStatusColor(center.status)}">${center.status}</span>
                    </div>
                </div>
                <p class="mb-1">Capacity: ${center.current_occupancy}/${center.capacity}</p>
                <small>Last updated: ${new Date(center.last_updated).toLocaleString()}</small>
            `;

            item.addEventListener('click', (e) => {
                e.preventDefault();
                map.setView([center.latitude, center.longitude], 13);
                markers[center.center_id].openPopup();
            });

            centerList.appendChild(item);
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

        // Filter functions
        function filterCenters() {
            const status = document.getElementById('statusFilter').value;
            const resource = document.getElementById('resourceFilter').value;
            const capacity = document.getElementById('capacityFilter').value;
            const search = document.getElementById('searchFilter').value.toLowerCase();

            centers.forEach(center => {
                const marker = markers[center.center_id];
                const listItem = centerList.children[center.center_id - 1];

                let show = true;

                // Status filter
                if (status !== 'all' && center.status.toLowerCase() !== status) {
                    show = false;
                }

                // Resource filter
                if (resource !== 'all' && (!center.resources || !center.resources.includes(resource))) {
                    show = false;
                }

                // Capacity filter
                if (capacity !== 'all') {
                    const occupancy = (center.current_occupancy / center.capacity) * 100;
                    if (capacity === 'low' && occupancy > 50) show = false;
                    if (capacity === 'medium' && (occupancy <= 50 || occupancy > 200)) show = false;
                    if (capacity === 'high' && occupancy <= 200) show = false;
                }

                // Search filter
                if (search && !center.name.toLowerCase().includes(search)) {
                    show = false;
                }

                // Update visibility
                if (marker) marker.setOpacity(show ? 1 : 0);
                if (listItem) listItem.style.display = show ? 'block' : 'none';
            });
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

            // Add event listeners
            document.getElementById('locateUser').addEventListener('click', locateUser);
            document.getElementById('statusFilter').addEventListener('change', filterCenters);
            document.getElementById('resourceFilter').addEventListener('change', filterCenters);
            document.getElementById('capacityFilter').addEventListener('change', filterCenters);
            document.getElementById('searchFilter').addEventListener('input', filterCenters);

            // Initialize location picker map
            const locationMap = L.map('locationMap').setView(defaultCenter, 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(locationMap);

            let locationMarker;
            let searchTimeout = null;
            let searchCache = new Map();
            let isSearching = false;

            // Debounce function
            function debounce(func, wait) {
                return function executedFunction(...args) {
                    const later = () => {
                        clearTimeout(searchTimeout);
                        func(...args);
                    };
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(later, wait);
                };
            }

            // Function to show notification
            function showNotification(message, type = 'error') {
                const notification = $('<div>')
                    .addClass('notification-snap-alert ' + type)
                    .html('<i class="bi bi-' + (type === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill') + '"></i> ' + message)
                    .appendTo('body');

                setTimeout(() => {
                    notification.css('animation', 'fadeOut 0.3s ease-out forwards');
                    setTimeout(() => {
                        notification.remove();
                    }, 300);
                }, 3000);
            }

            // Function to update location marker
            function updateLocationMarker(latlng) {
                if (locationMarker) {
                    locationMarker.setLatLng(latlng);
                } else {
                    locationMarker = L.marker(latlng).addTo(locationMap);
                }
                locationMap.setView(latlng, 13);
            }

            // Function to update location inputs
            function updateLocationInputs(latlng) {
                document.getElementById('selectedLat').value = latlng.lat;
                document.getElementById('selectedLng').value = latlng.lng;
            }

            // Optimized search function
            const performSearch = debounce(function(searchText) {
                if (isSearching) return;
                
                // Check cache first
                if (searchCache.has(searchText)) {
                    const cachedResult = searchCache.get(searchText);
                    updateLocationMarker([cachedResult.lat, cachedResult.lon]);
                    updateLocationInputs({ lat: cachedResult.lat, lng: cachedResult.lon });
                    document.getElementById('locationSearch').value = cachedResult.display_name;
                    return;
                }

                isSearching = true;
                const searchButton = document.getElementById('searchLocation');
                const originalText = searchButton.innerHTML;
                searchButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Searching...';
                searchButton.disabled = true;

                // Set timeout for search
                const searchTimeout = setTimeout(() => {
                    isSearching = false;
                    searchButton.innerHTML = originalText;
                    searchButton.disabled = false;
                    showNotification('Search timed out. Please try again.');
                }, 10000); // 10 second timeout

                try {
                    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchText)}&countrycodes=ph&limit=3&addressdetails=1&accept-language=en`)
                        .then(response => response.json())
                        .then(data => {
                            clearTimeout(searchTimeout);
                            isSearching = false;
                            searchButton.innerHTML = originalText;
                            searchButton.disabled = false;

                            if (data && data.length > 0) {
                                const { lat, lon, display_name } = data[0];
                                updateLocationMarker([lat, lon]);
                                updateLocationInputs({ lat: parseFloat(lat), lng: parseFloat(lon) });
                                document.getElementById('locationSearch').value = display_name;

                                // Cache the result
                                searchCache.set(searchText, {
                                    lat: parseFloat(lat),
                                    lon: parseFloat(lon),
                                    display_name: display_name
                                });
                            } else {
                                showNotification('Location not found. Please try a different search term.');
                            }
                        })
                        .catch(error => {
                            console.error('Search error:', error);
                            clearTimeout(searchTimeout);
                            isSearching = false;
                            searchButton.innerHTML = originalText;
                            searchButton.disabled = false;
                            showNotification('Error searching location. Please try again.');
                        });
                } catch (error) {
                    console.error('Search error:', error);
                    clearTimeout(searchTimeout);
                    isSearching = false;
                    searchButton.innerHTML = originalText;
                    searchButton.disabled = false;
                    showNotification('Error searching location. Please try again.');
                }
            }, 500); // 500ms debounce

            // Handle map clicks
            locationMap.on('click', function(e) {
                updateLocationMarker(e.latlng);
                updateLocationInputs(e.latlng);
            });

            // Handle search button click
            document.getElementById('searchLocation').addEventListener('click', function() {
                const searchText = document.getElementById('locationSearch').value.trim();
                if (!searchText) {
                    showNotification('Please enter a location to search.');
                    return;
                }
                performSearch(searchText);
            });

            // Handle enter key in search input
            document.getElementById('locationSearch').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    document.getElementById('searchLocation').click();
                }
            });

            // Clear cache when modal is closed
            document.getElementById('addCenterModal').addEventListener('hidden.bs.modal', function() {
                searchCache.clear();
            });
        }

        // Initialize map when page loads
        window.addEventListener('load', initMap);

        // Add Resource Row
        document.getElementById('addResource').addEventListener('click', function() {
            const container = document.getElementById('resourcesContainer');
            const newRow = container.querySelector('.resource-row').cloneNode(true);
            newRow.querySelectorAll('input').forEach(input => input.value = '');
            newRow.querySelector('.remove-resource').style.display = 'block';
            container.appendChild(newRow);
        });

        // Remove Resource Row
        document.getElementById('resourcesContainer').addEventListener('click', function(e) {
            if (e.target.closest('.remove-resource')) {
                const row = e.target.closest('.resource-row');
                if (document.querySelectorAll('.resource-row').length > 1) {
                    row.remove();
                }
            }
        });

        // Save Center
        document.getElementById('saveCenter').addEventListener('click', function() {
            const form = document.getElementById('addCenterForm');
            const formData = new FormData(form);

            // Validate form
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            // Convert form data to JSON
            const centerData = {
                name: formData.get('name'),
                status: formData.get('status'),
                latitude: parseFloat(formData.get('latitude')),
                longitude: parseFloat(formData.get('longitude')),
                capacity: parseInt(formData.get('capacity')),
                current_occupancy: parseInt(formData.get('current_occupancy')),
                contact_person: formData.get('contact_person'),
                contact_number: formData.get('contact_number'),
                resources: []
            };

            // Get resources
            const resourceTypes = formData.getAll('resource_type[]');
            const resourceQuantities = formData.getAll('resource_quantity[]');

            for (let i = 0; i < resourceTypes.length; i++) {
                if (resourceTypes[i] && resourceQuantities[i]) {
                    centerData.resources.push({
                        type: resourceTypes[i],
                        quantity: parseInt(resourceQuantities[i])
                    });
                }
            }

            // Debug log to check resources
            console.log('Resources being sent:', centerData.resources);

            // Send data to server
            fetch('add_center.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(centerData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Add new center to map and list
                    const newCenter = data.center;
                    addMarker(newCenter);
                    addToList(newCenter);
                    markers[newCenter.center_id] = markers[newCenter.center_id];

                    // Close modal and reset form
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addCenterModal'));
                    modal.hide();
                    form.reset();

                    // Show success message
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'notification-snap-alert success';
                    alertDiv.innerHTML = `<i class="bi bi-check-circle me-2"></i>${data.message}`;
                    document.body.appendChild(alertDiv);

                    // Remove alert after animation
                    setTimeout(() => {
                        alertDiv.style.animation = 'fadeOut 0.3s ease-out forwards';
                        setTimeout(() => {
                            alertDiv.remove();
                        }, 300);
                    }, 3000);
                } else {
                    // Show error message
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'notification-snap-alert error';
                    alertDiv.innerHTML = `<i class="bi bi-exclamation-triangle me-2"></i>${data.message}`;
                    document.body.appendChild(alertDiv);

                    // Remove alert after animation
                    setTimeout(() => {
                        alertDiv.style.animation = 'fadeOut 0.3s ease-out forwards';
                        setTimeout(() => {
                            alertDiv.remove();
                        }, 300);
                    }, 3000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Show error message
                const alertDiv = document.createElement('div');
                alertDiv.className = 'notification-snap-alert error';
                alertDiv.innerHTML = `<i class="bi bi-exclamation-triangle me-2"></i>Error adding center. Please try again.`;
                document.body.appendChild(alertDiv);

                // Remove alert after animation
                setTimeout(() => {
                    alertDiv.style.animation = 'fadeOut 0.3s ease-out forwards';
                    setTimeout(() => {
                        alertDiv.remove();
                    }, 300);
                }, 3000);
            });
        });

        // Emergency Modal Functionality
        document.getElementById('description').addEventListener('input', function() {
            const charCount = this.value.length;
            document.getElementById('charCount').textContent = charCount;
            
            // Add visual feedback for character limit
            if (charCount > 500) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });

        function confirmEmergencyCreation(event) {
            event.preventDefault();
            
            const form = document.getElementById('newEmergencyForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return false;
            }

            const emergencyType = document.getElementById('emergency_type').value;
            const description = document.getElementById('description').value;

            // Show confirmation dialog
            if (confirm(`Are you sure you want to trigger a ${emergencyType} emergency?\n\nThis action cannot be undone.`)) {
                // Here you would typically send the data to your server
                // For now, we'll just show a success message
                const alertDiv = document.createElement('div');
                alertDiv.className = 'notification-snap-alert success';
                alertDiv.innerHTML = `<i class="bi bi-check-circle me-2"></i>Emergency has been triggered successfully.`;
                document.body.appendChild(alertDiv);

                // Remove alert after animation
                setTimeout(() => {
                    alertDiv.style.animation = 'fadeOut 0.3s ease-out forwards';
                    setTimeout(() => {
                        alertDiv.remove();
                    }, 300);
                }, 3000);

                // Close the modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('newEmergencyModal'));
                modal.hide();

                // Reset the form
                form.reset();
                document.getElementById('charCount').textContent = '0';
            }
            
            return false;
        }
    </script>

    <!-- New Emergency Modal -->
    <div class="modal fade" id="newEmergencyModal" tabindex="-1" aria-labelledby="newEmergencyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="newEmergencyModalLabel">
                        <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>
                        Create New Emergency
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="newEmergencyForm" method="POST" onsubmit="return confirmEmergencyCreation(event)">
                        <div class="form-group mb-3">
                            <label for="emergency_type" class="form-label">
                                <i class="bi bi-shield-exclamation me-1"></i>
                                Emergency Type
                            </label>
                            <select id="emergency_type" name="emergency_type" class="form-select" required>
                                <option value="">Select Emergency Type</option>
                                <option value="FIRE">
                                    <i class="bi bi-fire"></i> Fire
                                </option>
                                <option value="EARTHQUAKE">
                                    <i class="bi bi-geo-alt"></i> Earthquake
                                </option>
                                <option value="TYPHOON">
                                    <i class="bi bi-cloud-rain-heavy"></i> Typhoon
                                </option>
                                <option value="OTHER">
                                    <i class="bi bi-exclamation-circle"></i> Other
                                </option>
                            </select>
                            <div class="form-text">Select the type of emergency that needs to be declared.</div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="description" class="form-label">
                                <i class="bi bi-card-text me-1"></i>
                                Description
                            </label>
                            <textarea id="description" name="description" class="form-control" required 
                                    minlength="10" maxlength="500" rows="4" 
                                    placeholder="Please provide detailed information about the emergency..."></textarea>
                            <div class="form-text">
                                <span id="charCount">0</span>/500 characters (minimum 10 required)
                            </div>
                        </div>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <strong>Warning:</strong> Creating a new emergency will:
                            <ul class="mb-0 mt-2">
                                <li>Notify all registered users</li>
                                <li>Trigger the welfare check system</li>
                                <li>Require immediate attention from all departments</li>
                            </ul>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" name="create_emergency" class="btn btn-danger">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> 
                                Trigger Emergency
                            </button>
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                <i class="bi bi-x-circle me-1"></i>
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
