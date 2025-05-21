<?php
/**
 * Packlink Lookup Class
 * Handles postal code lookups
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Packlink_Lookup {
    /**
     * Initialize hooks
     */
    public static function init() {
        // AJAX handler for postal code lookup
        add_action('wp_ajax_packlink_lookup_postal_code', array(__CLASS__, 'lookup_postal_code'));
        add_action('wp_ajax_nopriv_packlink_lookup_postal_code', array(__CLASS__, 'lookup_postal_code'));
    }
    
    /**
     * Lookup postal code via AJAX
     */
    public static function lookup_postal_code() {
        check_ajax_referer('packlink_shipping_nonce', 'nonce');
        
        $postal_code = isset($_POST['postal_code']) ? sanitize_text_field($_POST['postal_code']) : '';
        $country = isset($_POST['country']) ? sanitize_text_field($_POST['country']) : '';
        
        if (empty($postal_code) || empty($country)) {
            wp_send_json_error(array('message' => 'Postal code and country are required'));
            return;
        }
        
        $postal_code_obj = new Packlink_Postal_Code();
        $result = $postal_code_obj->lookup($postal_code, $country);
        
        wp_send_json_success($result);
    }
}

// Initialize the lookup class
Packlink_Lookup::init();