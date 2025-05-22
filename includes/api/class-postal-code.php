<?php
/**
 * Packlink Postal Code Class
 * Handles postal code operations
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Packlink_Postal_Code {
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
     * Get postal zones
     *
     * @return array
     */
    public function get_postal_zones() {
        $response = $this->api->get('locations/postalzones/destinations', [
            'language' => $this->api->get_language()
        ]);
        
        if (is_wp_error($response)) {
            return [];
        }
        
        return $response;
    }
    
    /**
     * Get formatted postal zones for dropdown
     *
     * @return array
     */
    public function get_formatted_postal_zones() {
        $zones = $this->get_postal_zones();
        $formatted_zones = [];
        
        if (!empty($zones)) {
            foreach ($zones as $zone) {
                if (isset($zone['id']) && isset($zone['name']) && isset($zone['isoCode'])) {
                    $formatted_zones[$zone['id']] = $zone['name'] . ' (' . $zone['isoCode'] . ')';
                }
            }
        }
        
        return $formatted_zones;
    }
    
    /**
     * Get postal code details
     *
     * @param string $postal_code Postal code
     * @param string $country Country code
     * @return array
     */
    public function get($postal_code, $country) {
        $response = $this->api->get('locations/postalcodes', [
            'postalzone' => $country,
            'q' => $postal_code,
            'language' => $this->api->get_language(),
            "platform" => 'PRO'
        ]);
        
        if (is_wp_error($response)) {
            return [];
        }
        
        return $response;
    }
    
    /**
     * Suggest postal codes
     *
     * @param string $term Search term
     * @param string $country Country code
     * @return array
     */
    public function suggest($term, $country) {
        // First try to get from cache
        $cache_key = 'packlink_postal_' . $country . '_' . $term;
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        // If not in cache, fetch from API
        $response = $this->api->get('locations/postalcodes', [
            'postalzone' => $country,
            'q' => $term,
            'language' => $this->api->get_language()
        ]);
        
        if (is_wp_error($response)) {
            return [];
        }
        
        // Format the response
        $suggestions = [];
        
        if (is_array($response)) {
            foreach ($response as $item) {
                $suggestions[] = [
                    'postal_code' => isset($item['zipcode']) ? $item['zipcode'] : '',
                    'city' => isset($item['city']) ? $item['city'] : '',
                    'state' => isset($item['state']) ? $item['state'] : '',
                ];
            }
        }
        
        // Cache for 1 hour
        set_transient($cache_key, $suggestions, HOUR_IN_SECONDS);
        
        return $suggestions;
    }
    
    /**
     * Lookup postal code to get city
     *
     * @param string $postal_code Postal code
     * @param string $country Country code
     * @return array
     */
    public function lookup($postal_code, $country) {
        $results = $this->get($postal_code, $country);
        
        if (empty($results)) {
            return [
                'city' => '',
                'state' => '',
            ];
        }
        
        // Return the first result
        return [
            'city' => isset($results[0]['city']) ? $results[0]['city'] : '',
            'state' => isset($results[0]['state']) ? $results[0]['state'] : '',
        ];
    }
}
