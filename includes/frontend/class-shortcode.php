<?php

/**
 * Shortcode functionality
 *
 * @package PacklinkCustomShipping
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles the shipping form shortcode
 */
class Packlink_Shortcode
{
    /**
     * Initialize the shortcode
     *
     * @return void
     */
    public static function init()
    {
        add_shortcode('packlink_tracking_form', array(self::class, 'render_tracking_form'));
        add_shortcode('packlink_shipping_form', array(self::class, 'render_shipping_form'));
    }

    /**
     * Render the shipping form
     *
     * @param array $atts Shortcode attributes.
     * @return string Rendered shortcode content
     */
    public static function render_shipping_form($atts = array())
    {
        // Process shortcode attributes
        $atts = self::get_shortcode_atts($atts);

        // Enqueue scripts and styles
        wp_enqueue_style('packlink-variables');
        wp_enqueue_style('packlink-animations');
        wp_enqueue_style('packlink-frontend-styles');

        // Enqueue scripts in the correct order
        wp_enqueue_script('packlink-map-loader');
        wp_enqueue_script('google-map-loader');
        wp_enqueue_script('packlink-custom-form');

        ob_start();
        include PACKLINK_CUSTOM_PLUGIN_DIR . 'templates/frontend/shipping-form.php';
        return ob_get_clean();
    }

    /**
     * Get shortcode attributes
     *
     * @param array $atts User provided attributes.
     * @return array Processed attributes
     */
    public static function get_shortcode_atts($atts)
    {
        $defaults = array(
            'title' => __('Calculate Shipping', 'packlink-custom-shipping'),
            'button_text' => __('Checkout', 'packlink-custom-shipping'),
            'show_origin' => 'no',
        );

        return shortcode_atts($defaults, $atts);
    }

    /**
     * Render the tracking form
     *
     * @param array $atts Shortcode attributes.
     * @return string Rendered shortcode content
     */
    public static function render_tracking_form($atts = array())
    {
        // Process shortcode attributes
        $atts = self::get_tracking_atts($atts);

        // Enqueue scripts and styles
        wp_enqueue_style('packlink-variables');
        wp_enqueue_style('packlink-animations');
        wp_enqueue_style('packlink-frontend-styles');

        // Enqueue tracking script
        wp_enqueue_script('packlink-tracking');

        ob_start();
        include PACKLINK_CUSTOM_PLUGIN_DIR . 'templates/frontend/tracking-form.php';
        return ob_get_clean();
    }
    /**
     * Get tracking shortcode attributes
     *
     * @param array $atts User provided attributes.
     * @return array Processed attributes
     */
    public static function get_tracking_atts($atts)
    {
        $defaults = array(
            'title' => __('Track Your Shipment', 'packlink-custom-shipping'),
        );

        return shortcode_atts($defaults, $atts);
    }
}
