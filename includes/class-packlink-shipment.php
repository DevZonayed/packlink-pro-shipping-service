<?php

/**
 * Packlink Shipment Class
 * 
 * Handles shipment operations
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Packlink_Shipment
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
     * Create a shipment from order
     * 
     * @param int $order_id Order ID
     * @return array|WP_Error Response data or WP_Error on failure
     */
    public function create_from_order($order_id)
    {
        // Get order
        $order = wc_get_order($order_id);

        if (!$order) {
            return new WP_Error(
                'invalid_order',
                __('Invalid order ID', 'packlink-custom-shipping')
            );
        }

        // Get shipping data from order meta
        $shipping_option_id = get_post_meta($order_id, '_packlink_shipping_option_id', true);
        $shipping_details = get_post_meta($order_id, '_packlink_shipping_details', true);
        $drop_off_id = get_post_meta($order_id, '_packlink_drop_off_id', true);

        if (!$shipping_option_id || !$shipping_details) {
            return new WP_Error(
                'missing_shipping_data',
                __('Missing Packlink shipping data', 'packlink-custom-shipping')
            );
        }

        // Get sender details
        $sender_name = get_option('packlink_sender_name', get_bloginfo('name'));
        $sender_company = get_option('packlink_sender_company', get_bloginfo('name'));
        $sender_phone = get_option('packlink_sender_phone', '');
        $sender_email = get_option('packlink_sender_email', get_option('admin_email'));

        // Create shipment data
        $shipment_data = [
            "user_id" => null,
            "client_id" => null,
            'service_id' => $shipping_option_id,
            'collection_date' => isset($shipping_details['collection_date']) ? str_replace('-', '/', $shipping_details['collection_date']) : null,
            'collection_time' => null,
            "dropoff_point_id" => isset($drop_off_id) ? $drop_off_id : null,
            "contentvalue" => 46.61,
            "content_second_hand" => false,
            "shipment_custom_reference" => null,
            "priority" => false,
            "contentValue_currency" => "EUR",
            'content' => sprintf(
                __('WooCommerce Order #%s', 'packlink-custom-shipping'),
                $order->get_order_number()
            ),
            'source' => 'woocommerce',
            'reference' => $order_id,
            'packages' => [
                [
                    'weight' => isset($shipping_details['weight']) ? (float) $shipping_details['weight'] : 1,
                    'length' => isset($shipping_details['dimensions']['length']) ? (float) $shipping_details['dimensions']['length'] : 10,
                    'width' => isset($shipping_details['dimensions']['width']) ? (float) $shipping_details['dimensions']['width'] : 10,
                    'height' => isset($shipping_details['dimensions']['height']) ? (float) $shipping_details['dimensions']['height'] : 10
                ]
            ],
            'sender' => [
                'name' => $sender_name,
                'company' => $sender_company,
                'country' => isset($shipping_details['origin_country']) ? $shipping_details['origin_country'] : WC()->countries->get_base_country(),
                'zip' => isset($shipping_details['origin_postal_code']) ? $shipping_details['origin_postal_code'] : WC()->countries->get_base_postcode(),
                'city' => isset($shipping_details['origin_city']) ? $shipping_details['origin_city'] : WC()->countries->get_base_city(),
                'phone' => $sender_phone,
                'email' => $sender_email
            ],
            'receiver' => [
                'name' => $order->get_formatted_shipping_full_name() ?: $order->get_formatted_billing_full_name(),
                'company' => $order->get_shipping_company() ?: $order->get_billing_company(),
                'country' => $order->get_shipping_country() ?: $order->get_billing_country(),
                'zip' => $order->get_shipping_postcode() ?: $order->get_billing_postcode(),
                'city' => $order->get_shipping_city() ?: $order->get_billing_city(),
                'street1' => $order->get_shipping_address_1() ?: $order->get_billing_address_1(),
                'street2' => $order->get_shipping_address_2() ?: $order->get_billing_address_2(),
                'phone' => $order->get_billing_phone(),
                'email' => $order->get_billing_email()
            ]
        ];

        // Add drop-off point if applicable
        if ($drop_off_id) {
            $shipment_data['dropoff'] = [
                'id' => $drop_off_id
            ];
        }

        // Create shipment
        $result = $this->create($shipment_data);

        if (is_wp_error($result)) {
            return $result;
        }

        if (isset($result['id'])) {
            // Save Packlink shipment ID to order
            update_post_meta($order_id, '_packlink_shipment_id', $result['id']);

            // Add order note
            $order->add_order_note(sprintf(
                __('Packlink shipment created successfully. Reference: %s', 'packlink-custom-shipping'),
                $result['id']
            ));
        }

        return $result;
    }
    /**
     * Create a shipment
     * 
     * @param array $data Shipment data
     * @return array|WP_Error Response data or WP_Error on failure
     */
    public function create($data)
    {
        // Ensure data follows Packlink's required format
        $required_fields = ['service_id', 'packages', 'sender', 'receiver', 'collection_date'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field])) {
                return new WP_Error(
                    'missing_required_field',
                    sprintf(__('Missing required field: %s', 'packlink-custom-shipping'), $field)
                );
            }
        }

        // Validate sender data
        $sender_fields = ['name', 'country', 'zip', 'city'];
        foreach ($sender_fields as $field) {
            if (!isset($data['sender'][$field]) || empty($data['sender'][$field])) {
                return new WP_Error(
                    'missing_sender_field',
                    sprintf(__('Missing sender field: %s', 'packlink-custom-shipping'), $field)
                );
            }
        }

        // Validate receiver data
        $receiver_fields = ['name', 'country', 'zip', 'city', 'street1'];
        foreach ($receiver_fields as $field) {
            if (!isset($data['receiver'][$field]) || empty($data['receiver'][$field])) {
                return new WP_Error(
                    'missing_receiver_field',
                    sprintf(__('Missing receiver field: %s', 'packlink-custom-shipping'), $field)
                );
            }
        }

        // Validate packages
        if (!is_array($data['packages']) || empty($data['packages'])) {
            return new WP_Error(
                'invalid_packages',
                __('Packages must be a non-empty array', 'packlink-custom-shipping')
            );
        }

        foreach ($data['packages'] as $package) {
            $package_fields = ['weight', 'length', 'width', 'height'];
            foreach ($package_fields as $field) {
                if (!isset($package[$field]) || !is_numeric($package[$field]) || $package[$field] <= 0) {
                    return new WP_Error(
                        'invalid_package_field',
                        sprintf(__('Invalid package field: %s', 'packlink-custom-shipping'), $field)
                    );
                }
            }
        }
        $formatted_data = [
            "user_id" => null,
            "client_id" => null,
            'service_id' => $data['service_id'],
            "service" => "brt - 2 DAYS delivery",
            "carrier" =>  "brt",
            "collection_date" => isset($data['collection_date']) ? str_replace('-', '/', $data['collection_date']) : null,
            "dropoff_point_id" => null,
            'content' => isset($data['content']) ? $data['content'] : '',
            "contentvalue" => 46.61,
            "content_second_hand" => false,
            "shipment_custom_reference" => isset($data['reference']) ? $data['reference'] : '',
            "priority" => false,
            "contentValue_currency" => "EUR",
            'packages' => $data['packages'],
            'from' => [
                'name' => $data['sender']['name'],
                "surname" => isset($data['sender']['surname']) ? $data['sender']['surname'] : '',
                'company' => isset($data['sender']['company']) ? $data['sender']['company'] : '',
                'street1' => isset($data['sender']['street1']) ? $data['sender']['street1'] : '',
                'street2' => isset($data['sender']['street2']) ? $data['sender']['street2'] : '',
                'zip_code' => $data['sender']['zip'],
                'city' => $data['sender']['city'],
                'country' => $data['sender']['country'],
                'phone' => isset($data['sender']['phone']) ? $data['sender']['phone'] : '',
                'email' => isset($data['sender']['email']) ? $data['sender']['email'] : ''
            ],
            'to' => [
                'name' => $data['receiver']['name'],
                'surname' => isset($data['receiver']['surname']) ? $data['receiver']['surname'] : '',
                'company' => isset($data['receiver']['company']) ? $data['receiver']['company'] : '',
                'street1' => $data['receiver']['street1'],
                'street2' => isset($data['receiver']['street2']) ? $data['receiver']['street2'] : '',
                'zip_code' => $data['receiver']['zip'],
                'city' => $data['receiver']['city'],
                'country' => $data['receiver']['country'],
                'phone' => isset($data['receiver']['phone']) ? $data['receiver']['phone'] : '',
                'email' => isset($data['receiver']['email']) ? $data['receiver']['email'] : ''
            ]
        ];

        // Add drop-off point if provided
        if (isset($data['dropoff']) && isset($data['dropoff']['id'])) {
            $formatted_data['dropoff'] = [
                'id' => $data['dropoff']['id']
            ];
        }

        error_log("Formatted Shipment data: " . print_r($formatted_data, true));

        // Make the API request
        return $this->api->post('shipments', $formatted_data);
    }
    /**
     * Get a shipment
     * 
     * @param string $shipment_id Shipment ID
     * @return array|WP_Error Response data or WP_Error on failure
     */
    public function get($shipment_id)
    {
        if (empty($shipment_id)) {
            return new WP_Error(
                'missing_shipment_id',
                __('Missing shipment ID', 'packlink-custom-shipping')
            );
        }

        return $this->api->get('shipments/' . $shipment_id);
    }

    /**
     * Delete a shipment
     * 
     * @param string $shipment_id Shipment ID
     * @return array|WP_Error Response data or WP_Error on failure
     */
    public function delete($shipment_id)
    {
        if (empty($shipment_id)) {
            return new WP_Error(
                'missing_shipment_id',
                __('Missing shipment ID', 'packlink-custom-shipping')
            );
        }

        return $this->api->delete('shipments/' . $shipment_id);
    }

    /**
     * Get all shipments
     * 
     * @param array $params Query parameters
     * @return array|WP_Error Response data or WP_Error on failure
     */
    public function get_all($params = [])
    {
        return $this->api->get('shipments', $params);
    }

    /**
     * Get shipment tracking
     * 
     * @param string $shipment_id Shipment ID
     * @return array|WP_Error Response data or WP_Error on failure
     */
    public function get_tracking($shipment_id)
    {
        if (empty($shipment_id)) {
            return new WP_Error(
                'missing_shipment_id',
                __('Missing shipment ID', 'packlink-custom-shipping')
            );
        }

        return $this->api->get('shipments/' . $shipment_id . '/tracking');
    }

    /**
     * Get shipment label
     * 
     * @param string $shipment_id Shipment ID
     * @return array|WP_Error Response data or WP_Error on failure
     */
    public function get_label($shipment_id)
    {
        if (empty($shipment_id)) {
            return new WP_Error(
                'missing_shipment_id',
                __('Missing shipment ID', 'packlink-custom-shipping')
            );
        }

        return $this->api->get('shipments/' . $shipment_id . '/labels');
    }

    /**
     * Get shipment status
     * 
     * @param string $shipment_id Shipment ID
     * @return string|WP_Error Shipment status or WP_Error on failure
     */
    public function get_status($shipment_id)
    {
        $shipment = $this->get($shipment_id);

        if (is_wp_error($shipment)) {
            return $shipment;
        }

        return isset($shipment['status']) ? $shipment['status'] : '';
    }
}
