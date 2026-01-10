<?php

/**
 * Order display handling for Packlink shipping details
 *
 * @package PacklinkCustomShipping
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class for handling display of Packlink shipping details in various WooCommerce locations
 */
class Packlink_Order_Display
{
    /**
     * Initialize hooks
     */
    public static function init()
    {
        // Display shipping details on thank you page
        add_action('woocommerce_thankyou', array(self::class, 'display_on_thankyou_page'), 20);

        // Display shipping details on order view in my-account
        add_action('woocommerce_order_details_after_order_table', array(self::class, 'display_on_order_details'), 20);

        // Add a more prominent display on my-account view-order page
        add_action('woocommerce_view_order', array(self::class, 'display_on_view_order_page'), 10);

        // Add shipping info to order emails
        add_action('woocommerce_email_after_order_table', array(self::class, 'display_in_emails'), 20);

        // Display in admin order view
        add_action('woocommerce_admin_order_data_after_shipping_address', array(self::class, 'display_in_admin_order'), 20);

        // Add to order totals table
        add_filter('woocommerce_get_order_item_totals', array(self::class, 'add_to_order_totals'), 25, 2);

        // Add CSS styles for the shipping details
        add_action('wp_enqueue_scripts', array(self::class, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array(self::class, 'enqueue_admin_styles'));
    }

    /**
     * Display shipping details prominently on view order page
     *
     * @param int $order_id Order ID
     */
    public static function display_on_view_order_page($order_id)
    {
        // Check if we have Packlink data
        $routes_info = get_post_meta($order_id, '_packlink_routes_info', true);
        $packages_info = get_post_meta($order_id, '_packlink_packages_info', true);

        if (empty($routes_info) && empty($packages_info)) {
            return;
        }

        echo '<h2>' . __('Reluggz Shipping Details', 'packlink-custom-shipping') . '</h2>';
        self::display_shipping_details($order_id);
    }

    /**
     * Display shipping details on checkout review section
     */
    public static function display_on_checkout_review()
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

        if (
            !$shipping_details || !isset($shipping_details['routes']) || empty($shipping_details['routes']) ||
            !isset($shipping_details['packages']) || empty($shipping_details['packages'])
        ) {
            return;
        }

        // Format data for display
        $routes_info = array();
        foreach ($shipping_details['routes'] as $index => $route) {
            $route_number = $index + 1;
            $routes_info["route_{$route_number}"] = array(
                'number' => $route_number,
                'origin' => $route['origin_address'] . ', ' . $route['origin_postal_code'] . ' - ' . $route['origin_city'] . ', ' . $route['origin_country'],
                'destination' => $route['destination_address'] . ', ' . $route['destination_postal_code'] . ' - ' . $route['destination_city'] . ', ' . $route['destination_country'],
                'collection_date' => $route['collection_date'],
                'shipping_method' => isset($route['shipping_method_name']) ? $route['shipping_method_name'] : '',
                'price' => isset($route['price']) ? $route['price'] : '0',
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

            if (isset($route['drop_off_id']) && isset($route['drop_off_name'])) {
                $routes_info["route_{$route_number}"]['drop_off'] = array(
                    'id' => $route['drop_off_id'],
                    'name' => $route['drop_off_name'],
                    'address' => isset($route['drop_off_address']) ? $route['drop_off_address'] : ''
                );
            }
        }

        $packages_info = array();
        foreach ($shipping_details['packages'] as $index => $package) {
            $package_number = $index + 1;
            $packages_info["package_{$package_number}"] = array(
                'number' => $package_number,
                'weight' => $package['weight'],
                'dimensions' => $package['length'] . ' × ' . $package['width'] . ' × ' . $package['height']
            );
        }

        $total_price = isset($shipping_details['total_price']) ? $shipping_details['total_price'] : '0';

        // Display the shipping details
?>
        <div class="packlink-checkout-review">
            <h3><?php esc_html_e('Reluggz Shipping Details', 'packlink-custom-shipping'); ?></h3>

            <?php if (!empty($routes_info)) : ?>
                <h4><?php esc_html_e('Routes Information', 'packlink-custom-shipping'); ?></h4>

                <?php foreach ($routes_info as $route_key => $route) : ?>
                    <div class="packlink-checkout-route">
                        <h5><?php echo sprintf(esc_html__('Route #%d', 'packlink-custom-shipping'), $route['number']); ?></h5>
                        <p><strong><?php esc_html_e('Origin:', 'packlink-custom-shipping'); ?></strong> <?php echo esc_html($route['origin']); ?></p>
                        <p><strong><?php esc_html_e('Destination:', 'packlink-custom-shipping'); ?></strong> <?php echo esc_html($route['destination']); ?></p>
                        <p><strong><?php esc_html_e('Collection Date:', 'packlink-custom-shipping'); ?></strong> <?php echo esc_html($route['collection_date']); ?></p>
                        <p><strong><?php esc_html_e('Shipping Method:', 'packlink-custom-shipping'); ?></strong> <?php echo esc_html($route['shipping_method']); ?></p>
                        <p><strong><?php esc_html_e('Price:', 'packlink-custom-shipping'); ?></strong> <?php echo '€' . esc_html($route['price']); ?></p>

                        <div class="packlink-contacts">
                            <div class="packlink-contact-box">
                                <h6><?php esc_html_e('Sender', 'packlink-custom-shipping'); ?></h6>
                                <p><?php echo esc_html($route['sender']['name']); ?></p>
                                <p><?php echo esc_html($route['sender']['email']); ?></p>
                                <p><?php echo esc_html($route['sender']['phone']); ?></p>
                                <?php if (!empty($route['sender']['company'])) : ?>
                                    <p><?php echo esc_html($route['sender']['company']); ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="packlink-contact-box">
                                <h6><?php esc_html_e('Recipient', 'packlink-custom-shipping'); ?></h6>
                                <p><?php echo esc_html($route['recipient']['name']); ?></p>
                                <p><?php echo esc_html($route['recipient']['email']); ?></p>
                                <p><?php echo esc_html($route['recipient']['phone']); ?></p>
                                <?php if (!empty($route['recipient']['company'])) : ?>
                                    <p><?php echo esc_html($route['recipient']['company']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (isset($route['drop_off']) && !empty($route['drop_off']['name'])) : ?>
                            <div class="packlink-drop-off">
                                <p><strong><?php esc_html_e('Drop-off Point:', 'packlink-custom-shipping'); ?></strong> <?php echo esc_html($route['drop_off']['name']); ?></p>
                                <p><?php echo esc_html($route['drop_off']['address']); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($packages_info)) : ?>
                <h4><?php esc_html_e('Package Information', 'packlink-custom-shipping'); ?></h4>

                <?php foreach ($packages_info as $package_key => $package) : ?>
                    <div class="packlink-checkout-package">
                        <h5><?php echo sprintf(esc_html__('Package %d', 'packlink-custom-shipping'), $package['number']); ?></h5>
                        <p><strong><?php esc_html_e('Weight (kg):', 'packlink-custom-shipping'); ?></strong> <?php echo esc_html($package['weight']); ?> kg</p>
                        <p><strong><?php esc_html_e('Dimensions (cm):', 'packlink-custom-shipping'); ?></strong> <?php echo esc_html($package['dimensions']); ?> cm</p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($total_price)) : ?>
                <h4><?php esc_html_e('Total Price', 'packlink-custom-shipping'); ?></h4>
                <p class="packlink-total-price"><strong><?php esc_html_e('Total:', 'packlink-custom-shipping'); ?></strong> <?php echo '€' . esc_html($total_price); ?></p>
            <?php endif; ?>
        </div>
<?php
    }

    /**
     * Display shipping details on thank you page
     *
     * @param int $order_id Order ID
     */
    public static function display_on_thankyou_page($order_id)
    {
        self::display_shipping_details($order_id);
    }

    /**
     * Display shipping details on order details page
     *
     * @param WC_Order $order Order object
     */
    public static function display_on_order_details($order)
    {
        if ($order && is_object($order) && method_exists($order, 'get_id')) {
            self::display_shipping_details($order->get_id());
        }
    }

    /**
     * Display shipping details in order emails
     *
     * @param WC_Order $order Order object
     */
    public static function display_in_emails($order)
    {
        if ($order && is_object($order) && method_exists($order, 'get_id')) {
            echo '<div style="margin-bottom: 40px; margin-top: 20px; padding: 15px; border: 1px solid #e5e5e5;">';
            self::display_shipping_details($order->get_id());
            echo '</div>';
        }
    }

    /**
     * Display shipping details in admin order view
     *
     * @param WC_Order $order Order object
     */
    public static function display_in_admin_order($order)
    {
        if ($order && is_object($order) && method_exists($order, 'get_id')) {
            echo '<div class="packlink-admin-order-details">';
            self::display_shipping_details($order->get_id());
            echo '</div>';
        }
    }

    /**
     * Add to order totals table
     *
     * @param array $total_rows Total rows
     * @param WC_Order $order Order object
     * @return array Modified total rows
     */
    public static function add_to_order_totals($total_rows, $order)
    {
        // Check if we have Packlink shipping information
        $routes_info = get_post_meta($order->get_id(), '_packlink_routes_info', true);

        if (!empty($routes_info) && is_array($routes_info)) {
            $route_details = array();
            foreach ($routes_info as $route) {
                $route_details[] = sprintf(
                    __('Route #%d: %s to %s', 'packlink-custom-shipping'),
                    $route['number'],
                    $route['origin'],
                    $route['destination']
                );
            }

            $total_rows['packlink_shipping'] = array(
                'label' => __('Reluggz Shipping:', 'packlink-custom-shipping'),
                'value' => implode('<br>', $route_details),
            );
        }

        return $total_rows;
    }

    /**
     * Display shipping details
     *
     * @param int $order_id Order ID
     */
    private static function display_shipping_details($order_id)
    {
        // Load the template
        include PACKLINK_CUSTOM_PLUGIN_DIR . 'templates/frontend/order-shipping-details.php';
    }

    /**
     * Enqueue styles for frontend
     */
    public static function enqueue_styles()
    {
        wp_enqueue_style(
            'packlink-order-details',
            PACKLINK_CUSTOM_PLUGIN_URL . 'assets/css/order-details.css',
            array(),
            PACKLINK_CUSTOM_VERSION
        );
    }

    /**
     * Enqueue styles for admin
     */
    public static function enqueue_admin_styles()
    {
        wp_enqueue_style(
            'packlink-admin-order-details',
            PACKLINK_CUSTOM_PLUGIN_URL . 'assets/css/admin-order-details.css',
            array(),
            PACKLINK_CUSTOM_VERSION
        );
    }
}
