<?php
/**
 * Packlink Carrier Class
 * 
 * Handles shipping carriers and rates
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Packlink_Carrier {
    /**
     * API instance
     * 
     * @var Packlink_API
     */
    private $api;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->api = new Packlink_API();
    }
    
    /**
     * Get shipping rates
     * 
     * @param string $from_country From country code
     * @param string $from_zip From postal code
     * @param string $to_country To country code
     * @param string $to_zip To postal code
     * @param array $packages Packages
     * @param string $sort_by Sort by (default: totalPrice)
     * @return array Shipping rates
     */
    public function get_shipping_rates($from_country, $from_zip, $to_country, $to_zip, $packages, $sort_by = 'totalPrice') {
        // Validate input
        if (empty($from_country) || empty($from_zip) || empty($to_country) || empty($to_zip) || empty($packages)) {
            return [];
        }
        
        // Prepare request parameters
        $params = [
            'from' => [
                'country' => $from_country,
                'zip' => $from_zip
            ],
            'to' => [
                'country' => $to_country,
                'zip' => $to_zip
            ],
            'packages' => $packages,
            'sortBy' => $sort_by
        ];
        
        // Make API request
        $response = $this->api->get('services', $params);
        
        if (is_wp_error($response)) {
            error_log('Packlink API Error: ' . $response->get_error_message());
            // if (defined('WP_DEBUG') && WP_DEBUG) {
            // }
            return [];
        }
        
        // Format shipping rates
        $shipping_rates = [];
        
        foreach ($response as $rate) {
            $shipping_rates[] = [
                'id' => $rate['id'],
                'carrierName' => isset($rate['carrier_name']) ? $rate['carrier_name'] : '',
                'serviceName' => isset($rate['name']) ? $rate['name'] : '',
                'logoUrl' => isset($rate['logo_id']) ? $this->get_carrier_logo_url($rate['logo_id']) : '',
                'price' => isset($rate['price']['total_price']) ? $rate['price']['total_price'] : (isset($rate['base_price']) ? $rate['base_price'] : 0),
                'currency' => isset($rate['price']['currency']) ? $rate['price']['currency'] : (isset($rate['currency']) ? $rate['currency'] : 'EUR'),
                'deliveryTime' => isset($rate['transit_time']) ? $rate['transit_time'] : '',
                'isDropOff' => !empty($rate['dropoff']),
                'firstDeliveryDate' => isset($rate['first_estimated_delivery_date']) ? $rate['first_estimated_delivery_date'] : '',
                'serviceInfo' => isset($rate['service_info']) ? $rate['service_info'] : [],
                'availableDates' => isset($rate['available_dates']) ? $rate['available_dates'] : []
            ];
        }
        
        return $shipping_rates;
    }
    
    /**
     * Get carrier logo URL based on logo ID
     * 
     * @param string $logo_id Logo identifier
     * @return string Logo URL
     */
    private function get_carrier_logo_url($logo_id) {
        // Check if we have a local copy of the logo
        $local_logo_path = PACKLINK_CUSTOM_PLUGIN_DIR . 'assets/images/carriers/' . $logo_id . '.svg';
        $local_logo_url = PACKLINK_CUSTOM_PLUGIN_URL . 'assets/images/carriers/' . $logo_id . '.svg';
        
        if (file_exists($local_logo_path)) {
            return $local_logo_url;
        }
        
        // If not, try to get it from Packlink's CDN
        $cdn_url = 'https://cdn.packlink.com/apps/carrier-logos/' . $logo_id . '.svg';
        
        // Try to download and save the logo locally for future use
        $this->maybe_download_logo($logo_id, $cdn_url, $local_logo_path);
        
        return $cdn_url;
    }
    
    /**
     * Download and save carrier logo
     * 
     * @param string $logo_id Logo identifier
     * @param string $cdn_url CDN URL
     * @param string $local_path Local path to save the logo
     */
    private function maybe_download_logo($logo_id, $cdn_url, $local_path) {
        // Only try to download if we're in the admin area to avoid slowing down the frontend
        if (!is_admin()) {
            return;
        }
        
        // Check if the carriers directory exists, if not create it
        $carriers_dir = PACKLINK_CUSTOM_PLUGIN_DIR . 'assets/images/carriers';
        
        if (!file_exists($carriers_dir)) {
            wp_mkdir_p($carriers_dir);
        }
        
        // Download the logo
        $response = wp_remote_get($cdn_url);
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return;
        }
        
        $image_data = wp_remote_retrieve_body($response);
        
        if (empty($image_data)) {
            return;
        }
        
        // Save the logo
        file_put_contents($local_path, $image_data);
    }
    
    /**
     * Get shipping method details
     * 
     * @param int $method_id Shipping method ID
     * @return array|false Shipping method details or false on failure
     */
    public function get_shipping_method($method_id) {
        if (empty($method_id)) {
            return false;
        }
        
        $response = $this->api->get('services/' . $method_id);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        return [
            'id' => $response['id'],
            'carrierName' => isset($response['carrier_name']) ? $response['carrier_name'] : '',
            'serviceName' => isset($response['name']) ? $response['name'] : '',
            'logoUrl' => isset($response['logo_id']) ? $this->get_carrier_logo_url($response['logo_id']) : '',
            'isDropOff' => isset($response['dropoff']) ? !empty($response['dropoff']) : false
        ];
    }
    
    /**
     * Get available carriers
     * 
     * @return array Available carriers
     */
    public function get_carriers() {
        $response = $this->api->get('carriers');
        
        if (is_wp_error($response)) {
            return [];
        }
        
        $carriers = [];
        
        foreach ($response as $carrier) {
            $carriers[] = [
                'id' => $carrier['id'],
                'name' => $carrier['name'],
                'logoUrl' => isset($carrier['logo_id']) ? $this->get_carrier_logo_url($carrier['logo_id']) : ''
            ];
        }
        
        return $carriers;
    }
}
