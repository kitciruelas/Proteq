<?php
require_once '../includes/db.php';

// Sample database connection
$query = "SELECT * FROM evacuation_centers WHERE status = 'open'";
$result = $conn->query($query);
$centers = [];

while ($row = $result->fetch_assoc()) {
    $centers[] = $row;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Evacuation Centers Map</title>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <style>
    #map { height: 100vh; }
  </style>
</head>
<body>
  <div id="map"></div>

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script>
    // User's current location (for testing; replace with actual if needed)
    const userLat = 16.463333;
    const userLng = 120.593056;

    const map = L.map('map').setView([userLat, userLng], 14);

    // Tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19
    }).addTo(map);

    // Marker for user location
    L.marker([userLat, userLng])
      .addTo(map)
      .bindPopup("📍 You are here")
      .openPopup();

    // Evacuation centers data from PHP
    const centers = <?php echo json_encode($centers); ?>;

    centers.forEach(center => {
      const marker = L.marker([center.latitude, center.longitude]).addTo(map);
      const popupContent = `
        <strong>${center.name}</strong><br>
        Capacity: ${center.capacity}<br>
        Occupied: ${center.current_occupancy}<br>
        Status: ${center.status}<br>
        Contact: ${center.contact_person} (${center.contact_number})<br>
        <a href="https://www.google.com/maps/dir/?api=1&destination=${center.latitude},${center.longitude}" target="_blank">📍 Get Directions</a>
      `;
      marker.bindPopup(popupContent);
    });
  </script>
</body>
</html>
