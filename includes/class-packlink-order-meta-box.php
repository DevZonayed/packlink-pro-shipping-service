<?php

/**
 * Packlink Order Meta Box
 * 
 * Adds a meta box to the order edit screen for Packlink shipments
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Packlink_Order_Meta_Box
{
    /**
     * Initialize the meta box
     */
    public static function init()
    {
        add_action('add_meta_boxes', [self::class, 'add_meta_box']);
        add_action('wp_ajax_packlink_create_shipment', [self::class, 'ajax_create_shipment']);
        add_action('wp_ajax_packlink_create_route_shipment', [self::class, 'ajax_create_route_shipment']); // Add this line
        add_action('wp_ajax_packlink_get_tracking', [self::class, 'ajax_get_tracking']);
        add_action('wp_ajax_packlink_get_label', [self::class, 'ajax_get_label']);
    }

    /**
     * Add meta box to order edit screen
     */
    public static function add_meta_box()
    {
        add_meta_box(
            'packlink-shipment-meta-box',
            __('Packlink Shipping', 'packlink-custom-shipping'),
            [self::class, 'render_meta_box'],
            'shop_order',
            'side',
            'high'
        );
    }

    /**
     * Render meta box content
     * 
     * @param WP_Post $post Post object
     */
    public static function render_meta_box($post)
    {
        $order_id = $post->ID;
        $order = wc_get_order($order_id);

        if (!$order) {
            return;
        }

        // Get Packlink data
        $shipping_details = get_post_meta($order_id, '_packlink_shipping_details', true);
        $all_shipments = get_post_meta($order_id, '_packlink_all_shipments', true);

        // Check if this is a Packlink order
        if (!$shipping_details || !isset($shipping_details['routes'])) {
            echo '<p>' . __('This order was not shipped with Packlink.', 'packlink-custom-shipping') . '</p>';
            return;
        }

        // Output shipping details
        echo '<div class="packlink-order-details">';

        // Package information
        echo '<h4>' . __('Package Details', 'packlink-custom-shipping') . '</h4>';

        if (isset($shipping_details['packages']) && is_array($shipping_details['packages'])) {
            // Multiple packages
            foreach ($shipping_details['packages'] as $index => $package) {
                echo '<div class="packlink-package-details">';
                echo '<p><strong>' . sprintf(__('Package %d:', 'packlink-custom-shipping'), $index + 1) . '</strong> ';
                echo esc_html($package['weight'] . 'kg, ' .
                    $package['length'] . 'x' .
                    $package['width'] . 'x' .
                    $package['height'] . 'cm');
                echo '</p>';
                echo '</div>';
            }
        }

        // Sender and recipient information
        echo '<h4>' . __('Sender Information', 'packlink-custom-shipping') . '</h4>';
        echo '<p><strong>' . __('Name:', 'packlink-custom-shipping') . '</strong> ' . esc_html($shipping_details['sender_name']) . '</p>';
        echo '<p><strong>' . __('Email:', 'packlink-custom-shipping') . '</strong> ' . esc_html($shipping_details['sender_email']) . '</p>';
        echo '<p><strong>' . __('Phone:', 'packlink-custom-shipping') . '</strong> ' . esc_html($shipping_details['sender_phone']) . '</p>';

        if (!empty($shipping_details['sender_company'])) {
            echo '<p><strong>' . __('Company:', 'packlink-custom-shipping') . '</strong> ' . esc_html($shipping_details['sender_company']) . '</p>';
        }

        // Recipient information
        echo '<h4>' . __('Recipient Information', 'packlink-custom-shipping') . '</h4>';
        echo '<p><strong>' . __('Name:', 'packlink-custom-shipping') . '</strong> ' . esc_html($shipping_details['recipient_name']) . '</p>';
        echo '<p><strong>' . __('Email:', 'packlink-custom-shipping') . '</strong> ' . esc_html($shipping_details['recipient_email']) . '</p>';
        echo '<p><strong>' . __('Phone:', 'packlink-custom-shipping') . '</strong> ' . esc_html($shipping_details['recipient_phone']) . '</p>';

        if (!empty($shipping_details['recipient_company'])) {
            echo '<p><strong>' . __('Company:', 'packlink-custom-shipping') . '</strong> ' . esc_html($shipping_details['recipient_company']) . '</p>';
        }

        // Routes information
        echo '<h4>' . __('Routes', 'packlink-custom-shipping') . '</h4>';
        
        foreach ($shipping_details['routes'] as $route_index => $route) {
            $route_number = $route_index + 1;
            $shipment_id = isset($all_shipments[$route_index]) ? $all_shipments[$route_index] : get_post_meta($order_id, '_packlink_shipment_id_route_' . $route_index, true);
            
            echo '<div class="packlink-route-details">';
            echo '<h5>' . sprintf(__('Route %d', 'packlink-custom-shipping'), $route_number) . '</h5>';
            
            echo '<p><strong>' . __('From:', 'packlink-custom-shipping') . '</strong> ';
            echo esc_html($route['origin_city'] . ', ' . $route['origin_postal_code'] . ', ' . $route['origin_country']);
            echo '<br>' . esc_html($route['origin_address']);
            echo '</p>';

            echo '<p><strong>' . __('To:', 'packlink-custom-shipping') . '</strong> ';
            echo esc_html($route['destination_city'] . ', ' . $route['destination_postal_code'] . ', ' . $route['destination_country']);
            echo '<br>' . esc_html($route['destination_address']);
            echo '</p>';
            
            echo '<p><strong>' . __('Collection Date:', 'packlink-custom-shipping') . '</strong> ' . esc_html($route['collection_date']) . '</p>';
            
            // Drop-off point details if applicable
            if (isset($route['drop_off_id']) && isset($route['drop_off_details'])) {
                echo '<div class="packlink-dropoff-details">';
                echo '<p><strong>' . __('Drop-off Point:', 'packlink-custom-shipping') . '</strong> ' . esc_html($route['drop_off_details']['name']) . '</p>';
                echo '<p>' . esc_html($route['drop_off_details']['address']) . '<br>';
                echo esc_html($route['drop_off_details']['city'] . ', ' . $route['drop_off_details']['zip']) . '</p>';
                echo '</div>';
            }
            
            // Shipment details
            if ($shipment_id) {
                echo '<p><strong>' . __('Shipment ID:', 'packlink-custom-shipping') . '</strong> ' . esc_html($shipment_id) . '</p>';
                
                // Tracking and label buttons
                echo '<div class="packlink-actions">';
                echo '<button type="button" class="button packlink-tracking-button" data-shipment-id="' . esc_attr($shipment_id) . '">' .
                    __('View Tracking', 'packlink-custom-shipping') . '</button> ';
                echo '<button type="button" class="button packlink-label-button" data-shipment-id="' . esc_attr($shipment_id) . '">' .
                    __('Get Label', 'packlink-custom-shipping') . '</button>';
                echo '</div>';
                
                // Tracking info container
                echo '<div class="packlink-tracking-info" style="display:none;"></div>';
            } else {
                // Create shipment button for this route
                echo '<div class="packlink-actions">';
                echo '<button type="button" class="button button-primary packlink-create-route-shipment" data-order-id="' . esc_attr($order_id) . '" data-route-index="' . esc_attr($route_index) . '">' .
                    __('Create Shipment for This Route', 'packlink-custom-shipping') . '</button>';
                echo '</div>';
                
                // Shipment creation result container
                echo '<div class="packlink-shipment-result-route-' . esc_attr($route_index) . '" style="display:none;"></div>';
            }
            
            echo '</div>';
            
            // Add a separator between routes
            if ($route_index < count($shipping_details['routes']) - 1) {
                echo '<hr>';
            }
        }

        // Add JavaScript for AJAX actions
?>
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
                    var $trackingInfo = $('.packlink-tracking-info');

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
<?php
    }

    /**
     * AJAX handler for creating a shipment
     */
    public static function ajax_create_shipment()
    {
        // Verify nonce
        if (!check_ajax_referer('packlink_order_actions', 'nonce', false)) {
            wp_send_json_error([
                'message' => __('Invalid security token.', 'packlink-custom-shipping')
            ]);
        }

        // Check if order ID is provided
        if (!isset($_POST['order_id'])) {
            wp_send_json_error([
                'message' => __('Missing order ID.', 'packlink-custom-shipping')
            ]);
        }

        $order_id = intval($_POST['order_id']);
        $order = wc_get_order($order_id);

        if (!$order) {
            wp_send_json_error([
                'message' => __('Invalid order.', 'packlink-custom-shipping')
            ]);
        }

        // Check if shipment already exists
        $existing_shipment_id = get_post_meta($order_id, '_packlink_shipment_id', true);
        if ($existing_shipment_id) {
            wp_send_json_error([
                'message' => __('A shipment already exists for this order.', 'packlink-custom-shipping')
            ]);
        }

        // Get shipping data from order meta
        $shipping_option_id = get_post_meta($order_id, '_packlink_shipping_option_id', true);
        $shipping_details = get_post_meta($order_id, '_packlink_shipping_details', true);

        if (!$shipping_option_id || !$shipping_details) {
            wp_send_json_error([
                'message' => __('Missing shipping data for this order.', 'packlink-custom-shipping')
            ]);
        }

        try {
            // Create shipment data
            $shipment_data = [
                'service_id' => $shipping_option_id,
                'order_id' => $order_id,
                'content' => sprintf(__('WooCommerce Order #%s', 'packlink-custom-shipping'), $order_id),
                'source' => 'woocommerce',
                'collection_time' => $shipping_details['collection_time'],
                'collection_date' => $shipping_details['collection_date'],
                'packages' => [
                    [
                        'weight' => $shipping_details['weight'],
                        'length' => $shipping_details['dimensions']['length'],
                        'width' => $shipping_details['dimensions']['width'],
                        'height' => $shipping_details['dimensions']['height']
                    ]
                ],
                'sender' => [
                    'name' => get_option('packlink_sender_name', get_bloginfo('name')),
                    'company' => get_option('packlink_sender_company', get_bloginfo('name')),
                    'country' => $shipping_details['origin_country'],
                    'postal_code' => $shipping_details['origin_postal_code'],
                    'city' => $shipping_details['origin_city'],
                    'phone' => get_option('packlink_sender_phone', ''),
                    'email' => get_option('packlink_sender_email', get_option('admin_email'))
                ],
                'recipient' => [
                    'name' => $order->get_formatted_billing_full_name(),
                    'country' => $shipping_details['destination_country'],
                    'postal_code' => $shipping_details['destination_postal_code'],
                    'city' => $shipping_details['destination_city'],
                    'street' => $order->get_billing_address_1(),
                    'phone' => $order->get_billing_phone(),
                    'email' => $order->get_billing_email()
                ]
            ];

            // Add drop-off point if applicable
            $drop_off_id = get_post_meta($order_id, '_packlink_drop_off_id', true);
            if ($drop_off_id) {
                $shipment_data['drop_off_point_id'] = $drop_off_id;
            }

            // Create shipment in Packlink
            $shipment = new Packlink_Shipment();
            $result = $shipment->create($shipment_data);

            if (isset($result['id'])) {
                // Save Packlink shipment ID to order
                update_post_meta($order_id, '_packlink_shipment_id', $result['id']);

                // Add order note
                $order->add_order_note(sprintf(
                    __('Packlink shipment created successfully. Reference: %s', 'packlink-custom-shipping'),
                    $result['id']
                ));

                // Update order status if configured
                $new_status = get_option('packlink_order_status_after_shipment');
                if ($new_status) {
                    $order->update_status($new_status, __('Status updated after Packlink shipment creation.', 'packlink-custom-shipping'));
                }

                wp_send_json_success([
                    'message' => __('Shipment created successfully.', 'packlink-custom-shipping'),
                    'shipment_id' => $result['id']
                ]);
            } else {
                throw new Exception(isset($result['message']) ? $result['message'] : __('Unknown error', 'packlink-custom-shipping'));
            }
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => sprintf(__('Failed to create shipment: %s', 'packlink-custom-shipping'), $e->getMessage())
            ]);
        }
    }

    /**
     * AJAX handler for getting tracking information
     */
    public static function ajax_get_tracking()
    {
        error_log('Arrived get_tracking_info');
        // Verify nonce
        if (!check_ajax_referer('packlink_order_actions', 'nonce', false)) {
            wp_send_json_error([
                'message' => __('Invalid security token.', 'packlink-custom-shipping')
            ]);
        }

        // Check if shipment ID is provided
        if (!isset($_POST['shipment_id'])) {
            wp_send_json_error([
                'message' => __('Missing shipment ID.', 'packlink-custom-shipping')
            ]);
        }

        $shipment_id = sanitize_text_field($_POST['shipment_id']);

        try {
            // Get tracking information
            $shipment = new Packlink_Shipment();
            $tracking = $shipment->get_tracking($shipment_id);

            if (is_wp_error($tracking)) {
                throw new Exception($tracking->get_error_message());
            }

            wp_send_json_success([
                'tracking_url' => isset($tracking['tracking_url']) ? $tracking['tracking_url'] : '',
                'history' => isset($tracking['history']) ? $tracking['history'] : []
            ]);
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => sprintf(__('Failed to get tracking information: %s', 'packlink-custom-shipping'), $e->getMessage())
            ]);
        }
    }

    /**
     * AJAX handler for getting shipping label
     */
    public static function ajax_get_label()
    {
        // Verify nonce
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'packlink_order_actions')) {
            wp_die(__('Invalid security token.', 'packlink-custom-shipping'));
        }

        // Check if shipment ID is provided
        if (!isset($_GET['shipment_id'])) {
            wp_die(__('Missing shipment ID.', 'packlink-custom-shipping'));
        }

        $shipment_id = sanitize_text_field($_GET['shipment_id']);

        try {
            // Get label
            $shipment = new Packlink_Shipment();
            $label = $shipment->get_label($shipment_id);

            if (is_wp_error($label)) {
                throw new Exception($label->get_error_message());
            }

            if (isset($label['url'])) {
                // Redirect to label URL
                wp_redirect($label['url']);
                exit;
            } else {
                throw new Exception(__('Label URL not found in response.', 'packlink-custom-shipping'));
            }
        } catch (Exception $e) {
            wp_die(sprintf(__('Failed to get shipping label: %s', 'packlink-custom-shipping'), $e->getMessage()));
        }
    }

    /**
     * AJAX handler for creating a shipment for a specific route
     */
    public static function ajax_create_route_shipment()
    {
        // Verify nonce
        if (!check_ajax_referer('packlink_order_actions', 'nonce', false)) {
            wp_send_json_error([
                'message' => __('Invalid security token.', 'packlink-custom-shipping')
            ]);
        }

        // Check if order ID and route index are provided
        if (!isset($_POST['order_id']) || !isset($_POST['route_index'])) {
            wp_send_json_error([
                'message' => __('Missing order ID or route index.', 'packlink-custom-shipping')
            ]);
        }

        $order_id = intval($_POST['order_id']);
        $route_index = intval($_POST['route_index']);
        
        $order = wc_get_order($order_id);

        if (!$order) {
            wp_send_json_error([
                'message' => __('Invalid order.', 'packlink-custom-shipping')
            ]);
        }

        // Check if shipment already exists for this route
        $existing_shipment_id = get_post_meta($order_id, '_packlink_shipment_id_route_' . $route_index, true);
        if ($existing_shipment_id) {
            wp_send_json_error([
                'message' => __('A shipment already exists for this route.', 'packlink-custom-shipping')
            ]);
        }

        // Get shipping data from order meta
        $shipping_details = get_post_meta($order_id, '_packlink_shipping_details', true);

        if (!$shipping_details || !isset($shipping_details['routes']) || !isset($shipping_details['routes'][$route_index])) {
            wp_send_json_error([
                'message' => __('Missing shipping data for this route.', 'packlink-custom-shipping')
            ]);
        }

        $route = $shipping_details['routes'][$route_index];

        try {
            // Create packages array from shipping details
            $packages = [];
            if (isset($shipping_details['packages']) && is_array($shipping_details['packages'])) {
                foreach ($shipping_details['packages'] as $package) {
                    $packages[] = [
                        'weight' => floatval($package['weight']),
                        'length' => floatval($package['length']),
                        'width' => floatval($package['width']),
                        'height' => floatval($package['height'])
                    ];
                }
            } else {
                // Fallback to default package dimensions
                $packages[] = [
                    'weight' => floatval(get_option('packlink_default_package_weight', '1')),
                    'length' => floatval(get_option('packlink_default_package_length', '10')),
                    'width' => floatval(get_option('packlink_default_package_width', '10')),
                    'height' => floatval(get_option('packlink_default_package_height', '10'))
                ];
            }

            // Prepare sender and receiver data
            $sender_name = isset($shipping_details['sender_name']) ? $shipping_details['sender_name'] : get_option('packlink_sender_name', get_bloginfo('name'));
            $sender_company = isset($shipping_details['sender_company']) ? $shipping_details['sender_company'] : get_option('packlink_sender_company', '');
            $sender_phone = isset($shipping_details['sender_phone']) ? $shipping_details['sender_phone'] : get_option('packlink_sender_phone', '');
            $sender_email = isset($shipping_details['sender_email']) ? $shipping_details['sender_email'] : get_option('packlink_sender_email', get_option('admin_email'));

            // Create shipment data
            $shipment_data = [
                'service_id' => $route['shipping_option_id'],
                'content' => sprintf(__('WooCommerce Order #%s - Route %d', 'packlink-custom-shipping'), $order_id, $route_index + 1),
                'reference' => $order_id . '-' . ($route_index + 1),
                'source' => 'woocommerce',
                'packages' => $packages,
                'collection_date' => $route['collection_date'],
                'collection_time' => $route['collection_time'],
                'sender' => [
                    'name' => $sender_name,
                    'company' => $sender_company,
                    'country' => $route['origin_country'],
                    'zip' => $route['origin_postal_code'],
                    'city' => $route['origin_city'],
                    'street1' => $route['origin_address'],
                    'phone' => $sender_phone,
                    'email' => $sender_email
                ],
                'receiver' => [
                    'name' => isset($shipping_details['recipient_name']) ? $shipping_details['recipient_name'] : $order->get_formatted_shipping_full_name(),
                    'company' => isset($shipping_details['recipient_company']) ? $shipping_details['recipient_company'] : $order->get_shipping_company(),
                    'country' => $route['destination_country'],
                    'zip' => $route['destination_postal_code'],
                    'city' => $route['destination_city'],
                    'street1' => $route['destination_address'],
                    'phone' => isset($shipping_details['recipient_phone']) ? $shipping_details['recipient_phone'] : $order->get_billing_phone(),
                    'email' => isset($shipping_details['recipient_email']) ? $shipping_details['recipient_email'] : $order->get_billing_email()
                ]
            ];

            // Add drop-off point if applicable
            if (isset($route['drop_off_id'])) {
                $shipment_data['dropoff'] = [
                    'id' => $route['drop_off_id']
                ];
            }

            // Create shipment in Packlink
            $shipment = new Packlink_Shipment();
            $result = $shipment->create($shipment_data);

            if (isset($result['id'])) {
                // Save Packlink shipment ID to order for this specific route
                update_post_meta($order_id, '_packlink_shipment_id_route_' . $route_index, $result['id']);

                // Also save a list of all shipment IDs
                $all_shipments = get_post_meta($order_id, '_packlink_all_shipments', true);
                if (!is_array($all_shipments)) {
                    $all_shipments = [];
                }
                $all_shipments[$route_index] = $result['id'];
                update_post_meta($order_id, '_packlink_all_shipments', $all_shipments);

                // Add order note
                $order->add_order_note(sprintf(
                    __('Packlink shipment created successfully for route %d. Reference: %s', 'packlink-custom-shipping'),
                    $route_index + 1,
                    $result['id']
                ));

                wp_send_json_success([
                    'message' => sprintf(__('Shipment created successfully for route %d.', 'packlink-custom-shipping'), $route_index + 1),
                    'shipment_id' => $result['id']
                ]);
            } else {
                throw new Exception(isset($result['message']) ? $result['message'] : __('Unknown error', 'packlink-custom-shipping'));
            }
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => sprintf(__('Failed to create shipment for route %d: %s', 'packlink-custom-shipping'), $route_index + 1, $e->getMessage())
            ]);
        }
    }
}

// Initialize the meta box
Packlink_Order_Meta_Box::init();
