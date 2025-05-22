<?php
/**
 * Uninstall script for Packlink Custom Shipping
 *
 * @package PacklinkCustomShipping
 */

// If uninstall is not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete options
$options = array(
    'packlink_api_key',
    'packlink_language',
    'packlink_country',
    'packlink_sender_name',
    'packlink_sender_company',
    'packlink_sender_phone',
    'packlink_sender_email',
    'packlink_sender_address',
    'packlink_auto_create_shipment',
    'packlink_order_status_after_shipment',
    'packlink_default_package_weight',
    'packlink_default_package_length',
    'packlink_default_package_width',
    'packlink_default_package_height',
    'packlink_allow_multiple_packages',
    'packlink_commission_type',
    'packlink_commission_fixed_amount',
    'packlink_commission_percentage'
);

foreach ($options as $option) {
    delete_option($option);
}

// Delete the virtual shipping product if it exists
$products = get_posts(array(
    'post_type' => 'product',
    'meta_key' => '_packlink_shipping_product',
    'meta_value' => 'yes',
    'posts_per_page' => 1,
    'post_status' => 'any'
));

if (!empty($products)) {
    wp_delete_post($products[0]->ID, true);
}

// Clear any cached data
global $wpdb;
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_packlink_%'");
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_packlink_%'");