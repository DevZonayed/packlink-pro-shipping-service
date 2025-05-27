<?php

/**
 * Checkout handling
 *
 * @package PacklinkCustomShipping
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class to handle checkout and order processing for Packlink shipping
 */
class Packlink_Checkout
{
    /**
     * Save shipping data to order meta
     *
     * @param int $order_id Order ID.
     * @return void
     */
    public static function save_shipping_data_to_order($order_id)
    {
        error_log("save_shipping_data_to_order called for order ID: $order_id");

        // Get order object
        $order = wc_get_order($order_id);
        if (!$order) {
            error_log("Invalid order object for ID: $order_id");
            return;
        }

        // Get shipping data from session
        $shipping_option_id = WC()->session->get('packlink_shipping_option_id');
        $shipping_price = WC()->session->get('packlink_shipping_price');
        $shipping_details = WC()->session->get('packlink_shipping_details');
        $drop_off_id = WC()->session->get('packlink_drop_off_id');
        $drop_off_details = WC()->session->get('packlink_drop_off_details');

        error_log("Session data - Option ID: " . print_r($shipping_option_id, true));
        error_log("Session data - Shipping details: " . print_r($shipping_details, true));

        // If session data is not available, try to get from cart items
        if (!$shipping_option_id || !$shipping_details) {
            error_log("Shipping data not found in session, checking cart items...");

            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                if (isset($cart_item['packlink_shipping_data']) && isset($cart_item['packlink_shipping_method_id'])) {
                    error_log("Found Packlink data in cart item");
                    $shipping_details = $cart_item['packlink_shipping_data'];
                    $shipping_option_id = $cart_item['packlink_shipping_method_id'];
                    $shipping_price = isset($cart_item['packlink_shipping_price']) ? $cart_item['packlink_shipping_price'] : 0;
                    break;
                }
            }
        }

        // Save data to order meta if available
        if ($shipping_option_id) {
            error_log("Saving shipping option ID to order meta: $shipping_option_id");
            update_post_meta($order_id, '_packlink_shipping_option_id', $shipping_option_id);

            // Also save to order items for redundancy
            foreach ($order->get_items() as $item) {
                $item->add_meta_data('packlink_shipping_method_id', $shipping_option_id, true);
                $item->save();
            }
        }

        if ($shipping_price) {
            error_log("Saving shipping price to order meta: $shipping_price");
            update_post_meta($order_id, '_packlink_shipping_price', $shipping_price);
        }

        if ($shipping_details) {
            error_log("Saving shipping details to order meta");
            update_post_meta($order_id, '_packlink_shipping_details', $shipping_details);

            // Also save to order items for redundancy
            foreach ($order->get_items() as $item) {
                $item->add_meta_data('packlink_shipping_data', $shipping_details, true);
                $item->save();
            }

            // Save detailed route and package information separately for display in order details
            if (isset($shipping_details['routes']) && is_array($shipping_details['routes'])) {
                $routes_info = array();
                foreach ($shipping_details['routes'] as $index => $route) {
                    $route_number = $index + 1;
                    $route_data = array(
                        'number' => $route_number,
                        'origin' => $route['origin_address'] . ', ' . $route['origin_postal_code'] . ' - ' . $route['origin_city'] . ', ' . $route['origin_country'],
                        'destination' => $route['destination_address'] . ', ' . $route['destination_postal_code'] . ' - ' . $route['destination_city'] . ', ' . $route['destination_country'],
                        'collection_date' => $route['collection_date'],
                        'shipping_method' => $route['shipping_method_name'],
                        'price' => $route['price'],
                        'sender' => array(
                            'name' => $route['sender_name'],
                            'email' => $route['sender_email'],
                            'phone' => $route['sender_phone'],
                            'company' => isset($route['sender_company']) ? $route['sender_company'] : ''
                        ),
                        'recipient' => array(
                            'name' => $route['recipient_name'],
                            'email' => $route['recipient_email'],
                            'phone' => $route['recipient_phone'],
                            'company' => isset($route['recipient_company']) ? $route['recipient_company'] : ''
                        )
                    );

                    if (isset($route['drop_off_id']) && !empty($route['drop_off_id'])) {
                        $route_data['drop_off'] = array(
                            'id' => $route['drop_off_id'],
                            'name' => isset($route['drop_off_name']) ? $route['drop_off_name'] : '',
                            'address' => isset($route['drop_off_address']) ? $route['drop_off_address'] : ''
                        );
                    }

                    $routes_info["route_{$route_number}"] = $route_data;
                }

                update_post_meta($order_id, '_packlink_routes_info', $routes_info);
            }

            if (isset($shipping_details['packages']) && is_array($shipping_details['packages'])) {
                $packages_info = array();
                foreach ($shipping_details['packages'] as $index => $package) {
                    $package_number = $index + 1;
                    $packages_info["package_{$package_number}"] = array(
                        'number' => $package_number,
                        'weight' => $package['weight'],
                        'dimensions' => $package['length'] . ' × ' . $package['width'] . ' × ' . $package['height']
                    );
                }

                update_post_meta($order_id, '_packlink_packages_info', $packages_info);
            }

            // Store total price
            if (isset($shipping_details['total_price']) && $shipping_details['total_price']) {
                update_post_meta($order_id, '_packlink_total_price', $shipping_details['total_price']);
            }
        }

        if ($drop_off_id) {
            error_log("Saving drop-off ID to order meta: $drop_off_id");
            update_post_meta($order_id, '_packlink_drop_off_id', $drop_off_id);
        }

        if ($drop_off_details) {
            error_log("Saving drop-off details to order meta");
            update_post_meta($order_id, '_packlink_drop_off_details', $drop_off_details);
        }

        // Clear session data
        WC()->session->__unset('packlink_shipping_option_id');
        WC()->session->__unset('packlink_shipping_price');
        WC()->session->__unset('packlink_shipping_details');
        WC()->session->__unset('packlink_drop_off_id');
        WC()->session->__unset('packlink_drop_off_details');

        error_log("Packlink shipping data saved to order $order_id");
    }

    /**
     * Populate checkout billing fields from packlink shipping data
     */
    public static function populate_checkout_fields()
    {
        // Get shipping data from session
        $shipping_details = WC()->session->get('packlink_shipping_details');

        // If no shipping data found, check cart items
        if (!$shipping_details) {
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                if (isset($cart_item['packlink_shipping_data'])) {
                    $shipping_details = $cart_item['packlink_shipping_data'];
                    break;
                }
            }
        }

        // If we have shipping details with routes and at least one route
        if ($shipping_details && isset($shipping_details['routes']) && !empty($shipping_details['routes'])) {
            // Get the first route information
            $first_route = reset($shipping_details['routes']);

            // Check if we have sender information
            if (isset($first_route['sender_name']) && !empty($first_route['sender_name'])) {
                // Extract first and last name from full name
                $name_parts = explode(' ', $first_route['sender_name']);
                $firstname = array_shift($name_parts);
                $lastname = implode(' ', $name_parts);

                // Populate billing fields with sender information
                WC()->customer->set_billing_first_name($firstname);
                WC()->customer->set_billing_last_name($lastname);

                if (isset($first_route['sender_email']) && !empty($first_route['sender_email'])) {
                    WC()->customer->set_billing_email($first_route['sender_email']);
                }

                if (isset($first_route['sender_phone']) && !empty($first_route['sender_phone'])) {
                    WC()->customer->set_billing_phone($first_route['sender_phone']);
                }

                if (isset($first_route['sender_company']) && !empty($first_route['sender_company'])) {
                    WC()->customer->set_billing_company($first_route['sender_company']);
                }

                // If address details are available, try to populate those as well
                if (isset($first_route['origin_address']) && !empty($first_route['origin_address'])) {
                    WC()->customer->set_billing_address_1($first_route['origin_address']);
                }

                if (isset($first_route['origin_city']) && !empty($first_route['origin_city'])) {
                    WC()->customer->set_billing_city($first_route['origin_city']);
                }

                if (isset($first_route['origin_postal_code']) && !empty($first_route['origin_postal_code'])) {
                    WC()->customer->set_billing_postcode($first_route['origin_postal_code']);
                }

                if (isset($first_route['origin_country']) && !empty($first_route['origin_country'])) {
                    WC()->customer->set_billing_country($first_route['origin_country']);
                }

                // Save the customer data
                WC()->customer->save();
            }
        }
    }

    /**
     * Create Packlink shipment when order status changes to processing
     *
     * @param int $order_id Order ID.
     * @return void
     */
    public static function create_packlink_shipment($order_id)
    {
        // Enhanced logging for debugging
        error_log("create_packlink_shipment called for order ID: $order_id");

        // Check if automatic shipment creation is enabled
        if (get_option('packlink_auto_create_shipment') !== 'yes') {
            error_log("Automatic shipment creation is disabled");
            return;
        }

        // Get order object
        $order = wc_get_order($order_id);
        if (!$order) {
            error_log("Invalid order object for ID: $order_id");
            return;
        }

        // Get shipping data from order meta
        $shipping_details = get_post_meta($order_id, '_packlink_shipping_details', true);

        error_log("Retrieved shipping details: " . print_r($shipping_details, true));

        // Check if this is a Packlink order with multi-route structure
        if (!$shipping_details || !isset($shipping_details['routes']) || !is_array($shipping_details['routes'])) {
            error_log("Shipping data not found in order meta or not in multi-route format, checking order items...");

            // Try to retrieve data from order items meta
            $items = $order->get_items();
            foreach ($items as $item) {
                $packlink_data = $item->get_meta('packlink_shipping_data');

                if ($packlink_data && isset($packlink_data['routes']) && is_array($packlink_data['routes'])) {
                    error_log("Found Packlink multi-route data in order item meta");
                    $shipping_details = $packlink_data;

                    // Save to order meta for future reference
                    update_post_meta($order_id, '_packlink_shipping_details', $shipping_details);
                    break;
                }
            }
        }

        // Final check if we have the required data
        if (!$shipping_details || !isset($shipping_details['routes']) || !is_array($shipping_details['routes'])) {
            error_log("Not a Packlink shipping order or missing required data");
            return; // Not a Packlink shipping order or missing required data
        }

        // Process each route and create a shipment for each
        foreach ($shipping_details['routes'] as $route_index => $route) {
            // Check if shipment already exists for this route
            $existing_shipment_id = get_post_meta($order_id, '_packlink_shipment_id_route_' . $route_index, true);
            if ($existing_shipment_id) {
                error_log("Shipment already exists for route $route_index with ID: $existing_shipment_id");
                continue; // Skip this route as shipment already exists
            }

            try {
                // Get shipping option ID for this route
                $shipping_option_id = $route['shipping_option_id'];
                if (!$shipping_option_id) {
                    error_log("Missing shipping option ID for route $route_index");
                    continue;
                }

                // Create packages array from shipping details
                $packages = array();
                if (isset($shipping_details['packages']) && is_array($shipping_details['packages'])) {
                    foreach ($shipping_details['packages'] as $package) {
                        $packages[] = array(
                            'weight' => floatval($package['weight']),
                            'length' => floatval($package['length']),
                            'width' => floatval($package['width']),
                            'height' => floatval($package['height'])
                        );
                    }
                } else {
                    // Fallback to default package dimensions
                    error_log("Using default package dimensions for route $route_index");
                    $packages[] = array(
                        'weight' => floatval(get_option('packlink_default_package_weight', '1')),
                        'length' => floatval(get_option('packlink_default_package_length', '10')),
                        'width' => floatval(get_option('packlink_default_package_width', '10')),
                        'height' => floatval(get_option('packlink_default_package_height', '10'))
                    );
                }

                error_log("Prepared packages for route $route_index: " . print_r($packages, true));

                // Use the sender and recipient data from the route
                // Create shipment data according to Packlink's required format
                $shipment_data = array(
                    'service_id' => $shipping_option_id,
                    'content' => sprintf(__('WooCommerce Order #%s - Route %d', 'packlink-custom-shipping'), $order_id, $route_index + 1),
                    'reference' => $order_id . '-' . ($route_index + 1),
                    'source' => 'woocommerce',
                    'packages' => $packages,
                    'collection_date' => $route['collection_date'],
                    'collection_time' => $route['collection_time'],
                    'sender' => array(
                        'name' => (strpos($route['sender_name'], ' ') !== false) ? substr($route['sender_name'], 0, strrpos($route['sender_name'], ' ')) : $route['sender_name'],
                        'surname' => (strpos($route['sender_name'], ' ') !== false) ? substr($route['sender_name'], strrpos($route['sender_name'], ' ') + 1) : 'Unknown',
                        'company' => isset($route['sender_company']) ? $route['sender_company'] : '',
                        'country' => $route['origin_country'],
                        'zip' => $route['origin_postal_code'],
                        'city' => $route['origin_city'],
                        'street1' => $route['origin_address'],
                        'phone' => $route['sender_phone'],
                        'email' => $route['sender_email']
                    ),
                    'receiver' => array(
                        'name' => (strpos($route['recipient_name'], ' ') !== false) ? substr($route['recipient_name'], 0, strrpos($route['recipient_name'], ' ')) : $route['recipient_name'],
                        'surname' => (strpos($route['recipient_name'], ' ') !== false) ? substr($route['recipient_name'], strrpos($route['recipient_name'], ' ') + 1) : 'Unknown',
                        'company' => isset($route['recipient_company']) ? $route['recipient_company'] : '',
                        'country' => $route['destination_country'],
                        'zip' => $route['destination_postal_code'],
                        'city' => $route['destination_city'],
                        'street1' => $route['destination_address'],
                        'phone' => $route['recipient_phone'],
                        'email' => $route['recipient_email']
                    )
                );

                // Add drop-off point if applicable
                if (isset($route['drop_off_id'])) {
                    $shipment_data['dropoff'] = array(
                        'id' => $route['drop_off_id']
                    );
                }

                // Create shipment in Packlink
                $shipment = new Packlink_Shipment();
                $result = $shipment->create($shipment_data);

                error_log("Shipment creation result for route $route_index: " . print_r($result, true));

                if (isset($result['reference'])) {
                    // Save Packlink shipment ID to order for this specific route
                    update_post_meta($order_id, '_packlink_shipment_id_route_' . $route_index, $result['reference']);

                    // Also save a list of all shipment IDs
                    $all_shipments = get_post_meta($order_id, '_packlink_all_shipments', true);
                    if (!is_array($all_shipments)) {
                        $all_shipments = array();
                    }
                    $all_shipments[$route_index] = $result['reference'];
                    update_post_meta($order_id, '_packlink_all_shipments', $all_shipments);

                    // Add order note
                    $order->add_order_note(sprintf(
                        __('Packlink shipment created successfully for route %d. Reference: %s', 'packlink-custom-shipping'),
                        $route_index + 1,
                        $result['id']
                    ));

                    error_log("Shipment created successfully for route $route_index with ID: " . $result['id']);
                } else {
                    throw new Exception(isset($result['message']) ? $result['message'] : __('Unknown error', 'packlink-custom-shipping'));
                }
            } catch (Exception $e) {
                error_log("Failed to create Packlink shipment for route $route_index: " . $e->getMessage());
                $order->add_order_note(sprintf(
                    __('Failed to create Packlink shipment for route %d: %s', 'packlink-custom-shipping'),
                    $route_index + 1,
                    $e->getMessage()
                ));
            }
        }

        // Update order status if configured and at least one shipment was created
        $all_shipments = get_post_meta($order_id, '_packlink_all_shipments', true);
        if (is_array($all_shipments) && !empty($all_shipments)) {
            $new_status = get_option('packlink_order_status_after_shipment');
            if ($new_status) {
                $order->update_status($new_status, __('Status updated after Packlink shipments creation.', 'packlink-custom-shipping'));
            }
        }
    }

    /**
     * Ensure Packlink data is saved to the order
     *
     * @param int $order_id Order ID.
     * @return void
     */
    public static function ensure_packlink_data_saved($order_id)
    {
        error_log("ensure_packlink_data_saved called for order ID: $order_id");

        // Check if Packlink data already exists
        $existing_shipping_details = get_post_meta($order_id, '_packlink_shipping_details', true);
        if ($existing_shipping_details && isset($existing_shipping_details['routes'])) {
            error_log("Packlink multi-route data already exists for order $order_id");
            return;
        }

        // Get order
        $order = wc_get_order($order_id);
        if (!$order) {
            error_log("Invalid order object for ID: $order_id");
            return;
        }

        // Check cart for Packlink data
        $packlink_data_found = false;
        foreach (WC()->cart->get_cart() as $cart_item) {
            if (isset($cart_item['packlink_shipping_data']) && isset($cart_item['packlink_shipping_data']['routes'])) {
                error_log("Found Packlink multi-route data in cart, saving to order $order_id");

                update_post_meta($order_id, '_packlink_shipping_details', $cart_item['packlink_shipping_data']);
                update_post_meta($order_id, '_packlink_shipping_price', $cart_item['packlink_shipping_price']);

                $packlink_data_found = true;
                break;
            }
        }

        // Check session as a fallback
        if (!$packlink_data_found) {
            $shipping_details = WC()->session->get('packlink_shipping_details');
            $shipping_price = WC()->session->get('packlink_shipping_price');

            if ($shipping_details && isset($shipping_details['routes'])) {
                error_log("Found Packlink multi-route data in session, saving to order $order_id");

                update_post_meta($order_id, '_packlink_shipping_details', $shipping_details);

                if ($shipping_price) {
                    update_post_meta($order_id, '_packlink_shipping_price', $shipping_price);
                }
            }
        }
    }

    /**
     * Add Packlink shipping fee to cart
     *
     * @param WC_Cart $cart Cart object.
     * @return void
     */
    public static function add_packlink_shipping_fee($cart)
    {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        // Check if we have Packlink shipping in the session
        $shipping_price = WC()->session->get('packlink_shipping_price');

        if ($shipping_price) {
            $cart->add_fee(__('Packlink Shipping', 'packlink-custom-shipping'), $shipping_price);
        }
    }

    /**
     * Add Packlink actions to order actions dropdown
     *
     * @param array $actions Order actions.
     * @return array Modified actions
     */
    public static function add_order_actions($actions)
    {
        global $theorder;

        // Check if this is a Packlink order
        if (!$theorder) {
            return $actions;
        }

        $order_id = $theorder->get_id();
        $shipping_details = get_post_meta($order_id, '_packlink_shipping_details', true);
        $all_shipments = get_post_meta($order_id, '_packlink_all_shipments', true);

        // Check if this is a multi-route Packlink order
        if ($shipping_details && isset($shipping_details['routes']) && is_array($shipping_details['routes'])) {
            // Check if there are any routes without shipments
            $missing_shipments = false;
            foreach ($shipping_details['routes'] as $route_index => $route) {
                $shipment_id = isset($all_shipments[$route_index]) ? $all_shipments[$route_index] : get_post_meta($order_id, '_packlink_shipment_id_route_' . $route_index, true);
                if (!$shipment_id) {
                    $missing_shipments = true;
                    break;
                }
            }

            if ($missing_shipments) {
                $actions['packlink_create_all_shipments'] = __('Create All Packlink Shipments', 'packlink-custom-shipping');
            }
        }

        return $actions;
    }

    /**
     * Process order action to create Packlink shipment
     *
     * @param WC_Order $order Order object.
     * @return void
     */
    public static function process_order_action_create_shipment($order)
    {
        $order_id = $order->get_id();

        // Get shipping data from order meta
        $shipping_option_id = get_post_meta($order_id, '_packlink_shipping_option_id', true);
        $shipping_details = get_post_meta($order_id, '_packlink_shipping_details', true);

        if (!$shipping_option_id || !$shipping_details) {
            return; // Not a Packlink shipping order
        }

        // Check if shipment already exists
        $existing_shipment_id = get_post_meta($order_id, '_packlink_shipment_id', true);
        if ($existing_shipment_id) {
            return; // Shipment already exists
        }

        try {
            // Create packages array from shipping details
            $packages = array();
            if (isset($shipping_details['packages']) && is_array($shipping_details['packages'])) {
                foreach ($shipping_details['packages'] as $package) {
                    $packages[] = array(
                        'weight' => floatval($package['weight']),
                        'length' => floatval($package['length']),
                        'width' => floatval($package['width']),
                        'height' => floatval($package['height'])
                    );
                }
            } else {
                // Fallback to single package
                $packages[] = array(
                    'weight' => floatval($shipping_details['weight']),
                    'length' => floatval($shipping_details['dimensions']['length']),
                    'width' => floatval($shipping_details['dimensions']['width']),
                    'height' => floatval($shipping_details['dimensions']['height'])
                );
            }

            // Create shipment data according to Packlink's required format
            $shipment_data = array(
                'service_id' => $shipping_option_id,
                'content' => sprintf(__('WooCommerce Order #%s', 'packlink-custom-shipping'), $order_id),
                'reference' => $order_id,
                'source' => 'woocommerce',
                'packages' => $packages,
                'sender' => array(
                    'name' => $shipping_details['sender_name'],
                    'company' => !empty($shipping_details['sender_company']) ? $shipping_details['sender_company'] : '',
                    'country' => $shipping_details['origin_country'],
                    'zip' => $shipping_details['origin_postal_code'],
                    'city' => $shipping_details['origin_city'],
                    'street1' => $shipping_details['origin_address'],
                    'phone' => $shipping_details['sender_phone'],
                    'email' => $shipping_details['sender_email']
                ),
                'receiver' => array(
                    'name' => $shipping_details['recipient_name'],
                    'company' => !empty($shipping_details['recipient_company']) ? $shipping_details['recipient_company'] : '',
                    'country' => $shipping_details['destination_country'],
                    'zip' => $shipping_details['destination_postal_code'],
                    'city' => $shipping_details['destination_city'],
                    'street1' => $shipping_details['destination_address'],
                    'phone' => $shipping_details['recipient_phone'],
                    'email' => $shipping_details['recipient_email']
                )
            );

            $drop_off_id = get_post_meta($order_id, '_packlink_drop_off_id', true);
            if ($drop_off_id) {
                $shipment_data['dropoff'] = array(
                    'id' => $drop_off_id
                );
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
            } else {
                throw new Exception(isset($result['message']) ? $result['message'] : __('Unknown error', 'packlink-custom-shipping'));
            }
        } catch (Exception $e) {
            $order->add_order_note(sprintf(
                __('Failed to create Packlink shipment: %s', 'packlink-custom-shipping'),
                $e->getMessage()
            ));
        }
    }

    /**
     * Process order action to create all Packlink shipments
     *
     * @param WC_Order $order Order object.
     * @return void
     */
    public static function process_order_action_create_all_shipments($order)
    {
        self::create_packlink_shipment($order->get_id(), null, $order);
    }

    /**
     * Get or create a virtual product for shipping
     *
     * @return int Product ID
     */
    public static function get_or_create_shipping_product()
    {
        // Check if product already exists
        $products = wc_get_products(array(
            'status' => 'publish',
            'limit' => 1,
            'meta_key' => '_packlink_shipping_product',
            'meta_value' => 'yes'
        ));

        if (!empty($products)) {
            return $products[0]->get_id();
        }

        // Create a new product
        $product = new WC_Product_Simple();
        $product->set_name('Packlink Shipping');
        $product->set_status('publish');
        $product->set_catalog_visibility('hidden');
        $product->set_price(0);
        $product->set_regular_price(0);
        $product->set_virtual(true);
        $product->set_sold_individually(true);
        $product->update_meta_data('_packlink_shipping_product', 'yes');
        $product->save();

        return $product->get_id();
    }
}
