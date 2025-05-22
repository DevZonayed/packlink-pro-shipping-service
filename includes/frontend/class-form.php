<?php
/**
 * Frontend form handling
 *
 * @package PacklinkCustomShipping
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class to handle frontend shipping form
 */
class Packlink_Form {
    /**
     * Render the shipping form shortcode
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output of the shipping form
     */
    public static function render_shipping_form($atts = array()) {
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
     * Get shortcode attributes with defaults
     *
     * @param array $atts User attributes.
     * @return array Processed attributes
     */
    public static function get_shortcode_atts($atts) {
        $defaults = array(
            'title' => __('Calculate Shipping', 'packlink-custom-shipping'),
            'button_text' => __('Checkout', 'packlink-custom-shipping'),
            'show_origin' => 'no',
        );

        return shortcode_atts($defaults, $atts);
    }
}