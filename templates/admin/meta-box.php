<div class="packlink-order-details">
    <!-- Package information -->
    <h4><?php _e('Package Details', 'packlink-custom-shipping'); ?></h4>

    <?php if (isset($shipping_details['packages']) && is_array($shipping_details['packages'])): ?>
        <!-- Multiple packages -->
        <?php foreach ($shipping_details['packages'] as $index => $package): ?>
            <div class="packlink-package-details">
                <p><strong><?php printf(__('Package %d:', 'packlink-custom-shipping'), $index + 1); ?></strong> 
                <?php echo esc_html($package['weight'] . 'kg, ' .
                    $package['length'] . 'x' .
                    $package['width'] . 'x' .
                    $package['height'] . 'cm'); ?>
                </p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Sender and recipient information -->
    <h4><?php _e('Sender Information', 'packlink-custom-shipping'); ?></h4>
    <p><strong><?php _e('Name:', 'packlink-custom-shipping'); ?></strong> <?php echo esc_html($shipping_details['sender_name']); ?></p>
    <p><strong><?php _e('Email:', 'packlink-custom-shipping'); ?></strong> <?php echo esc_html($shipping_details['sender_email']); ?></p>
    <p><strong><?php _e('Phone:', 'packlink-custom-shipping'); ?></strong> <?php echo esc_html($shipping_details['sender_phone']); ?></p>

    <?php if (!empty($shipping_details['sender_company'])): ?>
        <p><strong><?php _e('Company:', 'packlink-custom-shipping'); ?></strong> <?php echo esc_html($shipping_details['sender_company']); ?></p>
    <?php endif; ?>

    <!-- Recipient information -->
    <h4><?php _e('Recipient Information', 'packlink-custom-shipping'); ?></h4>
    <p><strong><?php _e('Name:', 'packlink-custom-shipping'); ?></strong> <?php echo esc_html($shipping_details['recipient_name']); ?></p>
    <p><strong><?php _e('Email:', 'packlink-custom-shipping'); ?></strong> <?php echo esc_html($shipping_details['recipient_email']); ?></p>
    <p><strong><?php _e('Phone:', 'packlink-custom-shipping'); ?></strong> <?php echo esc_html($shipping_details['recipient_phone']); ?></p>

    <?php if (!empty($shipping_details['recipient_company'])): ?>
        <p><strong><?php _e('Company:', 'packlink-custom-shipping'); ?></strong> <?php echo esc_html($shipping_details['recipient_company']); ?></p>
    <?php endif; ?>

    <!-- Routes information -->
    <h4><?php _e('Routes', 'packlink-custom-shipping'); ?></h4>
    
    <?php foreach ($shipping_details['routes'] as $route_index => $route): ?>
        <?php
        $route_number = $route_index + 1;
        $shipment_id = isset($all_shipments[$route_index]) ? $all_shipments[$route_index] : get_post_meta($order_id, '_packlink_shipment_id_route_' . $route_index, true);
        ?>
        
        <div class="packlink-route-details">
            <h5><?php printf(__('Route %d', 'packlink-custom-shipping'), $route_number); ?></h5>
            
            <p><strong><?php _e('From:', 'packlink-custom-shipping'); ?></strong> 
            <?php echo esc_html($route['origin_city'] . ', ' . $route['origin_postal_code'] . ', ' . $route['origin_country']); ?>
            <br><?php echo esc_html($route['origin_address']); ?>
            </p>

            <p><strong><?php _e('To:', 'packlink-custom-shipping'); ?></strong> 
            <?php echo esc_html($route['destination_city'] . ', ' . $route['destination_postal_code'] . ', ' . $route['destination_country']); ?>
            <br><?php echo esc_html($route['destination_address']); ?>
            </p>
            
            <p><strong><?php _e('Collection Date:', 'packlink-custom-shipping'); ?></strong> <?php echo esc_html($route['collection_date']); ?></p>
            
            <!-- Drop-off point details if applicable -->
            <?php if (isset($route['drop_off_id']) && isset($route['drop_off_details'])): ?>
                <div class="packlink-dropoff-details">
                    <p><strong><?php _e('Drop-off Point:', 'packlink-custom-shipping'); ?></strong> <?php echo esc_html($route['drop_off_details']['name']); ?></p>
                    <p><?php echo esc_html($route['drop_off_details']['address']); ?><br>
                    <?php echo esc_html($route['drop_off_details']['city'] . ', ' . $route['drop_off_details']['zip']); ?></p>
                </div>
            <?php endif; ?>
            
            <!-- Shipment details -->
            <?php if ($shipment_id): ?>
                <p><strong><?php _e('Shipment ID:', 'packlink-custom-shipping'); ?></strong> <?php echo esc_html($shipment_id); ?></p>
                
                <!-- Tracking and label buttons -->
                <div class="packlink-actions">
                    <button type="button" class="button packlink-tracking-button" data-shipment-id="<?php echo esc_attr($shipment_id); ?>">
                        <?php _e('View Tracking', 'packlink-custom-shipping'); ?>
                    </button> 
                    <button type="button" class="button packlink-label-button" data-shipment-id="<?php echo esc_attr($shipment_id); ?>">
                        <?php _e('Get Label', 'packlink-custom-shipping'); ?>
                    </button>
                </div>
                
                <!-- Tracking info container -->
                <div class="packlink-tracking-info" style="display:none;"></div>
            <?php else: ?>
                <!-- Create shipment button for this route -->
                <div class="packlink-actions">
                    <button type="button" class="button button-primary packlink-create-route-shipment" data-order-id="<?php echo esc_attr($order_id); ?>" data-route-index="<?php echo esc_attr($route_index); ?>">
                        <?php _e('Create Shipment for This Route', 'packlink-custom-shipping'); ?>
                    </button>
                </div>
                
                <!-- Shipment creation result container -->
                <div class="packlink-shipment-result-route-<?php echo esc_attr($route_index); ?>" style="display:none;"></div>
            <?php endif; ?>
            
        </div>
        
        <!-- Add a separator between routes -->
        <?php if ($route_index < count($shipping_details['routes']) - 1): ?>
            <hr>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

<!-- Add JavaScript for AJAX actions -->
<script>
    jQuery(document).ready(function($) {
        // Create shipment for a specific route
        $('.packlink-create-route-shipment').on('click', function() {
            var $button = $(this);
            var orderId = $button.data('order-id');
            var routeIndex = $button.data('route-index');

            $button.prop('disabled', true).text('<?php _e('Creating...', 'packlink-custom-shipping'); ?>');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'packlink_create_route_shipment',
                    order_id: orderId,
                    route_index: routeIndex,
                    nonce: '<?php echo wp_create_nonce('packlink_order_actions'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $('.packlink-shipment-result-route-' + routeIndex).html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>').show();
                        // Reload page after a short delay
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        $('.packlink-shipment-result-route-' + routeIndex).html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>').show();
                        $button.prop('disabled', false).text('<?php _e('Create Shipment for This Route', 'packlink-custom-shipping'); ?>');
                    }
                },
                error: function() {
                    $('.packlink-shipment-result-route-' + routeIndex).html('<div class="notice notice-error inline"><p><?php _e('An error occurred while creating the shipment.', 'packlink-custom-shipping'); ?></p></div>').show();
                    $button.prop('disabled', false).text('<?php _e('Create Shipment for This Route', 'packlink-custom-shipping'); ?>');
                }
            });
        });

        // Get tracking info
        $('.packlink-tracking-button').on('click', function() {
            var $button = $(this);
            var shipmentId = $button.data('shipment-id');
            var $trackingInfo = $button.closest('.packlink-route-details').find('.packlink-tracking-info');

            if ($trackingInfo.is(':visible')) {
                $trackingInfo.slideUp();
                return;
            }

            $button.prop('disabled', true);
            $trackingInfo.html('<p><?php _e('Loading tracking information...', 'packlink-custom-shipping'); ?></p>').show();

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'packlink_get_tracking',
                    shipment_id: shipmentId,
                    nonce: '<?php echo wp_create_nonce('packlink_order_actions'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        var trackingHtml = '<h4><?php _e('Tracking Information', 'packlink-custom-shipping'); ?></h4>';

                        if (response.data.tracking_url) {
                            trackingHtml += '<p><a href="' + response.data.tracking_url + '" target="_blank"><?php _e('View Tracking Page', 'packlink-custom-shipping'); ?></a></p>';
                        }

                        if (response.data.history && response.data.history.length > 0) {
                            trackingHtml += '<ul class="packlink-tracking-history">';
                            $.each(response.data.history, function(index, item) {
                                trackingHtml += '<li><strong>' + item.date + '</strong>: ' + item.description + '</li>';
                            });
                            trackingHtml += '</ul>';
                        } else {
                            trackingHtml += '<p><?php _e('No tracking history available yet.', 'packlink-custom-shipping'); ?></p>';
                        }

                        $trackingInfo.html(trackingHtml);
                    } else {
                        $trackingInfo.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                    }
                    $button.prop('disabled', false);
                },
                error: function() {
                    $trackingInfo.html('<div class="notice notice-error inline"><p><?php _e('An error occurred while retrieving tracking information.', 'packlink-custom-shipping'); ?></p></div>');
                    $button.prop('disabled', false);
                }
            });
        });

        // Get shipping label
        $('.packlink-label-button').on('click', function() {
            var shipmentId = $(this).data('shipment-id');
            window.open(ajaxurl + '?action=packlink_get_label&shipment_id=' + shipmentId + '&nonce=<?php echo wp_create_nonce('packlink_order_actions'); ?>', '_blank');
        });
    });
</script>

<style>
    .packlink-order-details h4 {
        margin: 1.33em 0 0.5em;
        border-bottom: 1px solid #eee;
        padding-bottom: 3px;
    }

    .packlink-actions {
        margin: 15px 0;
    }

    .packlink-tracking-history {
        margin: 10px 0;
        padding-left: 20px;
    }

    .packlink-tracking-history li {
        margin-bottom: 5px;
    }

    .packlink-package-details {
        margin-bottom: 8px;
        padding-left: 10px;
        border-left: 3px solid #eee;
    }

    .packlink-route-details {
        margin-bottom: 15px;
        padding: 10px;
        background-color: #f9f9f9;
        border-radius: 4px;
        border: 1px solid #eee;
    }
    
    .packlink-route-details h5 {
        margin-top: 0;
        color: #0073aa;
        border-bottom: 1px solid #eee;
        padding-bottom: 5px;
    }
    
    .packlink-dropoff-details {
        margin-top: 10px;
        padding: 8px;
        background-color: #f0f0f0;
        border-radius: 3px;
    }
</style>