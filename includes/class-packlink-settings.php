<?php

/**
 * Packlink Settings Class
 * 
 * Handles plugin settings
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Packlink_Settings
{
    public static function register_settings()
    {
        // API Settings
        register_setting('packlink_api_settings', 'packlink_api_key');
        register_setting('packlink_api_settings', 'packlink_language');
        register_setting('packlink_api_settings', 'packlink_country');

        // Sender Settings
        register_setting('packlink_sender_settings', 'packlink_sender_name');
        register_setting('packlink_sender_settings', 'packlink_sender_company');
        register_setting('packlink_sender_settings', 'packlink_sender_phone');
        register_setting('packlink_sender_settings', 'packlink_sender_email');
        register_setting('packlink_sender_settings', 'packlink_sender_address');

        // Shipping Settings
        register_setting('packlink_shipping_settings', 'packlink_auto_create_shipment');
        register_setting('packlink_shipping_settings', 'packlink_order_status_after_shipment');
        register_setting('packlink_shipping_settings', 'packlink_default_package_weight');
        register_setting('packlink_shipping_settings', 'packlink_default_package_length');
        register_setting('packlink_shipping_settings', 'packlink_default_package_width');
        register_setting('packlink_shipping_settings', 'packlink_default_package_height');

        register_setting('packlink_shipping_settings', 'packlink_allow_multiple_packages');
        register_setting('packlink_shipping_settings', 'packlink_commission_type');
        register_setting('packlink_shipping_settings', 'packlink_commission_fixed_amount');
        register_setting('packlink_shipping_settings', 'packlink_commission_percentage');

        // Currency Settings
        register_setting('packlink_currency_settings', 'packlink_default_currency');
        register_setting('packlink_currency_settings', 'packlink_multi_currency_enabled');
        register_setting('packlink_currency_settings', 'packlink_currency_display_notice');
    }
    /**
     * Add menu page
     */
    public static function add_menu_page()
    {
        add_menu_page(
            __('Packlink Shipping', 'packlink-custom-shipping'),
            __('Packlink Shipping', 'packlink-custom-shipping'),
            'manage_options',
            'packlink-settings',
            [self::class, 'render_settings_page'],
            'dashicons-cart',
            56
        );

        add_submenu_page(
            'packlink-settings',
            __('API Settings', 'packlink-custom-shipping'),
            __('API Settings', 'packlink-custom-shipping'),
            'manage_options',
            'packlink-settings',
            [self::class, 'render_settings_page']
        );

        add_submenu_page(
            'packlink-settings',
            __('Shipping Settings', 'packlink-custom-shipping'),
            __('Shipping Settings', 'packlink-custom-shipping'),
            'manage_options',
            'packlink-shipping-settings',
            [self::class, 'render_shipping_settings_page']
        );

        add_submenu_page(
            'packlink-settings',
            __('Currency Settings', 'packlink-custom-shipping'),
            __('Currency Settings', 'packlink-custom-shipping'),
            'manage_options',
            'packlink-currency-settings',
            [self::class, 'render_currency_settings_page']
        );
    }
    /**
     * Render settings page
     */
    public static function render_settings_page()
    {
?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <form method="post" action="options.php">
                <?php settings_fields('packlink_api_settings'); ?>
                <?php do_settings_sections('packlink_api_settings'); ?>

                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e('API Key', 'packlink-custom-shipping'); ?></th>
                        <td>
                            <input type="text" name="packlink_api_key" value="<?php echo esc_attr(get_option('packlink_api_key')); ?>" class="regular-text" />
                            <p class="description"><?php _e('Enter your Packlink API key. You can find it in your Packlink PRO account settings.', 'packlink-custom-shipping'); ?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e('Language', 'packlink-custom-shipping'); ?></th>
                        <td>
                            <select name="packlink_language">
                                <option value="en_US" <?php selected(get_option('packlink_language', 'en_US'), 'en_US'); ?>><?php _e('English', 'packlink-custom-shipping'); ?></option>
                                <option value="es_ES" <?php selected(get_option('packlink_language', 'en_US'), 'es_ES'); ?>><?php _e('Spanish', 'packlink-custom-shipping'); ?></option>
                                <option value="de_DE" <?php selected(get_option('packlink_language', 'en_US'), 'de_DE'); ?>><?php _e('German', 'packlink-custom-shipping'); ?></option>
                                <option value="fr_FR" <?php selected(get_option('packlink_language', 'en_US'), 'fr_FR'); ?>><?php _e('French', 'packlink-custom-shipping'); ?></option>
                                <option value="it_IT" <?php selected(get_option('packlink_language', 'en_US'), 'it_IT'); ?>><?php _e('Italian', 'packlink-custom-shipping'); ?></option>
                            </select>
                            <p class="description"><?php _e('Select the language for API responses.', 'packlink-custom-shipping'); ?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e('Country', 'packlink-custom-shipping'); ?></th>
                        <td>
                            <select name="packlink_country">
                                <option value="" <?php selected(get_option('packlink_country', ''), ''); ?>><?php _e('Select One', 'packlink-custom-shipping'); ?></option>
                                <option value="ES" <?php selected(get_option('packlink_country', 'ES'), 'ES'); ?>><?php _e('Spain', 'packlink-custom-shipping'); ?></option>
                                <option value="DE" <?php selected(get_option('packlink_country', 'ES'), 'DE'); ?>><?php _e('Germany', 'packlink-custom-shipping'); ?></option>
                                <option value="FR" <?php selected(get_option('packlink_country', 'ES'), 'FR'); ?>><?php _e('France', 'packlink-custom-shipping'); ?></option>
                                <option value="IT" <?php selected(get_option('packlink_country', 'ES'), 'IT'); ?>><?php _e('Italy', 'packlink-custom-shipping'); ?></option>
                                <option value="UK" <?php selected(get_option('packlink_country', 'ES'), 'UK'); ?>><?php _e('United Kingdom', 'packlink-custom-shipping'); ?></option>
                            </select>
                            <p class="description"><?php _e('Select your Packlink platform country.', 'packlink-custom-shipping'); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>

                <?php if (get_option('packlink_api_key')): ?>
                    <div class="packlink-test-connection">
                        <h2><?php _e('Test Connection', 'packlink-custom-shipping'); ?></h2>
                        <button type="button" class="button" id="packlink-test-connection-btn"><?php _e('Test Connection', 'packlink-custom-shipping'); ?></button>
                        <span class="spinner"></span>
                        <div id="packlink-test-connection-result"></div>
                    </div>

                    <script>
                        jQuery(document).ready(function($) {
                            $('#packlink-test-connection-btn').on('click', function() {
                                var $button = $(this);
                                var $spinner = $button.next('.spinner');
                                var $result = $('#packlink-test-connection-result');

                                $button.prop('disabled', true);
                                $spinner.css('visibility', 'visible');
                                $result.html('');

                                $.ajax({
                                    url: ajaxurl,
                                    type: 'POST',
                                    data: {
                                        action: 'packlink_test_connection',
                                        nonce: '<?php echo wp_create_nonce('packlink_test_connection'); ?>'
                                    },
                                    success: function(response) {
                                        if (response.success) {
                                            $result.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                                        } else {
                                            $result.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                                        }
                                    },
                                    error: function() {
                                        $result.html('<div class="notice notice-error inline"><p><?php _e('An error occurred while testing the connection.', 'packlink-custom-shipping'); ?></p></div>');
                                    },
                                    complete: function() {
                                        $button.prop('disabled', false);
                                        $spinner.css('visibility', 'hidden');
                                    }
                                });
                            });
                        });
                    </script>
                <?php endif; ?>
            </form>
        </div>
    <?php
    }

    /**
     * Render sender settings page
     */
    public static function render_sender_settings_page()
    {
    ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <form method="post" action="options.php">
                <?php settings_fields('packlink_sender_settings'); ?>
                <?php do_settings_sections('packlink_sender_settings'); ?>

                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e('Sender Name', 'packlink-custom-shipping'); ?></th>
                        <td>
                            <input type="text" name="packlink_sender_name" value="<?php echo esc_attr(get_option('packlink_sender_name', get_bloginfo('name'))); ?>" class="regular-text" />
                            <p class="description"><?php _e('Enter the sender name for shipments.', 'packlink-custom-shipping'); ?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e('Company Name', 'packlink-custom-shipping'); ?></th>
                        <td>
                            <input type="text" name="packlink_sender_company" value="<?php echo esc_attr(get_option('packlink_sender_company', get_bloginfo('name'))); ?>" class="regular-text" />
                            <p class="description"><?php _e('Enter the company name for shipments.', 'packlink-custom-shipping'); ?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e('Phone Number', 'packlink-custom-shipping'); ?></th>
                        <td>
                            <input type="text" name="packlink_sender_phone" value="<?php echo esc_attr(get_option('packlink_sender_phone')); ?>" class="regular-text" />
                            <p class="description"><?php _e('Enter the sender phone number for shipments.', 'packlink-custom-shipping'); ?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e('Email Address', 'packlink-custom-shipping'); ?></th>
                        <td>
                            <input type="email" name="packlink_sender_email" value="<?php echo esc_attr(get_option('packlink_sender_email', get_option('admin_email'))); ?>" class="regular-text" />
                            <p class="description"><?php _e('Enter the sender email address for shipments.', 'packlink-custom-shipping'); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
    <?php
    }

    /**
     * Render shipping settings page
     */
    public static function render_shipping_settings_page()
    {
    ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <form method="post" action="options.php">
                <?php settings_fields('packlink_shipping_settings'); ?>
                <?php do_settings_sections('packlink_shipping_settings'); ?>

                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e('Automatic Shipment Creation', 'packlink-custom-shipping'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="packlink_auto_create_shipment" value="yes" <?php checked(get_option('packlink_auto_create_shipment'), 'yes'); ?> />
                                <?php _e('Automatically create shipments when orders are placed', 'packlink-custom-shipping'); ?>
                            </label>
                            <p class="description"><?php _e('If enabled, shipments will be created automatically when orders are processed.', 'packlink-custom-shipping'); ?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e('Order Status After Shipment', 'packlink-custom-shipping'); ?></th>
                        <td>
                            <select name="packlink_order_status_after_shipment">
                                <option value=""><?php _e('Do not change', 'packlink-custom-shipping'); ?></option>
                                <?php
                                $order_statuses = wc_get_order_statuses();
                                foreach ($order_statuses as $status => $label) {
                                    $status_key = str_replace('wc-', '', $status);
                                    echo '<option value="' . esc_attr($status_key) . '" ' . selected(get_option('packlink_order_status_after_shipment'), $status_key, false) . '>' . esc_html($label) . '</option>';
                                }
                                ?>
                            </select>
                            <p class="description"><?php _e('Select the order status to set after a shipment is created.', 'packlink-custom-shipping'); ?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e('Default Package Dimensions', 'packlink-custom-shipping'); ?></th>
                        <td>
                            <div class="packlink-dimensions-group">
                                <div class="packlink-dimension">
                                    <label><?php _e('Weight (kg)', 'packlink-custom-shipping'); ?></label>
                                    <input type="number" name="packlink_default_package_weight" value="<?php echo esc_attr(get_option('packlink_default_package_weight', '1')); ?>" step="0.01" min="0.01" />
                                </div>
                                <div class="packlink-dimension">
                                    <label><?php _e('Length (cm)', 'packlink-custom-shipping'); ?></label>
                                    <input type="number" name="packlink_default_package_length" value="<?php echo esc_attr(get_option('packlink_default_package_length', '10')); ?>" min="1" />
                                </div>
                                <div class="packlink-dimension">
                                    <label><?php _e('Width (cm)', 'packlink-custom-shipping'); ?></label>
                                    <input type="number" name="packlink_default_package_width" value="<?php echo esc_attr(get_option('packlink_default_package_width', '10')); ?>" min="1" />
                                </div>
                                <div class="packlink-dimension">
                                    <label><?php _e('Height (cm)', 'packlink-custom-shipping'); ?></label>
                                    <input type="number" name="packlink_default_package_height" value="<?php echo esc_attr(get_option('packlink_default_package_height', '10')); ?>" min="1" />
                                </div>
                            </div>
                            <p class="description"><?php _e('Default package dimensions to use when not specified.', 'packlink-custom-shipping'); ?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e('Commission Settings', 'packlink-custom-shipping'); ?></th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text"><?php _e('Commission Settings', 'packlink-custom-shipping'); ?></legend>

                                <p>
                                    <label>
                                        <input type="radio" name="packlink_commission_type" value="none" <?php checked(get_option('packlink_commission_type', 'none'), 'none'); ?> />
                                        <?php _e('No Commission', 'packlink-custom-shipping'); ?>
                                    </label>
                                </p>

                                <p>
                                    <label>
                                        <input type="radio" name="packlink_commission_type" value="fixed" <?php checked(get_option('packlink_commission_type', 'none'), 'fixed'); ?> />
                                        <?php _e('Fixed Amount', 'packlink-custom-shipping'); ?>
                                    </label>
                                    <input type="number" name="packlink_commission_fixed_amount" value="<?php echo esc_attr(get_option('packlink_commission_fixed_amount', '0')); ?>" step="0.01" min="0" style="width: 80px;" />
                                    <?php echo get_woocommerce_currency_symbol(); ?>
                                </p>

                                <p>
                                    <label>
                                        <input type="radio" name="packlink_commission_type" value="percentage" <?php checked(get_option('packlink_commission_type', 'none'), 'percentage'); ?> />
                                        <?php _e('Percentage', 'packlink-custom-shipping'); ?>
                                    </label>
                                    <input type="number" name="packlink_commission_percentage" value="<?php echo esc_attr(get_option('packlink_commission_percentage', '0')); ?>" step="0.1" min="0" max="100" style="width: 80px;" />
                                    %
                                </p>

                                <p class="description"><?php _e('Add a commission to Packlink shipping rates.', 'packlink-custom-shipping'); ?></p>
                            </fieldset>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e('Multiple Packages', 'packlink-custom-shipping'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="packlink_allow_multiple_packages" value="yes" <?php checked(get_option('packlink_allow_multiple_packages'), 'yes'); ?> />
                                <?php _e('Allow customers to add multiple packages', 'packlink-custom-shipping'); ?>
                            </label>
                            <p class="description"><?php _e('If enabled, customers can add multiple packages with different dimensions.', 'packlink-custom-shipping'); ?></p>
                        </td>
                    </tr>

                </table>

                <?php submit_button(); ?>
            </form>
        </div>

        <style>
            .packlink-dimensions-group {
                display: flex;
                gap: 15px;
                margin-bottom: 10px;
            }

            .packlink-dimension {
                display: flex;
                flex-direction: column;
            }

            .packlink-dimension label {
                margin-bottom: 5px;
                font-weight: 500;
            }

            .packlink-dimension input {
                width: 80px;
            }
        </style>
    <?php
    }

    /**
     * Render currency settings page
     */
    public static function render_currency_settings_page()
    {
        $woocs_active = class_exists('WOOCS') && isset($GLOBALS['WOOCS']);
        $is_multi_currency_available = $woocs_active && $GLOBALS['WOOCS']->is_multiple_allowed;
    ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <?php if (!$woocs_active): ?>
                <div class="notice notice-warning">
                    <p>
                        <?php _e('Multi-currency support requires the FOX - Currency Switcher Professional for WooCommerce plugin to be installed and activated.', 'packlink-custom-shipping'); ?>
                        <a href="https://wordpress.org/plugins/woocommerce-currency-switcher/" target="_blank"><?php _e('Download Plugin', 'packlink-custom-shipping'); ?></a>
                    </p>
                </div>
            <?php elseif (!$is_multi_currency_available): ?>
                <div class="notice notice-warning">
                    <p>
                        <?php _e('Multi-currency is not enabled in your Currency Switcher plugin. Please enable "Is Multiple Currency" option in Currency Switcher settings.', 'packlink-custom-shipping'); ?>
                        <a href="<?php echo admin_url('admin.php?page=woocs'); ?>" target="_blank"><?php _e('Currency Switcher Settings', 'packlink-custom-shipping'); ?></a>
                    </p>
                </div>
            <?php else: ?>
                <div class="notice notice-success">
                    <p>
                        <?php _e('Multi-currency support is active! Your Packlink shipping rates will automatically convert to the selected currency.', 'packlink-custom-shipping'); ?>
                    </p>
                </div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php settings_fields('packlink_currency_settings'); ?>
                <?php do_settings_sections('packlink_currency_settings'); ?>

                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e('Default Currency', 'packlink-custom-shipping'); ?></th>
                        <td>
                            <select name="packlink_default_currency" <?php echo !$woocs_active ? 'disabled' : ''; ?>>
                                <?php
                                $default_currency = get_option('packlink_default_currency', 'EUR');
                                $currencies = [
                                    'EUR' => 'Euro (€)',
                                    'USD' => 'US Dollar ($)',
                                    'GBP' => 'British Pound (£)',
                                    'JPY' => 'Japanese Yen (¥)',
                                    'CAD' => 'Canadian Dollar (C$)',
                                    'AUD' => 'Australian Dollar (A$)',
                                    'CHF' => 'Swiss Franc (CHF)',
                                    'SEK' => 'Swedish Krona (kr)',
                                    'NOK' => 'Norwegian Krone (kr)',
                                    'DKK' => 'Danish Krone (kr)',
                                    'PLN' => 'Polish Złoty (zł)',
                                    'CZK' => 'Czech Koruna (Kč)',
                                    'HUF' => 'Hungarian Forint (Ft)'
                                ];

                                if ($woocs_active) {
                                    $woocs_currencies = $GLOBALS['WOOCS']->get_currencies();
                                    if (!empty($woocs_currencies)) {
                                        $currencies = [];
                                        foreach ($woocs_currencies as $code => $currency) {
                                            $currencies[$code] = $currency['name'] . ' (' . $currency['symbol'] . ')';
                                        }
                                    }
                                }

                                foreach ($currencies as $code => $name) {
                                    echo '<option value="' . esc_attr($code) . '" ' . selected($default_currency, $code, false) . '>' . esc_html($name) . '</option>';
                                }
                                ?>
                            </select>
                            <p class="description">
                                <?php _e('This is the base currency that Packlink API returns. Prices will be converted from this currency to the customer\'s selected currency.', 'packlink-custom-shipping'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e('Currency Display Notice', 'packlink-custom-shipping'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="packlink_currency_display_notice" value="yes" <?php checked(get_option('packlink_currency_display_notice', 'no'), 'yes'); ?> <?php echo !$is_multi_currency_available ? 'disabled' : ''; ?> />
                                <?php _e('Show currency information in shipping form', 'packlink-custom-shipping'); ?>
                            </label>
                            <p class="description"><?php _e('Display current currency and conversion notice to customers.', 'packlink-custom-shipping'); ?></p>
                        </td>
                    </tr>

                    <?php if ($woocs_active): ?>
                        <tr valign="top">
                            <th scope="row"><?php _e('Available Currencies', 'packlink-custom-shipping'); ?></th>
                            <td>
                                <?php
                                $woocs_currencies = $GLOBALS['WOOCS']->get_currencies();
                                if (!empty($woocs_currencies)) {
                                    echo '<div class="packlink-currency-list">';
                                    foreach ($woocs_currencies as $code => $currency) {
                                        $is_default = isset($currency['is_etalon']) && $currency['is_etalon'];
                                        echo '<div class="currency-item">';
                                        echo '<span class="currency-code">' . esc_html($code) . '</span>';
                                        echo '<span class="currency-name">' . esc_html($currency['name']) . '</span>';
                                        echo '<span class="currency-symbol">' . esc_html($currency['symbol']) . '</span>';
                                        echo '<span class="currency-rate">Rate: ' . esc_html($currency['rate']) . '</span>';
                                        if ($is_default) {
                                            echo '<span class="currency-default">' . __('(Default)', 'packlink-custom-shipping') . '</span>';
                                        }
                                        echo '</div>';
                                    }
                                    echo '</div>';
                                }
                                ?>
                                <p class="description">
                                    <?php _e('These are the currencies configured in your Currency Switcher plugin.', 'packlink-custom-shipping'); ?>
                                    <a href="<?php echo admin_url('admin.php?page=woocs'); ?>" target="_blank"><?php _e('Manage Currencies', 'packlink-custom-shipping'); ?></a>
                                </p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>

        <style>
            .packlink-currency-list {
                max-height: 300px;
                overflow-y: auto;
                border: 1px solid #ddd;
                padding: 10px;
                background: #f9f9f9;
                border-radius: 4px;
            }

            .currency-item {
                padding: 8px 0;
                border-bottom: 1px solid #eee;
                display: flex;
                gap: 15px;
                align-items: center;
            }

            .currency-item:last-child {
                border-bottom: none;
            }

            .currency-code {
                font-weight: bold;
                min-width: 50px;
            }

            .currency-name {
                flex: 1;
            }

            .currency-symbol {
                font-weight: bold;
                color: #0073aa;
            }

            .currency-rate {
                color: #666;
                font-size: 12px;
            }

            .currency-default {
                color: #d63638;
                font-weight: bold;
                font-size: 12px;
            }
        </style>
<?php
    }

    /**
     * Test connection
     */
    public static function test_connection()
    {
        // Verify nonce
        if (!check_ajax_referer('packlink_test_connection', 'nonce', false)) {
            wp_send_json_error([
                'message' => __('Invalid security token.', 'packlink-custom-shipping')
            ]);
        }

        $api = new Packlink_API();

        // Test connection by getting postal zones
        $response = $api->get('locations/postalzones/origins', [
            'language' => $api->get_language()
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error([
                'message' => $response->get_error_message()
            ]);
        } else {
            wp_send_json_success([
                'message' => __('Connection successful! Your API key is valid.', 'packlink-custom-shipping')
            ]);
        }
    }


    /**
     * Add shortcode attributes
     * 
     * @param array $atts Shortcode attributes
     * @return array Modified attributes
     */
    public static function get_shortcode_atts($atts)
    {
        $defaults = [
            'title' => __('Calculate Shipping', 'packlink-custom-shipping'),
            'button_text' => __('Checkout', 'packlink-custom-shipping'),
            'show_origin' => 'no',
        ];

        return shortcode_atts($defaults, $atts);
    }
}

// Add AJAX handler for testing connection
add_action('wp_ajax_packlink_test_connection', ['Packlink_Settings', 'test_connection']);
