<?php

/**
 * Plugin Name: Packlink Custom Shipping Extension
 * Description: Custom integration with Packlink PRO shipping API
 * Version: 1.0.6
 * Author: Jonayed Ahamed
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * WC requires at least: 4.0
 * WC tested up to: 7.0
 * Text Domain: packlink-custom-shipping
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('PACKLINK_CUSTOM_PLUGIN_FILE', __FILE__);
define('PACKLINK_CUSTOM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PACKLINK_CUSTOM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PACKLINK_CUSTOM_VERSION', '1.0.2');

// Check if WooCommerce is active
add_action('plugins_loaded', 'packlink_custom_check_woocommerce');

function packlink_custom_check_woocommerce()
{
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'packlink_custom_woocommerce_notice');
        return;
    }

    // Initialize our plugin
    PacklinkCustomShipping::init();
}

function packlink_custom_woocommerce_notice()
{
?>
    <div class="error">
        <p><?php _e('Packlink Custom Shipping Extension requires WooCommerce to be installed and activated.', 'packlink-custom-shipping'); ?>
        </p>
    </div>
<?php
}

// Include core files
require_once PACKLINK_CUSTOM_PLUGIN_DIR . 'includes/class-packlink-api.php';
require_once PACKLINK_CUSTOM_PLUGIN_DIR . 'includes/class-packlink-carrier.php';
require_once PACKLINK_CUSTOM_PLUGIN_DIR . 'includes/class-packlink-shipment.php';
require_once PACKLINK_CUSTOM_PLUGIN_DIR . 'includes/class-packlink-warehouse.php';
require_once PACKLINK_CUSTOM_PLUGIN_DIR . 'includes/class-packlink-postal-code.php';
require_once PACKLINK_CUSTOM_PLUGIN_DIR . 'includes/class-packlink-drop-off-locations.php';
require_once PACKLINK_CUSTOM_PLUGIN_DIR . 'includes/class-packlink-settings.php';
require_once PACKLINK_CUSTOM_PLUGIN_DIR . 'includes/class-packlink-order-meta-box.php';
require_once PACKLINK_CUSTOM_PLUGIN_DIR . 'includes/class-packlink-ajax.php';
require_once PACKLINK_CUSTOM_PLUGIN_DIR . 'includes/class-packlink-lookup.php';

class PacklinkCustomShipping
{
    public static function init()
    {
        // Register settings
        add_action('admin_init', ['Packlink_Settings', 'register_settings']);
        add_action('admin_menu', ['Packlink_Settings', 'add_menu_page']);

        // Register scripts and styles
        add_action('wp_enqueue_scripts', [self::class, 'register_scripts']);
        add_action('admin_enqueue_scripts', [self::class, 'register_admin_scripts']);

        // Register shortcode
        add_shortcode('packlink_shipping_form', [self::class, 'render_shipping_form']);

        // Register AJAX handlers
        add_action('wp_ajax_get_packlink_shipping_rates', [self::class, 'get_shipping_rates']);
        add_action('wp_ajax_nopriv_get_packlink_shipping_rates', [self::class, 'get_shipping_rates']);

        add_action('wp_ajax_get_packlink_drop_off_locations', [self::class, 'get_drop_off_locations']);
        add_action('wp_ajax_nopriv_get_packlink_drop_off_locations', [self::class, 'get_drop_off_locations']);

        add_action('wp_ajax_add_packlink_shipping_to_cart', [self::class, 'add_shipping_to_cart']);
        add_action('wp_ajax_nopriv_add_packlink_shipping_to_cart', [self::class, 'add_shipping_to_cart']);
        // Save shipping data to order meta
        add_action('woocommerce_checkout_update_order_meta', [self::class, 'save_shipping_data_to_order']);

        // Hook into WooCommerce order processing
        add_action('woocommerce_order_status_processing', [self::class, 'create_packlink_shipment'], 10, 1);
        // add_action('woocommerce_payment_complete', [self::class, 'create_packlink_shipment'], 10, 1);
        // add_action('woocommerce_thankyou', [self::class, 'create_packlink_shipment'], 10, 1);
        // Add custom fee to display shipping cost at checkout
        add_action('woocommerce_cart_calculate_fees', [self::class, 'add_packlink_shipping_fee']);


        // Add order actions
        add_action('woocommerce_order_actions', [self::class, 'add_order_actions']);
        add_action('woocommerce_order_action_packlink_create_shipment', [self::class, 'process_order_action_create_shipment']);
        add_action('woocommerce_order_action_packlink_create_all_shipments', [self::class, 'process_order_action_create_all_shipments']);

        // Register plugin text domain
        add_action('init', [self::class, 'load_textdomain']);

        // Add action to ensure Packlink data is saved
        add_action('woocommerce_new_order', [self::class, 'ensure_packlink_data_saved'], 10, 1);
    }

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
     * Load plugin text domain
     */
    public static function load_textdomain()
    {
        load_plugin_textdomain('packlink-custom-shipping', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public static function register_scripts()
    {
        // Register jQuery UI dependencies first
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-autocomplete');

        // Register jQuery UI CSS
        wp_enqueue_style(
            'jquery-ui-style',
            '//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css',
            [],
            '1.13.2'
        );

        // Register map script first (no dependencies)
        wp_register_script(
            'packlink-map-loader',
            PACKLINK_CUSTOM_PLUGIN_URL . 'assets/js/components/map.js',
            ['jquery'],
            PACKLINK_CUSTOM_VERSION,
            true
        );

        // Register Google Maps loader with map script as dependency
        wp_register_script(
            'google-map-loader',
            PACKLINK_CUSTOM_PLUGIN_URL . 'assets/js/google-maps-loader.js',
            ['jquery', 'packlink-map-loader'],
            PACKLINK_CUSTOM_VERSION,
            true
        );

        // Register your custom script with jQuery UI as dependencies
        wp_register_script(
            'packlink-custom-form',
            PACKLINK_CUSTOM_PLUGIN_URL . 'assets/js/packlink-custom-form.js',
            ['jquery', 'jquery-ui-core', 'jquery-ui-autocomplete', 'packlink-map-loader'],
            PACKLINK_CUSTOM_VERSION,
            true
        );

        // Register CSS files
        wp_register_style(
            'packlink-variables',
            PACKLINK_CUSTOM_PLUGIN_URL . 'assets/css/variables.css',
            [],
            PACKLINK_CUSTOM_VERSION
        );

        wp_register_style(
            'packlink-animations',
            PACKLINK_CUSTOM_PLUGIN_URL . 'assets/css/animations.css',
            ['packlink-variables'],
            PACKLINK_CUSTOM_VERSION
        );

        wp_register_style(
            'packlink-custom-styles',
            PACKLINK_CUSTOM_PLUGIN_URL . 'assets/css/packlink-custom-styles.css',
            ['packlink-variables', 'packlink-animations'],
            PACKLINK_CUSTOM_VERSION
        );

        // Add these to the wp_localize_script call in the register_scripts method
        wp_localize_script('packlink-custom-form', 'packlink_custom', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'ajax_nonce' => wp_create_nonce('packlink-ajax-nonce'),
            'default_origin_country' => get_option('packlink_country', 'ES'),
            'default_destination_country' => '',
            'default_carrier_logo' => PACKLINK_CUSTOM_PLUGIN_URL . 'assets/images/default-carrier.png',
            'default_package_weight' => get_option('packlink_default_package_weight', '1'),
            'default_package_length' => get_option('packlink_default_package_length', '10'),
            'default_package_width' => get_option('packlink_default_package_width', '10'),
            'default_package_height' => get_option('packlink_default_package_height', '10'),
            'loading_shipping_options' => __('Loading shipping options...', 'packlink-custom-shipping'),
            'loading_drop_off_locations' => __('Loading drop-off locations...', 'packlink-custom-shipping'),
            'no_shipping_options' => __('No shipping options available for this route', 'packlink-custom-shipping'),
            'available_shipping_options' => __('Available Shipping Options', 'packlink-custom-shipping'),
            'delivery_time' => __('Delivery Time', 'packlink-custom-shipping'),
            'estimated_delivery' => __('Estimated Delivery', 'packlink-custom-shipping'),
            'drop_off_point_required' => __('Drop-off point required', 'packlink-custom-shipping'),
            'select_button_text' => __('Select', 'packlink-custom-shipping'),
            'select_location_button_text' => __('Select This Location', 'packlink-custom-shipping'),
            'processing_text' => __('Processing...', 'packlink-custom-shipping'),
            'error_connecting_server' => __('Error connecting to server', 'packlink-custom-shipping'),
            'error_adding_to_cart' => __('Error adding shipping to cart', 'packlink-custom-shipping'),
            'please_fill_required_fields' => __('Please fill in all required fields', 'packlink-custom-shipping'),
            'working_hours_not_available' => __('Working hours not available', 'packlink-custom-shipping'),
            'package_text' => __('Package', 'packlink-custom-shipping'),
            'weight_text' => __('Weight (kg)', 'packlink-custom-shipping'),
            'dimensions_text' => __('Dimensions (cm)', 'packlink-custom-shipping'),
            'length_text' => __('Length', 'packlink-custom-shipping'),
            'width_text' => __('Width', 'packlink-custom-shipping'),
            'height_text' => __('Height', 'packlink-custom-shipping'),
            'collection_date_future_message' => __('Collection date must be at least one day in the future.', 'packlink-custom-shipping'),
            'collection_date_label' => __('Collection Date', 'packlink-custom-shipping'),
            'collection_time_label' => __('Collection Time', 'packlink-custom-shipping'),
            'select_time_label' => __('Select time', 'packlink-custom-shipping'),
            'value_must_be_at_least' => __('Value must be at least', 'packlink-custom-shipping'),
            'error_loading_drop_off_locations' => __('Error loading drop-off locations', 'packlink-custom-shipping'),
            'no_drop_off_locations_message' => __('No drop-off locations found for this address', 'packlink-custom-shipping'),
            'shipment_submitted' => __('Shipment Request Submitted!', 'packlink-custom-shipping'),
            'shipment_success_message' => __('Your request has been successfully submitted. You will be redirected to checkout.', 'packlink-custom-shipping'),
            'close' => __('Close', 'packlink-custom-shipping'),
            'route_information' => __('Route Information', 'packlink-custom-shipping'),
            'origin' => __('Origin', 'packlink-custom-shipping'),
            'destination' => __('Destination', 'packlink-custom-shipping'),
            'transport_type' => __('Transport Type', 'packlink-custom-shipping'),
            'road_transport' => __('Road Transport', 'packlink-custom-shipping'),
            'air_transport' => __('Air Transport', 'packlink-custom-shipping'),
            'distance' => __('Distance', 'packlink-custom-shipping'),
            'contact_information' => __('Contact Information', 'packlink-custom-shipping'),
            'sender' => __('Sender', 'packlink-custom-shipping'),
            'recipient' => __('Recipient', 'packlink-custom-shipping'),
            'package_information' => __('Package Information', 'packlink-custom-shipping'),
            'continue_to_details' => __('Continue to Details', 'packlink-custom-shipping'),
            'continue_to_packages' => __('Continue to Packages', 'packlink-custom-shipping'),
            'continue_to_review' => __('Continue to Review', 'packlink-custom-shipping'),
            'back' => __('Back', 'packlink-custom-shipping'),
            'add_another_package' => __('Add Another Package', 'packlink-custom-shipping'),
            'review_your_shipment' => __('Review Your Shipment', 'packlink-custom-shipping'),
            'check_details_before_submitting' => __('Check all details before submitting', 'packlink-custom-shipping'),
        ]);
    }

    /**
     * Register admin scripts and styles
     */
    public static function register_admin_scripts()
    {
        wp_register_style(
            'packlink-admin-styles',
            PACKLINK_CUSTOM_PLUGIN_URL . 'assets/css/packlink-admin-styles.css',
            [],
            PACKLINK_CUSTOM_VERSION
        );

        wp_enqueue_style('packlink-admin-styles');
    }


    public static function render_shipping_form($atts = [])
    {
        // Process shortcode attributes
        $atts = Packlink_Settings::get_shortcode_atts($atts);

        // Enqueue scripts and styles
        wp_enqueue_style('packlink-variables');
        wp_enqueue_style('packlink-animations');
        wp_enqueue_style('packlink-custom-styles');

        // Enqueue scripts in the correct order
        wp_enqueue_script('packlink-map-loader');
        wp_enqueue_script('google-map-loader');
        wp_enqueue_script('packlink-custom-form');

        ob_start();
        include PACKLINK_CUSTOM_PLUGIN_DIR . 'templates/shipping-form.php';
        return ob_get_clean();
    }

    // Modify the get_shipping_rates method
    public static function get_shipping_rates()
    {
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
            $packages = [];
            foreach ($packages_data as $package) {
                $weight = floatval($package['weight']);
                $length = floatval($package['length']);
                $width = floatval($package['width']);
                $height = floatval($package['height']);

                // Validate package dimensions
                if ($weight <= 0 || $length <= 0 || $width <= 0 || $height <= 0) {
                    throw new Exception('Invalid package dimensions');
                }

                $packages[] = [
                    'weight' => $weight,
                    'length' => $length,
                    'width' => $width,
                    'height' => $height
                ];
            }


            error_log(print_r([
                "Origin_country" => $origin_country,
                "Origin_postal_code" => $origin_postal_code,
                "Origin_city" => $origin_city,
                "Destination_country" => $destination_country,
                "Destination_postal_code" => $destination_postal_code,
                "Destination_city" => $destination_city,
                "Sender_name" => $sender_name,
                "Sender_email" => $sender_email,
                "Sender_phone" => $sender_phone,
                "Recipient_name" => $recipient_name,
                "Recipient_email" => $recipient_email,
                "Recipient_phone" => $recipient_phone,
                "Packages" => $packages
            ], true));

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

            $formatted_options = [];
            foreach ($shipping_rates as $rate) {
                $price = $rate['price'];

                // Apply commission
                if ($commission_type === 'fixed') {
                    $price += $commission_fixed;
                } elseif ($commission_type === 'percentage') {
                    $price += ($price * $commission_percentage / 100);
                }

                $formatted_options[] = [
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
                    'serviceInfo' => isset($rate['serviceInfo']) ? $rate['serviceInfo'] : []
                ];
            }

            wp_send_json_success($formatted_options);
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
        die();
    }


    public static function get_drop_off_locations()
    {
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
            $drop_off = new Packlink_Drop_Off_Locations();

            error_log("country: $country");
            error_log("Postal code: $postal_code");

            $locations = $drop_off->get_locations($method_id, $country, $postal_code);

            if (empty($locations)) {
                wp_send_json_error([
                    'message' => __('No drop-off locations found for this address', 'packlink-custom-shipping')
                ]);
            }

            wp_send_json_success($locations);
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
        die();
    }

    public static function add_shipping_to_cart()
    {
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
            $product_id = self::get_or_create_shipping_product();

            // Add to cart
            WC()->cart->empty_cart();

            $cart_item_data = [
                'packlink_shipping_data' => $shipping_details,
                'packlink_shipping_price' => $shipping_details['total_price']
            ];

            // Log the data being added to cart
            error_log("Adding Packlink shipping to cart with data: " . print_r($cart_item_data, true));

            $cart_item_key = WC()->cart->add_to_cart(
                $product_id,
                1,
                0,
                [],
                $cart_item_data
            );

            if (!$cart_item_key) {
                throw new Exception('Failed to add shipping to cart');
            }

            wp_send_json_success([
                'redirect' => wc_get_checkout_url()
            ]);
        } catch (Exception $e) {
            error_log("Error adding Packlink shipping to cart: " . $e->getMessage());
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
        die();
    }

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

    public static function create_packlink_shipment($order_id, $posted_data = null, $order = null)
    {
        // Enhanced logging for debugging
        error_log("create_packlink_shipment called for order ID: $order_id");

        // Check if automatic shipment creation is enabled
        if (get_option('packlink_auto_create_shipment') !== 'yes') {
            error_log("Automatic shipment creation is disabled");
            return;
        }

        // Get order object if not provided
        if (!$order) {
            $order = wc_get_order($order_id);
            if (!$order) {
                error_log("Invalid order object for ID: $order_id");
                return;
            }
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
                    error_log("Using default package dimensions for route $route_index");
                    $packages[] = [
                        'weight' => floatval(get_option('packlink_default_package_weight', '1')),
                        'length' => floatval(get_option('packlink_default_package_length', '10')),
                        'width' => floatval(get_option('packlink_default_package_width', '10')),
                        'height' => floatval(get_option('packlink_default_package_height', '10'))
                    ];
                }

                error_log("Prepared packages for route $route_index: " . print_r($packages, true));

                // Use the sender and recipient data from the route
                // Create shipment data according to Packlink's required format
                $shipment_data = [
                    'service_id' => $shipping_option_id,
                    'content' => sprintf(__('WooCommerce Order #%s - Route %d', 'packlink-custom-shipping'), $order_id, $route_index + 1),
                    'reference' => $order_id . '-' . ($route_index + 1),
                    'source' => 'woocommerce',
                    'packages' => $packages,
                    'collection_date' => $route['collection_date'],
                    'collection_time' => $route['collection_time'],
                    'sender' => [
                        'name' => (strpos($route['sender_name'], ' ') !== false) ? substr($route['sender_name'], 0, strrpos($route['sender_name'], ' ')) : $route['sender_name'],
                        'surname' => (strpos($route['sender_name'], ' ') !== false) ? substr($route['sender_name'], strrpos($route['sender_name'], ' ') + 1) : 'Unknown',
                        'company' => isset($route['sender_company']) ? $route['sender_company'] : '',
                        'country' => $route['origin_country'],
                        'zip' => $route['origin_postal_code'],
                        'city' => $route['origin_city'],
                        'street1' => $route['origin_address'],
                        'phone' => $route['sender_phone'],
                        'email' => $route['sender_email']
                    ],
                    'receiver' => [
                        'name' => (strpos($route['recipient_name'], ' ') !== false) ? substr($route['recipient_name'], 0, strrpos($route['recipient_name'], ' ')) : $route['recipient_name'],
                        'surname' => (strpos($route['recipient_name'], ' ') !== false) ? substr($route['recipient_name'], strrpos($route['recipient_name'], ' ') + 1) : 'Unknown',
                        'company' => isset($route['recipient_company']) ? $route['recipient_company'] : '',
                        'country' => $route['destination_country'],
                        'zip' => $route['destination_postal_code'],
                        'city' => $route['destination_city'],
                        'street1' => $route['destination_address'],
                        'phone' => $route['recipient_phone'],
                        'email' => $route['recipient_email']
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

                error_log("Shipment creation result for route $route_index: " . print_r($result, true));

                if (isset($result['reference'])) {
                    // Save Packlink shipment ID to order for this specific route
                    update_post_meta($order_id, '_packlink_shipment_id_route_' . $route_index, $result['reference']);

                    // Also save a list of all shipment IDs
                    $all_shipments = get_post_meta($order_id, '_packlink_all_shipments', true);
                    if (!is_array($all_shipments)) {
                        $all_shipments = [];
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
     * Add Packlink actions to order actions dropdown
     * 
     * @param array $actions Order actions
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
     * Process order action to create all Packlink shipments
     * 
     * @param WC_Order $order Order object
     */
    public static function process_order_action_create_all_shipments($order)
    {
        self::create_packlink_shipment($order->get_id(), null, $order);
    }

    /**
     * Process order action to create Packlink shipment
     * 
     * @param WC_Order $order Order object
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
                // Fallback to single package
                $packages[] = [
                    'weight' => floatval($shipping_details['weight']),
                    'length' => floatval($shipping_details['dimensions']['length']),
                    'width' => floatval($shipping_details['dimensions']['width']),
                    'height' => floatval($shipping_details['dimensions']['height'])
                ];
            }

            // Create shipment data according to Packlink's required format
            $shipment_data = [
                'service_id' => $shipping_option_id,
                'content' => sprintf(__('WooCommerce Order #%s', 'packlink-custom-shipping'), $order_id),
                'reference' => $order_id,
                'source' => 'woocommerce',
                'packages' => $packages,
                'sender' => [
                    'name' => $shipping_details['sender_name'],
                    'company' => !empty($shipping_details['sender_company']) ? $shipping_details['sender_company'] : '',
                    'country' => $shipping_details['origin_country'],
                    'zip' => $shipping_details['origin_postal_code'],
                    'city' => $shipping_details['origin_city'],
                    'street1' => $shipping_details['origin_address'],
                    'phone' => $shipping_details['sender_phone'],
                    'email' => $shipping_details['sender_email']
                ],
                'receiver' => [
                    'name' => $shipping_details['recipient_name'],
                    'company' => !empty($shipping_details['recipient_company']) ? $shipping_details['recipient_company'] : '',
                    'country' => $shipping_details['destination_country'],
                    'zip' => $shipping_details['destination_postal_code'],
                    'city' => $shipping_details['destination_city'],
                    'street1' => $shipping_details['destination_address'],
                    'phone' => $shipping_details['recipient_phone'],
                    'email' => $shipping_details['recipient_email']
                ]
            ];

            $drop_off_id = get_post_meta($order_id, '_packlink_drop_off_id', true);
            if ($drop_off_id) {
                $shipment_data['dropoff'] = [
                    'id' => $drop_off_id
                ];
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
     * Get or create a virtual product for shipping
     * 
     * @return int Product ID
     */
    private static function get_or_create_shipping_product()
    {
        // Check if product already exists
        $products = wc_get_products([
            'status' => 'publish',
            'limit' => 1,
            'meta_key' => '_packlink_shipping_product',
            'meta_value' => 'yes'
        ]);

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

    /**
     * Parse address string to extract country, postal code and city
     * 
     * @param string $address Address in format "postal_code, city, country"
     * @return array|false Array with country, postal_code and city or false if parsing failed
     */
    private static function parse_address($address)
    {
        // Try to parse address in format: "postal_code, city, country"
        $parts = array_map('trim', explode(',', $address));

        if (count($parts) >= 2) {
            $postal_code = $parts[0];
            $city = $parts[1];
            $country = isset($parts[2]) ? $parts[2] : '';

            // If country is not provided or is a full name, try to convert it to country code
            if (empty($country) || strlen($country) > 2) {
                $country = self::get_country_code($country);
            }

            return [
                'postal_code' => $postal_code,
                'city' => $city,
                'country' => $country
            ];
        }

        return false;
    }

    /**
     * Get country code from country name
     * 
     * @param string $country_name Country name
     * @return string Country code
     */
    private static function get_country_code($country_name)
    {
        // Default to Spain if empty
        if (empty($country_name)) {
            return 'ES';
        }

        // If already a 2-letter code, return it
        if (strlen($country_name) === 2) {
            return strtoupper($country_name);
        }

        // Get WooCommerce countries
        $countries = WC()->countries->get_countries();

        // Try to find country by name
        $country_code = array_search(trim($country_name), $countries);

        if ($country_code) {
            return $country_code;
        }

        // Default to Spain if not found
        return 'ES';
    }
}
