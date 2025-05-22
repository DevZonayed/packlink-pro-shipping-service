<?php
/**
 * Drop-off locations handling
 *
 * @package PacklinkCustomShipping
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class to handle drop-off locations
 */
class Packlink_Drop_Off {
    /**
     * API instance
     * 
     * @var Packlink_Api
     */
    private $api;

    /**
     * Constructor
     */
    public function __construct() {
        $this->api = new Packlink_Api();
    }

    /**
     * Get drop-off locations for a shipping method
     * 
     * @param int    $method_id   Shipping method ID.
     * @param string $country     Country code.
     * @param string $postal_code Postal code.
     * @return array Array of drop-off locations
     */
    public function get_locations($method_id, $country, $postal_code) {
        if (empty($method_id) || empty($country) || empty($postal_code)) {
            return array();
        }

        $response = $this->api->get('dropoffs/' . $method_id . '/' . $country . '/' . $postal_code, array('platform' => 'PRO'));

        if (is_wp_error($response)) {
            error_log("Error getting drop-off locations: " . $response->get_error_message());
            return array();
        }

        $locations = array();

        foreach ($response as $location) {
            $locations[] = array(
                'id' => $location['id'],
                'name' => isset($location['commerce_name']) ? $location['commerce_name'] : '',
                'address' => isset($location['address']) ? $location['address'] : '',
                'city' => isset($location['city']) ? $location['city'] : '',
                'zip' => isset($location['zip']) ? $location['zip'] : '',
                'state' => isset($location['state']) ? $location['state'] : '',
                'country' => isset($location['country']) ? $location['country'] : $country,
                'phone' => isset($location['phone']) ? $location['phone'] : '',
                'carrier' => isset($location['carrier']) ? $location['carrier'] : '',
                'workingHours' => isset($location['opening_times']['opening_times']) ? $this->format_working_hours($location['opening_times']['opening_times']) : array(),
                'coordinates' => array(
                    'lat' => isset($location['lat']) ? $location['lat'] : 0,
                    'lng' => isset($location['long']) ? $location['long'] : 0
                )
            );
        }
        
        return $locations;
    }

    /**
     * Format working hours from API response
     * 
     * @param array $opening_times Opening times from API.
     * @return array Formatted working hours
     */
    private function format_working_hours($opening_times) {
        if (!is_array($opening_times)) {
            return array();
        }

        $working_hours = array();

        foreach ($opening_times as $day => $hours) {
            $working_hours[] = array(
                'day' => $day,
                'periods' => $hours ? array($hours) : array()
            );
        }

        return $working_hours;
    }

    /**
     * Get drop-off location details
     * 
     * @param string $location_id Location ID.
     * @param int    $method_id   Shipping method ID.
     * @return array|false Location details or false on failure
     */
    public function get_location($location_id, $method_id) {
        if (empty($location_id) || empty($method_id)) {
            return false;
        }

        $response = $this->api->get('drop-off-locations/' . $method_id . '/details/' . $location_id);

        if (is_wp_error($response)) {
            return false;
        }

        return array(
            'id' => $response['id'],
            'name' => isset($response['name']) ? $response['name'] : '',
            'address' => isset($response['address']) ? $response['address'] : '',
            'city' => isset($response['city']) ? $response['city'] : '',
            'zip' => isset($response['zipCode']) ? $response['zipCode'] : '',
            'state' => isset($response['state']) ? $response['state'] : '',
            'country' => isset($response['country']) ? $response['country'] : '',
            'workingHours' => isset($response['workingHours']) ? $this->format_working_hours_detailed($response['workingHours']) : array(),
            'coordinates' => array(
                'lat' => isset($response['latitude']) ? $response['latitude'] : 0,
                'lng' => isset($response['longitude']) ? $response['longitude'] : 0
            )
        );
    }

    /**
     * Format working hours from detailed API response
     * 
     * @param array $working_hours Working hours from API.
     * @return array Formatted working hours
     */
    private function format_working_hours_detailed($working_hours) {
        if (!is_array($working_hours)) {
            return array();
        }

        $formatted = array();

        foreach ($working_hours as $day => $hours) {
            $periods = array();

            if (is_array($hours)) {
                foreach ($hours as $period) {
                    if (isset($period['from']) && isset($period['to'])) {
                        $periods[] = $period['from'] . ' - ' . $period['to'];
                    }
                }
            }

            $formatted[] = array(
                'day' => $day,
                'periods' => $periods
            );
        }

        return $formatted;
    }
}