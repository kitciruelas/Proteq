// Location tracking functionality
function updateLocation() {
    if ("geolocation" in navigator) {
        navigator.geolocation.getCurrentPosition(function(position) {
            const latitude = position.coords.latitude;
            const longitude = position.coords.longitude;
            
            // Convert coordinates to place name using Google Maps Geocoding API
            const apiKey = 'AIzaSyB41DRUbKWJHPxaFjMAwdrzWzbVKartNGg';
            fetch(`https://maps.googleapis.com/maps/api/geocode/json?latlng=${latitude},${longitude}&key=${apiKey}`)
                .then(response => response.json())
                .then(data => {
                    if (data.results && data.results.length > 0) {
                        // Get the most specific place name
                        let placeName = '';
                        for (let result of data.results) {
                            // Look for establishment, point_of_interest, or locality in address_components
                            const components = result.address_components;
                            for (let component of components) {
                                if (component.types.includes('establishment') || 
                                    component.types.includes('point_of_interest') ||
                                    component.types.includes('locality')) {
                                    placeName = component.long_name;
                                    break;
                                }
                            }
                            if (placeName) break;
                        }
                        
                        // If no specific place found, use the formatted address
                        if (!placeName) {
                            placeName = data.results[0].formatted_address;
                        }
                        
                        // Send location and place name to server
                        fetch('../User/update_location.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                latitude: latitude,
                                longitude: longitude,
                                address: placeName
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                console.log('Location updated successfully');
                                // Update the location display on the page
                                updateLocationDisplay(latitude, longitude, placeName);
                            } else {
                                console.error('Failed to update location:', data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error getting place name:', error);
                });
        }, function(error) {
            console.error('Error getting location:', error.message);
        }, {
            enableHighAccuracy: true,  // Request highest accuracy
            timeout: 10000,           // Wait up to 10 seconds
            maximumAge: 0             // Don't use cached position
        });
    }
}

// Function to update the location display on the page
function updateLocationDisplay(latitude, longitude, placeName) {
    const locationElement = document.querySelector('.location-display');
    if (locationElement) {
        locationElement.innerHTML = `
            <small class="d-block mb-1">
                <i class="bi bi-geo-alt"></i> 
                ${placeName}
            </small>
            <small class="text-muted">
                <i class="bi bi-geo-alt-fill"></i> 
                ${latitude.toFixed(6)}, ${longitude.toFixed(6)}
            </small>
        `;
    }
}

// Update location every 5 minutes
document.addEventListener('DOMContentLoaded', function() {
    updateLocation(); // Initial update
    setInterval(updateLocation, 5 * 60 * 1000); // Update every 5 minutes
}); 