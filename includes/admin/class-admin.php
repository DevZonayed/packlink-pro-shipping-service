<?php
/**
 * Admin functionality
 *
 * @package PacklinkCustomShipping
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Admin class to handle admin-specific functionality
 */
class Packlink_Admin {
    /**
     * Initialize admin functionality
     *
     * @return void
     */
    public static function init() {
        // Add test connection AJAX handler
        add_action('wp_ajax_packlink_test_connection', array(self::class, 'test_connection'));
    }
    
    /**
     * Test connection to Packlink API
     *
     * @return void
     */
    public static function test_connection() {
        // Verify nonce
        if (!check_ajax_referer('packlink_test_connection', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Invalid security token.', 'packlink-custom-shipping')
            ));
        }

        $api = new Packlink_Api();

        // Test connection by getting postal zones
        $response = $api->get('locations/postalzones/origins', array(
            'language' => $api->get_language()
        ));

        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => $response->get_error_message()
            ));
        } else {
            wp_send_json_success(array(
                'message' => __('Connection successful! Your API key is valid.', 'packlink-custom-shipping')
            ));
        }
    }
}