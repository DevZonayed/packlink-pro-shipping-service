jQuery(document).ready(function($) {
    const trackingForm = $('#packlink-tracking-form');
    const trackingResults = $('#packlink-tracking-results');
    const trackingError = $('#tracking-error');
    const trackingLoading = $('#tracking-loading');
    
    // Initialize the tracking form
    trackingForm.on('submit', function(e) {
        e.preventDefault();
        
        const referenceNumber = $('#tracking_reference').val().trim();
        
        if (!referenceNumber) {
            alert(packlink_tracking.enter_reference);
            return;
        }
        
        // Show loading indicator
        trackingResults.hide();
        trackingError.hide();
        trackingLoading.show();
        
        // Make AJAX request to get tracking information
        $.ajax({
            url: packlink_tracking.ajax_url,
            type: 'POST',
            data: {
                action: 'packlink_get_tracking',
                nonce: $('#packlink_tracking_nonce').val(), // Changed from security to nonce
                reference: referenceNumber
            },
            success: function(response) {
                trackingLoading.hide();
                
                if (response.success && response.data) {
                    displayTrackingInfo(response.data);
                    trackingResults.show();
                } else {
                    trackingError.show();
                }
            },
            error: function() {
                trackingLoading.hide();
                trackingError.show();
            }
        });
    });
    // Display tracking information
    function displayTrackingInfo(data) {
        // Update status text and color
        updateShipmentStatus(data.status);
        
        // Update shipment info
        updateShipmentInfo(data.shipment);
        
        // Update tracking timeline
        updateTrackingTimeline(data.tracking);
    }
    
    // Update shipment status
    function updateShipmentStatus(status) {
        const statusElement = $('#status-text');
        const statusCircle = $('.status-circle');
        
        // Reset classes
        statusCircle.removeClass('status-pending status-transit status-delivered status-exception');
        
        // Set status text and color based on status code
        statusElement.text(getStatusText(status));
        
        // Add appropriate status class
        if (status === 'DELIVERED') {
            statusCircle.addClass('status-delivered');
        } else if (status === 'IN_TRANSIT') {
            statusCircle.addClass('status-transit');
        } else if (status === 'EXCEPTION' || status === 'CANCELLED') {
            statusCircle.addClass('status-exception');
        } else {
            statusCircle.addClass('status-pending');
        }
    }
    
    // Get human-readable status text
    function getStatusText(status) {
        const statusMap = {
            'DELIVERED': packlink_tracking.status_delivered,
            'IN_TRANSIT': packlink_tracking.status_in_transit,
            'EXCEPTION': packlink_tracking.status_exception,
            'CANCELLED': packlink_tracking.status_cancelled,
            'PENDING': packlink_tracking.status_pending,
            'READY_TO_SHIP': packlink_tracking.status_ready_to_ship,
            'READY_TO_PURCHASE': packlink_tracking.status_ready_to_purchase || 'Ready to be purchased'
        };
        
        return statusMap[status] || packlink_tracking.status_unknown;
    }
    
    // Update shipment information
    function updateShipmentInfo(shipment) {
        if (!shipment) return;
        
        let shipmentHtml = `
            <div class="shipment-info-grid">
                <div class="shipment-info-item">
                    <span class="info-label">${packlink_tracking.carrier}:</span>
                    <span class="info-value">${shipment.carrier || '-'}</span>
                </div>
                <div class="shipment-info-item">
                    <span class="info-label">${packlink_tracking.service}:</span>
                    <span class="info-value">${shipment.service || '-'}</span>
                </div>
                <div class="shipment-info-item">
                    <span class="info-label">${packlink_tracking.reference}:</span>
                    <span class="info-value">${shipment.reference || '-'}</span>
                </div>
                <div class="shipment-info-item">
                    <span class="info-label">${packlink_tracking.created_date}:</span>
                    <span class="info-value">${formatDate(shipment.date_created) || '-'}</span>
                </div>
                ${shipment.tracking_url ? `
                <div class="shipment-info-item tracking-url-item">
                    <span class="info-label">${packlink_tracking.tracking_link}:</span>
                    <span class="info-value"><a href="${shipment.tracking_url}" target="_blank">${packlink_tracking.view_on_carrier_site}</a></span>
                </div>` : ''}
            </div>
            
            <div class="shipment-locations">
                <div class="location-from">
                    <h5>${packlink_tracking.from}</h5>
                    <p>${formatAddress(shipment.from) || '-'}</p>
                </div>
                <div class="location-arrow">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-right"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </div>
                <div class="location-to">
                    <h5>${packlink_tracking.to}</h5>
                    <p>${formatAddress(shipment.to) || '-'}</p>
                </div>
            </div>
        `;
        
        $('#shipment-info').html(shipmentHtml);
    }
    
    // Format address for display
    function formatAddress(address) {
        if (!address) return '';
        
        const parts = [];
        
        if (address.name) parts.push(address.name);
        if (address.street1) parts.push(address.street1);
        if (address.street2) parts.push(address.street2);
        
        const cityParts = [];
        if (address.city) cityParts.push(address.city);
        if (address.zip_code) cityParts.push(address.zip_code);
        if (cityParts.length) parts.push(cityParts.join(', '));
        
        if (address.country) parts.push(address.country);
        
        return parts.join('<br>');
    }
    
    // Update tracking timeline
    function updateTrackingTimeline(tracking) {
        if (!tracking || !tracking.length) {
            $('#tracking-timeline').html(`<p class="no-tracking">${packlink_tracking.no_tracking_available}</p>`);
            return;
        }
        
        let timelineHtml = '<ul class="timeline-events">';
        
        // Sort tracking events by date (newest first)
        tracking.sort((a, b) => {
            return new Date(b.date) - new Date(a.date);
        });
        
        tracking.forEach(event => {
            timelineHtml += `
                <li class="timeline-event">
                    <div class="event-date">${formatDate(event.date)}</div>
                    <div class="event-content">
                        <div class="event-title">${event.status || event.description}</div>
                        ${event.location ? `<div class="event-location">${event.location}</div>` : ''}
                        ${event.description && event.description !== event.status ? `<div class="event-description">${event.description}</div>` : ''}
                    </div>
                </li>
            `;
        });
        
        timelineHtml += '</ul>';
        $('#tracking-timeline').html(timelineHtml);
    }
    
    // Format date for display
    function formatDate(dateString) {
        if (!dateString) return '';
        
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return dateString;
        
        return date.toLocaleString(undefined, {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
});