<?php
/**
 * Settings functionality
 *
 * @package PacklinkCustomShipping
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Settings class to handle plugin settings
 */
class Packlink_Settings {
    /**
     * Register settings
     *
     * @return void
     */
    public static function register_settings() {
        // API Settings
        register_setting('packlink_api_settings', 'packlink_api_key');
        register_setting('packlink_api_settings', 'packlink_language');
        register_setting('packlink_api_settings', 'packlink_country');

        // Shipping Settings
        register_setting('packlink_shipping_settings', 'packlink_auto_create_shipment');
        register_setting('packlink_shipping_settings', 'packlink_order_status_after_shipment');
        register_setting('packlink_shipping_settings', 'packlink_default_package_weight');
        register_setting('packlink_shipping_settings', 'packlink_default_package_length');
        register_setting('packlink_shipping_settings', 'packlink_default_package_width');
        register_setting('packlink_shipping_settings', 'packlink_default_package_height');

        register_setting('packlink_shipping_settings', 'packlink_allow_multiple_packages');
        register_setting('packlink_shipping_settings', 'packlink_commission_type');
        register_setting('packlink_shipping_settings', 'packlink_commission_fixed_amount');
        register_setting('packlink_shipping_settings', 'packlink_commission_percentage');
    }

    /**
     * Add menu page
     *
     * @return void
     */
    public static function add_menu_page() {
        add_menu_page(
            __('Packlink Shipping', 'packlink-custom-shipping'),
            __('Packlink Shipping', 'packlink-custom-shipping'),
            'manage_options',
            'packlink-settings',
            array(self::class, 'render_settings_page'),
            'dashicons-cart',
            56
        );

        add_submenu_page(
            'packlink-settings',
            __('API Settings', 'packlink-custom-shipping'),
            __('API Settings', 'packlink-custom-shipping'),
            'manage_options',
            'packlink-settings',
            array(self::class, 'render_settings_page')
        );

        add_submenu_page(
            'packlink-settings',
            __('Shipping Settings', 'packlink-custom-shipping'),
            __('Shipping Settings', 'packlink-custom-shipping'),
            'manage_options',
            'packlink-shipping-settings',
            array(self::class, 'render_shipping_settings_page')
        );
    }

    /**
     * Render settings page
     *
     * @return void
     */
    public static function render_settings_page() {
        include PACKLINK_CUSTOM_PLUGIN_DIR . 'templates/admin/settings-api.php';
    }

    /**
     * Render shipping settings page
     *
     * @return void
     */
    public static function render_shipping_settings_page() {
        include PACKLINK_CUSTOM_PLUGIN_DIR . 'templates/admin/settings-shipping.php';
    }
}