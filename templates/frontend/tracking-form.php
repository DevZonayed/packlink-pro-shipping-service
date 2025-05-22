<!-- Add these translations to be used by JavaScript -->
<script type="text/javascript">
    var packlink_tracking = {
        ajax_url: '<?php echo esc_js(admin_url('admin-ajax.php')); ?>',
        enter_reference: '<?php echo esc_js(__('Please enter a tracking reference number', 'packlink-custom-shipping')); ?>',
        status_delivered: '<?php echo esc_js(__('Delivered', 'packlink-custom-shipping')); ?>',
        status_in_transit: '<?php echo esc_js(__('In Transit', 'packlink-custom-shipping')); ?>',
        status_exception: '<?php echo esc_js(__('Exception', 'packlink-custom-shipping')); ?>',
        status_cancelled: '<?php echo esc_js(__('Cancelled', 'packlink-custom-shipping')); ?>',
        status_pending: '<?php echo esc_js(__('Pending', 'packlink-custom-shipping')); ?>',
        status_ready_to_ship: '<?php echo esc_js(__('Ready to Ship', 'packlink-custom-shipping')); ?>',
        status_ready_to_purchase: '<?php echo esc_js(__('Ready to Purchase', 'packlink-custom-shipping')); ?>',
        status_unknown: '<?php echo esc_js(__('Unknown Status', 'packlink-custom-shipping')); ?>',
        no_tracking_available: '<?php echo esc_js(__('No tracking information is available yet. The shipment may still be in processing.', 'packlink-custom-shipping')); ?>',
        carrier: '<?php echo esc_js(__('Carrier', 'packlink-custom-shipping')); ?>',
        service: '<?php echo esc_js(__('Service', 'packlink-custom-shipping')); ?>',
        reference: '<?php echo esc_js(__('Reference', 'packlink-custom-shipping')); ?>',
        created_date: '<?php echo esc_js(__('Created Date', 'packlink-custom-shipping')); ?>',
        tracking_link: '<?php echo esc_js(__('Tracking Link', 'packlink-custom-shipping')); ?>',
        view_on_carrier_site: '<?php echo esc_js(__('View on carrier site', 'packlink-custom-shipping')); ?>',
        from: '<?php echo esc_js(__('From', 'packlink-custom-shipping')); ?>',
        to: '<?php echo esc_js(__('To', 'packlink-custom-shipping')); ?>'
    };
</script>

<div class="packlink-tracking-container">
    <h2 class="packlink-form-title"><?php echo esc_html($atts['title']); ?></h2>
    
    <form id="packlink-tracking-form">
        <?php wp_nonce_field('packlink_tracking_nonce', 'packlink_tracking_nonce'); ?>
        
        <div class="form-group">
            <label for="tracking_reference"><?php _e('Tracking Reference Number', 'packlink-custom-shipping'); ?></label>
            <div class="input-group">
                <input type="text" id="tracking_reference" name="tracking_reference" 
                       placeholder="<?php _e('Enter your tracking reference number', 'packlink-custom-shipping'); ?>" required>
                <button type="submit" class="tracking-submit-btn">
                    <?php _e('Track', 'packlink-custom-shipping'); ?>
                </button>
            </div>
        </div>
    </form>
    
    <div id="packlink-tracking-results" class="tracking-results" style="display: none;">
        <div class="tracking-status-container">
            <h3><?php _e('Shipment Status', 'packlink-custom-shipping'); ?></h3>
            <div class="tracking-status">
                <div class="status-indicator">
                    <div class="status-circle"></div>
                    <div class="status-text" id="status-text"></div>
                </div>
            </div>
            
            <div class="shipment-details">
                <h4><?php _e('Shipment Information', 'packlink-custom-shipping'); ?></h4>
                <div id="shipment-info">
                    <!-- Shipment details will be injected here -->
                </div>
            </div>
        </div>
        
        <div class="tracking-timeline-container">
            <h3><?php _e('Tracking Timeline', 'packlink-custom-shipping'); ?></h3>
            <div class="tracking-timeline" id="tracking-timeline">
                <!-- Timeline will be injected here -->
            </div>
        </div>
    </div>
    
    <div id="tracking-error" class="tracking-error" style="display: none;">
        <p><?php _e('Sorry, we couldn\'t find any information for that tracking number. Please check the number and try again.', 'packlink-custom-shipping'); ?></p>
    </div>
    
    <div id="tracking-loading" class="tracking-loading" style="display: none;">
        <div class="packlink-loading-spinner"></div>
        <p><?php _e('Loading tracking information...', 'packlink-custom-shipping'); ?></p>
    </div>
</div>