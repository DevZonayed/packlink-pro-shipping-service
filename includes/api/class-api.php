<?php
/**
 * Packlink API Class
 *
 * @package PacklinkCustomShipping
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles communication with the Packlink API
 */
class Packlink_Api {
    /**
     * API base URL
     *
     * @var string
     */
    const API_BASE_URL = 'https://api.packlink.com/v1/';
    
    /**
     * API language
     *
     * @var string
     */
    private $language = 'en_US';
    
    /**
     * API platform country
     *
     * @var string
     */
    private $platform_country = 'ES';
    
    /**
     * API key
     *
     * @var string
     */
    private $api_key;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->api_key = get_option('packlink_api_key', '');
        $this->language = get_option('packlink_language', 'en_US');
        $this->platform_country = get_option('packlink_country', 'ES');
    }
    
    /**
     * Make a GET request to the Packlink API
     *
     * @param string $endpoint API endpoint.
     * @param array  $params Query parameters.
     * @return array|WP_Error Response data or WP_Error on failure
     */
    public function get($endpoint, $params = array()) {
        return $this->request('GET', $endpoint, $params);
    }
    
    /**
     * Make a POST request to the Packlink API
     *
     * @param string $endpoint API endpoint.
     * @param array  $data Request data.
     * @return array|WP_Error Response data or WP_Error on failure
     */
    public function post($endpoint, $data = array()) {
        return $this->request('POST', $endpoint, array(), $data);
    }
    
    /**
     * Make a PUT request to the Packlink API
     *
     * @param string $endpoint API endpoint.
     * @param array  $data Request data.
     * @return array|WP_Error Response data or WP_Error on failure
     */
    public function put($endpoint, $data = array()) {
        return $this->request('PUT', $endpoint, array(), $data);
    }
    
    /**
     * Make a DELETE request to the Packlink API
     *
     * @param string $endpoint API endpoint.
     * @param array  $params Query parameters.
     * @return array|WP_Error Response data or WP_Error on failure
     */
    public function delete($endpoint, $params = array()) {
        return $this->request('DELETE', $endpoint, $params);
    }
    
    /**
     * Make a request to the Packlink API
     *
     * @param string $method HTTP method.
     * @param string $endpoint API endpoint.
     * @param array  $params Query parameters.
     * @param array  $data Request data.
     * @return array|WP_Error Response data or WP_Error on failure
     */
    private function request($method, $endpoint, $params = array(), $data = null) {
        if (empty($this->api_key)) {
            return new WP_Error(
                'packlink_api_error',
                __('Packlink API key is not set', 'packlink-custom-shipping')
            );
        }
        
        $url = self::API_BASE_URL . $endpoint;
        
        // Add query parameters
        if (!empty($params)) {
            $url = add_query_arg($params, $url);
        }
        
        $args = array(
            'method' => $method,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => $this->api_key,
                'Accept-Language' => $this->language
            ),
            'timeout' => 30,
            'sslverify' => true
        );
        
        // Add request body for POST and PUT requests
        if (in_array($method, array('POST', 'PUT')) && !is_null($data)) {
            $args['body'] = json_encode($data);
        }
        
        // Log request for debugging
        $this->log_request($method, $url, $args);
        
        // Make the request
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            $this->log_error($response->get_error_message());
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        // Log response for debugging
        $this->log_response($response_code, $response_body);
        
        if ($response_code >= 400) {
            $error_message = $this->parse_error_message($response_body);
            
            return new WP_Error(
                'packlink_api_error',
                sprintf(
                    __('Packlink API error (%d): %s', 'packlink-custom-shipping'),
                    $response_code,
                    $error_message
                ),
                array('status' => $response_code)
            );
        }
        
        $data = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error(
                'packlink_api_error',
                __('Invalid JSON response from Packlink API', 'packlink-custom-shipping')
            );
        }
        
        return $data;
    }
    
    /**
     * Parse error message from response body
     *
     * @param string $response_body Response body.
     * @return string Error message
     */
    private function parse_error_message($response_body) {
        $data = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $response_body;
        }
        
        if (isset($data['message'])) {
            return $data['message'];
        }
        
        if (isset($data['error'])) {
            if (is_string($data['error'])) {
                return $data['error'];
            }
            
            if (is_array($data['error']) && isset($data['error']['message'])) {
                return $data['error']['message'];
            }
        }
        
        return __('Unknown error', 'packlink-custom-shipping');
    }
    
    /**
     * Log API request for debugging
     *
     * @param string $method HTTP method.
     * @param string $url Request URL.
     * @param array  $args Request arguments.
     * @return void
     */
    private function log_request($method, $url, $args) {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        $log_message = sprintf(
            "[Packlink API Request] %s %s\nHeaders: %s\nBody: %s",
            $method,
            $url,
            json_encode($args['headers']),
            isset($args['body']) ? $args['body'] : 'N/A'
        );
        
        error_log($log_message);
    }
    
    /**
     * Log API response for debugging
     *
     * @param int    $response_code Response code.
     * @param string $response_body Response body.
     * @return void
     */
    private function log_response($response_code, $response_body) {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        $log_message = sprintf(
            "[Packlink API Response] Status: %d\nBody: %s",
            $response_code,
            $response_body
        );
        
        error_log($log_message);
    }
    
    /**
     * Log error message
     *
     * @param string $message Error message.
     * @return void
     */
    private function log_error($message) {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        error_log("[Packlink API Error] " . $message);
    }
    
    /**
     * Get API key
     *
     * @return string API key
     */
    public function get_api_key() {
        return $this->api_key;
    }
    
    /**
     * Set API key
     *
     * @param string $api_key API key.
     * @return void
     */
    public function set_api_key($api_key) {
        $this->api_key = $api_key;
    }
    
    /**
     * Get language
     *
     * @return string Language
     */
    public function get_language() {
        return $this->language;
    }
    
    /**
     * Set language
     *
     * @param string $language Language.
     * @return void
     */
    public function set_language($language) {
        $this->language = $language;
    }
    
    /**
     * Get platform country
     *
     * @return string Platform country
     */
    public function get_platform_country() {
        return $this->platform_country;
    }
    
    /**
     * Set platform country
     *
     * @param string $platform_country Platform country.
     * @return void
     */
    public function set_platform_country($platform_country) {
        $this->platform_country = $platform_country;
    }
}