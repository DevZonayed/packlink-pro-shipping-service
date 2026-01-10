<?php

/**
 * Currency handler for multi-currency support
 *
 * @package PacklinkCustomShipping
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles currency conversion and integration with WOOCS
 */
class Packlink_Currency_Handler
{

    /**
     * WOOCS instance
     *
     * @var WOOCS
     */
    private static $woocs = null;

    /**
     * Default currency
     *
     * @var string
     */
    private static $default_currency = 'EUR';

    /**
     * Current currency
     *
     * @var string
     */
    private static $current_currency = null;

    /**
     * Initialize currency handler
     *
     * @return void
     */
    public static function init()
    {
        // Check if WOOCS is available
        if (class_exists('WOOCS') && isset($GLOBALS['WOOCS'])) {
            self::$woocs = $GLOBALS['WOOCS'];
        }

        // Set default currency from Packlink settings or fallback
        self::$default_currency = get_option('packlink_default_currency', 'EUR');
    }

    /**
     * Check if multi-currency is active
     *
     * @return bool
     */
    public static function is_multi_currency_active()
    {
        return self::$woocs !== null && self::$woocs->is_multiple_allowed;
    }

    /**
     * Get current currency
     *
     * @return string
     */
    public static function get_current_currency()
    {
        if (self::$current_currency !== null) {
            return self::$current_currency;
        }

        if (self::is_multi_currency_active()) {
            self::$current_currency = self::$woocs->get_woocommerce_currency();
        } else {
            self::$current_currency = get_woocommerce_currency();
        }

        return self::$current_currency;
    }

    /**
     * Get currency rate
     *
     * @param string $from_currency From currency
     * @param string $to_currency To currency
     * @return float
     */
    public static function get_currency_rate($from_currency = null, $to_currency = null)
    {
        if (!self::is_multi_currency_active()) {
            return 1.0;
        }

        if ($from_currency === null) {
            $from_currency = self::$default_currency;
        }

        if ($to_currency === null) {
            $to_currency = self::get_current_currency();
        }

        if ($from_currency === $to_currency) {
            return 1.0;
        }

        $currencies = self::$woocs->get_currencies();

        if (!isset($currencies[$to_currency])) {
            return 1.0;
        }

        return floatval($currencies[$to_currency]['rate']);
    }

    /**
     * Convert price from one currency to another
     *
     * @param float $amount Amount to convert
     * @param string $from_currency From currency
     * @param string $to_currency To currency
     * @return float
     */
    public static function convert_price($amount, $from_currency = null, $to_currency = null)
    {
        if (!self::is_multi_currency_active()) {
            return $amount;
        }

        if ($from_currency === null) {
            $from_currency = self::$default_currency;
        }

        if ($to_currency === null) {
            $to_currency = self::get_current_currency();
        }

        if ($from_currency === $to_currency) {
            return $amount;
        }

        $rate = self::get_currency_rate($from_currency, $to_currency);
        return floatval($amount) * $rate;
    }

    /**
     * Format price with currency symbol
     *
     * @param float $amount Amount to format
     * @param string $currency Currency code
     * @param bool $convert Whether to convert the price
     * @return string
     */
    public static function format_price($amount, $currency = null, $convert = true)
    {
        if ($currency === null) {
            $currency = self::get_current_currency();
        }

        // Convert price if needed
        if ($convert && self::is_multi_currency_active()) {
            $amount = self::convert_price($amount, self::$default_currency, $currency);
        }

        // Use WooCommerce price formatting if available
        if (function_exists('wc_price')) {
            // Temporarily set the currency for formatting
            $original_currency = get_woocommerce_currency();

            if (self::is_multi_currency_active() && self::$woocs) {
                return self::$woocs->wc_price($amount, false, ['currency' => $currency]);
            } else {
                return wc_price($amount);
            }
        }

        // Fallback formatting
        return self::get_currency_symbol($currency) . number_format($amount, 2);
    }

    /**
     * Get currency symbol
     *
     * @param string $currency Currency code
     * @return string
     */
    public static function get_currency_symbol($currency = null)
    {
        if ($currency === null) {
            $currency = self::get_current_currency();
        }

        if (self::is_multi_currency_active() && self::$woocs) {
            $currencies = self::$woocs->get_currencies();
            if (isset($currencies[$currency]) && isset($currencies[$currency]['symbol'])) {
                return $currencies[$currency]['symbol'];
            }
        }

        // Fallback to WooCommerce currency symbols
        if (function_exists('get_woocommerce_currency_symbol')) {
            return get_woocommerce_currency_symbol($currency);
        }

        // Basic fallback symbols
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'CAD' => 'C$',
            'AUD' => 'A$',
        ];

        return isset($symbols[$currency]) ? $symbols[$currency] : $currency . ' ';
    }

    /**
     * Get currency position
     *
     * @param string $currency Currency code
     * @return string
     */
    public static function get_currency_position($currency = null)
    {
        if ($currency === null) {
            $currency = self::get_current_currency();
        }

        if (self::is_multi_currency_active() && self::$woocs) {
            $currencies = self::$woocs->get_currencies();
            if (isset($currencies[$currency]) && isset($currencies[$currency]['position'])) {
                return $currencies[$currency]['position'];
            }
        }

        // Fallback to WooCommerce setting
        return get_option('woocommerce_currency_pos', 'left');
    }

    /**
     * Get all available currencies
     *
     * @return array
     */
    public static function get_available_currencies()
    {
        if (self::is_multi_currency_active()) {
            return self::$woocs->get_currencies();
        }

        return [
            get_woocommerce_currency() => [
                'name' => get_woocommerce_currency(),
                'symbol' => get_woocommerce_currency_symbol(),
                'rate' => 1.0,
                'is_etalon' => 1
            ]
        ];
    }

    /**
     * Get currency data for JavaScript
     *
     * @return array
     */
    public static function get_currency_data_for_js()
    {
        $current_currency = self::get_current_currency();

        return [
            'current_currency' => $current_currency,
            'symbol' => self::get_currency_symbol($current_currency),
            'position' => self::get_currency_position($current_currency),
            'decimals' => wc_get_price_decimals(),
            'decimal_separator' => wc_get_price_decimal_separator(),
            'thousand_separator' => wc_get_price_thousand_separator(),
            'is_multi_currency_active' => self::is_multi_currency_active(),
            'rates' => self::is_multi_currency_active() ? self::$woocs->get_currencies() : []
        ];
    }

    /**
     * Convert Packlink API response to current currency
     *
     * @param array $shipping_rates Shipping rates from API
     * @return array
     */
    public static function convert_shipping_rates($shipping_rates)
    {
        if (!self::is_multi_currency_active() || empty($shipping_rates)) {
            return $shipping_rates;
        }

        $current_currency = self::get_current_currency();

        foreach ($shipping_rates as &$rate) {
            // Store original currency and price
            $rate['original_currency'] = isset($rate['currency']) ? $rate['currency'] : self::$default_currency;
            $rate['original_price'] = $rate['price'];

            // Convert price to current currency
            $rate['price'] = self::convert_price(
                $rate['price'],
                $rate['original_currency'],
                $current_currency
            );

            // Update currency
            $rate['currency'] = $current_currency;
        }

        return $shipping_rates;
    }

    /**
     * Save currency data to order meta
     *
     * @param WC_Order $order Order object
     * @param array $packlink_data Packlink data
     * @return void
     */
    public static function save_currency_to_order($order, $packlink_data = [])
    {
        $current_currency = self::get_current_currency();
        $rate = self::get_currency_rate(self::$default_currency, $current_currency);

        // Save currency information
        $order->update_meta_data('_packlink_currency', $current_currency);
        $order->update_meta_data('_packlink_currency_rate', $rate);
        $order->update_meta_data('_packlink_base_currency', self::$default_currency);

        if (self::is_multi_currency_active()) {
            $order->update_meta_data('_packlink_multi_currency_active', 'yes');
        }
    }
}