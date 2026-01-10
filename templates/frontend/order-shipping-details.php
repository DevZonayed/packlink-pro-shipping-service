<?php

/**
 * Template for displaying Packlink shipping details on order pages
 *
 * @package PacklinkCustomShipping
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get the order ID
$order_id = isset($order_id) ? $order_id : false;
if (!$order_id && isset($order) && is_object($order)) {
    $order_id = $order->get_id();
}

// Exit if no order ID
if (!$order_id) {
    return;
}

// Retrieve route and package information
$routes_info = get_post_meta($order_id, '_packlink_routes_info', true);
$packages_info = get_post_meta($order_id, '_packlink_packages_info', true);
$total_price = get_post_meta($order_id, '_packlink_total_price', true);

// Check if we have any Packlink data to display
if (empty($routes_info) && empty($packages_info)) {
    return;
}
?>

<div class="packlink-shipping-details">
    <h3><?php esc_html_e('Reluggz Shipping Details', 'packlink-custom-shipping'); ?></h3>

    <?php if (!empty($routes_info) && is_array($routes_info)) : ?>
        <h4><?php esc_html_e('Routes Information', 'packlink-custom-shipping'); ?></h4>

        <?php foreach ($routes_info as $route_key => $route) : ?>
            <div class="packlink-route">
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

    <?php if (!empty($packages_info) && is_array($packages_info)) : ?>
        <h4><?php esc_html_e('Package Information', 'packlink-custom-shipping'); ?></h4>

        <?php foreach ($packages_info as $package_key => $package) : ?>
            <div class="packlink-package">
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