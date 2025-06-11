<?php
/**
 * Get location name from coordinates using Google Maps Geocoding API
 * 
 * @param float $latitude The latitude coordinate
 * @param float $longitude The longitude coordinate
 * @return string|null The location name or null if not found
 */
function getLocationFromCoordinates($latitude, $longitude) {
    $apiKey = 'AIzaSyB41DRUbKWJHPxaFjMAwdrzWzbVKartNGg';
    $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng={$latitude},{$longitude}&key={$apiKey}";
    
    try {
        $context = stream_context_create([
            'http' => [
                'timeout' => 5, // 5 second timeout
                'ignore_errors' => true
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            error_log("Failed to fetch location data from Google Maps API");
            return formatCoordinatesWithDegrees($latitude, $longitude);
        }
        
        $data = json_decode($response, true);
        
        if ($data && isset($data['results']) && !empty($data['results'])) {
            // First try to get the city/municipality
            foreach ($data['results'] as $result) {
                if (isset($result['address_components'])) {
                    foreach ($result['address_components'] as $component) {
                        // Look for locality (city) or administrative_area_level_2 (municipality)
                        if (in_array('locality', $component['types']) || 
                            in_array('administrative_area_level_2', $component['types'])) {
                            return $component['long_name'];
                        }
                    }
                }
            }
            
            // If no city found, try to get the province
            foreach ($data['results'] as $result) {
                if (isset($result['address_components'])) {
                    foreach ($result['address_components'] as $component) {
                        if (in_array('administrative_area_level_1', $component['types'])) {
                            return $component['long_name'];
                        }
                    }
                }
            }
            
            // If still no specific place found, return the formatted address
            return $data['results'][0]['formatted_address'];
        }
    } catch (Exception $e) {
        error_log("Error getting location from coordinates: " . $e->getMessage());
    }
    
    // Fallback to coordinates if no location name found
    return formatCoordinatesWithDegrees($latitude, $longitude);
}

/**
 * Format coordinates in degrees format
 * 
 * @param float $latitude The latitude coordinate
 * @param float $longitude The longitude coordinate
 * @return string Formatted coordinates with degrees
 */
function formatCoordinatesWithDegrees($latitude, $longitude) {
    $latDirection = $latitude >= 0 ? 'N' : 'S';
    $longDirection = $longitude >= 0 ? 'E' : 'W';
    
    $latDegrees = abs($latitude);
    $longDegrees = abs($longitude);
    
    return sprintf("%.4f° %s, %.4f° %s", 
        $latDegrees, $latDirection, 
        $longDegrees, $longDirection
    );
}
?> 