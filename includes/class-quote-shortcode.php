<?php

/**
 * Simple Quote Shortcode Class
 * 
 * Provides a simple form with origin and destination country selection
 * that redirects to the main shipping form with pre-filled data.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Packlink_Quote_Shortcode
{

    public function __construct()
    {
        add_shortcode('packlink_quote_form', array($this, 'render_quote_form'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Enqueue scripts and styles for the quote form
     */
    public function enqueue_scripts()
    {
        if ($this->should_load_scripts()) {
            // Enqueue main form scripts since we use the same functionality
            wp_enqueue_script(
                'packlink-custom-form',
                PACKLINK_CUSTOM_PLUGIN_URL . 'assets/js/frontend/packlink-form.js',
                array('jquery'),
                PACKLINK_CUSTOM_VERSION,
                true
            );

            // Enqueue quote form specific scripts
            wp_enqueue_script(
                'packlink-quote-form',
                PACKLINK_CUSTOM_PLUGIN_URL . 'assets/js/quote-form.js',
                array('jquery', 'packlink-custom-form'),
                PACKLINK_CUSTOM_VERSION,
                true
            );

            // Enqueue main form styles (includes country selection styling)
            wp_enqueue_style(
                'packlink-frontend-styles',
                PACKLINK_CUSTOM_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                PACKLINK_CUSTOM_VERSION
            );

            // Enqueue quote form specific styles
            wp_enqueue_style(
                'packlink-quote-form',
                PACKLINK_CUSTOM_PLUGIN_URL . 'assets/css/quote-form.css',
                array('packlink-frontend-styles'),
                PACKLINK_CUSTOM_VERSION
            );

            // Use the same AJAX variables as the main form
            wp_localize_script('packlink-custom-form', 'packlink_custom', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'ajax_nonce' => wp_create_nonce('packlink-ajax-nonce'),
                'default_origin_country' => get_option('packlink_country', 'ES'),
                'default_destination_country' => '',
                'default_carrier_logo' => PACKLINK_CUSTOM_PLUGIN_URL . 'assets/images/default-carrier.png',
                'loading_shipping_options' => __('Loading shipping options...', 'packlink-custom-shipping'),
                'no_shipping_options' => __('No shipping options available for this route', 'packlink-custom-shipping'),
                'select_button_text' => __('Select', 'packlink-custom-shipping'),
                'processing_text' => __('Processing...', 'packlink-custom-shipping'),
            ));
        }
    }

    /**
     * Check if scripts should be loaded
     */
    private function should_load_scripts()
    {
        global $post;

        if (is_admin()) {
            return false;
        }

        if (is_object($post) && has_shortcode($post->post_content, 'packlink_quote_form')) {
            return true;
        }

        return false;
    }

    /**
     * Render the quote form shortcode
     */
    public function render_quote_form($atts)
    {
        $atts = shortcode_atts(array(
            'redirect_url' => '',
            'button_text' => 'Get My Quote',
            'title' => 'Ship my luggage from',
            'button_color' => '#027361'
        ), $atts);

        ob_start();
        include PACKLINK_CUSTOM_PLUGIN_DIR . 'templates/quote-form.php';
        return ob_get_clean();
    }
}

// Initialize the quote shortcode
new Packlink_Quote_Shortcode();
