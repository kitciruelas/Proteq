<?php
session_start();

// Check if user is not logged in
if (!isset($_SESSION['admin_id'])) {
    // Redirect to login page
    header("Location: admin-login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PROTEQ</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Custom Admin CSS -->
    <link rel="stylesheet" href="../assets/css/admin_css/admin-styles.css">
    <style>
        #heatmap { 
            height: 500px; 
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .card { 
            margin-bottom: 1rem;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-card { 
            transition: transform 0.2s;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-card:hover { 
            transform: translateY(-5px);
        }
        .heatmap-controls {
            background: white;
            padding: 12px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        .heatmap-toggle {
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 6px;
            background-color: #f8f9fa;
            margin-bottom: 12px;
            font-weight: 500;
            transition: all 0.2s;
            border: 1px solid #e9ecef;
        }
        .heatmap-toggle:hover {
            background-color: #e9ecef;
        }
        .heatmap-toggle.active {
            background-color: #007bff;
            color: white;
            border-color: #0056b3;
        }
        .heatmap-legend {
            margin-top: 12px;
            padding: 8px;
            background-color: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }
        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 4px;
            font-size: 12px;
            color: #495057;
        }
        .legend-color {
            width: 24px;
            height: 8px;
            margin-right: 8px;
            border-radius: 2px;
        }
        .density-controls {
            margin-top: 12px;
            padding: 8px;
            background-color: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }
        .density-slider {
            width: 100%;
            margin: 8px 0;
        }
        .density-label {
            font-size: 12px;
            color: #495057;
            margin-bottom: 4px;
        }
        .map-controls {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1000;
        }
        .map-control-btn {
            background: white;
            border: none;
            border-radius: 4px;
            padding: 8px;
            margin-bottom: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: all 0.2s;
        }
        .map-control-btn:hover {
            background: #f8f9fa;
        }
        .affected-area-popup {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        .affected-area-popup h4 {
            margin: 0 0 8px 0;
            color: #dc3545;
        }
        .affected-area-popup p {
            margin: 4px 0;
            font-size: 13px;
        }
        .affected-area-popup .status {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            margin-top: 8px;
        }
        .status.critical {
            background-color: #dc3545;
            color: white;
        }
        .status.warning {
            background-color: #ffc107;
            color: #212529;
        }
        .status.safe {
            background-color: #28a745;
            color: white;
        }
        .affected-areas-list {
            max-height: 300px;
            overflow-y: auto;
            margin-top: 12px;
            padding: 8px;
            background-color: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }
        .affected-area-item {
            padding: 8px;
            margin-bottom: 8px;
            background: white;
            border-radius: 4px;
            border: 1px solid #e9ecef;
            cursor: pointer;
            transition: all 0.2s;
        }
        .affected-area-item:hover {
            background: #f8f9fa;
            transform: translateX(4px);
        }
    </style>
</head>
<body>
    <div class="d-flex" id="wrapper">
        <?php include 'components/_sidebar.php'; ?>

        <!-- Page Content -->
        <div id="page-content-wrapper">
            <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-primary btn-sm" id="sidebarToggle"><i class="bi bi-list"></i></button>
                    <h4 class="ms-3 mb-0">Dashboard</h4>
                    <div class="ms-auto">
                        <span class="text-muted me-3">Last Updated: <span id="lastUpdate">Loading...</span></span>
                    </div>
                </div>
            </nav>

            <div class="container-fluid p-4">
                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white stat-card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-people-fill me-2"></i>Total Affected</h5>
                                <h2 class="card-text" id="totalAffected">0</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white stat-card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-shield-check me-2"></i>Safe Individuals</h5>
                                <h2 class="card-text" id="safeIndividuals">0</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white stat-card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-exclamation-triangle me-2"></i>Needing Help</h5>
                                <h2 class="card-text" id="needingHelp">0</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white stat-card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-building me-2"></i>Active Centers</h5>
                                <h2 class="card-text" id="activeCenters">0</h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Heatmap Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Affected Areas Heatmap</h5>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-primary" id="refreshHeatmap">
                                        <i class="bi bi-arrow-clockwise"></i> Refresh
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="heatmap"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resources and Centers Section -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Available Resources</h5>
                                <button class="btn btn-sm btn-outline-primary" id="refreshResources">
                                    <i class="bi bi-arrow-clockwise"></i> Refresh
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Resource</th>
                                                <th>Quantity</th>
                                                <th>Location</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="resourcesTable">
                                            <!-- Resources will be populated dynamically -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Evacuation Centers</h5>
                                <button class="btn btn-sm btn-outline-primary" id="refreshCenters">
                                    <i class="bi bi-arrow-clockwise"></i> Refresh
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Center Name</th>
                                                <th>Capacity</th>
                                                <th>Current Occupancy</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="centersTable">
                                            <!-- Centers will be populated dynamically -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- Leaflet Heatmap Plugin -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.heat/0.2.0/leaflet-heat.js"></script>
    <!-- Custom Admin JS -->
    <script src="../assets/js/admin-script.js"></script>

    <script>
        // Initialize the map
        const map = L.map('heatmap', {
            zoomControl: false,
            attributionControl: false
        }).setView([16.4023, 120.5960], 13);

        // Add CartoDB Voyager tiles for a cleaner look
        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors, © CartoDB'
        }).addTo(map);

        // Add zoom control to top right
        L.control.zoom({
            position: 'topright'
        }).addTo(map);

        // Create a canvas with willReadFrequently attribute
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        
        // Override the default canvas creation in Leaflet.heat
        const originalCreateCanvas = L.HeatLayer.prototype._createCanvas;
        L.HeatLayer.prototype._createCanvas = function() {
            this._canvas = canvas;
            this._ctx = ctx;
            return this._canvas;
        };

        // Generate density data for heatmap
        function generateDensityData(center, radius, points, intensity) {
            const data = [];
            for (let i = 0; i < points; i++) {
                const angle = Math.random() * 2 * Math.PI;
                const distance = Math.random() * radius;
                const lat = center[0] + (distance * Math.cos(angle) / 111);
                const lng = center[1] + (distance * Math.sin(angle) / (111 * Math.cos(center[0] * Math.PI / 180)));
                data.push([lat, lng, intensity * (1 - distance / radius)]);
            }
            return data;
        }

        // Sample affected areas data
        const affectedAreas = [
            {
                id: 1,
                name: "Baguio City Center",
                location: [16.4023, 120.5960],
                status: "critical",
                affectedCount: 150,
                safeCount: 120,
                needingHelp: 30,
                lastUpdate: "2024-03-20 10:30",
                description: "Heavy flooding in central area"
            },
            {
                id: 2,
                name: "North Baguio Area",
                location: [16.4150, 120.5950],
                status: "warning",
                affectedCount: 80,
                safeCount: 60,
                needingHelp: 20,
                lastUpdate: "2024-03-20 10:15",
                description: "Moderate flooding, roads affected"
            },
            {
                id: 3,
                name: "South Baguio Area",
                location: [16.3900, 120.5800],
                status: "safe",
                affectedCount: 40,
                safeCount: 35,
                needingHelp: 5,
                lastUpdate: "2024-03-20 10:00",
                description: "Minor flooding, situation under control"
            }
        ];

        // Create markers for affected areas
        const markers = {};
        affectedAreas.forEach(area => {
            const marker = L.marker(area.location).addTo(map);
            
            // Create popup content
            const popupContent = `
                <div class="affected-area-popup">
                    <h4>${area.name}</h4>
                    <p><strong>Status:</strong> <span class="status ${area.status}">${area.status.toUpperCase()}</span></p>
                    <p><strong>Affected:</strong> ${area.affectedCount} people</p>
                    <p><strong>Safe:</strong> ${area.safeCount} people</p>
                    <p><strong>Needing Help:</strong> ${area.needingHelp} people</p>
                    <p><strong>Last Update:</strong> ${area.lastUpdate}</p>
                    <p><strong>Description:</strong> ${area.description}</p>
                </div>
            `;
            
            marker.bindPopup(popupContent);
            markers[area.id] = marker;
        });

        // Generate density data based on affected areas
        const sampleHeatmapData = affectedAreas.flatMap(area => {
            const baseIntensity = area.status === 'critical' ? 0.9 : 
                                area.status === 'warning' ? 0.6 : 0.3;
            return generateDensityData(area.location, 0.02, 30, baseIntensity);
        });

        // Initialize heatmap layer with density plot styling
        window.heatLayer = L.heatLayer(sampleHeatmapData, {
            radius: 25,
            blur: 15,
            maxZoom: 15,
            minOpacity: 0.3,
            gradient: {
                0.0: '#ff0000', // Red (Critical)
                0.3: '#ffa500', // Orange (Warning)
                0.6: '#ffff00', // Yellow (Moderate)
                0.8: '#00ff00', // Green (Safe)
                1.0: '#ffffff'  // White (No impact)
            }
        }).addTo(map);

        // Add heatmap controls
        const heatmapControls = L.control({ position: 'topright' });
        heatmapControls.onAdd = function(map) {
            const div = L.DomUtil.create('div', 'heatmap-controls');
            
            // Toggle button
            const toggleBtn = L.DomUtil.create('div', 'heatmap-toggle', div);
            toggleBtn.innerHTML = '🌡️ Toggle Heatmap';
            toggleBtn.classList.add('active');
            toggleBtn.onclick = function() {
                if (window.heatLayer) {
                    if (map.hasLayer(window.heatLayer)) {
                        map.removeLayer(window.heatLayer);
                        toggleBtn.classList.remove('active');
                    } else {
                        map.addLayer(window.heatLayer);
                        toggleBtn.classList.add('active');
                    }
                }
            };

            // Add affected areas list
            const areasList = L.DomUtil.create('div', 'affected-areas-list', div);
            areasList.innerHTML = '<div style="font-weight: 500; margin-bottom: 8px;">Affected Areas:</div>';
            
            affectedAreas.forEach(area => {
                const areaItem = L.DomUtil.create('div', 'affected-area-item', areasList);
                areaItem.innerHTML = `
                    <div style="font-weight: 500;">${area.name}</div>
                    <div style="font-size: 12px; color: #6c757d;">
                        Status: <span class="status ${area.status}">${area.status.toUpperCase()}</span>
                    </div>
                    <div style="font-size: 12px; color: #6c757d;">
                        Affected: ${area.affectedCount} | Safe: ${area.safeCount} | Need Help: ${area.needingHelp}
                    </div>
                `;
                
                areaItem.onclick = function() {
                    map.setView(area.location, 15);
                    markers[area.id].openPopup();
                };
            });

            // Add density controls
            const densityControls = L.DomUtil.create('div', 'density-controls', div);
            densityControls.innerHTML = `
                <div style="font-weight: 500; margin-bottom: 8px;">Heatmap Controls:</div>
                <div class="density-label">Spread Radius: <span id="radiusValue">25</span>m</div>
                <input type="range" class="density-slider" min="10" max="50" value="25" id="radiusSlider">
                <div class="density-label">Blur Intensity: <span id="blurValue">15</span></div>
                <input type="range" class="density-slider" min="5" max="30" value="15" id="blurSlider">
            `;

            // Add legend with new color scheme
            const legend = L.DomUtil.create('div', 'heatmap-legend', div);
            legend.innerHTML = `
                <div style="font-size: 13px; margin-bottom: 8px; font-weight: 500; color: #212529;">Impact Levels:</div>
                <div class="legend-item">
                    <div class="legend-color" style="background: #ff0000;"></div>
                    <span>Critical (100+ affected)</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background: #ffa500;"></div>
                    <span>Warning (50-100 affected)</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background: #ffff00;"></div>
                    <span>Moderate (20-50 affected)</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background: #00ff00;"></div>
                    <span>Safe (< 20 affected)</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background: #ffffff;"></div>
                    <span>No Impact</span>
                </div>
            `;

            return div;
        };
        heatmapControls.addTo(map);

        // Add event listeners after controls are added to the map
        setTimeout(() => {
            const radiusSlider = document.getElementById('radiusSlider');
            const blurSlider = document.getElementById('blurSlider');
            const radiusValue = document.getElementById('radiusValue');
            const blurValue = document.getElementById('blurValue');

            if (radiusSlider) {
                radiusSlider.addEventListener('input', function(e) {
                    if (window.heatLayer) {
                        const value = parseInt(e.target.value);
                        window.heatLayer.setOptions({ radius: value });
                        radiusValue.textContent = value;
                    }
                });
            }

            if (blurSlider) {
                blurSlider.addEventListener('input', function(e) {
                    if (window.heatLayer) {
                        const value = parseInt(e.target.value);
                        window.heatLayer.setOptions({ blur: value });
                        blurValue.textContent = value;
                    }
                });
            }
        }, 100);

        // Add map controls
        const mapControls = L.control({ position: 'topright' });
        mapControls.onAdd = function(map) {
            const div = L.DomUtil.create('div', 'map-controls');
            
            // Reset view button
            const resetBtn = L.DomUtil.create('button', 'map-control-btn', div);
            resetBtn.innerHTML = '↺ Reset View';
            resetBtn.onclick = function() {
                map.setView([16.4023, 120.5960], 13);
            };

            return div;
        };
        mapControls.addTo(map);

        // Update statistics with sample data
        document.getElementById('totalAffected').textContent = '270';
        document.getElementById('safeIndividuals').textContent = '215';
        document.getElementById('needingHelp').textContent = '55';
        document.getElementById('activeCenters').textContent = '3';
        document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString();

        // Set up refresh button
        document.getElementById('refreshHeatmap').addEventListener('click', function() {
            document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString();
        });
    </script>
</body>
</html>