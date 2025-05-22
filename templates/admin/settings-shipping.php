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