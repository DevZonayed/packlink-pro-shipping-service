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
     * Initialize hooks
     */
    public static function init()
    {
        // Prefill checkout fields with Packlink data
        add_filter('woocommerce_checkout_get_value', array(__CLASS__, 'prefill_checkout_fields'), 10, 2);

        // Save Packlink data to order meta
        add_action('woocommerce_checkout_create_order', array(__CLASS__, 'save_packlink_data_to_order'), 10, 2);

        // Display Packlink shipping information in order
        add_action('woocommerce_order_details_after_order_table', array(__CLASS__, 'display_packlink_shipping_info'), 10, 1);

        // Display Packlink shipping information in emails
        add_action('woocommerce_email_order_details', array(__CLASS__, 'display_packlink_shipping_info_email'), 20, 4);

        // Add Packlink shipping info to order admin page
        add_action('woocommerce_admin_order_data_after_shipping_address', array(__CLASS__, 'display_packlink_shipping_info_admin'), 10, 1);
    }

    /**
     * Prefill checkout fields with Packlink data
     *
     * @param mixed  $value Field value.
     * @param string $key Field key.
     * @return mixed Modified value
     */
    public static function prefill_checkout_fields($value, $key)
    {
        // Check if we have Packlink data in session
        $billing_data = WC()->session->get('packlink_billing_data');

        if ($billing_data && isset($billing_data[$key])) {
            return $billing_data[$key];
        }

        return $value;
    }

    /**
     * Display Packlink shipping information in checkout order summary
     *
     * @return void
     */
    public static function display_packlink_shipping_in_checkout()
    {
        // Get Packlink data from session
        $routes_data = WC()->session->get('packlink_routes_data');
        $packages_data = WC()->session->get('packlink_packages_data');

        if (!$routes_data) {
            return;
        }

        echo '<div class="packlink-checkout-summary">';
        echo '<h3>' . __('Reluggz Shipping Details', 'packlink-custom-shipping') . '</h3>';

        // Display routes information
        if ($routes_data && is_array($routes_data)) {
            foreach ($routes_data as $route) {
                echo '<div class="packlink-checkout-route">';
                echo '<h4>' . sprintf(__('Route #%d', 'packlink-custom-shipping'), $route['route_number']) . '</h4>';
                echo '<ul class="packlink-route-details">';
                echo '<li><strong>' . __('Origin:', 'packlink-custom-shipping') . '</strong> ' . esc_html($route['origin']) . '</li>';
                echo '<li><strong>' . __('Destination:', 'packlink-custom-shipping') . '</strong> ' . esc_html($route['destination']) . '</li>';
                echo '<li><strong>' . __('Shipping Method:', 'packlink-custom-shipping') . '</strong> ' . esc_html($route['shipping_method']) . '</li>';
                echo '</ul>';
                echo '</div>';
            }
        }

        // Display packages information
        if ($packages_data && is_array($packages_data)) {
            foreach ($packages_data as $package) {
                echo '<div class="packlink-checkout-package">';
                echo '<h4>' . sprintf(__('Package %d', 'packlink-custom-shipping'), $package['package_number']) . '</h4>';
                echo '<ul class="packlink-package-details">';
                echo '<li><strong>' . __('Weight (kg):', 'packlink-custom-shipping') . '</strong> ' . esc_html($package['weight']) . '</li>';
                echo '<li><strong>' . __('Dimensions (cm):', 'packlink-custom-shipping') . '</strong> ' . esc_html($package['dimensions']) . '</li>';
                echo '</ul>';
                echo '</div>';
            }
        }

        echo '</div>';

        // Add some CSS for the summary
        echo '<style>
            .packlink-checkout-summary { margin: 20px 0; padding: 15px; border: 1px solid #ddd; background-color: #f9f9f9; }
            .packlink-checkout-summary h3 { margin-top: 0; }
            .packlink-checkout-route, .packlink-checkout-package { margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px dashed #ddd; }
            .packlink-checkout-route:last-child, .packlink-checkout-package:last-child { border-bottom: none; }
            .packlink-route-details, .packlink-package-details { list-style: none; margin: 0; padding: 0; }
            .packlink-route-details li, .packlink-package-details li { margin-bottom: 5px; }
        </style>';
    }

    /**
     * Save Packlink data to order meta
     *
     * @param WC_Order $order Order object.
     * @param array    $data Posted data.
     * @return void
     */
    public static function save_packlink_data_to_order($order, $data)
    {
        // Get Packlink data from session
        $routes_data = WC()->session->get('packlink_routes_data');
        $packages_data = WC()->session->get('packlink_packages_data');
        $total_price = WC()->session->get('packlink_total_price');

        if ($routes_data) {
            // Save routes data
            $order->update_meta_data('_packlink_routes_data', $routes_data);

            // Save individual route data separately for easier access
            foreach ($routes_data as $index => $route) {
                $route_number = $index + 1;
                $order->update_meta_data('_packlink_route_' . $route_number . '_origin', $route['origin']);
                $order->update_meta_data('_packlink_route_' . $route_number . '_destination', $route['destination']);
                $order->update_meta_data('_packlink_route_' . $route_number . '_shipping_method', $route['shipping_method']);
                $order->update_meta_data('_packlink_route_' . $route_number . '_collection_date', $route['collection_date']);

                // Save sender and recipient information
                if (isset($route['sender'])) {
                    $order->update_meta_data('_packlink_route_' . $route_number . '_sender_name', $route['sender']['name']);
                    $order->update_meta_data('_packlink_route_' . $route_number . '_sender_email', $route['sender']['email']);
                    $order->update_meta_data('_packlink_route_' . $route_number . '_sender_phone', $route['sender']['phone']);
                    $order->update_meta_data('_packlink_route_' . $route_number . '_sender_company', $route['sender']['company']);
                }

                if (isset($route['recipient'])) {
                    $order->update_meta_data('_packlink_route_' . $route_number . '_recipient_name', $route['recipient']['name']);
                    $order->update_meta_data('_packlink_route_' . $route_number . '_recipient_email', $route['recipient']['email']);
                    $order->update_meta_data('_packlink_route_' . $route_number . '_recipient_phone', $route['recipient']['phone']);
                    $order->update_meta_data('_packlink_route_' . $route_number . '_recipient_company', $route['recipient']['company']);
                }

                // Save drop-off information
                if (isset($route['drop_off']) && !empty($route['drop_off']['point'])) {
                    $order->update_meta_data('_packlink_route_' . $route_number . '_drop_off_point', $route['drop_off']['point']);
                    $order->update_meta_data('_packlink_route_' . $route_number . '_drop_off_address', $route['drop_off']['address']);
                }
            }

            // Save route count
            $order->update_meta_data('_packlink_route_count', count($routes_data));

            // Save packages data
            if ($packages_data) {
                $order->update_meta_data('_packlink_packages_data', $packages_data);

                // Save individual package data separately for easier access
                foreach ($packages_data as $index => $package) {
                    $package_number = $index + 1;
                    $order->update_meta_data('_packlink_package_' . $package_number . '_weight', $package['weight']);
                    $order->update_meta_data('_packlink_package_' . $package_number . '_dimensions', $package['dimensions']);
                }

                // Save package count
                $order->update_meta_data('_packlink_package_count', count($packages_data));
            }

            // Save total price
            if ($total_price) {
                $order->update_meta_data('_packlink_total_price', $total_price);
            }

            // Save currency information
            Packlink_Currency_Handler::save_currency_to_order($order, $routes_data);

            // Create a summary for order notes and meta
            $summary = "Reluggz Shipping Details:\n";

            // Add route information to summary
            foreach ($routes_data as $index => $route) {
                $route_number = $index + 1;
                $summary .= "\nRoute #{$route_number}\n";
                $summary .= "Origin: {$route['origin']}\n";
                $summary .= "Destination: {$route['destination']}\n";
                $summary .= "Shipping Method: {$route['shipping_method']}\n";
                $summary .= "Collection Date: {$route['collection_date']}\n";
            }

            // Add package information to summary
            if ($packages_data) {
                $summary .= "\nPackage Information:\n";
                foreach ($packages_data as $index => $package) {
                    $package_number = $index + 1;
                    $summary .= "\nPackage {$package_number}\n";
                    $summary .= "Weight (kg): {$package['weight']}\n";
                    $summary .= "Dimensions (cm): {$package['dimensions']}\n";
                }
            }

            // Save shipping summary as meta for display in order admin
            $order->update_meta_data('_packlink_shipping_summary', $summary);

            // Add order note with summary
            $order->add_order_note($summary);

            // Clear session data after order is created
            add_action('woocommerce_checkout_order_processed', function () {
                WC()->session->__unset('packlink_routes_data');
                WC()->session->__unset('packlink_packages_data');
                WC()->session->__unset('packlink_total_price');
                WC()->session->__unset('packlink_billing_data');
            });
        }
    }

    /**
     * Display Packlink shipping information in order
     *
     * @param WC_Order $order Order object.
     * @return void
     */
    public static function display_packlink_shipping_info($order)
    {
        $routes_data = $order->get_meta('_packlink_routes_data');
        $packages_data = $order->get_meta('_packlink_packages_data');

        if (!$routes_data) {
            return;
        }

        echo '<h2>' . __('Reluggz Shipping Information', 'packlink-custom-shipping') . '</h2>';
        echo '<div class="packlink-order-shipping-info">';

        // Display routes information
        echo '<h3>' . __('Routes Information', 'packlink-custom-shipping') . '</h3>';
        foreach ($routes_data as $route) {
            echo '<div class="packlink-route">';
            echo '<h4>' . sprintf(__('Route #%d', 'packlink-custom-shipping'), $route['route_number']) . '</h4>';
            echo '<p><strong>' . __('Origin:', 'packlink-custom-shipping') . '</strong> ' . esc_html($route['origin']) . '</p>';
            echo '<p><strong>' . __('Destination:', 'packlink-custom-shipping') . '</strong> ' . esc_html($route['destination']) . '</p>';
            echo '<p><strong>' . __('Collection Date:', 'packlink-custom-shipping') . '</strong> ' . esc_html($route['collection_date']) . '</p>';
            echo '<p><strong>' . __('Shipping Method:', 'packlink-custom-shipping') . '</strong> ' . esc_html($route['shipping_method']) . '</p>';

            // Display sender and recipient information
            echo '<div class="packlink-route-contacts">';

            // Sender
            echo '<div class="packlink-sender">';
            echo '<h5>' . __('Sender', 'packlink-custom-shipping') . '</h5>';
            echo '<p>' . esc_html($route['sender']['name']) . '</p>';
            echo '<p>' . esc_html($route['sender']['email']) . '</p>';
            echo '<p>' . esc_html($route['sender']['phone']) . '</p>';
            if (!empty($route['sender']['company'])) {
                echo '<p>' . esc_html($route['sender']['company']) . '</p>';
            }
            echo '</div>';

            // Recipient
            echo '<div class="packlink-recipient">';
            echo '<h5>' . __('Recipient', 'packlink-custom-shipping') . '</h5>';
            echo '<p>' . esc_html($route['recipient']['name']) . '</p>';
            echo '<p>' . esc_html($route['recipient']['email']) . '</p>';
            echo '<p>' . esc_html($route['recipient']['phone']) . '</p>';
            if (!empty($route['recipient']['company'])) {
                echo '<p>' . esc_html($route['recipient']['company']) . '</p>';
            }
            echo '</div>';

            echo '</div>'; // .packlink-route-contacts

            // Display drop-off information if available
            if (!empty($route['drop_off']['point'])) {
                echo '<div class="packlink-drop-off">';
                echo '<h5>' . __('Drop-off Point', 'packlink-custom-shipping') . '</h5>';
                echo '<p>' . esc_html($route['drop_off']['point']) . '</p>';
                if (!empty($route['drop_off']['address'])) {
                    echo '<p>' . esc_html($route['drop_off']['address']) . '</p>';
                }
                echo '</div>';
            }

            echo '</div>'; // .packlink-route
        }

        // Display packages information if available
        if ($packages_data) {
            echo '<h3>' . __('Package Information', 'packlink-custom-shipping') . '</h3>';
            foreach ($packages_data as $package) {
                echo '<div class="packlink-package">';
                echo '<h4>' . sprintf(__('Package %d', 'packlink-custom-shipping'), $package['package_number']) . '</h4>';
                echo '<p><strong>' . __('Weight:', 'packlink-custom-shipping') . '</strong> ' . esc_html($package['weight']) . '</p>';
                echo '<p><strong>' . __('Dimensions:', 'packlink-custom-shipping') . '</strong> ' . esc_html($package['dimensions']) . '</p>';
                echo '</div>';
            }
        }

        echo '</div>'; // .packlink-order-shipping-info
    }

    /**
     * Display Packlink shipping information in emails
     *
     * @param WC_Order $order Order object.
     * @param bool     $sent_to_admin Whether the email is sent to admin.
     * @param bool     $plain_text Whether the email is plain text.
     * @param WC_Email $email Email object.
     * @return void
     */
    public static function display_packlink_shipping_info_email($order, $sent_to_admin, $plain_text, $email)
    {
        if ($plain_text) {
            // Plain text email
            self::display_packlink_shipping_info_plain_text($order);
        } else {
            // HTML email
            self::display_packlink_shipping_info($order);
        }
    }

    /**
     * Display Packlink shipping information in plain text emails
     *
     * @param WC_Order $order Order object.
     * @return void
     */
    private static function display_packlink_shipping_info_plain_text($order)
    {
        $routes_data = $order->get_meta('_packlink_routes_data');
        $packages_data = $order->get_meta('_packlink_packages_data');

        if (!$routes_data) {
            return;
        }

        echo "\n\n" . __('RELUGGZ SHIPPING INFORMATION', 'packlink-custom-shipping') . "\n";
        echo "====================================\n";

        // Display routes information
        echo "\n" . __('ROUTES INFORMATION', 'packlink-custom-shipping') . "\n";
        foreach ($routes_data as $route) {
            echo "\n" . sprintf(__('Route #%d', 'packlink-custom-shipping'), $route['route_number']) . "\n";
            echo __('Origin:', 'packlink-custom-shipping') . ' ' . $route['origin'] . "\n";
            echo __('Destination:', 'packlink-custom-shipping') . ' ' . $route['destination'] . "\n";
            echo __('Collection Date:', 'packlink-custom-shipping') . ' ' . $route['collection_date'] . "\n";
            echo __('Shipping Method:', 'packlink-custom-shipping') . ' ' . $route['shipping_method'] . "\n";

            // Sender
            echo "\n" . __('Sender', 'packlink-custom-shipping') . "\n";
            echo $route['sender']['name'] . "\n";
            echo $route['sender']['email'] . "\n";
            echo $route['sender']['phone'] . "\n";
            if (!empty($route['sender']['company'])) {
                echo $route['sender']['company'] . "\n";
            }

            // Recipient
            echo "\n" . __('Recipient', 'packlink-custom-shipping') . "\n";
            echo $route['recipient']['name'] . "\n";
            echo $route['recipient']['email'] . "\n";
            echo $route['recipient']['phone'] . "\n";
            if (!empty($route['recipient']['company'])) {
                echo $route['recipient']['company'] . "\n";
            }

            // Drop-off information if available
            if (!empty($route['drop_off']['point'])) {
                echo "\n" . __('Drop-off Point', 'packlink-custom-shipping') . "\n";
                echo $route['drop_off']['point'] . "\n";
                if (!empty($route['drop_off']['address'])) {
                    echo $route['drop_off']['address'] . "\n";
                }
            }
        }

        // Display packages information if available
        if ($packages_data) {
            echo "\n" . __('PACKAGE INFORMATION', 'packlink-custom-shipping') . "\n";
            foreach ($packages_data as $package) {
                echo "\n" . sprintf(__('Package %d', 'packlink-custom-shipping'), $package['package_number']) . "\n";
                echo __('Weight:', 'packlink-custom-shipping') . ' ' . $package['weight'] . "\n";
                echo __('Dimensions:', 'packlink-custom-shipping') . ' ' . $package['dimensions'] . "\n";
            }
        }
    }

    /**
     * Display Packlink shipping information in admin order page
     *
     * @param WC_Order $order Order object.
     * @return void
     */
    public static function display_packlink_shipping_info_admin($order)
    {
        $routes_data = $order->get_meta('_packlink_routes_data');
        $packages_data = $order->get_meta('_packlink_packages_data');

        if (!$routes_data) {
            return;
        }

        echo '<div class="packlink-admin-order-shipping-info">';
        echo '<h3>' . __('Reluggz Shipping Information', 'packlink-custom-shipping') . '</h3>';

        // Display routes information
        echo '<h4>' . __('Routes Information', 'packlink-custom-shipping') . '</h4>';
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>' . __('Route', 'packlink-custom-shipping') . '</th>';
        echo '<th>' . __('Origin', 'packlink-custom-shipping') . '</th>';
        echo '<th>' . __('Destination', 'packlink-custom-shipping') . '</th>';
        echo '<th>' . __('Collection Date', 'packlink-custom-shipping') . '</th>';
        echo '<th>' . __('Shipping Method', 'packlink-custom-shipping') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($routes_data as $route) {
            echo '<tr>';
            echo '<td>' . sprintf(__('Route #%d', 'packlink-custom-shipping'), $route['route_number']) . '</td>';
            echo '<td>' . esc_html($route['origin']) . '</td>';
            echo '<td>' . esc_html($route['destination']) . '</td>';
            echo '<td>' . esc_html($route['collection_date']) . '</td>';
            echo '<td>' . esc_html($route['shipping_method']) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';

        // Display sender and recipient information for each route
        foreach ($routes_data as $route) {
            echo '<h4>' . sprintf(__('Route #%d Contact Information', 'packlink-custom-shipping'), $route['route_number']) . '</h4>';
            echo '<table class="widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th></th>';
            echo '<th>' . __('Name', 'packlink-custom-shipping') . '</th>';
            echo '<th>' . __('Email', 'packlink-custom-shipping') . '</th>';
            echo '<th>' . __('Phone', 'packlink-custom-shipping') . '</th>';
            echo '<th>' . __('Company', 'packlink-custom-shipping') . '</th>';
            echo '</tr></thead><tbody>';

            // Sender
            echo '<tr>';
            echo '<td><strong>' . __('Sender', 'packlink-custom-shipping') . '</strong></td>';
            echo '<td>' . esc_html($route['sender']['name']) . '</td>';
            echo '<td>' . esc_html($route['sender']['email']) . '</td>';
            echo '<td>' . esc_html($route['sender']['phone']) . '</td>';
            echo '<td>' . esc_html($route['sender']['company'] ?? '') . '</td>';
            echo '</tr>';

            // Recipient
            echo '<tr>';
            echo '<td><strong>' . __('Recipient', 'packlink-custom-shipping') . '</strong></td>';
            echo '<td>' . esc_html($route['recipient']['name']) . '</td>';
            echo '<td>' . esc_html($route['recipient']['email']) . '</td>';
            echo '<td>' . esc_html($route['recipient']['phone']) . '</td>';
            echo '<td>' . esc_html($route['recipient']['company'] ?? '') . '</td>';
            echo '</tr>';

            echo '</tbody></table>';

            // Display drop-off information if available
            if (!empty($route['drop_off']['point'])) {
                echo '<h4>' . sprintf(__('Route #%d Drop-off Point', 'packlink-custom-shipping'), $route['route_number']) . '</h4>';
                echo '<p><strong>' . esc_html($route['drop_off']['point']) . '</strong></p>';
                if (!empty($route['drop_off']['address'])) {
                    echo '<p>' . esc_html($route['drop_off']['address']) . '</p>';
                }
            }
        }

        // Display packages information if available
        if ($packages_data) {
            echo '<h4>' . __('Packages Information', 'packlink-custom-shipping') . '</h4>';
            echo '<table class="widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th>' . __('Package', 'packlink-custom-shipping') . '</th>';
            echo '<th>' . __('Weight', 'packlink-custom-shipping') . '</th>';
            echo '<th>' . __('Dimensions', 'packlink-custom-shipping') . '</th>';
            echo '</tr></thead><tbody>';

            foreach ($packages_data as $package) {
                echo '<tr>';
                echo '<td>' . sprintf(__('Package %d', 'packlink-custom-shipping'), $package['package_number']) . '</td>';
                echo '<td>' . esc_html($package['weight']) . '</td>';
                echo '<td>' . esc_html($package['dimensions']) . '</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        }

        echo '</div>'; // .packlink-admin-order-shipping-info
    }
}

// Initialize the checkout class
Packlink_Checkout::init();
