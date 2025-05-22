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