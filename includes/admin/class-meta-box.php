<?php

/**
 * Order meta box functionality
 *
 * @package PacklinkCustomShipping
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Meta box class for displaying Packlink shipment details in order
 */
class Packlink_Meta_Box
{
    /**
     * Initialize the meta box
     *
     * @return void
     */
    public static function init()
    {
        add_action('add_meta_boxes', array(self::class, 'add_meta_box'));
        add_action('wp_ajax_packlink_create_shipment', array(self::class, 'ajax_create_shipment'));
        add_action('wp_ajax_packlink_create_route_shipment', array(self::class, 'ajax_create_route_shipment'));
        add_action('wp_ajax_packlink_get_tracking', array(self::class, 'ajax_get_tracking'));
        add_action('wp_ajax_packlink_get_label', array(self::class, 'ajax_get_label'));
    }

    /**
     * Add meta box to order edit screen
     *
     * @return void
     */
    public static function add_meta_box()
    {
        add_meta_box(
            'packlink-shipment-meta-box',
            __('Packlink Shipping', 'packlink-custom-shipping'),
            array(self::class, 'render_meta_box'),
            'shop_order',
            'side',
            'high'
        );
    }

    /**
     * Render meta box content
     *
     * @param WP_Post $post Post object.
     * @return void
     */
    public static function render_meta_box($post)
    {
        $order_id = $post->ID;
        $order = wc_get_order($order_id);

        if (!$order) {
            return;
        }

        // Get Packlink data
        $shipping_details = get_post_meta($order_id, '_packlink_shipping_details', true);
        $all_shipments = get_post_meta($order_id, '_packlink_all_shipments', true);

        // Check if this is a Packlink order
        if (!$shipping_details || !isset($shipping_details['routes'])) {
            echo '<p>' . __('This order was not shipped with Packlink.', 'packlink-custom-shipping') . '</p>';
            return;
        }

        include PACKLINK_CUSTOM_PLUGIN_DIR . 'templates/admin/meta-box.php';
    }

    /**
     * AJAX handler for creating a shipment
     *
     * @return void
     */
    public static function ajax_create_shipment()
    {
        // Verify nonce
        if (!check_ajax_referer('packlink_order_actions', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Invalid security token.', 'packlink-custom-shipping')
            ));
        }

        // Check if order ID is provided
        if (!isset($_POST['order_id'])) {
            wp_send_json_error(array(
                'message' => __('Missing order ID.', 'packlink-custom-shipping')
            ));
        }

        $order_id = intval($_POST['order_id']);
        $order = wc_get_order($order_id);

        if (!$order) {
            wp_send_json_error(array(
                'message' => __('Invalid order.', 'packlink-custom-shipping')
            ));
        }

        // Process the shipment creation
        $result = Packlink_Checkout::process_order_action_create_shipment($order);

        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message()
            ));
        } else {
            wp_send_json_success(array(
                'message' => __('Shipment created successfully.', 'packlink-custom-shipping'),
                'shipment_id' => $result
            ));
        }
    }

    /**
     * AJAX handler for creating a route shipment
     *
     * @return void
     */
    public static function ajax_create_route_shipment()
    {
        // Verify nonce
        if (!check_ajax_referer('packlink_order_actions', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Invalid security token.', 'packlink-custom-shipping')
            ));
        }

        // Check if order ID and route index are provided
        if (!isset($_POST['order_id']) || !isset($_POST['route_index'])) {
            wp_send_json_error(array(
                'message' => __('Missing order ID or route index.', 'packlink-custom-shipping')
            ));
        }

        $order_id = intval($_POST['order_id']);
        $route_index = intval($_POST['route_index']);

        $order = wc_get_order($order_id);

        if (!$order) {
            wp_send_json_error(array(
                'message' => __('Invalid order.', 'packlink-custom-shipping')
            ));
        }

        // Check if shipment already exists for this route
        $existing_shipment_id = get_post_meta($order_id, '_packlink_shipment_id_route_' . $route_index, true);
        if ($existing_shipment_id) {
            wp_send_json_error(array(
                'message' => __('A shipment already exists for this route.', 'packlink-custom-shipping')
            ));
        }

        // Get shipment
        $shipment = new Packlink_Shipment();
        $result = $shipment->create_route_shipment($order, $route_index);

        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message()
            ));
        } else {
            wp_send_json_success(array(
                'message' => sprintf(__('Shipment created successfully for route %d.', 'packlink-custom-shipping'), $route_index + 1),
                'shipment_id' => $result
            ));
        }
    }

    /**
     * AJAX handler for getting tracking information
     *
     * @return void
     */
    public static function ajax_get_tracking()
    {
        // Verify nonce
        if (!check_ajax_referer('packlink_tracking_nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Invalid security token.', 'packlink-custom-shipping')
            ));
        }

        // Check if reference is provided
        if (!isset($_POST['reference'])) {
            wp_send_json_error(array(
                'message' => __('Missing reference number.', 'packlink-custom-shipping')
            ));
        }

        $reference = sanitize_text_field($_POST['reference']);

        try {
            $shipment = new Packlink_Shipment();

            // First try to get the shipment details
            $shipment_details = $shipment->get($reference);

            if (is_wp_error($shipment_details)) {
                throw new Exception($shipment_details->get_error_message());
            }

            // Try to get tracking information if shipment is beyond READY_TO_PURCHASE
            $tracking_events = array();
            $tracking_url = '';

            if (isset($shipment_details['state']) && $shipment_details['state'] !== 'READY_TO_PURCHASE') {
                $tracking = $shipment->get_tracking($reference);

                if (!is_wp_error($tracking) && isset($tracking['history']) && !empty($tracking['history'])) {
                    $tracking_events = $tracking['history'];
                }
            }

            // If no tracking events but we have shipment details, create a placeholder event
            if (empty($tracking_events) && isset($shipment_details['state'])) {
                $tracking_events[] = array(
                    'date' => isset($shipment_details['updated_at']) ? $shipment_details['updated_at'] : date('Y-m-d\TH:i:s.000\Z'),
                    'status' => $shipment_details['state'],
                    'description' => self::get_status_description($shipment_details['state']),
                    'location' => ''
                );
            }

            // Format address information from shipment details
            $from_address = isset($shipment_details['from']) ? array(
                'name' => isset($shipment_details['from']['name']) ?
                    $shipment_details['from']['name'] . ' ' . (isset($shipment_details['from']['surname']) ? $shipment_details['from']['surname'] : '') : '',
                'company' => isset($shipment_details['from']['company']) ? $shipment_details['from']['company'] : '',
                'street1' => isset($shipment_details['from']['street1']) ? $shipment_details['from']['street1'] : '',
                'street2' => isset($shipment_details['from']['street2']) ? $shipment_details['from']['street2'] : '',
                'city' => isset($shipment_details['from']['city']) ? $shipment_details['from']['city'] : '',
                'zip_code' => isset($shipment_details['from']['zip_code']) ? $shipment_details['from']['zip_code'] : '',
                'country' => isset($shipment_details['from']['country']) ? $shipment_details['from']['country'] : ''
            ) : array();

            $to_address = isset($shipment_details['to']) ? array(
                'name' => isset($shipment_details['to']['name']) ?
                    $shipment_details['to']['name'] . ' ' . (isset($shipment_details['to']['surname']) ? $shipment_details['to']['surname'] : '') : '',
                'company' => isset($shipment_details['to']['company']) ? $shipment_details['to']['company'] : '',
                'street1' => isset($shipment_details['to']['street1']) ? $shipment_details['to']['street1'] : '',
                'street2' => isset($shipment_details['to']['street2']) ? $shipment_details['to']['street2'] : '',
                'city' => isset($shipment_details['to']['city']) ? $shipment_details['to']['city'] : '',
                'zip_code' => isset($shipment_details['to']['zip_code']) ? $shipment_details['to']['zip_code'] : '',
                'country' => isset($shipment_details['to']['country']) ? $shipment_details['to']['country'] : ''
            ) : array();

            // Format the data to match what the frontend expects
            $formatted_data = array(
                'status' => isset($shipment_details['state']) ? $shipment_details['state'] : 'UNKNOWN',
                'shipment' => array(
                    'reference' => $reference,
                    'carrier' => isset($shipment_details['carrier']) ? $shipment_details['carrier'] : '',
                    'service' => isset($shipment_details['service']) ? $shipment_details['service'] : '',
                    'date_created' => isset($shipment_details['order_date']) ? str_replace('/', '-', $shipment_details['order_date']) : '',
                    'from' => $from_address,
                    'to' => $to_address,
                    'tracking_url' => isset($shipment_details['tracking_url']) ? $shipment_details['tracking_url'] : ''
                ),
                'tracking' => $tracking_events
            );

            wp_send_json_success($formatted_data);
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => sprintf(__('Failed to get tracking information: %s', 'packlink-custom-shipping'), $e->getMessage())
            ));
        }
    }

    /**
     * Get human-readable description for status code
     *
     * @param string $status Status code
     * @return string Description
     */
    private static function get_status_description($status)
    {
        $descriptions = array(
            'DELIVERED' => __('Shipment has been delivered', 'packlink-custom-shipping'),
            'IN_TRANSIT' => __('Shipment is in transit', 'packlink-custom-shipping'),
            'PENDING' => __('Shipment is pending', 'packlink-custom-shipping'),
            'READY_TO_SHIP' => __('Shipment is ready to ship', 'packlink-custom-shipping'),
            'READY_TO_PURCHASE' => __('Shipment is ready to be purchased', 'packlink-custom-shipping'),
            'CANCELLED' => __('Shipment has been cancelled', 'packlink-custom-shipping'),
            'EXCEPTION' => __('There is an issue with the shipment', 'packlink-custom-shipping')
        );

        return isset($descriptions[$status]) ? $descriptions[$status] : $status;
    }



    /**
     * AJAX handler for getting shipping label
     *
     * @return void
     */
    public static function ajax_get_label()
    {
        // Verify nonce
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'packlink_order_actions')) {
            wp_die(__('Invalid security token.', 'packlink-custom-shipping'));
        }

        // Check if shipment ID is provided
        if (!isset($_GET['shipment_id'])) {
            wp_die(__('Missing shipment ID.', 'packlink-custom-shipping'));
        }

        $shipment_id = sanitize_text_field($_GET['shipment_id']);

        try {
            // Get label
            $shipment = new Packlink_Shipment();
            $label = $shipment->get_label($shipment_id);

            if (is_wp_error($label)) {
                throw new Exception($label->get_error_message());
            }

            if (isset($label['url'])) {
                // Redirect to label URL
                wp_redirect($label['url']);
                exit;
            } else {
                throw new Exception(__('Label URL not found in response.', 'packlink-custom-shipping'));
            }
        } catch (Exception $e) {
            wp_die(sprintf(__('Failed to get shipping label: %s', 'packlink-custom-shipping'), $e->getMessage()));
        }
    }
}
