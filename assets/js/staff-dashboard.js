// Staff Dashboard JavaScript

// Initialize map
let map;
let currentLocationMarker;
let incidentMarkers = [];
let watchId;
let lastNotificationCheck = new Date();
let locationUpdateInterval;
let lastLocationUpdate = null;
const UPDATE_INTERVAL = 30000; // 30 seconds

// Initialize the dashboard when the page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeMap();
    initLocationTracking();
    initializeAvailabilityToggle();
    initializeIncidentList();
    startNotificationPolling();
    initializeUIHandlers();
    initializeValidationForm();
});

// Initialize Leaflet map
function initializeMap() {
    map = L.map('incidentMap').setView([0, 0], 2);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
}

// Location tracking functionality
function initLocationTracking() {
    if ("geolocation" in navigator) {
        // Start periodic location updates
        locationUpdateInterval = setInterval(updateLocation, UPDATE_INTERVAL);
        // Get initial location
        updateLocation();
    } else {
        updateLocationStatus('Location services not available', 'danger');
    }
}

// Update staff location
function updateLocation() {
    navigator.geolocation.getCurrentPosition(
        // Success callback
        function(position) {
            const locationData = {
                latitude: position.coords.latitude,
                longitude: position.coords.longitude
            };

            // Get address from coordinates
            getAddressFromCoordinates(position.coords.latitude, position.coords.longitude)
                .then(address => {
                    locationData.address = address;
                    saveLocation(locationData);
                })
                .catch(error => {
                    console.error('Error getting address:', error);
                    saveLocation(locationData); // Save without address
                });
        },
        // Error callback
        function(error) {
            console.error('Error getting location:', error);
            updateLocationStatus('Location update failed', 'danger');
        },
        // Options
        {
            enableHighAccuracy: true,
            timeout: 5000,
            maximumAge: 0
        }
    );
}

// Save location to server
function saveLocation(locationData) {
    // Show updating status
    updateLocationStatus('Updating location...', 'primary');
    
    fetch('../api/update_location.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(locationData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateLocationStatus('Location updated', 'success');
            updateLocationDisplay(locationData);
            lastLocationUpdate = new Date();
            updateLastUpdateTime();
            
            // Update progress bar
            const progressBar = document.getElementById('locationUpdateProgress');
            if (progressBar) {
                progressBar.style.width = '100%';
                setTimeout(() => {
                    progressBar.style.width = '0%';
                }, 100);
            }
        } else {
            updateLocationStatus(data.message || 'Failed to save location', 'danger');
        }
    })
    .catch(error => {
        console.error('Error saving location:', error);
        updateLocationStatus('Error saving location', 'danger');
    });
}

// Get address from coordinates using reverse geocoding
function getAddressFromCoordinates(latitude, longitude) {
    return new Promise((resolve, reject) => {
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}`)
            .then(response => response.json())
            .then(data => {
                if (data.display_name) {
                    resolve(data.display_name);
                } else {
                    reject('No address found');
                }
            })
            .catch(error => reject(error));
    });
}

// Update location status display
function updateLocationStatus(message, type) {
    const statusElement = document.getElementById('locationStatus');
    if (statusElement) {
        statusElement.textContent = message;
        statusElement.className = `badge bg-${type}`;
    }
}

// Update location display
function updateLocationDisplay(locationData) {
    const locationElement = document.getElementById('currentLocation');
    if (locationElement) {
        if (locationData.address) {
            locationElement.textContent = locationData.address;
        } else {
            locationElement.textContent = `${locationData.latitude.toFixed(6)}, ${locationData.longitude.toFixed(6)}`;
        }
    }
}

// Update last update time display
function updateLastUpdateTime() {
    const timeElement = document.getElementById('locationUpdateTime');
    if (timeElement && lastLocationUpdate) {
        timeElement.textContent = lastLocationUpdate.toLocaleTimeString();
    }
}

// Availability toggle functionality
function initializeAvailabilityToggle() {
    const toggle = document.getElementById('availabilityToggle');
    toggle.addEventListener('change', function() {
        const isAvailable = this.checked;
        updateAvailabilityStatus(isAvailable);
    });
}

function updateAvailabilityStatus(isAvailable) {
    fetch('api/update_availability.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            available: isAvailable
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification({
                message: `Status updated to ${isAvailable ? 'Available' : 'Unavailable'}`,
                type: 'success'
            });
        }
    })
    .catch(error => {
        console.error('Error updating availability:', error);
        showNotification({
            message: 'Error updating availability status',
            type: 'danger'
        });
    });
}

// Incident list functionality
function initializeIncidentList() {
    fetchIncidents();
    // Refresh incidents every 30 seconds
    setInterval(fetchIncidents, 30000);
}

function fetchIncidents() {
    fetch('api/get_assigned_incidents.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateIncidentList(data.incidents);
                updateIncidentMap(data.incidents);
            }
        })
        .catch(error => console.error('Error fetching incidents:', error));
}

function updateIncidentList(incidents) {
    const incidentList = document.getElementById('incidentList');
    incidentList.innerHTML = '';

    incidents.forEach(incident => {
        const incidentElement = createIncidentElement(incident);
        incidentList.appendChild(incidentElement);
    });
}

function createIncidentElement(incident) {
    const div = document.createElement('div');
    div.className = `incident-card card mb-3 priority-${incident.priority_level}`;
    
    const validationBadge = getValidationBadge(incident.validation_status);
    const priorityBadge = getPriorityBadge(incident.priority_level);
    
    div.innerHTML = `
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <h6 class="card-title mb-0">Incident #${incident.incident_id}</h6>
                <div>
                    ${validationBadge}
                    ${priorityBadge}
                </div>
            </div>
            <p class="card-text mb-2"><strong>Type:</strong> ${incident.incident_type}</p>
            <p class="card-text mb-2"><strong>Status:</strong> ${incident.current_status}</p>
            <p class="card-text mb-2"><strong>Reporter Status:</strong> ${incident.reporter_safe_status}</p>
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">Reported: ${new Date(incident.date_reported).toLocaleString()}</small>
                ${getActionButtons(incident)}
            </div>
        </div>
    `;
    return div;
}

function getValidationBadge(status) {
    const badges = {
        'unvalidated': '<span class="badge bg-warning">Unvalidated</span>',
        'validated': '<span class="badge bg-success">Validated</span>',
        'rejected': '<span class="badge bg-danger">Rejected</span>'
    };
    return badges[status] || '';
}

function getPriorityBadge(priority) {
    const colors = {
        'critical': 'danger',
        'high': 'warning',
        'moderate': 'info',
        'low': 'success'
    };
    return `<span class="badge bg-${colors[priority] || 'secondary'} ms-1">${priority}</span>`;
}

function getActionButtons(incident) {
    if (incident.validation_status === 'unvalidated') {
        return `
            <div class="btn-group">
                <button class="btn btn-sm btn-success validate-incident" data-incident-id="${incident.incident_id}">
                    <i class="bi bi-check-circle"></i> Validate
                </button>
                <button class="btn btn-sm btn-danger reject-incident" data-incident-id="${incident.incident_id}">
                    <i class="bi bi-x-circle"></i> Reject
                </button>
            </div>
        `;
    } else if (incident.assigned_to === null && incident.validation_status === 'validated') {
        return `
            <button class="btn btn-sm btn-primary assign-incident" data-incident-id="${incident.incident_id}">
                <i class="bi bi-person-plus"></i> Assign
            </button>
        `;
    } else {
        return `
            <button class="btn btn-sm btn-primary update-status" data-incident-id="${incident.incident_id}">
                Update Status
            </button>
        `;
    }
}

function updateIncidentMap(incidents) {
    // Clear existing markers
    incidentMarkers.forEach(marker => map.removeLayer(marker));
    incidentMarkers = [];

    // Add new markers
    incidents.forEach(incident => {
        const marker = L.marker([incident.latitude, incident.longitude])
            .bindPopup(`
                <strong>Incident #${incident.incident_id}</strong><br>
                Type: ${incident.incident_type}<br>
                Priority: ${incident.priority_level}<br>
                Status: ${incident.current_status}
            `)
            .addTo(map);
        incidentMarkers.push(marker);
    });

    // Fit map to show all markers
    if (incidentMarkers.length > 0) {
        const group = new L.featureGroup(incidentMarkers);
        map.fitBounds(group.getBounds().pad(0.1));
    }
}

// Notification polling
function startNotificationPolling() {
    checkNewNotifications();
    // Check for new notifications every 10 seconds
    setInterval(checkNewNotifications, 10000);
}

function checkNewNotifications() {
    fetch(`api/check_notifications.php?last_check=${lastNotificationCheck.toISOString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.notifications.length > 0) {
                data.notifications.forEach(showNotification);
                lastNotificationCheck = new Date(data.timestamp);
            }
        })
        .catch(error => console.error('Error checking notifications:', error));
}

function showNotification(notification) {
    const notificationArea = document.getElementById('notificationArea');
    const notificationElement = document.createElement('div');
    notificationElement.className = `alert alert-${notification.type || 'info'} alert-dismissible fade show`;
    notificationElement.innerHTML = `
        ${notification.message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    notificationArea.insertBefore(notificationElement, notificationArea.firstChild);

    // Play notification sound
    const audio = new Audio('../assets/sounds/notification.mp3');
    audio.play().catch(error => console.log('Error playing notification sound:', error));
}

// Validation form handling
function initializeValidationForm() {
    const form = document.getElementById('incidentValidationForm');
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const data = {
            incident_id: document.getElementById('validationIncidentId').value,
            validation_status: document.getElementById('validationStatus').value,
            validation_notes: document.getElementById('validationNotes').value
        };

        fetch('api/validate_incident.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                showNotification({
                    message: 'Incident validation updated successfully',
                    type: 'success'
                });
                this.reset();
                fetchIncidents();
            } else {
                showNotification({
                    message: 'Error updating validation: ' + result.message,
                    type: 'danger'
                });
            }
        })
        .catch(error => {
            console.error('Error updating validation:', error);
            showNotification({
                message: 'Error updating validation',
                type: 'danger'
            });
        });
    });
}

// UI Handlers
function initializeUIHandlers() {
    // Clear notifications
    document.getElementById('clearNotifications').addEventListener('click', function() {
        document.getElementById('notificationArea').innerHTML = '';
    });

    // Refresh incidents
    document.getElementById('refreshIncidents').addEventListener('click', fetchIncidents);

    // Toggle report form
    document.getElementById('toggleReportForm').addEventListener('click', function() {
        const container = document.getElementById('reportFormContainer');
        const icon = this.querySelector('i');
        if (container.style.display === 'none') {
            container.style.display = 'block';
            icon.className = 'bi bi-chevron-down';
        } else {
            container.style.display = 'none';
            icon.className = 'bi bi-chevron-up';
        }
    });

    // Toggle validation form
    document.getElementById('toggleValidationForm').addEventListener('click', function() {
        const container = document.getElementById('validationFormContainer');
        const icon = this.querySelector('i');
        if (container.style.display === 'none') {
            container.style.display = 'block';
            icon.className = 'bi bi-chevron-down';
        } else {
            container.style.display = 'none';
            icon.className = 'bi bi-chevron-up';
        }
    });

    // Filter incidents
    document.querySelectorAll('[data-filter]').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const filter = this.dataset.filter;
            filterIncidents(filter);
        });
    });

    // Status update form
    document.getElementById('statusUpdateForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const incidentId = document.getElementById('incidentDetailsId').textContent;
        const status = document.getElementById('statusSelect').value;
        const notes = document.getElementById('statusNotes').value;
        updateIncidentStatus(incidentId, status, notes);
    });

    // File attachment preview
    document.getElementById('statusAttachments').addEventListener('change', function() {
        const preview = document.getElementById('attachmentPreview');
        preview.innerHTML = '';
        
        for (const file of this.files) {
            const div = document.createElement('div');
            div.className = 'mb-2';
            div.innerHTML = `
                <i class="bi bi-file-earmark"></i>
                ${file.name}
                <small class="text-muted">(${(file.size / 1024).toFixed(1)} KB)</small>
            `;
            preview.appendChild(div);
        }
    });
}

function filterIncidents(filter) {
    const incidents = document.querySelectorAll('.incident-card');
    incidents.forEach(card => {
        if (filter === 'all') {
            card.style.display = 'block';
        } else {
            const validationStatus = card.querySelector('.badge').textContent.toLowerCase();
            card.style.display = validationStatus === filter ? 'block' : 'none';
        }
    });
}

// Incident report form handling
document.getElementById('incidentReportForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('incidentId', document.getElementById('incidentId').value);
    formData.append('status', document.getElementById('incidentStatus').value);
    formData.append('notes', document.getElementById('incidentNotes').value);
    
    const imageFiles = document.getElementById('incidentImages').files;
    for (let i = 0; i < imageFiles.length; i++) {
        formData.append('attachments[]', imageFiles[i]);
    }

    fetch('api/update_incident_status.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification({
                message: 'Report submitted successfully',
                type: 'success'
            });
            this.reset();
            fetchIncidents(); // Refresh the incident list
        } else {
            showNotification({
                message: 'Error submitting report: ' + result.message,
                type: 'danger'
            });
        }
    })
    .catch(error => {
        console.error('Error submitting report:', error);
        showNotification({
            message: 'Error submitting report',
            type: 'danger'
        });
    });
});

// Incident assignment handling
function handleIncidentAssignment(incident) {
    if (incident.assigned_to === null && incident.validation_status === 'validated') {
        // Show assignment prompt
        const modal = new bootstrap.Modal(document.getElementById('assignmentModal'));
        document.getElementById('assignmentIncidentId').textContent = incident.incident_id;
        document.getElementById('assignmentIncidentType').textContent = incident.incident_type;
        document.getElementById('assignmentIncidentLocation').textContent = incident.location;
        document.getElementById('assignmentIncidentPriority').textContent = incident.priority_level;
        
        // Set up accept/reject handlers
        document.getElementById('acceptAssignment').onclick = () => respondToAssignment(incident.incident_id, 'accept');
        document.getElementById('rejectAssignment').onclick = () => respondToAssignment(incident.incident_id, 'reject');
        
        modal.show();
    }
}

function respondToAssignment(incidentId, response) {
    fetch('api/respond_to_assignment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            incident_id: incidentId,
            response: response
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification({
                message: result.message,
                type: 'success'
            });
            if (response === 'accept') {
                showIncidentDetails(incidentId);
            }
            fetchIncidents();
        } else {
            showNotification({
                message: result.message,
                type: 'danger'
            });
        }
    })
    .catch(error => {
        console.error('Error responding to assignment:', error);
        showNotification({
            message: 'Error responding to assignment',
            type: 'danger'
        });
    });
}

// Incident status updates
function updateIncidentStatus(incidentId, status, notes = '') {
    const formData = new FormData();
    formData.append('incident_id', incidentId);
    formData.append('status', status);
    formData.append('notes', notes);

    // Add any file attachments
    const fileInput = document.getElementById('statusAttachments');
    if (fileInput && fileInput.files.length > 0) {
        for (let i = 0; i < fileInput.files.length; i++) {
            formData.append('attachments[]', fileInput.files[i]);
        }
    }

    fetch('api/update_incident_status.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification({
                message: result.message,
                type: 'success'
            });
            fetchIncidents();
            if (status === 'resolved' || status === 'needs_escalation') {
                hideIncidentDetails();
            }
        } else {
            showNotification({
                message: result.message,
                type: 'danger'
            });
        }
    })
    .catch(error => {
        console.error('Error updating incident status:', error);
        showNotification({
            message: 'Error updating incident status',
            type: 'danger'
        });
    });
}

// Show incident details and route guidance
function showIncidentDetails(incidentId) {
    fetch(`api/get_incident_details.php?id=${incidentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const incident = data.incident;
                
                // Update incident details modal
                document.getElementById('incidentDetailsId').textContent = incident.incident_id;
                document.getElementById('incidentDetailsType').textContent = incident.incident_type;
                document.getElementById('incidentDetailsLocation').textContent = incident.location;
                document.getElementById('incidentDetailsPriority').textContent = incident.priority_level;
                document.getElementById('incidentDetailsDescription').textContent = incident.description;
                
                // Show status update form
                document.getElementById('statusUpdateForm').style.display = 'block';
                
                // Update map with route
                if (incident.latitude && incident.longitude) {
                    updateMapWithRoute(incident.latitude, incident.longitude);
                }
                
                // Show the modal
                const modal = new bootstrap.Modal(document.getElementById('incidentDetailsModal'));
                modal.show();
            }
        })
        .catch(error => {
            console.error('Error fetching incident details:', error);
            showNotification({
                message: 'Error fetching incident details',
                type: 'danger'
            });
        });
}

function hideIncidentDetails() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('incidentDetailsModal'));
    if (modal) {
        modal.hide();
    }
}

// Map route guidance
function updateMapWithRoute(targetLat, targetLng) {
    if ("geolocation" in navigator) {
        navigator.geolocation.getCurrentPosition(position => {
            const startLat = position.coords.latitude;
            const startLng = position.coords.longitude;
            
            // Use OpenStreetMap Routing Machine for directions
            const routingControl = L.Routing.control({
                waypoints: [
                    L.latLng(startLat, startLng),
                    L.latLng(targetLat, targetLng)
                ],
                routeWhileDragging: true
            }).addTo(map);
            
            // Center map on route
            map.fitBounds(routingControl.getBounds());
        });
    }
} 