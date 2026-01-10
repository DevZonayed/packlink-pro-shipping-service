<?php

/**
 * Simple Quote Form Template
 * 
 * Displays a simple form with origin and destination country selection
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get the redirect URL - if not provided, try to find a page with the main shortcode
$redirect_url = $atts['redirect_url'];
if (empty($redirect_url)) {
    // Try to find a page with the main shortcode
    $pages = get_posts(array(
        'post_type' => 'page',
        'post_status' => 'publish',
        'meta_query' => array(),
        's' => '[packlink_shipping_form]',
        'posts_per_page' => 1
    ));

    if (!empty($pages)) {
        $redirect_url = get_permalink($pages[0]->ID);
    } else {
        $redirect_url = home_url();
    }
}
?>

<div class="packlink-quote-form-container">
    <form id="packlink-quote-form" class="packlink-quote-form" method="post">
        <div class="quote-form-header">
            <h2 class="quote-form-title">
                <?php echo esc_html($atts['title']); ?>
            </h2>
            <div class="quote-form-fields">
                <span class="country-inputs">
                    <span class="country-input-wrapper">
                        <div class="country-search-wrapper">
                            <input type="text" class="country-search" id="quote-origin-country-search"
                                placeholder="<?php _e('Search origin country', 'packlink-custom-shipping'); ?>"
                                autocomplete="off">
                            <input type="hidden" name="origin_country" id="quote-origin-country" required>
                            <div class="country-options" id="quote-origin-country-options"></div>
                        </div>
                    </span>

                    <span class="to-text">to</span>

                    <span class="country-input-wrapper">
                        <div class="country-search-wrapper">
                            <input type="text" class="country-search" id="quote-destination-country-search"
                                placeholder="<?php _e('Search destination country', 'packlink-custom-shipping'); ?>"
                                autocomplete="off">
                            <input type="hidden" name="destination_country" id="quote-destination-country" required>
                            <div class="country-options" id="quote-destination-country-options"></div>
                        </div>
                    </span>
                </span>
            </div>

            <div class="quote-form-actions">
                <button type="submit" class="get-quote-btn"
                    style="background-color: <?php echo esc_attr($atts['button_color']); ?>">
                    <?php echo esc_html($atts['button_text']); ?> →
                </button>
            </div>

            <input type="hidden" name="redirect_url" value="<?php echo esc_url($redirect_url); ?>">
            <input type="hidden" name="action" value="packlink_quote_redirect">
            <input type="hidden" id="packlink-ajax-nonce" value="<?php echo wp_create_nonce('packlink-ajax-nonce'); ?>">
            <?php wp_nonce_field('packlink_quote_nonce', 'quote_nonce'); ?>
    </form>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Initialize the quote form with the same functionality as main form
    initializeQuoteFormCountrySearch();
});

function initializeQuoteFormCountrySearch() {
    // Load destinations for country search (same as main form)
    jQuery.ajax({
        url: packlink_custom.ajax_url,
        type: 'GET',
        data: {
            action: 'packlink_get_destinations',
            security: packlink_custom.ajax_nonce
        },
        success: function(response) {
            if (response.success && Array.isArray(response.data)) {
                var destinations = response.data;

                // Setup country search for origin (using same function as main form)
                setupQuoteCountrySearch(
                    jQuery('#quote-origin-country-search'),
                    jQuery('#quote-origin-country'),
                    jQuery('#quote-origin-country-options'),
                    destinations,
                    'quote-origin',
                    'origin'
                );

                // Setup country search for destination (using same function as main form)
                setupQuoteCountrySearch(
                    jQuery('#quote-destination-country-search'),
                    jQuery('#quote-destination-country'),
                    jQuery('#quote-destination-country-options'),
                    destinations,
                    'quote-destination',
                    'destination'
                );
            }
        },
        error: function() {
            console.error('Failed to load destinations for quote form');
        }
    });
}

// Use the exact same country search logic as the main form
function setupQuoteCountrySearch(searchInput, hiddenInput, optionsContainer, destinations, routeIndex, type) {
    var debounceTimer;

    // Search input handler
    searchInput.on("input", function() {
        var query = jQuery(this).val().toLowerCase();
        clearTimeout(debounceTimer);

        if (query.length < 1) {
            optionsContainer.removeClass("active");
            return;
        }

        debounceTimer = setTimeout(function() {
            var filtered = destinations.filter(function(d) {
                return d.name.toLowerCase().includes(query) ||
                    d.isoCode.toLowerCase().includes(query);
            });

            renderQuoteCountryOptions(filtered, optionsContainer, searchInput, hiddenInput, routeIndex,
                type);
            optionsContainer.addClass("active");
        }, 300);
    });

    // Show options on focus
    searchInput.on("focus", function() {
        var query = jQuery(this).val().toLowerCase();
        var filtered = destinations.filter(function(d) {
            return query === '' || d.name.toLowerCase().includes(query) ||
                d.isoCode.toLowerCase().includes(query);
        });
        renderQuoteCountryOptions(filtered, optionsContainer, searchInput, hiddenInput, routeIndex, type);
        optionsContainer.addClass("active");
    });

    // Handle click outside
    jQuery(document).on("click", function(e) {
        if (!jQuery(e.target).closest(".country-search-wrapper").length) {
            optionsContainer.removeClass("active");
        }
    });

    // Handle keyboard navigation
    searchInput.on('keydown', function(e) {
        var options = optionsContainer.find('.country-option');
        var selected = optionsContainer.find('.country-option.selected');
        var currentIndex = selected.length ? selected.index() : -1;

        switch (e.keyCode) {
            case 38: // Up arrow
                e.preventDefault();
                if (currentIndex > 0) {
                    options.removeClass('selected');
                    options.eq(currentIndex - 1).addClass('selected');
                }
                break;
            case 40: // Down arrow
                e.preventDefault();
                if (currentIndex < options.length - 1) {
                    options.removeClass('selected');
                    options.eq(currentIndex + 1).addClass('selected');
                }
                break;
            case 13: // Enter
                e.preventDefault();
                if (selected.length) {
                    selected.trigger('click');
                }
                break;
            case 27: // Escape
                optionsContainer.removeClass("active");
                break;
        }
    });
}

// Use the exact same country options rendering as the main form
function renderQuoteCountryOptions(countries, container, searchInput, hiddenInput, routeIndex, type) {
    container.empty();

    if (countries.length === 0) {
        container.html('<div class="country-option no-results">No countries found</div>').addClass("active");
        return;
    }

    // Sort countries alphabetically
    countries.sort(function(a, b) {
        if (a.name < b.name) return -1;
        if (a.name > b.name) return 1;
        return 0;
    });

    countries.slice(0, 10).forEach(function(country) {
        var option = jQuery('<div>')
            .addClass('country-option')
            .text(country.name + ' (' + country.isoCode + ')')
            .on('click', function() {
                selectQuoteCountry(country, searchInput, hiddenInput, routeIndex, type);
                container.removeClass("active");
            });

        container.append(option);
    });
}

// Use the exact same country selection logic as the main form
function selectQuoteCountry(country, searchInput, hiddenInput, routeIndex, type) {
    searchInput.val(country.name + ' (' + country.isoCode + ')');
    hiddenInput.val(country.id).attr('data-iso', country.isoCode);

    // Add visual feedback
    searchInput.removeClass('error').addClass('valid');

    // Remove any error messages for this field
    var errorMessage = searchInput.closest('.country-search-wrapper').find('.error-message');
    if (errorMessage.length) {
        errorMessage.remove();
    }

    // Trigger change event
    hiddenInput.trigger('change');

    // Auto-clear the other field's error if both are now filled
    setTimeout(function() {
        var originFilled = jQuery('#quote-origin-country').val();
        var destinationFilled = jQuery('#quote-destination-country').val();

        if (originFilled && destinationFilled) {
            // Clear all error messages
            jQuery('.packlink-quote-form .error-message').remove();
            jQuery('.packlink-quote-form .country-search').removeClass('error');
        }
    }, 100);
}

// Immediate validation clearing when countries are selected
jQuery(document).ready(function($) {
    // Clear validation errors immediately when typing
    $('#quote-origin-country-search, #quote-destination-country-search').on('input keyup', function() {
        var $this = $(this);
        $this.removeClass('error');
        $this.closest('.country-search-wrapper').find('.error-message').remove();
    });

    // Clear validation errors when hidden inputs change (country selected)
    $('#quote-origin-country, #quote-destination-country').on('change', function() {
        var $searchInput = $(this).siblings('.country-search');
        $searchInput.removeClass('error');
        $searchInput.closest('.country-search-wrapper').find('.error-message').remove();

        // If both fields are filled, clear all errors
        var originFilled = $('#quote-origin-country').val();
        var destinationFilled = $('#quote-destination-country').val();

        if (originFilled && destinationFilled) {
            $('.packlink-quote-form .error-message').remove();
            $('.packlink-quote-form .country-search').removeClass('error');
        }
    });
});
</script>