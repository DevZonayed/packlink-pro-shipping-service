<?php
/**
 * Drop-off locations handler
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Packlink_Drop_Off_Locations {
    /**
     * Get drop-off locations for a shipping method
     * 
     * @param int $method_id Shipping method ID
     * @param string $country Country code
     * @param string $postal_code Postal code
     * @return array Array of drop-off locations
     */
    public static function get_locations($method_id, $country, $postal_code) {
        try {
            // Get location service
            $location_service = \Logeecom\Infrastructure\ServiceRegister::getService(
                \Packlink\BusinessLogic\Location\LocationService::CLASS_NAME
            );
            
            if (!$location_service) {
                return [];
            }
            
            // Get locations
            return $location_service->getLocations($method_id, $country, $postal_code);
        } catch (\Exception $e) {
            return [];
        }
    }
}
