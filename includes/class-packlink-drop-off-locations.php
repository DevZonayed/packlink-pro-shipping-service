<?php

/**
 * Packlink Drop-off Locations Class
 * 
 * Handles drop-off locations
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Packlink_Drop_Off_Locations
{
    /**
     * API instance
     * 
     * @var Packlink_API
     */
    private $api;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->api = new Packlink_API();
    }

    /**
     * Get drop-off locations for a shipping method
     * 
     * @param int $method_id Shipping method ID
     * @param string $country Country code
     * @param string $postal_code Postal code
     * @return array Array of drop-off locations
     */
    public function get_locations($method_id, $country, $postal_code)
    {
        if (empty($method_id) || empty($country) || empty($postal_code)) {
            return [];
        }

        $response = $this->api->get('dropoffs/' . $method_id . '/' . $country . '/' . $postal_code, ['platform' => 'PRO', 'city' => "Sitges"]);

        if (is_wp_error($response)) {
            error_log("Error getting drop-off locations: " . $response->get_error_message());
            return [];
        }

        $locations = [];

        foreach ($response as $location) {
            $locations[] = [
                'id' => $location['id'],
                'name' => isset($location['commerce_name']) ? $location['commerce_name'] : '',
                'address' => isset($location['address']) ? $location['address'] : '',
                'city' => isset($location['city']) ? $location['city'] : '',
                'zip' => isset($location['zip']) ? $location['zip'] : '',
                'state' => isset($location['state']) ? $location['state'] : '',
                'country' => isset($location['country']) ? $location['country'] : $country,
                'phone' => isset($location['phone']) ? $location['phone'] : '',
                'carrier' => isset($location['carrier']) ? $location['carrier'] : '',
                'workingHours' => isset($location['opening_times']['opening_times']) ? array_map(function($day, $hours) {
                    return [
                        'day' => $day,
                        'periods' => $hours ? [$hours] : []
                    ];
                }, array_keys($location['opening_times']['opening_times']), $location['opening_times']['opening_times']) : [],
                'coordinates' => [
                    'lat' => isset($location['lat']) ? $location['lat'] : 0,
                    'lng' => isset($location['long']) ? $location['long'] : 0
                ]
            ];
        }
        return $locations;
    }

    /**
     * Format working hours
     * 
     * @param array $working_hours Working hours
     * @return array Formatted working hours
     */
    private function format_working_hours($working_hours)
    {
        if (!is_array($working_hours)) {
            return [];
        }

        $formatted = [];

        foreach ($working_hours as $day => $hours) {
            $periods = [];

            if (is_array($hours)) {
                foreach ($hours as $period) {
                    if (isset($period['from']) && isset($period['to'])) {
                        $periods[] = $period['from'] . ' - ' . $period['to'];
                    }
                }
            }

            $formatted[] = [
                'day' => $day,
                'periods' => $periods
            ];
        }

        return $formatted;
    }

    /**
     * Get drop-off location details
     * 
     * @param string $location_id Location ID
     * @param int $method_id Shipping method ID
     * @return array|false Location details or false on failure
     */
    public function get_location($location_id, $method_id)
    {
        if (empty($location_id) || empty($method_id)) {
            return false;
        }

        $response = $this->api->get('drop-off-locations/' . $method_id . '/details/' . $location_id);

        if (is_wp_error($response)) {
            return false;
        }

        return [
            'id' => $response['id'],
            'name' => isset($response['name']) ? $response['name'] : '',
            'address' => isset($response['address']) ? $response['address'] : '',
            'city' => isset($response['city']) ? $response['city'] : '',
            'zip' => isset($response['zipCode']) ? $response['zipCode'] : '',
            'state' => isset($response['state']) ? $response['state'] : '',
            'country' => isset($response['country']) ? $response['country'] : '',
            'workingHours' => isset($response['workingHours']) ? $this->format_working_hours($response['workingHours']) : [],
            'coordinates' => [
                'lat' => isset($response['latitude']) ? $response['latitude'] : 0,
                'lng' => isset($response['longitude']) ? $response['longitude'] : 0
            ]
        ];
    }
}
