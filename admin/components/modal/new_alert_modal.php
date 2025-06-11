<?php 
// Remove session_start() from here since it's called in the parent file
require_once '../../includes/db.php';
// Check if user is not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin-login.php");
    exit();
}
?>

<div class="modal fade" id="newAlertModal" tabindex="-1" aria-labelledby="newAlertModalLabel">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newAlertModalLabel">Create New Alert</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="alertForm">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Alert Type</label>
                            <select class="form-select" name="alert_type" required>
                                <option value="typhoon">Typhoon</option>
                                <option value="flood">Flood</option>
                                <option value="fire">Fire</option>
                                <option value="earthquake">Earthquake</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3" required></textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Latitude</label>
                            <input type="number" class="form-control" name="latitude" step="any" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Longitude</label>
                            <input type="number" class="form-control" name="longitude" step="any" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Radius (km)</label>
                            <input type="number" class="form-control" name="radius_km" step="0.1" required>
                        </div>
                    </div>
                    <div class="mb-3">
                    <div class="col-md-6">
                                <label class="form-label">Location</label>
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
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveAlert">
                    <span class="button-text">Create Alert</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Wait for jQuery to be fully loaded
  

        // Handle search button click
        $('#searchButton').on('click', function() {
            const searchText = $('#searchLocation').val();
            if (searchText && geocoder) {
                geocoder.options.geocoder.geocode(searchText, function(results) {
                    if (results && results.length > 0) {
                        geocoder.fire('markgeocode', {
                            geocode: results[0]
                        });
                    }
                });
            }
        });

        // Handle enter key in search input
        $('#searchLocation').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#searchButton').click();
            }
        });

        // Save alert
        $('#saveAlert').on('click', function() {
            const saveButton = $(this);
            const buttonText = saveButton.find('.button-text');
            const spinner = saveButton.find('.spinner-border');

            // Show loading state
            buttonText.text('Creating Alert...');
            spinner.removeClass('d-none');
            saveButton.prop('disabled', true);

            // Get all form values
            const formData = {
                alert_type: $('select[name="alert_type"]').val(),
                title: $('input[name="title"]').val(),
                description: $('textarea[name="description"]').val(),
                latitude: $('input[name="latitude"]').val(),
                longitude: $('input[name="longitude"]').val(),
                radius_km: $('input[name="radius_km"]').val()
            };

            // Debug: Log form data
            console.log('Form data being sent:', formData);
            
            $.ajax({
                url: '/Proteq/admin/alerts/process_alert.php',
                type: 'POST',
                data: formData,
                success: function(response) {
                    console.log('Server response:', response);
                    try {
                        const res = typeof response === 'object' ? response : JSON.parse(response);
                        
                        // Show notification based on response
                        const notification = $('<div>')
                            .addClass('notification-snap-alert ' + (res.success ? 'success' : 'error'))
                            .html('<i class="bi bi-' + (res.success ? 'check-circle-fill' : 'exclamation-circle-fill') + '"></i> ' + res.message)
                            .appendTo('body');

                        // Remove notification after 3 seconds
                        setTimeout(() => {
                            notification.css('animation', 'fadeOut 0.3s ease-out forwards');
                            setTimeout(() => {
                                notification.remove();
                            }, 300);
                        }, 3000);

                        if (res.success) {
                            // Close modal and reload page
                            $('#newAlertModal').modal('hide');
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        } else {
                            // Reset button state on error
                            buttonText.text('Create Alert');
                            spinner.addClass('d-none');
                            saveButton.prop('disabled', false);
                            
                            console.error('Alert creation failed:', res.message);
                            if (res.debug) {
                                console.error('Debug info:', res.debug);
                            }
                        }
                    } catch (e) {
                        // Reset button state on error
                        buttonText.text('Create Alert');
                        spinner.addClass('d-none');
                        saveButton.prop('disabled', false);
                        
                        console.error('Response parsing error:', e);
                        console.error('Raw response:', response);
                        
                        // Show error notification
                        const notification = $('<div>')
                            .addClass('notification-snap-alert error')
                            .html('<i class="bi bi-exclamation-circle-fill"></i> Error processing server response. Please try again.')
                            .appendTo('body');

                        setTimeout(() => {
                            notification.css('animation', 'fadeOut 0.3s ease-out forwards');
                            setTimeout(() => {
                                notification.remove();
                            }, 300);
                        }, 3000);
                    }
                },
                error: function(xhr, status, error) {
                    // Reset button state on error
                    buttonText.text('Create Alert');
                    spinner.addClass('d-none');
                    saveButton.prop('disabled', false);

                    console.error('AJAX error:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });

                    let errorMessage = 'Error creating alert';
                    
                    // Try to parse the error response if it's JSON
                    try {
                        const errorResponse = JSON.parse(xhr.responseText);
                        if (errorResponse.message) {
                            errorMessage = errorResponse.message;
                        }
                    } catch (e) {
                        // If not JSON, use the status text or error
                        errorMessage = xhr.statusText || error;
                    }

                    // Show error notification
                    const notification = $('<div>')
                        .addClass('notification-snap-alert error')
                        .html('<i class="bi bi-exclamation-circle-fill"></i> ' + errorMessage)
                        .appendTo('body');

                    setTimeout(() => {
                        notification.css('animation', 'fadeOut 0.3s ease-out forwards');
                        setTimeout(() => {
                            notification.remove();
                        }, 300);
                    }, 3000);
                }
            });
        });
</script>