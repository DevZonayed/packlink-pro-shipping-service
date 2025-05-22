<?php
/**
 * Admin Order Columns
 *
 * @package PacklinkCustomShipping
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class to handle admin order columns
 */
class Packlink_Admin_Order_Columns {
    /**
     * Initialize the class
     */
    public static function init() {
        // Add the column to the orders table
        add_filter('manage_edit-shop_order_columns', array(self::class, 'add_packlink_tracking_column'), 20);
        
        // Populate the column with data
        add_action('manage_shop_order_posts_custom_column', array(self::class, 'populate_packlink_tracking_column'), 10, 2);
        
        // Make the column sortable
        add_filter('manage_edit-shop_order_sortable_columns', array(self::class, 'make_packlink_tracking_column_sortable'));
        
        // Add column styles
        add_action('admin_head', array(self::class, 'add_column_styles'));
    }
    
    /**
     * Add Packlink tracking column to orders table
     *
     * @param array $columns Order table columns.
     * @return array Modified columns
     */
    public static function add_packlink_tracking_column($columns) {
        $new_columns = array();
        
        // Insert column after order status
        foreach ($columns as $column_name => $column_info) {
            $new_columns[$column_name] = $column_info;
            
            if ('order_status' === $column_name) {
                $new_columns['packlink_tracking'] = __('Packlink Tracking', 'packlink-custom-shipping');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Populate Packlink tracking column
     *
     * @param string $column Column name.
     * @param int    $post_id Post ID.
     */
    public static function populate_packlink_tracking_column($column, $post_id) {
        if ('packlink_tracking' !== $column) {
            return;
        }
        
        // Get tracking references for the order
        $references = self::get_packlink_references($post_id);
        
        if (empty($references)) {
            echo '<span class="na">–</span>';
            return;
        }
        
        // Create a tracking link for each reference
        foreach ($references as $reference) {
            $tracking_page_url = admin_url('admin.php?page=packlink-tracking&reference=' . urlencode($reference));
            echo '<a href="' . esc_url($tracking_page_url) . '" class="button-packlink-tracking" target="_blank">';
            echo '<span class="dashicons dashicons-location-alt"></span> ';
            echo esc_html($reference);
            echo '</a><br>';
        }
    }
    
    /**
     * Make Packlink tracking column sortable
     *
     * @param array $columns Sortable columns.
     * @return array Modified sortable columns
     */
    public static function make_packlink_tracking_column_sortable($columns) {
        $columns['packlink_tracking'] = 'packlink_tracking';
        return $columns;
    }
    
    /**
     * Add CSS styles for the column
     */
    public static function add_column_styles() {
        echo '<style>
            .widefat .column-packlink_tracking {
                width: 150px;
            }
            .button-packlink-tracking {
                display: inline-flex;
                align-items: center;
                background: #f0f5ff;
                color: #4a6ee0;
                border: 1px solid #d0d8ff;
                padding: 3px 8px;
                border-radius: 3px;
                text-decoration: none;
                margin-bottom: 5px;
                font-size: 12px;
            }
            .button-packlink-tracking:hover {
                background: #e0e8ff;
                color: #3a5ec0;
            }
            .button-packlink-tracking .dashicons {
                font-size: 16px;
                height: 16px;
                width: 16px;
                margin-right: 3px;
            }
        </style>';
    }
    
    /**
     * Get all Packlink references for an order
     *
     * @param int $order_id Order ID.
     * @return array Array of reference numbers
     */
    private static function get_packlink_references($order_id) {
        $references = array();
        
        // Check for main shipment ID
        $main_reference = get_post_meta($order_id, '_packlink_shipment_id', true);
        if (!empty($main_reference)) {
            $references[] = $main_reference;
        }
        
        // Check for multi-route shipments
        $all_shipments = get_post_meta($order_id, '_packlink_all_shipments', true);
        if (is_array($all_shipments) && !empty($all_shipments)) {
            foreach ($all_shipments as $shipment_id) {
                if (!in_array($shipment_id, $references)) {
                    $references[] = $shipment_id;
                }
            }
        }
        
        // Check for route-specific shipment IDs
        global $wpdb;
        $route_references = $wpdb->get_col($wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key LIKE '_packlink_shipment_id_route_%%' AND meta_value != ''",
            $order_id
        ));
        
        if (!empty($route_references)) {
            foreach ($route_references as $reference) {
                if (!in_array($reference, $references)) {
                    $references[] = $reference;
                }
            }
        }
        
        return $references;
    }
}

// Initialize the class
Packlink_Admin_Order_Columns::init();
