<div class="packlink-custom-shipping-container">
    <h2 class="packlink-form-title"><?php echo esc_html($atts['title']); ?></h2>

    <form id="packlink-custom-shipping-form">
        <?php wp_nonce_field('packlink_shipping_nonce', 'packlink_nonce'); ?>
        <?php $ajax_nonce = wp_create_nonce('packlink-ajax-nonce'); ?>
        <input type="hidden" id="packlink-ajax-nonce" value="<?php echo esc_attr($ajax_nonce); ?>">

        <div class="progress-tracker">
            <div class="progress-step active" data-step="1">
                <div class="step-icon">1</div>
                <div class="step-label"><?php _e('Route', 'packlink-custom-shipping'); ?></div>
            </div>
            <div class="progress-step" data-step="2">
                <div class="step-icon">2</div>
                <div class="step-label"><?php _e('Packages', 'packlink-custom-shipping'); ?></div>
            </div>
            <div class="progress-step" data-step="3">
                <div class="step-icon">3</div>
                <div class="step-label"><?php _e('Shipping', 'packlink-custom-shipping'); ?></div>
            </div>
            <div class="progress-step" data-step="4">
                <div class="step-icon">4</div>
                <div class="step-label"><?php _e('Details', 'packlink-custom-shipping'); ?></div>
            </div>
            <div class="progress-step" data-step="5">
                <div class="step-icon">5</div>
                <div class="step-label"><?php _e('Review', 'packlink-custom-shipping'); ?></div>
            </div>
        </div>

        <div class="form-section active" id="route-section">
            <div class="section-header">
                <h3><?php _e('Route Information', 'packlink-custom-shipping'); ?></h3>
                <p><?php _e('Tell us where your package is going', 'packlink-custom-shipping'); ?></p>
            </div>

            <div class="route-options">
                <label class="route-connect-option">
                    <input type="checkbox" id="connect-locations" checked>
                    <span class="route-connect-text"><?php _e('Connect routes (destination of one route becomes origin of next)', 'packlink-custom-shipping'); ?></span>
                </label>
            </div>

            <div id="routes-container">
                <!-- Routes will be added here dynamically -->
            </div>

            <div class="route-actions">
                <button type="button" id="add-route" class="btn-add">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-plus">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <?php _e('Add Another Route', 'packlink-custom-shipping'); ?>
                </button>
            </div>

            <div class="form-navigation">
                <button type="button" class="btn-next" data-next="packages-section"><?php _e('Continue to Packages', 'packlink-custom-shipping'); ?></button>
            </div>
        </div>

        <div class="form-section" id="packages-section">
            <div class="section-header">
                <h3><?php _e('Package Details', 'packlink-custom-shipping'); ?></h3>
                <p><?php _e('Tell us about what you\'re shipping', 'packlink-custom-shipping'); ?></p>
            </div>

            <div id="packlink-packages-container">
                <!-- Packages will be added here dynamically -->
            </div>

            <?php if (get_option('packlink_allow_multiple_packages') === 'yes'): ?>
                <button type="button" id="add-package" class="btn-add">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-plus">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <?php _e('Add Another Package', 'packlink-custom-shipping'); ?>
                </button>
            <?php endif; ?>

            <div class="form-navigation">
                <button type="button" class="btn-prev" data-prev="route-section"><?php _e('Back', 'packlink-custom-shipping'); ?></button>
                <button type="button" class="btn-next" data-next="shipping-section"><?php _e('Continue to Shipping Options', 'packlink-custom-shipping'); ?></button>
            </div>
        </div>

        <div class="form-section" id="shipping-section">
            <div class="section-header">
                <h3><?php _e('Shipping Options', 'packlink-custom-shipping'); ?></h3>
                <p><?php _e('Select your preferred shipping method for each route', 'packlink-custom-shipping'); ?></p>
            </div>

            <div id="shipping-options-container">
                <div class="packlink-loading">
                    <div class="packlink-loading-spinner"></div>
                    <p><?php _e('Loading shipping options...', 'packlink-custom-shipping'); ?></p>
                </div>
            </div>

            <div class="form-navigation">
                <button type="button" class="btn-prev" data-prev="packages-section"><?php _e('Back', 'packlink-custom-shipping'); ?></button>
                <button type="button" class="btn-next" data-next="details-section" disabled id="shipping-continue-btn"><?php _e('Continue to Details', 'packlink-custom-shipping'); ?></button>
            </div>
        </div>

        <div class="form-section" id="details-section">
            <div class="section-header">
                <h3><?php _e('Contact Information', 'packlink-custom-shipping'); ?></h3>
                <p><?php _e('Who\'s sending and receiving the package', 'packlink-custom-shipping'); ?></p>
            </div>

            <!-- Add the contact connection switch -->
            <div class="contact-options">
                <label class="contact-connect-option" style="display: flex; align-items: center; cursor: pointer;">
                    <input type="checkbox" id="connect-contacts" checked style="position: relative; width: 40px; height: 20px; margin: 0 10px 0 0; appearance: none; background-color: #ccc; border-radius: 20px; transition: 0.4s; outline: none; cursor: pointer;">
                    <span class="contact-connect-text" style="user-select: none;"><?php _e('Connect contacts (recipient of one route becomes sender of next)', 'packlink-custom-shipping'); ?></span>
                    
                </label>
            </div>

            <!-- This is the container for per-route contact forms -->
            <div id="route-contacts-container">
                <!-- Route contact forms will be added here dynamically -->
            </div>

            <div class="form-navigation">
                <button type="button" class="btn-prev" data-prev="shipping-section"><?php _e('Back', 'packlink-custom-shipping'); ?></button>
                <button type="button" class="btn-next" data-next="review-section"><?php _e('Continue to Review', 'packlink-custom-shipping'); ?></button>
            </div>
        </div>


        <div class="form-section" id="review-section">
            <div class="section-header">
                <h3><?php _e('Review Your Shipment', 'packlink-custom-shipping'); ?></h3>
                <p><?php _e('Check all details before submitting', 'packlink-custom-shipping'); ?></p>
            </div>

            <div class="review-summary">
                <div class="review-item">
                    <h4><?php _e('Routes Information', 'packlink-custom-shipping'); ?></h4>
                    <div class="review-details" id="summary-routes">
                        <!-- Route summaries will be inserted here -->
                    </div>
                </div>

                <div class="review-item">
                    <h4><?php _e('Package Information', 'packlink-custom-shipping'); ?></h4>
                    <div class="review-details" id="summary-packages">
                        <!-- Package summaries will be inserted here -->
                    </div>
                </div>

                <div class="review-item">
                    <h4><?php _e('Total Price', 'packlink-custom-shipping'); ?></h4>
                    <div class="review-details">
                        <p class="total-price"><?php _e('Total', 'packlink-custom-shipping'); ?>: <span id="summary-total-price">€0.00</span></p>
                    </div>
                </div>
            </div>

            <div class="form-navigation">
                <button type="button" class="btn-prev" data-prev="details-section"><?php _e('Back', 'packlink-custom-shipping'); ?></button>
                <button type="submit" class="btn-submit"><?php echo esc_html($atts['button_text']); ?></button>
            </div>
        </div>
    </form>

    <!-- Route template for JavaScript to clone -->
    <template id="route-template">
        <div class="route-set" data-route-index="{ROUTE_INDEX}">
            <div class="route-header">
                <h4><?php _e('Route', 'packlink-custom-shipping'); ?> #{ROUTE_NUMBER}</h4>
                <button type="button" class="btn-remove-route">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash-2">
                        <path d="M3 6h18"></path>
                        <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                        <line x1="10" y1="11" x2="10" y2="17"></line>
                        <line x1="14" y1="11" x2="14" y2="17"></line>
                    </svg>
                </button>
            </div>

            <div class="form-grid">
                <div class="form-column">
                    <h4><?php _e('Origin', 'packlink-custom-shipping'); ?></h4>
                    <div class="form-group">
                        <label for="origin_country_{ROUTE_INDEX}"><?php _e('Country', 'packlink-custom-shipping'); ?></label>
                        <div class="country-search-wrapper">
                            <input type="text" class="country-search" id="origin_country_search_{ROUTE_INDEX}" placeholder="<?php _e('Search country', 'packlink-custom-shipping'); ?>" autocomplete="off">
                            <input type="hidden" name="origin_country_{ROUTE_INDEX}" id="origin_country_{ROUTE_INDEX}" required>
                            <div class="country-options" id="origin_country_options_{ROUTE_INDEX}"></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="origin_postal_code_{ROUTE_INDEX}"><?php _e('Postal Code', 'packlink-custom-shipping'); ?></label>
                        <input type="text" name="origin_postal_code_{ROUTE_INDEX}" id="origin_postal_code_{ROUTE_INDEX}" placeholder="<?php _e('Enter postal code', 'packlink-custom-shipping'); ?>" required>
                    </div>
                    <input type="hidden" name="origin_city_{ROUTE_INDEX}" id="origin_city_{ROUTE_INDEX}" required>

                    <div class="form-group">
                        <label for="origin_address_{ROUTE_INDEX}"><?php _e('Address', 'packlink-custom-shipping'); ?></label>
                        <input type="text" name="origin_address_{ROUTE_INDEX}" id="origin_address_{ROUTE_INDEX}" placeholder="<?php _e('Enter full address', 'packlink-custom-shipping'); ?>" required>
                    </div>
                </div>

                <div class="form-column">
                    <h4><?php _e('Destination', 'packlink-custom-shipping'); ?></h4>
                    <div class="form-group">
                        <label for="destination_country_{ROUTE_INDEX}"><?php _e('Country', 'packlink-custom-shipping'); ?></label>
                        <div class="country-search-wrapper">
                            <input type="text" class="country-search" id="destination_country_search_{ROUTE_INDEX}" placeholder="<?php _e('Search country', 'packlink-custom-shipping'); ?>" autocomplete="off">
                            <input type="hidden" name="destination_country_{ROUTE_INDEX}" id="destination_country_{ROUTE_INDEX}" required>
                            <div class="country-options" id="destination_country_options_{ROUTE_INDEX}"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="destination_postal_code_{ROUTE_INDEX}"><?php _e('Postal Code', 'packlink-custom-shipping'); ?></label>
                        <input type="text" name="destination_postal_code_{ROUTE_INDEX}" id="destination_postal_code_{ROUTE_INDEX}" placeholder="<?php _e('Enter postal code', 'packlink-custom-shipping'); ?>" required>
                    </div>
                    <input type="hidden" name="destination_city_{ROUTE_INDEX}" id="destination_city_{ROUTE_INDEX}" required>

                    <div class="form-group">
                        <label for="destination_address_{ROUTE_INDEX}"><?php _e('Address', 'packlink-custom-shipping'); ?></label>
                        <input type="text" name="destination_address_{ROUTE_INDEX}" id="destination_address_{ROUTE_INDEX}" placeholder="<?php _e('Enter full address', 'packlink-custom-shipping'); ?>" required>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label for="collection_date_{ROUTE_INDEX}"><?php _e('Collection Date', 'packlink-custom-shipping'); ?></label>
                    <input type="date" name="collection_date_{ROUTE_INDEX}" id="collection_date_{ROUTE_INDEX}" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                </div>
                <input type="hidden" name="collection_time_{ROUTE_INDEX}" id="collection_time_{ROUTE_INDEX}" value="12:00">
            </div>
        </div>
    </template>

    <!-- Template for route contact information -->
    <!-- Route contact template for JavaScript to clone -->
    <template id="route-contact-template">
        <div class="route-contact-set" data-route-index="{ROUTE_INDEX}">
            <div class="route-contact-header">
                <h4><?php _e('Contact Information for Route', 'packlink-custom-shipping'); ?> #{ROUTE_NUMBER}</h4>
            </div>

            <div class="form-grid">
                <div class="form-column">
                    <h5><?php _e('Sender Information', 'packlink-custom-shipping'); ?></h5>
                    <div class="form-group">
                        <label for="sender_name_{ROUTE_INDEX}"><?php _e('Full Name', 'packlink-custom-shipping'); ?></label>
                        <input type="text" name="sender_name_{ROUTE_INDEX}" id="sender_name_{ROUTE_INDEX}"
                            placeholder="<?php _e('Enter full name', 'packlink-custom-shipping'); ?>"
                            data-default="<?php echo esc_attr(get_option('packlink_sender_name', '')); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="sender_email_{ROUTE_INDEX}"><?php _e('Email', 'packlink-custom-shipping'); ?></label>
                        <input type="email" name="sender_email_{ROUTE_INDEX}" id="sender_email_{ROUTE_INDEX}"
                            placeholder="<?php _e('Enter email address', 'packlink-custom-shipping'); ?>"
                            data-default="<?php echo esc_attr(get_option('packlink_sender_email', '')); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="sender_phone_{ROUTE_INDEX}"><?php _e('Phone Number', 'packlink-custom-shipping'); ?></label>
                        <input type="tel" name="sender_phone_{ROUTE_INDEX}" id="sender_phone_{ROUTE_INDEX}"
                            placeholder="<?php _e('Enter phone number', 'packlink-custom-shipping'); ?>"
                            data-default="<?php echo esc_attr(get_option('packlink_sender_phone', '')); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="sender_company_{ROUTE_INDEX}"><?php _e('Company (Optional)', 'packlink-custom-shipping'); ?></label>
                        <input type="text" name="sender_company_{ROUTE_INDEX}" id="sender_company_{ROUTE_INDEX}"
                            placeholder="<?php _e('Enter company name', 'packlink-custom-shipping'); ?>"
                            data-default="<?php echo esc_attr(get_option('packlink_sender_company', '')); ?>">
                    </div>
                </div>

                <div class="form-column">
                    <h5><?php _e('Recipient Information', 'packlink-custom-shipping'); ?></h5>
                    <div class="form-group">
                        <label for="recipient_name_{ROUTE_INDEX}"><?php _e('Full Name', 'packlink-custom-shipping'); ?></label>
                        <input type="text" name="recipient_name_{ROUTE_INDEX}" id="recipient_name_{ROUTE_INDEX}"
                            placeholder="<?php _e('Enter full name', 'packlink-custom-shipping'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="recipient_email_{ROUTE_INDEX}"><?php _e('Email', 'packlink-custom-shipping'); ?></label>
                        <input type="email" name="recipient_email_{ROUTE_INDEX}" id="recipient_email_{ROUTE_INDEX}"
                            placeholder="<?php _e('Enter email address', 'packlink-custom-shipping'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="recipient_phone_{ROUTE_INDEX}"><?php _e('Phone Number', 'packlink-custom-shipping'); ?></label>
                        <input type="tel" name="recipient_phone_{ROUTE_INDEX}" id="recipient_phone_{ROUTE_INDEX}"
                            placeholder="<?php _e('Enter phone number', 'packlink-custom-shipping'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="recipient_company_{ROUTE_INDEX}"><?php _e('Company (Optional)', 'packlink-custom-shipping'); ?></label>
                        <input type="text" name="recipient_company_{ROUTE_INDEX}" id="recipient_company_{ROUTE_INDEX}"
                            placeholder="<?php _e('Enter company name', 'packlink-custom-shipping'); ?>">
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- Drop-off location picker container -->
    <div id="pl-drop-off-picker-container" class="modal">
        <div class="modal-content pl-drop-off-picker">
            <div class="pl-drop-off-picker-header">
                <h3><?php _e('Select Drop-Off Location', 'packlink-custom-shipping'); ?></h3>
                <button type="button" class="pl-close-picker">×</button>
            </div>
            <div class="pl-drop-off-picker-content">
                <div id="pl-drop-off-locations-container"></div>
            </div>
        </div>
    </div>

    <!-- Success modal -->
    <div id="success-modal" class="modal">
        <div class="modal-content">
            <div class="modal-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-circle">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>
            <h3><?php _e('Shipment Request Submitted!', 'packlink-custom-shipping'); ?></h3>

            <p><?php _e('Your request has been successfully submitted. You will be redirected to checkout.', 'packlink-custom-shipping'); ?></p>
            <button type="button" class="btn-close-modal"><?php _e('Close', 'packlink-custom-shipping'); ?></button>
        </div>
    </div>
</div>