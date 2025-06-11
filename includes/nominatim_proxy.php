<?php
header('Content-Type: application/json');

// Get the search query from the request
$query = isset($_GET['q']) ? urlencode($_GET['q']) : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;

// Batangas bounding box
$viewbox = '120.6,13.4,121.5,14.1';

// Build the Nominatim API URL
$url = "https://nominatim.openstreetmap.org/search?q={$query}&limit={$limit}&format=json&addressdetails=1&countrycodes=ph&viewbox={$viewbox}&bounded=1&accept-language=en";

// Set up cURL request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'PROTEQ/1.0');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json'
]);

// Execute the request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Check if the request was successful
if ($httpCode === 200) {
    echo $response;
} else {
    http_response_code($httpCode);
    echo json_encode([
        'error' => true,
        'message' => 'Failed to fetch location data'
    ]);
} 