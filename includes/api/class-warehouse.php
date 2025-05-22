<?php
/**
 * Packlink Warehouse Class
 * 
 * Handles warehouse operations
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Packlink_Warehouse {
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
     * Get all warehouses
     * 
     * @return array|WP_Error Response data or WP_Error on failure
     */
    public function get_all() {
        return $this->api->get('warehouses');
    }
    
    /**
     * Get a warehouse
     * 
     * @param string $warehouse_id Warehouse ID
     * @return array|WP_Error Response data or WP_Error on failure
     */
    public function get($warehouse_id) {
        return $this->api->get('clients/warehouses/' . $warehouse_id);
    }
    
    /**
     * Create a warehouse
     * 
     * @param array $data Warehouse data
     * @return array|WP_Error Response data or WP_Error on failure
     */
    public function create($data) {
        return $this->api->post('clients/warehouses', $data);
    }
    
    /**
     * Update a warehouse
     * 
     * @param string $warehouse_id Warehouse ID
     * @param array $data Warehouse data
     * @return array|WP_Error Response data or WP_Error on failure
     */
    public function update($warehouse_id, $data) {
        return $this->api->put('clients/warehouses/' . $warehouse_id, $data);
    }
    
    /**
     * Delete a warehouse
     * 
     * @param string $warehouse_id Warehouse ID
     * @return array|WP_Error Response data or WP_Error on failure
     */
    public function delete($warehouse_id) {
        return $this->api->delete('clients/warehouses/' . $warehouse_id);
    }
}
