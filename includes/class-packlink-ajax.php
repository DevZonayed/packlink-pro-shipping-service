<?php
/**
 * Packlink AJAX Class
 * Handles AJAX operations for Packlink
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Packlink_AJAX {
    /**
     * Initialize AJAX hooks
     */
    public static function init() {
        // AJAX handlers for both logged in and non-logged in users
        add_action('wp_ajax_packlink_get_destinations', array(__CLASS__, 'get_destinations'));
        add_action('wp_ajax_nopriv_packlink_get_destinations', array(__CLASS__, 'get_destinations'));
        
        // AJAX handler for postal code search
        add_action('wp_ajax_packlink_search_postal_code', array(__CLASS__, 'search_postal_code'));
        add_action('wp_ajax_nopriv_packlink_search_postal_code', array(__CLASS__, 'search_postal_code'));
        
        // AJAX handler for postal code suggestions
        add_action('wp_ajax_packlink_suggest_postal_codes', array(__CLASS__, 'suggest_postal_codes'));
        add_action('wp_ajax_nopriv_packlink_suggest_postal_codes', array(__CLASS__, 'suggest_postal_codes'));
        
        // AJAX handler for getting formatted destinations
        add_action('wp_ajax_packlink_get_formatted_destinations', array(__CLASS__, 'get_formatted_destinations'));
        add_action('wp_ajax_nopriv_packlink_get_formatted_destinations', array(__CLASS__, 'get_formatted_destinations'));
    }
    
    /**
     * Get destinations via AJAX
     */
    public static function get_destinations() {
        check_ajax_referer('packlink-ajax-nonce', 'security');
        
        $postal_code = new Packlink_Postal_Code();
        $destinations = $postal_code->get_postal_zones();
        
        wp_send_json_success($destinations);
    }
    
    /**
     * Get formatted destinations via AJAX
     */
    public static function get_formatted_destinations() {
        check_ajax_referer('packlink-ajax-nonce', 'security');
        
        $postal_code = new Packlink_Postal_Code();
        $destinations = $postal_code->get_formatted_postal_zones();
        
        wp_send_json_success($destinations);
    }
    
    /**
     * Search postal code via AJAX
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
}

// Initialize the AJAX class
Packlink_AJAX::init();
