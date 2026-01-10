<?php

/**
 * Plugin Name: Packlink Custom Shipping Extension
 * Description: Custom integration with Packlink PRO shipping API
 * Version: 3.2.8
 * Author: Jonayed Ahamed, DeveloperJillur
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * WC requires at least: 4.0
 * WC tested up to: 7.0
 * Text Domain: packlink-custom-shipping
 * Domain Path: /languages
 *
 * @package PacklinkCustomShipping
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('PACKLINK_CUSTOM_VERSION', '1.0.7');
define('PACKLINK_CUSTOM_PLUGIN_FILE', __FILE__);
define('PACKLINK_CUSTOM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PACKLINK_CUSTOM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PACKLINK_CUSTOM_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Autoloader for plugin classes
 *
 * @param string $class_name Class name to load.
 * @return void
 */
function packlink_custom_autoloader($class_name)
{
    // Exit if the class doesn't start with our prefix
    if (strpos($class_name, 'Packlink_') !== 0) {
        return;
    }

    // Convert class name to file path format
    $class_file = 'class-' . str_replace('_', '-', strtolower(substr($class_name, 9))) . '.php';

    // Try to load from different directories
    $dirs = [
        'admin' => PACKLINK_CUSTOM_PLUGIN_DIR . 'includes/admin/',
        'api' => PACKLINK_CUSTOM_PLUGIN_DIR . 'includes/api/',
        'core' => PACKLINK_CUSTOM_PLUGIN_DIR . 'includes/core/',
        'frontend' => PACKLINK_CUSTOM_PLUGIN_DIR . 'includes/frontend/',
        // Backward compatibility
        'root' => PACKLINK_CUSTOM_PLUGIN_DIR . 'includes/',
    ];

    foreach ($dirs as $dir) {
        $file = $dir . $class_file;
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
}

spl_autoload_register('packlink_custom_autoloader');

// Initialize the plugin
add_action('plugins_loaded', 'packlink_custom_init');

/**
 * Initialize the plugin
 *
 * @return void
 */
function packlink_custom_init()
{
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'packlink_custom_woocommerce_notice');
        return;
    }

    // Initialize the plugin
    Packlink_Init::init();
}

/**
 * Display notice if WooCommerce is not active
 *
 * @return void
 */
function packlink_custom_woocommerce_notice()
{
?>
<div class="error">
    <p><?php _e('Packlink Custom Shipping Extension requires WooCommerce to be installed and activated.', 'packlink-custom-shipping'); ?>
    </p>
</div>
<?php
}

/**
 * Load plugin text domain
 *
 * @return void
 */
function packlink_custom_load_textdomain()
{
    load_plugin_textdomain('packlink-custom-shipping', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('init', 'packlink_custom_load_textdomain');