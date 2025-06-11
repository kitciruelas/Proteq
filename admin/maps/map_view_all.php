<?php

session_start();

// --- Optional: Admin Authentication Check ---
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header("Location: ../admin/login.php");
//     exit;
// }

// --- Database Connection ---
require_once '../includes/db.php'; // Adjust path as needed

// --- Get filter parameters ---
$alert_type = isset($_GET['alert_type']) ? $_GET['alert_type'] : 'all';

// --- Fetch Incidents for Map & List ---
$map_incidents = [];
$error_message = null;

// Build the SQL query with filter
$sql = "SELECT id, alert_type, title, description, latitude, longitude, radius_km, status, created_at 
        FROM alerts 
        WHERE latitude IS NOT NULL AND longitude IS NOT NULL 
        AND status = 'Active'";

if ($alert_type !== 'all') {
    $sql .= " AND alert_type = ?";
}

$sql .= " ORDER BY created_at DESC";

// Prepare and execute the query
$stmt = $conn->prepare($sql);
if ($alert_type !== 'all') {
    $stmt->bind_param("s", $alert_type);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    // Fetch all results into an associative array
    $map_incidents = $result->fetch_all(MYSQLI_ASSOC);
} elseif ($conn->error) {
    $error_message = "Error fetching incidents: " . $conn->error;
}

$stmt->close();
$conn->close();

// Encode the incident data as JSON to pass to JavaScript
$incidentsJson = json_encode($map_incidents);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incidents Maps - Admin Dashboard - PROTEQ</title>
    <!-- Existing CSS imports -->
    <!-- Add Leaflet CSS for maps -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="../assets/css/admin_css/admin-styles.css">
    <!-- Include Leaflet Control Search CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-search/dist/leaflet-search.min.css" />
    <!-- Include Notifications CSS -->
    <link rel="stylesheet" href="../assets/css/notifications.css">
    <!-- DataTables Buttons CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
     integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
     crossorigin=""/>

</head>
<body>
    <div class="d-flex" id="wrapper">
        <?php include 'components/_sidebar.php'; ?>

        <div id="page-content-wrapper">
            <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-primary btn-sm" id="sidebarToggle"><i class="bi bi-list"></i></button>
                    <h4 class="ms-3 mb-0">Incidents Maps</h4>
                    <div class="ms-auto">
                        <form class="d-flex" method="GET" action="">
                            <select class="form-select form-select-sm me-2" name="alert_type" onchange="this.form.submit()">
                                <option value="all" <?php echo $alert_type === 'all' ? 'selected' : ''; ?>>All Alert Types</option>
                                <option value="Flood" <?php echo $alert_type === 'Flood' ? 'selected' : ''; ?>>Flood</option>
                                <option value="Fire" <?php echo $alert_type === 'Fire' ? 'selected' : ''; ?>>Fire</option>
                                <option value="Earthquake" <?php echo $alert_type === 'Earthquake' ? 'selected' : ''; ?>>Earthquake</option>
                                <option value="Typhoon" <?php echo $alert_type === 'Typhoon' ? 'selected' : ''; ?>>Typhoon</option>
                                <option value="Landslide" <?php echo $alert_type === 'Landslide' ? 'selected' : ''; ?>>Landslide</option>
                                <option value="Tsunami" <?php echo $alert_type === 'Tsunami' ? 'selected' : ''; ?>>Tsunami</option>
                                <option value="Volcanic Eruption" <?php echo $alert_type === 'Volcanic Eruption' ? 'selected' : ''; ?>>Volcanic Eruption</option>
                            </select>
                        </form>
                    </div>
                </div>
            </nav>

            <div class="container-fluid p-0">
                <div class="row g-0">
                    <!-- Alert List Sidebar -->
                    <div class="col-md-3 border-end" style="height: calc(100vh - 56px); overflow-y: auto;">
                        <div class="p-3">
                            <h5 class="mb-3 border-bottom pb-2"><?php echo count($map_incidents); ?> ACTIVE ALERTS</h5>
                            <?php if (isset($error_message)): ?>
                                <div class="alert alert-danger alert-sm">
                                    <?php echo htmlspecialchars($error_message); ?>
                                </div>
                            <?php endif; ?>

                            <div class="list-group">
                                <?php if (empty($map_incidents)): ?>
                                    <p class="text-muted">No active alerts found.</p>
                                <?php else: ?>
                                    <?php foreach ($map_incidents as $alert): ?>
                                        <a href="#" class="list-group-item list-group-item-action incident-item"
                                           data-lat="<?php echo htmlspecialchars($alert['latitude']); ?>"
                                           data-lng="<?php echo htmlspecialchars($alert['longitude']); ?>"
                                           data-id="<?php echo htmlspecialchars($alert['id']); ?>"
                                           data-type="<?php echo htmlspecialchars($alert['alert_type']); ?>">
                                            <h6><?php echo htmlspecialchars($alert['alert_type']); ?> (ID: <?php echo htmlspecialchars($alert['id']); ?>)</h6>
                                            <small>Title: <?php echo htmlspecialchars($alert['title']); ?></small><br>
                                            <small>Radius: <?php echo htmlspecialchars($alert['radius_km']); ?> km</small><br>
                                            <small>Created: <?php echo htmlspecialchars(date('M d, Y H:i', strtotime($alert['created_at']))); ?></small>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Map Container -->
                    <div class="col-md-9">
                        <div id="map-container" style="position: relative; height: calc(100vh - 56px);">
                            <div id="allIncidentsMap" style="height: 100%;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

  

    <?php include 'components/modal/new_alert_modal.php'; ?>

    <!-- Script imports in correct order -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <!-- DataTables Buttons JS -->
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- Include Leaflet Control Search JS -->
    <script src="https://unpkg.com/leaflet-search/dist/leaflet-search.min.js"></script>
    <script src="../assets/js/admin-script.js"></script>
 <!-- Leaflet JS -->
 <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
     integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
     crossorigin=""></script>
    <!-- Simple Heatmap Library -->
    <script src="https://cdn.jsdelivr.net/npm/simpleheat@0.4.0/simpleheat.min.js"></script>

    <script> 
  const incidentsData = <?php echo $incidentsJson; ?>;
        const defaultCenter = [12.8797, 121.7740]; // Approx center of Philippines
        const defaultZoom = 6;
        const markers = {}; // Store markers by alert ID
        const circles = {}; // Store circles by alert ID
        let heatmapCanvas = null;
        let heatmap = null;

        // Define colors for different alert types
        const alertColors = {
            'Flood': {
                color: '#0066cc', // Blue
                fillColor: '#0066cc'
            },
            'Fire': {
                color: '#ff0000', // Red
                fillColor: '#ff0000'
            },
            'Earthquake': {
                color: '#ff9900', // Orange
                fillColor: '#ff9900'
            },
            'Typhoon': {
                color: '#00ccff', // Light Blue
                fillColor: '#00ccff'
            },
            'Landslide': {
                color: '#996633', // Brown
                fillColor: '#996633'
            },
            'Tsunami': {
                color: '#006699', // Dark Blue
                fillColor: '#006699'
            },
            'Volcanic Eruption': {
                color: '#cc3300', // Dark Red
                fillColor: '#cc3300'
            },
            'default': {
                color: '#ff7800', // Default Orange
                fillColor: '#ff7800'
            }
        };

        // Function to get color for alert type
        function getAlertColor(alertType) {
            return alertColors[alertType] || alertColors.default;
        }

        // Initialize the map
        const map = L.map('allIncidentsMap');

        // Add Dark Tile Layer (CartoDB Dark Matter)
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
            subdomains: 'abcd',
            maxZoom: 20
        }).addTo(map);

        // Create heatmap data points
        const heatmapPoints = [];
        const alertTypePoints = {}; // Store points by alert type

        if (incidentsData && incidentsData.length > 0) {
            const markerLatLngs = []; // For bounding box calculation
            incidentsData.forEach(alert => {
                const lat = parseFloat(alert.latitude);
                const lng = parseFloat(alert.longitude);
                const radius = parseFloat(alert.radius_km) || 1; // Default to 1km if not set
                const alertType = alert.alert_type;
                const colors = getAlertColor(alertType);

                if (!isNaN(lat) && !isNaN(lng)) {
                    // Add point to heatmap data
                    heatmapPoints.push([lat, lng, radius]);

                    // Store point by alert type
                    if (!alertTypePoints[alertType]) {
                        alertTypePoints[alertType] = [];
                    }
                    alertTypePoints[alertType].push([lat, lng, radius]);

                    // Create circle for alert area with alert type color
                    const circle = L.circle([lat, lng], {
                        radius: radius * 1000, // Convert km to meters
                        color: colors.color,
                        fillColor: colors.fillColor,
                        fillOpacity: 0.2,
                        weight: 2,
                        className: `alert-circle alert-type-${alertType.toLowerCase().replace(/\s+/g, '-')}`
                    });

                    // Create marker for alert center with alert type color
                    const marker = L.circleMarker([lat, lng], {
                        radius: 6,
                        fillColor: colors.fillColor,
                        color: "#fff",
                        weight: 1,
                        opacity: 1,
                        fillOpacity: 0.8,
                        className: `alert-marker alert-type-${alertType.toLowerCase().replace(/\s+/g, '-')}`
                    });

                    // Create popup content
                    const popupContent = `<b>${alertType || 'N/A'} (ID: ${alert.id})</b><br>
                                        Title: ${alert.title || 'N/A'}<br>
                                        Radius: ${radius} km<br>
                                        Created: ${new Date(alert.created_at).toLocaleString()}<br>
                                        Desc: ${(alert.description || 'No description').substring(0, 100)}...`;
                    
                    marker.bindPopup(popupContent);
                    circle.bindPopup(popupContent);

                    // Add to map
                    circle.addTo(map);
                    marker.addTo(map);

                    // Store references
                    markers[alert.id] = marker;
                    circles[alert.id] = circle;
                    markerLatLngs.push([lat, lng]);
                } else {
                    console.warn(`Invalid coordinates for alert ID: ${alert.id}`);
                }
            });

            // Create heatmap canvas
            heatmapCanvas = document.createElement('canvas');
            heatmapCanvas.style.position = 'absolute';
            heatmapCanvas.style.top = '0';
            heatmapCanvas.style.left = '0';
            heatmapCanvas.style.pointerEvents = 'none';
            heatmapCanvas.style.display = 'none';
            // Set willReadFrequently attribute for better performance
            heatmapCanvas.getContext('2d', { willReadFrequently: true });
            document.getElementById('map-container').appendChild(heatmapCanvas);

            // Initialize heatmap
            heatmap = simpleheat(heatmapCanvas);

            // Function to update heatmap
            function updateHeatmap() {
                const size = map.getSize();
                const bounds = map.getBounds();

                // Update canvas size
                heatmapCanvas.width = size.x;
                heatmapCanvas.height = size.y;
                heatmap.resize();

                // Convert geographic points to canvas coordinates for simpleheat
                const heatmapDataForSimpleHeat = heatmapPoints.map(point => {
                    const lat = point[0]; // Access latitude using array index
                    const lng = point[1]; // Access longitude using array index
                    const intensity = point[2]; // Use radius as intensity (array index)
                    // Ensure lat and lng are valid numbers before creating LatLng object
                    if (isNaN(lat) || isNaN(lng)) {
                       console.warn("Skipping invalid heatmap point:", point);
                       return null; // Skip invalid points
                    }
                    const containerPoint = map.latLngToContainerPoint([lat, lng]);
                    return [
                        containerPoint.x,
                        containerPoint.y,
                        intensity // simpleheat expects [x, y, intensity]
                    ];
                }).filter(point => point !== null); // Filter out null points

                // Calculate intensity based on zoom level
                const zoom = map.getZoom();
                // Adjust radius based on zoom, maybe make it less sensitive or use a different scaling
                // Keeping the previous adjustment for now, but might need fine-tuning
                const baseRadius = Math.max(35, 50 - (zoom * 2)); 
                heatmap.radius(baseRadius, baseRadius + 15);

                // Update and draw heatmap
                heatmap.data(heatmapDataForSimpleHeat);
                heatmap.draw();
            }

            // Add heatmap controls
            const heatmapControls = L.control({ position: 'topright' });
            heatmapControls.onAdd = function(map) {
                const div = L.DomUtil.create('div', 'heatmap-controls');
                div.style.backgroundColor = 'white';
                div.style.padding = '10px';
                div.style.borderRadius = '5px';
                div.style.boxShadow = '0 0 15px rgba(0,0,0,0.2)';

                // Toggle button
                const toggleBtn = L.DomUtil.create('div', 'heatmap-toggle', div);
                toggleBtn.innerHTML = '🌡️ Toggle Heatmap';
                toggleBtn.style.cursor = 'pointer';
                toggleBtn.style.marginBottom = '10px';
                toggleBtn.style.padding = '5px';
                toggleBtn.style.borderRadius = '3px';
                toggleBtn.style.backgroundColor = '#f8f9fa';
                toggleBtn.onclick = function() {
                    if (heatmapCanvas.style.display === 'none') {
                        heatmapCanvas.style.display = 'block';
                        toggleBtn.classList.add('active');
                        updateHeatmap();
                    } else {
                        heatmapCanvas.style.display = 'none';
                        toggleBtn.classList.remove('active');
                    }
                };

                // Add legend
                const legend = L.DomUtil.create('div', 'heatmap-legend', div);
                legend.style.marginTop = '10px';
                legend.style.padding = '5px';
                legend.style.backgroundColor = '#f8f9fa';
                legend.style.borderRadius = '3px';
                legend.innerHTML = `
                    <div style="font-size: 12px; margin-bottom: 5px;"><b>Alert Concentration:</b></div>
                    <div style="display: flex; align-items: center; margin-bottom: 3px;">
                        <div style="width: 20px; height: 10px; background: blue; margin-right: 5px;"></div>
                        <span style="font-size: 11px;">Low</span>
                    </div>
                    <div style="display: flex; align-items: center; margin-bottom: 3px;">
                        <div style="width: 20px; height: 10px; background: cyan; margin-right: 5px;"></div>
                        <span style="font-size: 11px;">Low-Medium</span>
                    </div>
                    <div style="display: flex; align-items: center; margin-bottom: 3px;">
                        <div style="width: 20px; height: 10px; background: lime; margin-right: 5px;"></div>
                        <span style="font-size: 11px;">Medium</span>
                    </div>
                    <div style="display: flex; align-items: center; margin-bottom: 3px;">
                        <div style="width: 20px; height: 10px; background: yellow; margin-right: 5px;"></div>
                        <span style="font-size: 11px;">Medium-High</span>
                    </div>
                    <div style="display: flex; align-items: center;">
                        <div style="width: 20px; height: 10px; background: red; margin-right: 5px;"></div>
                        <span style="font-size: 11px;">High</span>
                    </div>
                `;

                return div;
            };
            heatmapControls.addTo(map);

            // Update heatmap on map move
            map.on('moveend', updateHeatmap);
            map.on('zoomend', updateHeatmap);

            // Fit map view to markers if any valid markers were added
            if (markerLatLngs.length > 0) {
                map.fitBounds(markerLatLngs, { padding: [50, 50] }); // Add padding
            } else {
                map.setView(defaultCenter, defaultZoom); // Fallback if no valid coordinates
            }

            // Add CSS for alert type colors
            const style = document.createElement('style');
            style.textContent = `
                .alert-circle {
                    transition: fill-opacity 0.3s;
                }
                .alert-circle:hover {
                    fill-opacity: 0.4 !important;
                }
                .alert-marker {
                    transition: transform 0.3s;
                }
                .alert-marker:hover {
                    transform: scale(1.2);
                }
                ${Object.entries(alertColors).map(([type, colors]) => `
                    .alert-type-${type.toLowerCase().replace(/\s+/g, '-')} {
                        stroke: ${colors.color} !important;
                        fill: ${colors.fillColor} !important;
                    }
                `).join('\n')}
            `;
            document.head.appendChild(style);

        } else {
            // If no alerts, set map to default view
            map.setView(defaultCenter, defaultZoom);
            if (!<?php echo isset($error_message) ? 'true' : 'false'; ?>) {
                 L.popup()
                  .setLatLng(defaultCenter)
                  .setContent('No active alerts found.')
                  .openOn(map);
            }
        }

        // Add click listener to sidebar items to pan/zoom and open popup
        document.querySelectorAll('.incident-item').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault(); // Prevent default link behavior
                const lat = parseFloat(this.getAttribute('data-lat'));
                const lng = parseFloat(this.getAttribute('data-lng'));
                const id = this.getAttribute('data-id');

                if (!isNaN(lat) && !isNaN(lng)) {
                    map.flyTo([lat, lng], 13); // Zoom level 13 to show the circle

                    // Open the corresponding marker's popup
                    if (markers[id]) {
                        // Delay opening popup slightly to allow map animation
                        setTimeout(() => {
                             markers[id].openPopup();
                        }, 500); // Adjust delay if needed
                    }
                }
            });
        });


    </script>
</body>
</html>