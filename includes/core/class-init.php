<?php

/**
 * Plugin initialization class
 *
 * @package PacklinkCustomShipping
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Main initialization class for the plugin
 */
class Packlink_Init
{
    /**
     * Initialize the plugin
     *
     * @return void
     */
    public static function init()
    {
        // Initialize API components
        self::init_api();

        // Initialize admin components if in admin area
        if (is_admin()) {
            self::init_admin();
        }

        // Initialize frontend components
        self::init_frontend();

        // Initialize AJAX handlers
        self::init_ajax();

        // Initialize shortcodes
        self::init_shortcodes();

        // Register hooks
        self::register_hooks();
    }

    /**
     * Initialize API components
     *
     * @return void
     */
    private static function init_api()
    {
        // Load API classes
        require_once PACKLINK_CUSTOM_PLUGIN_DIR . 'includes/api/class-api.php';
        require_once PACKLINK_CUSTOM_PLUGIN_DIR . 'includes/api/class-carrier.php';
        require_once PACKLINK_CUSTOM_PLUGIN_DIR . 'includes/api/class-drop-off.php';
        require_once PACKLINK_CUSTOM_PLUGIN_DIR . 'includes/api/class-postal-code.php';
        require_once PACKLINK_CUSTOM_PLUGIN_DIR . 'includes/api/class-shipment.php';
        require_once PACKLINK_CUSTOM_PLUGIN_DIR . 'includes/api/class-warehouse.php';
    }

    /**
     * Initialize admin components
     *
     * @return void
     */
    private static function init_admin()
    {
        // Load admin classes
        require_once PACKLINK_CUSTOM_PLUGIN_DIR . 'includes/admin/class-admin.php';
        require_once PACKLINK_CUSTOM_PLUGIN_DIR . 'includes/admin/class-meta-box.php';
        require_once PACKLINK_CUSTOM_PLUGIN_DIR . 'includes/admin/class-settings.php';
        require_once PACKLINK_CUSTOM_PLUGIN_DIR . 'includes/admin/class-admin-order-columns.php';

        // Initialize admin
        Packlink_Admin::init();
        Packlink_Meta_Box::init();
        Packlink_Settings::register_settings();

        // Add admin menu
        add_action('admin_menu', array('Packlink_Settings', 'add_menu_page'));

        // Register admin scripts
        add_action('admin_enqueue_scripts', array(self::class, 'register_admin_scripts'));
    }

    /**
     * Initialize frontend components
     *
     * @return void
     */
    private static function init_frontend()
    {
        // Load frontend classes
        require_once PACKLINK_CUSTOM_PLUGIN_DIR . 'includes/frontend/class-checkout.php';
        require_once PACKLINK_CUSTOM_PLUGIN_DIR . 'includes/frontend/class-form.php';
        require_once PACKLINK_CUSTOM_PLUGIN_DIR . 'includes/frontend/class-order-display.php';

        // Initialize order display
        Packlink_Order_Display::init();

        // Register frontend scripts
        add_action('wp_enqueue_scripts', array(self::class, 'register_frontend_scripts'));

        // Add currency data to frontend scripts
        add_action('wp_footer', array(self::class, 'add_currency_data_to_js'), 25);
    }

    /**
     * Initialize AJAX handlers
     *
     * @return void
     */
    private static function init_ajax()
    {
        // Load AJAX classes
        require_once PACKLINK_CUSTOM_PLUGIN_DIR . 'includes/core/class-ajax.php';
        require_once PACKLINK_CUSTOM_PLUGIN_DIR . 'includes/core/class-lookup.php';
        require_once PACKLINK_CUSTOM_PLUGIN_DIR . 'includes/core/class-currency-handler.php';

        // Initialize AJAX handlers
        Packlink_Ajax::init();
        Packlink_Lookup::init();

        // Initialize currency handler
        Packlink_Currency_Handler::init();
    }

    /**
     * Initialize shortcodes
     *
     * @return void
     */
    private static function init_shortcodes()
    {
        require_once PACKLINK_CUSTOM_PLUGIN_DIR . 'includes/frontend/class-shortcode.php';
        require_once PACKLINK_CUSTOM_PLUGIN_DIR . 'includes/class-quote-shortcode.php';

        Packlink_Shortcode::init();

        // add_shortcode('packlink_shipping_form', array('Packlink_Form', 'render_shipping_form'));
        // add_shortcode('packlink_tracking_form', array('Packlink_Tracking', 'render_tracking_form'));
    }

    /**
     * Register hooks
     *
     * @return void
     */
    private static function register_hooks()
    {
        // Hook into WooCommerce order processing
        add_action('woocommerce_checkout_update_order_meta', array('Packlink_Checkout', 'save_shipping_data_to_order'));
        add_action('woocommerce_order_status_processing', array('Packlink_Checkout', 'create_packlink_shipment'), 10, 1);
        add_action('woocommerce_cart_calculate_fees', array('Packlink_Checkout', 'add_packlink_shipping_fee'));
        add_action('woocommerce_new_order', array('Packlink_Checkout', 'ensure_packlink_data_saved'), 10, 1);

        // Add order actions
        add_action('woocommerce_order_actions', array('Packlink_Checkout', 'add_order_actions'));
        add_action('woocommerce_order_action_packlink_create_shipment', array('Packlink_Checkout', 'process_order_action_create_shipment'));
        add_action('woocommerce_order_action_packlink_create_all_shipments', array('Packlink_Checkout', 'process_order_action_create_all_shipments'));

        // Populate checkout fields with sender information from Route #1
        add_action('woocommerce_before_checkout_form', array('Packlink_Checkout', 'populate_checkout_fields'));
    }

    /**
     * Register admin scripts and styles
     *
     * @return void
     */
    public static function register_admin_scripts()
    {
        wp_register_style(
            'packlink-admin-styles',
            PACKLINK_CUSTOM_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            PACKLINK_CUSTOM_VERSION
        );

        wp_enqueue_style('packlink-admin-styles');
    }

    /**
     * Register frontend scripts and styles
     *
     * @return void
     */
    public static function register_frontend_scripts()
    {
        // Register jQuery UI dependencies
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-autocomplete');

        // Register jQuery UI CSS
        wp_enqueue_style(
            'jquery-ui-style',
            '//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css',
            array(),
            '1.13.2'
        );

        // Register map script first (no dependencies)
        wp_register_script(
            'packlink-map-loader',
            PACKLINK_CUSTOM_PLUGIN_URL . 'assets/js/frontend/components/map.js',
            array('jquery'),
            PACKLINK_CUSTOM_VERSION,
            true
        );

        // Register Google Maps loader with map script as dependency
        wp_register_script(
            'google-map-loader',
            PACKLINK_CUSTOM_PLUGIN_URL . 'assets/js/frontend/google-maps-loader.js',
            array('jquery', 'packlink-map-loader'),
            PACKLINK_CUSTOM_VERSION,
            true
        );

        // Register custom form script
        wp_register_script(
            'packlink-custom-form',
            PACKLINK_CUSTOM_PLUGIN_URL . 'assets/js/frontend/packlink-form.js',
            array('jquery', 'jquery-ui-core', 'jquery-ui-autocomplete', 'packlink-map-loader'),
            PACKLINK_CUSTOM_VERSION,
            true
        );

        // Register CSS files
        wp_register_style(
            'packlink-variables',
            PACKLINK_CUSTOM_PLUGIN_URL . 'assets/css/variables.css',
            array(),
            PACKLINK_CUSTOM_VERSION
        );

        wp_register_style(
            'packlink-animations',
            PACKLINK_CUSTOM_PLUGIN_URL . 'assets/css/animations.css',
            array('packlink-variables'),
            PACKLINK_CUSTOM_VERSION
        );

        wp_register_style(
            'packlink-frontend-styles',
            PACKLINK_CUSTOM_PLUGIN_URL . 'assets/css/frontend.css',
            array('packlink-variables', 'packlink-animations'),
            PACKLINK_CUSTOM_VERSION
        );

        // Add localization for the custom form script
        wp_localize_script('packlink-custom-form', 'packlink_custom', self::get_js_variables());


        // Register tracking script
        wp_register_script(
            'packlink-tracking',
            PACKLINK_CUSTOM_PLUGIN_URL . 'assets/js/frontend/packlink-tracking.js',
            array('jquery'),
            PACKLINK_CUSTOM_VERSION,
            true
        );

        // Add localization for the tracking script
        wp_localize_script('packlink-tracking', 'packlink_tracking', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'enter_reference' => __('Please enter a tracking reference number', 'packlink-custom-shipping'),
            'status_delivered' => __('Delivered', 'packlink-custom-shipping'),
            'status_in_transit' => __('In Transit', 'packlink-custom-shipping'),
            'status_exception' => __('Exception', 'packlink-custom-shipping'),
            'status_cancelled' => __('Cancelled', 'packlink-custom-shipping'),
            'status_pending' => __('Pending', 'packlink-custom-shipping'),
            'status_ready_to_ship' => __('Ready to Ship', 'packlink-custom-shipping'),
            'status_unknown' => __('Unknown Status', 'packlink-custom-shipping'),
            'carrier' => __('Carrier', 'packlink-custom-shipping'),
            'service' => __('Service', 'packlink-custom-shipping'),
            'reference' => __('Reference', 'packlink-custom-shipping'),
            'created_date' => __('Created Date', 'packlink-custom-shipping'),
            'from' => __('From', 'packlink-custom-shipping'),
            'to' => __('To', 'packlink-custom-shipping'),
            'no_tracking_available' => __('No tracking information available yet', 'packlink-custom-shipping')
        ));

        // Register multistep checkout assets (loaded only on checkout page)
        wp_register_script(
            'packlink-checkout-steps',
            PACKLINK_CUSTOM_PLUGIN_URL . 'assets/js/frontend/checkout-steps.js',
            array('jquery'),
            PACKLINK_CUSTOM_VERSION,
            true
        );

        // Localize brand logos and links for checkout steps
        wp_localize_script('packlink-checkout-steps', 'packlink_checkout_steps', array(
            'brand_logos' => array(
                'maestro' => PACKLINK_CUSTOM_PLUGIN_URL . 'assets/images/carriers/maestro.jpeg',
                'mastercard' => PACKLINK_CUSTOM_PLUGIN_URL . 'assets/images/carriers/mastercard.jpeg',
                'visa' => PACKLINK_CUSTOM_PLUGIN_URL . 'assets/images/carriers/visa.jpeg',
                'links' => array(
                    'maestro' => 'https://www.mastercard.com',
                    'mastercard' => 'https://www.mastercard.com/brandcenter/us/en/home.html',
                    'visa' => 'https://www.visaeurope.com'
                )
            )
        ));

        wp_register_style(
            'packlink-checkout-steps',
            PACKLINK_CUSTOM_PLUGIN_URL . 'assets/css/checkout-steps.css',
            array('packlink-frontend-styles'),
            PACKLINK_CUSTOM_VERSION
        );

        if (function_exists('is_checkout') && is_checkout()) {
            wp_enqueue_style('packlink-checkout-steps');
            wp_enqueue_script('packlink-checkout-steps');
        }
    }

    /**
     * Get JavaScript variables for localization
     *
     * @return array Array of JavaScript variables
     */
    private static function get_js_variables()
    {
        return array(
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
        );
    }

    /**
     * Add currency data to JavaScript
     *
     * @return void
     */
    public static function add_currency_data_to_js()
    {
        // Only add currency data where Packlink scripts are enqueued
        if (!wp_script_is('packlink-custom-form', 'enqueued')) {
            return;
        }

        $currency_data = Packlink_Currency_Handler::get_currency_data_for_js();

?>
        <script type="text/javascript">
            window.packlinkCurrency = <?php echo json_encode($currency_data); ?>;

            // Function to format price with currency
            window.formatPacklinkPrice = function(amount, convert) {
                convert = convert !== false; // Default to true

                if (!window.packlinkCurrency) {
                    return amount.toFixed(2);
                }

                var currency = window.packlinkCurrency;
                var price = convert && currency.is_multi_currency_active ? amount : amount;

                // Format number
                var formatted = price.toFixed(currency.decimals);
                formatted = formatted.replace('.', currency.decimal_separator);

                // Add thousand separators
                if (currency.thousand_separator) {
                    var parts = formatted.split(currency.decimal_separator);
                    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, currency.thousand_separator);
                    formatted = parts.join(currency.decimal_separator);
                }

                // Add currency symbol
                switch (currency.position) {
                    case 'left':
                        return currency.symbol + formatted;
                    case 'right':
                        return formatted + currency.symbol;
                    case 'left_space':
                        return currency.symbol + ' ' + formatted;
                    case 'right_space':
                        return formatted + ' ' + currency.symbol;
                    default:
                        return currency.symbol + formatted;
                }
            };

            // Function to convert price using current rates
            window.convertPacklinkPrice = function(amount, fromCurrency, toCurrency) {
                if (!window.packlinkCurrency || !window.packlinkCurrency.is_multi_currency_active) {
                    return amount;
                }

                toCurrency = toCurrency || window.packlinkCurrency.current_currency;

                if (fromCurrency === toCurrency) {
                    return amount;
                }

                var rates = window.packlinkCurrency.rates;
                if (rates[toCurrency]) {
                    return amount * parseFloat(rates[toCurrency].rate);
                }

                return amount;
            };
        </script>
<?php
    }
}
