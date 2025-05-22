<?php
/**
 * AJAX handler class
 *
 * @package PacklinkCustomShipping
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class to handle AJAX operations for Packlink
 */
class Packlink_Ajax {
    /**
     * Initialize AJAX hooks
     *
     * @return void
     */
    public static function init() {
        // Register AJAX handlers
        add_action('wp_ajax_get_packlink_shipping_rates', array(self::class, 'get_shipping_rates'));
        add_action('wp_ajax_nopriv_get_packlink_shipping_rates', array(self::class, 'get_shipping_rates'));

        add_action('wp_ajax_get_packlink_drop_off_locations', array(self::class, 'get_drop_off_locations'));
        add_action('wp_ajax_nopriv_get_packlink_drop_off_locations', array(self::class, 'get_drop_off_locations'));

        add_action('wp_ajax_add_packlink_shipping_to_cart', array(self::class, 'add_shipping_to_cart'));
        add_action('wp_ajax_nopriv_add_packlink_shipping_to_cart', array(self::class, 'add_shipping_to_cart'));
        
        // AJAX handlers for both logged in and non-logged in users
        add_action('wp_ajax_packlink_get_destinations', array(self::class, 'get_destinations'));
        add_action('wp_ajax_nopriv_packlink_get_destinations', array(self::class, 'get_destinations'));
        
        // AJAX handler for postal code search
        add_action('wp_ajax_packlink_search_postal_code', array(self::class, 'search_postal_code'));
        add_action('wp_ajax_nopriv_packlink_search_postal_code', array(self::class, 'search_postal_code'));
        
        // AJAX handler for postal code suggestions
        add_action('wp_ajax_packlink_suggest_postal_codes', array(self::class, 'suggest_postal_codes'));
        add_action('wp_ajax_nopriv_packlink_suggest_postal_codes', array(self::class, 'suggest_postal_codes'));
        
        // AJAX handler for getting formatted destinations
        add_action('wp_ajax_packlink_get_formatted_destinations', array(self::class, 'get_formatted_destinations'));
        add_action('wp_ajax_nopriv_packlink_get_formatted_destinations', array(self::class, 'get_formatted_destinations'));
        
        // Add tracking AJAX handler
        add_action('wp_ajax_packlink_get_tracking', array(self::class, 'get_tracking_info'));
        add_action('wp_ajax_nopriv_packlink_get_tracking', array(self::class, 'get_tracking_info'));
    }
    
    /**
     * Get shipping rates via AJAX
     *
     * @return void
     */
    public static function get_shipping_rates() {
        try {
            // Verify nonce
            if (!check_ajax_referer('packlink_shipping_nonce', 'nonce', false)) {
                throw new Exception('Invalid security token');
            }

            // Validate required fields
            if (
                !isset($_POST['origin_country']) || !isset($_POST['origin_postal_code']) || !isset($_POST['origin_city']) ||
                !isset($_POST['destination_country']) || !isset($_POST['destination_postal_code']) || !isset($_POST['destination_city']) ||
                !isset($_POST['sender_name']) || !isset($_POST['sender_email']) || !isset($_POST['sender_phone']) ||
                !isset($_POST['recipient_name']) || !isset($_POST['recipient_email']) || !isset($_POST['recipient_phone']) ||
                !isset($_POST['packages'])
            ) {
                throw new Exception('Missing required fields');
            }

            // Get and sanitize form data
            $origin_country = sanitize_text_field($_POST['origin_country']);
            $origin_postal_code = sanitize_text_field($_POST['origin_postal_code']);
            $origin_city = sanitize_text_field($_POST['origin_city']);
            $origin_address = sanitize_text_field($_POST['origin_address']);

            $destination_country = sanitize_text_field($_POST['destination_country']);
            $destination_postal_code = sanitize_text_field($_POST['destination_postal_code']);
            $destination_city = sanitize_text_field($_POST['destination_city']);
            $destination_address = sanitize_text_field($_POST['destination_address']);

            $sender_name = sanitize_text_field($_POST['sender_name']);
            $sender_email = sanitize_email($_POST['sender_email']);
            $sender_phone = sanitize_text_field($_POST['sender_phone']);
            $sender_company = isset($_POST['sender_company']) ? sanitize_text_field($_POST['sender_company']) : '';

            $recipient_name = sanitize_text_field($_POST['recipient_name']);
            $recipient_email = sanitize_email($_POST['recipient_email']);
            $recipient_phone = sanitize_text_field($_POST['recipient_phone']);
            $recipient_company = isset($_POST['recipient_company']) ? sanitize_text_field($_POST['recipient_company']) : '';

            // Parse packages data
            $packages_json = sanitize_text_field($_POST['packages']);
            $packages_data = json_decode(stripslashes($packages_json), true);

            if (!$packages_data || !is_array($packages_data)) {
                throw new Exception('Invalid package data');
            }

            // Validate data
            if (
                empty($origin_country) || empty($origin_postal_code) || empty($origin_city) ||
                empty($destination_country) || empty($destination_postal_code) || empty($destination_city) ||
                empty($packages_data)
            ) {
                throw new Exception('Invalid input values');
            }

            // Format packages for API
            $packages = array();
            foreach ($packages_data as $package) {
                $weight = floatval($package['weight']);
                $length = floatval($package['length']);
                $width = floatval($package['width']);
                $height = floatval($package['height']);

                // Validate package dimensions
                if ($weight <= 0 || $length <= 0 || $width <= 0 || $height <= 0) {
                    throw new Exception('Invalid package dimensions');
                }

                $packages[] = array(
                    'weight' => $weight,
                    'length' => $length,
                    'width' => $width,
                    'height' => $height
                );
            }

            // Get shipping rates from Packlink API
            $carrier = new Packlink_Carrier();
            $shipping_rates = $carrier->get_shipping_rates(
                $origin_country,
                $origin_postal_code,
                $destination_country,
                $destination_postal_code,
                $packages
            );

            if (empty($shipping_rates)) {
                throw new Exception('No shipping rates available for this route');
            }

            // Apply commission to shipping rates
            $commission_type = get_option('packlink_commission_type', 'none');
            $commission_fixed = floatval(get_option('packlink_commission_fixed_amount', 0));
            $commission_percentage = floatval(get_option('packlink_commission_percentage', 0));

            $formatted_options = array();
            foreach ($shipping_rates as $rate) {
                $price = $rate['price'];

                // Apply commission
                if ($commission_type === 'fixed') {
                    $price += $commission_fixed;
                } elseif ($commission_type === 'percentage') {
                    $price += ($price * $commission_percentage / 100);
                }

                $formatted_options[] = array(
                    'id' => $rate['id'],
                    'carrierName' => $rate['carrierName'],
                    'serviceName' => $rate['serviceName'],
                    'logoUrl' => $rate['logoUrl'],
                    'price' => $price,
                    'original_price' => $rate['price'], // Store original price for reference
                    'currency' => $rate['currency'],
                    'deliveryTime' => $rate['deliveryTime'],
                    'isDropOff' => $rate['isDropOff'],
                    'firstDeliveryDate' => isset($rate['firstDeliveryDate']) ? $rate['firstDeliveryDate'] : '',
                    'serviceInfo' => isset($rate['serviceInfo']) ? $rate['serviceInfo'] : array()
                );
            }

            wp_send_json_success($formatted_options);
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
        die();
    }

    /**
     * Get drop-off locations via AJAX
     *
     * @return void
     */
    public static function get_drop_off_locations() {
        try {
            // Verify nonce
            if (!check_ajax_referer('packlink_shipping_nonce', 'nonce', false)) {
                throw new Exception('Invalid security token');
            }

            // Check required fields
            if (!isset($_POST['shipping_method_id']) || !isset($_POST['country']) || !isset($_POST['postal_code'])) {
                throw new Exception('Missing required fields');
            }

            $method_id = intval($_POST['shipping_method_id']);
            $country = sanitize_text_field($_POST['country']);
            $postal_code = sanitize_text_field($_POST['postal_code']);

            // Get drop-off locations
            $drop_off = new Packlink_Drop_Off();
            $locations = $drop_off->get_locations($method_id, $country, $postal_code);

            if (empty($locations)) {
                wp_send_json_error(array(
                    'message' => __('No drop-off locations found for this address', 'packlink-custom-shipping')
                ));
            }

            wp_send_json_success($locations);
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
        die();
    }

    /**
     * Add shipping to cart via AJAX
     *
     * @return void
     */
    public static function add_shipping_to_cart() {
        try {
            // Verify nonce
            if (!check_ajax_referer('packlink_shipping_nonce', 'nonce', false)) {
                throw new Exception('Invalid security token');
            }

            // Check required fields
            if (!isset($_POST['shipping_details'])) {
                throw new Exception('Missing required fields');
            }

            $shipping_details = json_decode(stripslashes($_POST['shipping_details']), true);

            if (!$shipping_details) {
                throw new Exception('Invalid shipping details');
            }

            // Validate required shipping details
            if (!isset($shipping_details['routes']) || !is_array($shipping_details['routes']) || empty($shipping_details['routes'])) {
                throw new Exception('Missing routes information');
            }

            if (!isset($shipping_details['packages']) || !is_array($shipping_details['packages']) || empty($shipping_details['packages'])) {
                throw new Exception('Missing packages information');
            }

            // Store shipping details in session
            WC()->session->set('packlink_shipping_details', $shipping_details);
            WC()->session->set('packlink_shipping_price', $shipping_details['total_price']);

            // Create a virtual product for the shipping
            $product_id = Packlink_Checkout::get_or_create_shipping_product();

                       // Add to cart
            WC()->cart->empty_cart();

            $cart_item_data = array(
                'packlink_shipping_data' => $shipping_details,
                'packlink_shipping_price' => $shipping_details['total_price']
            );

            // Log the data being added to cart
            error_log("Adding Packlink shipping to cart with data: " . print_r($cart_item_data, true));

            $cart_item_key = WC()->cart->add_to_cart(
                $product_id,
                1,
                0,
                array(),
                $cart_item_data
            );

            if (!$cart_item_key) {
                throw new Exception('Failed to add shipping to cart');
            }

            wp_send_json_success(array(
                'redirect' => wc_get_checkout_url()
            ));
        } catch (Exception $e) {
            error_log("Error adding Packlink shipping to cart: " . $e->getMessage());
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
        die();
    }
    
    /**
     * Get destinations via AJAX
     *
     * @return void
     */
    public static function get_destinations() {
        check_ajax_referer('packlink-ajax-nonce', 'security');
        
        $postal_code = new Packlink_Postal_Code();
        $destinations = $postal_code->get_postal_zones();
        
        wp_send_json_success($destinations);
    }
    
    /**
     * Get formatted destinations via AJAX
     *
     * @return void
     */
    public static function get_formatted_destinations() {
        check_ajax_referer('packlink-ajax-nonce', 'security');
        
        $postal_code = new Packlink_Postal_Code();
        $destinations = $postal_code->get_formatted_postal_zones();
        
        wp_send_json_success($destinations);
    }
    
    /**
     * Search postal code via AJAX
     *
     * @return void
     */
    public static function search_postal_code() {
        check_ajax_referer('packlink-ajax-nonce', 'security');
        
        $query = isset($_GET['query']) ? sanitize_text_field($_GET['query']) : '';
        $country = isset($_GET['country']) ? sanitize_text_field($_GET['country']) : '';
        
        if (empty($query)) {
            wp_send_json_error(array('message' => 'Query parameter is required'));
            return;
        }
        
        $postal_code = new Packlink_Postal_Code();
        $results = $postal_code->get($query, $country);
        
        wp_send_json_success($results);
    }
    
    /**
     * Suggest postal codes via AJAX
     *
     * @return void
     */
    public static function suggest_postal_codes() {
        check_ajax_referer('packlink-ajax-nonce', 'security');
        
        $term = isset($_GET['term']) ? sanitize_text_field($_GET['term']) : '';
        $country = isset($_GET['country']) ? sanitize_text_field($_GET['country']) : '';
        
        if (empty($term) || empty($country)) {
            wp_send_json_error(array('message' => 'Term and country parameters are required'));
            return;
        }
        
        $postal_code = new Packlink_Postal_Code();
        $suggestions = $postal_code->suggest($term, $country);
        
        // Format results for autocomplete
        $results = array();
        foreach ($suggestions as $suggestion) {
            $results[] = array(
                'value' => $suggestion['postal_code'],
                'label' => $suggestion['postal_code'] . ' - ' . $suggestion['city'],
                'city' => $suggestion['city'],
                'state' => isset($suggestion['state']) ? $suggestion['state'] : '',
            );
        }
        
        wp_send_json($results);
    }
    
    /**
     * Get tracking information for a shipment
     * 
     * @return void
     */
    public static function get_tracking_info() {
        try {

            error_log('Arrived get_tracking_info');
            // Verify nonce
            if (!check_ajax_referer('packlink_tracking_nonce', 'security', false)) {
                throw new Exception(__('Invalid security token', 'packlink-custom-shipping'));
            }
            
            // Check required fields
            if (!isset($_POST['reference']) || empty($_POST['reference'])) {
                throw new Exception(__('Tracking reference is required', 'packlink-custom-shipping'));
            }
            
            $reference = sanitize_text_field($_POST['reference']);
            
            // First try to find the order by reference
            $order_id = self::find_order_by_reference($reference);
            
            // Initialize shipment API
            $shipment_api = new Packlink_Shipment();
            
            if ($order_id) {
                // If order found, get all details from the order
                $order = wc_get_order($order_id);
                
                if (!$order) {
                    throw new Exception(__('Order not found', 'packlink-custom-shipping'));
                }
                
                // Get shipment details from the order
                $shipment_details = get_post_meta($order_id, '_packlink_shipping_details', true);
                
                // Get tracking information
                $tracking = $shipment_api->get_tracking($reference);
                
                if (is_wp_error($tracking)) {
                    throw new Exception($tracking->get_error_message());
                }
                
                // Get shipment status
                $shipment = $shipment_api->get($reference);
                
                if (is_wp_error($shipment)) {
                    throw new Exception($shipment->get_error_message());
                }
                
                wp_send_json_success(array(
                    'status' => isset($shipment['status']) ? $shipment['status'] : 'UNKNOWN',
                    'tracking' => isset($tracking['tracking_events']) ? $tracking['tracking_events'] : array(),
                    'shipment' => array(
                        'reference' => $reference,
                        'carrier' => isset($shipment['carrier']) ? $shipment['carrier'] : '',
                        'service' => isset($shipment['service']) ? $shipment['service'] : '',
                        'date_created' => isset($shipment['date_created']) ? $shipment['date_created'] : '',
                        'from' => isset($shipment['from']) ? $shipment['from'] : null,
                        'to' => isset($shipment['to']) ? $shipment['to'] : null
                    )
                ));
            } else {
                // If no order found, try to get tracking directly from Packlink API
                // Get shipment status
                $shipment = $shipment_api->get($reference);
                
                if (is_wp_error($shipment)) {
                    throw new Exception($shipment->get_error_message());
                }
                
                // Get tracking information
                $tracking = $shipment_api->get_tracking($reference);
                
                if (is_wp_error($tracking)) {
                    throw new Exception($tracking->get_error_message());
                }
                
                wp_send_json_success(array(
                    'status' => isset($shipment['status']) ? $shipment['status'] : 'UNKNOWN',
                    'tracking' => isset($tracking['tracking_events']) ? $tracking['tracking_events'] : array(),
                    'shipment' => array(
                        'reference' => $reference,
                        'carrier' => isset($shipment['carrier']) ? $shipment['carrier'] : '',
                        'service' => isset($shipment['service']) ? $shipment['service'] : '',
                        'date_created' => isset($shipment['date_created']) ? $shipment['date_created'] : '',
                        'from' => isset($shipment['from']) ? $shipment['from'] : null,
                        'to' => isset($shipment['to']) ? $shipment['to'] : null
                    )
                ));
            }
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
        die();
    }

    /**
     * Find order by Packlink reference
     * 
     * @param string $reference Packlink reference number
     * @return int|bool Order ID or false if not found
     */
    private static function find_order_by_reference($reference) {
        global $wpdb;
        
        // First check for the main shipment ID
        $order_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_packlink_shipment_id' AND meta_value = %s LIMIT 1",
            $reference
        ));
        
        if ($order_id) {
            return $order_id;
        }
        
        // Then check for specific route shipment IDs
        $order_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key LIKE '_packlink_shipment_id_route_%' AND meta_value = %s LIMIT 1",
            $reference
        ));
        
        if ($order_id) {
            return $order_id;
        }
        
        // Also check in all shipments array
        $all_shipments_orders = $wpdb->get_results(
            "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_packlink_all_shipments'"
        );
        
        foreach ($all_shipments_orders as $order_meta) {
            $shipments = maybe_unserialize($order_meta->meta_value);
            if (is_array($shipments) && in_array($reference, $shipments)) {
                return $order_meta->post_id;
            }
        }
        
        return false;
    }
}