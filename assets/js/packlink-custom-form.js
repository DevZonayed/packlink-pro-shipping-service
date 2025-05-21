jQuery(document).ready(function ($) {
    // Store selected drop-off points for each route
    let selectedDropOffPoints = {};

    // Store selected shipping options for each route
    let selectedShippingOptions = {};

    // Route counter
    let routeCounter = 0;

    // Package counter
    let packageCounter = 0;

    // Initialize form with default values
    function initializeForm() {
        // Add route button click handler
        $("#add-route").on("click", function () {
            addRoute();
        });

        // Add first route
        addRoute();

        // Initialize packages
        initializePackages();

        // Setup form navigation
        setupFormNavigation();

        // Setup form submission
        setupFormSubmission();
    }

    // Initialize form
    initializeForm();


    // Handle the Contact Information section
    function initializeContactsSection() {
        // Clear existing contact forms
        $('#route-contacts-container').empty();

        // Create contact form for each route
        const routeCount = $('.route-set').length;
        for (let i = 0; i < routeCount; i++) {
            addRouteContactForm(i);
        }

        // Apply connected routes logic if enabled
        if ($('#connect-locations').is(':checked')) {
            connectRouteContacts();
        }

        // When connect-locations is toggled, update the contact forms
        $('#connect-locations').on('change', function () {
            if ($(this).is(':checked')) {
                connectRouteContacts();
            }
        });
    }

    // Add contact form for a specific route
    function addRouteContactForm(routeIndex) {
        const template = $('#route-contact-template').html();
        const routeNumber = routeIndex + 1;

        // Replace placeholders
        const html = template
            .replace(/{ROUTE_INDEX}/g, routeIndex)
            .replace(/{ROUTE_NUMBER}/g, routeNumber);

        $('#route-contacts-container').append(html);

        // Prefill with default values if available
        prefillContactForm(routeIndex);
    }

    // Prefill contact form with default values or user data
    function prefillContactForm(routeIndex) {
        // Default sender values
        if (routeIndex === 0) {
            // For first route, use user profile or default values
            const defaultSenderName = $('#sender_name_0').data('default') || '';
            const defaultSenderEmail = $('#sender_email_0').data('default') || '';
            const defaultSenderPhone = $('#sender_phone_0').data('default') || '';
            const defaultSenderCompany = $('#sender_company_0').data('default') || '';

            $('#sender_name_0').val(defaultSenderName);
            $('#sender_email_0').val(defaultSenderEmail);
            $('#sender_phone_0').val(defaultSenderPhone);
            $('#sender_company_0').val(defaultSenderCompany);
        }
    }

    // Connect route contacts when the checkbox is enabled
    function connectRouteContacts() {
        const routeCount = $('.route-contact-set').length;
        const isConnected = $('#connect-contacts').is(':checked');

        // Skip if there's only one route
        if (routeCount <= 1) return;

        // Remove previous event listeners by cloning and replacing elements
        for (let i = 1; i < routeCount; i++) {
            const prevIndex = i - 1;

            // Remove all event handlers from the previous recipient fields
            $(`#recipient_name_${prevIndex}`).off('change');
            $(`#recipient_email_${prevIndex}`).off('change');
            $(`#recipient_phone_${prevIndex}`).off('change');
            $(`#recipient_company_${prevIndex}`).off('change');
        }

        // For routes after the first one, copy recipient from previous route to sender
        if (isConnected) {
            for (let i = 1; i < routeCount; i++) {
                const prevIndex = i - 1;

                // Create change event handlers to sync data
                $(`#recipient_name_${prevIndex}`).on('change', function () {
                    $(`#sender_name_${i}`).val($(this).val());
                });

                $(`#recipient_email_${prevIndex}`).on('change', function () {
                    $(`#sender_email_${i}`).val($(this).val());
                });

                $(`#recipient_phone_${prevIndex}`).on('change', function () {
                    $(`#sender_phone_${i}`).val($(this).val());
                });

                $(`#recipient_company_${prevIndex}`).on('change', function () {
                    $(`#sender_company_${i}`).val($(this).val());
                });

                // Also copy current values
                $(`#sender_name_${i}`).val($(`#recipient_name_${prevIndex}`).val());
                $(`#sender_email_${i}`).val($(`#recipient_email_${prevIndex}`).val());
                $(`#sender_phone_${i}`).val($(`#recipient_phone_${prevIndex}`).val());
                $(`#sender_company_${i}`).val($(`#recipient_company_${prevIndex}`).val());
            }
        } else {
            // If connection is disabled, reset sender info for routes after the first one
            for (let i = 1; i < routeCount; i++) {
                // Reset to default data values if they exist
                const defaultName = $(`#sender_name_${i}`).data('default') || '';
                const defaultEmail = $(`#sender_email_${i}`).data('default') || '';
                const defaultPhone = $(`#sender_phone_${i}`).data('default') || '';
                const defaultCompany = $(`#sender_company_${i}`).data('default') || '';

                $(`#sender_name_${i}`).val(defaultName);
                $(`#sender_email_${i}`).val(defaultEmail);
                $(`#sender_phone_${i}`).val(defaultPhone);
                $(`#sender_company_${i}`).val(defaultCompany);
            }
        }
    }

    // Add change handler for the connect-contacts switch
    $('#connect-contacts').on('change', function () {
        connectRouteContacts();
    });

    // When routes are added or removed, update contact connections if enabled
    $('#add-route, .btn-remove-route').on('click', function () {
        setTimeout(function () {
            // Only update if we're on the details section
            if ($('#details-section').is(':visible')) {
                connectRouteContacts();
            }
        }, 100);
    });


    // Update the section navigation to initialize contacts when reaching that section
    $('.btn-next').on('click', function () {
        const nextSection = $(this).data('next');

        if (nextSection === 'details-section') {
            initializeContactsSection();
        }
    });

    // When adding or removing routes, update the contact section
    $('#add-route').on('click', function () {
        // Wait for the new route to be added
        setTimeout(function () {
            if ($('#details-section').is(':visible')) {
                initializeContactsSection();
            }
        }, 100);
    });


    $(document).on('click', '.btn-remove-route', function () {
        // Wait for the route to be removed
        setTimeout(function () {
            if ($('#details-section').is(':visible')) {
                initializeContactsSection();
            }
        }, 100);
    });

    // Add a new route
    function addRoute() {
        const routeIndex = routeCounter++;
        const routeNumber = routeIndex + 1;

        // Get the route template
        let routeHtml = $("#route-template").html();

        // Replace placeholders
        routeHtml = routeHtml.replace(/{ROUTE_INDEX}/g, routeIndex);
        routeHtml = routeHtml.replace(/{ROUTE_NUMBER}/g, routeNumber);

        // Add to routes container
        $("#routes-container").append(routeHtml);

        // Initialize the new route
        initializeRoute(routeIndex);

        // If this is not the first route, show the remove button
        if (routeIndex > 0) {
            $(".route-set").first().find(".btn-remove-route").show();
        } else {
            $(".route-set").first().find(".btn-remove-route").hide();
        }

        // If "connect locations" is checked and this is not the first route,
        // copy the destination of the previous route to the origin of this route
        if (routeIndex > 0 && $("#connect-locations").is(":checked")) {
            const prevRouteIndex = routeIndex - 1;

            // Wait for countries to load
            setTimeout(function () {
                // Copy country
                const prevDestCountry = $(`#destination_country_${prevRouteIndex}`).val();
                const prevDestCountryIso = $(`#destination_country_${prevRouteIndex}`).data("iso");

                console.log({
                    prevDestCountry,
                    prevDestCountryIso,
                })
                if (prevDestCountry) {
                    $(`#origin_country_${routeIndex}`).val(prevDestCountry).trigger("change");
                    $(`#origin_country_${routeIndex}`).data("iso", prevDestCountryIso);
                }

                // Copy postal code and city
                const prevDestPostalVal = $(`#destination_postal_code_${prevRouteIndex}`).val();
                const prevDestPostcode = $(`#destination_postal_code_${prevRouteIndex}`).data("postcode");
                const prevDestCity = $(`#destination_city_${prevRouteIndex}`).val();

                if (prevDestPostalVal) {
                    $(`#origin_postal_code_${routeIndex}`).val(prevDestPostalVal);
                    $(`#origin_postal_code_${routeIndex}`).data("postcode", prevDestPostcode);
                }

                if (prevDestCity) {
                    $(`#origin_city_${routeIndex}`).val(prevDestCity);
                }

                // Copy address
                const prevDestAddress = $(`#destination_address_${prevRouteIndex}`).val();

                if (prevDestAddress) {
                    $(`#origin_address_${routeIndex}`).val(prevDestAddress);
                }

                // Update route info
                updateRouteInfo(routeIndex);
            }, 1000);
        }
    }

    // Initialize a route
    function initializeRoute(routeIndex) {
        // Load destinations
        loadDestinations(routeIndex);

        // Initialize postal code autocomplete
        initPostalCodeAutocomplete(routeIndex);

        // Initialize collection date
        initializeCollectionDate(routeIndex);

        // Setup remove route button
        $(`#routes-container .route-set[data-route-index="${routeIndex}"] .btn-remove-route`).on("click", function () {
            removeRoute($(this).closest(".route-set"));
        });
    }

    // Remove a route
    function removeRoute(routeElement) {
        const routeIndex = routeElement.data("route-index");

        // Remove from selected shipping options and drop-off points
        delete selectedShippingOptions[routeIndex];
        delete selectedDropOffPoints[routeIndex];

        // Remove the route element
        routeElement.remove();

        // Renumber routes
        $(".route-set").each(function (index) {
            $(this).find("h4").text(`${packlink_custom.route_text || 'Route'} #${index + 1}`);
        });

        // Hide the remove button on the first route if it's the only one
        if ($(".route-set").length === 1) {
            $(".route-set").first().find(".btn-remove-route").hide();
        }
    }

    // Initialize collection date for a route
    function initializeCollectionDate(routeIndex) {
        // Set minimum date to tomorrow
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);

        // Format date as YYYY-MM-DD
        const formattedDate = tomorrow.toISOString().split('T')[0];

        // Set default date to tomorrow
        $(`#collection_date_${routeIndex}`).val(formattedDate);

        // Add validation for collection date
        $(`#collection_date_${routeIndex}`).on("change", function () {
            const selectedDate = new Date($(this).val());
            const today = new Date();

            // Reset time to midnight for comparison
            today.setHours(0, 0, 0, 0);

            // Check if selected date is at least tomorrow
            if (selectedDate <= today) {
                alert(packlink_custom.collection_date_future_message || "Collection date must be at least one day in the future.");
                $(this).val(formattedDate);
            }
        });
    }

    // Load destinations for a route
    function loadDestinations(routeIndex) {
        $.ajax({
            url: packlink_custom.ajax_url,
            type: 'GET',
            data: {
                action: 'packlink_get_destinations',
                security: $('#packlink-ajax-nonce').val()
            },
            success: function (response) {
                if (response.success && Array.isArray(response.data)) {
                    populateDestinationDropdowns(response.data, routeIndex);
                }
            },
            error: function () {
                console.error('Failed to load destinations');
            }
        });
    }

    // Populate destination dropdowns for a route
    function populateDestinationDropdowns(destinations, routeIndex) {
        const originSelect = $(`#origin_country_${routeIndex}`);
        const destinationSelect = $(`#destination_country_${routeIndex}`);

        // Clear existing options except the placeholder
        originSelect.find('option:not(:first)').remove();
        destinationSelect.find('option:not(:first)').remove();

        // Sort destinations by name
        destinations.sort((a, b) => {
            if (a.name < b.name) return -1;
            if (a.name > b.name) return 1;
            return 0;
        });

        // Add destinations to dropdowns
        destinations.forEach(function (destination) {
            if (destination.id && destination.name) {
                const optionText = destination.name + ' (' + destination.isoCode + ')';

                // Add to origin dropdown
                originSelect.append(
                    $('<option></option>')
                        .attr('value', destination.id)
                        .attr('data-iso', destination.isoCode)
                        .text(optionText)
                );

                // Add to destination dropdown
                destinationSelect.append(
                    $('<option></option>')
                        .attr('value', destination.id)
                        .attr('data-iso', destination.isoCode)
                        .text(optionText)
                );
            }
        });

        // Set default country values if available
        if (packlink_custom.default_origin_country) {
            // Try to find by ISO code first
            let originOption = originSelect.find(`option[data-iso="${packlink_custom.default_origin_country}"]`);

            if (originOption.length) {
                originOption.prop('selected', true);
            } else {
                // Try by ID
                originSelect.val(packlink_custom.default_origin_country);
            }
        }

        if (packlink_custom.default_destination_country) {
            // Try to find by ISO code first
            let destOption = destinationSelect.find(`option[data-iso="${packlink_custom.default_destination_country}"]`);

            if (destOption.length) {
                destOption.prop('selected', true);
            } else {
                // Try by ID
                destinationSelect.val(packlink_custom.default_destination_country);
            }
        }

        // Set data-iso attribute and add change event
        originSelect.on("change", function () {
            const country = $(this).val();
            const isoCode = $(this).find("option:selected").data("iso");

            if (country && isoCode) {
                $(this).attr("data-iso", isoCode);

                // Update route information
                updateRouteInfo(routeIndex);
            }
        });

        destinationSelect.on("change", function () {
            const country = $(this).val();
            const isoCode = $(this).find("option:selected").data("iso");

            if (country && isoCode) {
                $(this).attr("data-iso", isoCode);

                // Update route information
                updateRouteInfo(routeIndex);
            }
        });

        // Set initial data-iso attribute
        if (originSelect.val()) {
            const isoCode = originSelect.find("option:selected").data("iso");
            originSelect.attr("data-iso", isoCode);
        }

        if (destinationSelect.val()) {
            const isoCode = destinationSelect.find("option:selected").data("iso");
            destinationSelect.attr("data-iso", isoCode);
        }
    }

    // Initialize postal code autocomplete for a route
    function initPostalCodeAutocomplete(routeIndex) {
        // Setup autocomplete for origin postal code
        setupPostalCodeAutocomplete(`#origin_postal_code_${routeIndex}`, `#origin_country_${routeIndex}`, `#origin_city_${routeIndex}`, routeIndex, 'origin');

        // Setup autocomplete for destination postal code
        setupPostalCodeAutocomplete(`#destination_postal_code_${routeIndex}`, `#destination_country_${routeIndex}`, `#destination_city_${routeIndex}`, routeIndex, 'destination');

        // Add change event to country selects to reset postal code field
        $(`#origin_country_${routeIndex}, #destination_country_${routeIndex}`).on("change", function () {
            const isOrigin = $(this).attr("id").includes("origin");
            const postalCodeId = isOrigin ? `#origin_postal_code_${routeIndex}` : `#destination_postal_code_${routeIndex}`;
            $(postalCodeId).val("");
        });
    }

    // Setup postal code autocomplete for a specific field
    function setupPostalCodeAutocomplete(postalCodeSelector, countrySelector, citySelector, routeIndex, type) {
        // Create a loading indicator element after the postal code field
        const $postalCodeField = $(postalCodeSelector);
        const loadingIndicatorId = postalCodeSelector.replace('#', '') + '_loading';

        // Add the loading indicator if it doesn't exist
        if ($('#' + loadingIndicatorId).length === 0) {
            $postalCodeField.after('<div id="' + loadingIndicatorId + '" class="packlink-loading-indicator" style="display:none;"><div class="packlink-loading-spinner"></div></div>');
        }

        $(postalCodeSelector).autocomplete({
            minLength: 2,
            source: function (request, response) {
                const country = $(countrySelector).val();

                if (!country) {
                    response([]);
                    return;
                }

                // Show loading indicator
                $('#' + loadingIndicatorId).show();

                $.ajax({
                    url: packlink_custom.ajax_url,
                    dataType: "json",
                    data: {
                        action: "packlink_suggest_postal_codes",
                        security: $('#packlink-ajax-nonce').val(),
                        term: request.term,
                        country: country
                    },
                    success: function (data) {
                        // Hide loading indicator
                        $('#' + loadingIndicatorId).hide();
                        response(data);
                    },
                    error: function () {
                        // Hide loading indicator
                        $('#' + loadingIndicatorId).hide();
                        response([]);
                    }
                });
            },
            select: function (event, ui) {
                $(postalCodeSelector).val(ui.item.label).attr('data-postcode', ui.item.value);
                $(citySelector).val(ui.item.city);

                // Update route information
                updateRouteInfo(routeIndex);

                return false;
            }
        }).autocomplete("instance")._renderItem = function (ul, item) {
            return $("<li>")
                .append("<div>" + item.label + "</div>")
                .appendTo(ul);
        };
    }

    // Update route information
    function updateRouteInfo(routeIndex) {
        const originCountry = $(`#origin_country_${routeIndex}`).val();
        const originPostal = $(`#origin_postal_code_${routeIndex}`).val();
        const destinationCountry = $(`#destination_country_${routeIndex}`).val();
        const destinationPostal = $(`#destination_postal_code_${routeIndex}`).val();

        if (!originCountry || !originPostal || !destinationCountry || !destinationPostal) {
            return;
        }

        // Determine if it's domestic or international
        const isSameCountry = $(`#origin_country_${routeIndex}`).data("iso") === $(`#destination_country_${routeIndex}`).data("iso");
        const transportType = isSameCountry ? 'Road Transport' : 'Air Transport';

        // Update transport type
        $(`#route-info-${routeIndex} .route-type .value`).text(transportType);

        // Update icon based on transport type
        const routeIcon = $(`#route-info-${routeIndex} .route-type .icon svg`);
        if (routeIcon.length) {
            if (!isSameCountry) {
                routeIcon.replaceWith(`<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-plane">
                    <path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"></path>
                </svg>`);
            } else {
                routeIcon.replaceWith(`<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-truck">
                    <path d="M5 18H3c-.6 0-1-.4-1-1V7c0-.6.4-1 1-1h10c.6 0 1 .4 1 1v11"></path>
                    <path d="M14 9h4l4 4v4c0 .6-.4 1-1 1h-2"></path>
                    <circle cx="7" cy="18" r="2"></circle>
                    <circle cx="17" cy="18" r="2"></circle>
                </svg>`);
            }
        }

        // Simulate distance calculation
        const minDistance = isSameCountry ? 50 : 500;
        const maxDistance = isSameCountry ? 500 : 5000;
        const distance = Math.floor(Math.random() * (maxDistance - minDistance + 1)) + minDistance;

        $(`#route-info-${routeIndex} .route-distance .value`).text(distance + " km");
    }

    // Initialize the first package
    function initializePackages() {
        packageCounter = 0;
        $("#packlink-packages-container").empty(); // Clear any existing packages
        addPackage(); // Add the first package
    }

    // Add a new package
    function addPackage() {
        const packageIndex = packageCounter++;
        const isFirstPackage = packageIndex === 0;

        const packageHtml = `
            <div class="package" data-package-index="${packageIndex}">
                <div class="package-header">
                    <h4>${packlink_custom.package_text || 'Package'} ${packageIndex + 1}</h4>
                    <button type="button" class="btn-remove-package" ${isFirstPackage ? 'style="display: none;"' : ''}>
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
                    <div class="form-group">
                        <label for="weight_${packageIndex}">${packlink_custom.weight_text || 'Weight (kg)'}</label>
                        <input type="number" name="weight[]" id="weight_${packageIndex}" step="0.01" min="0.01" placeholder="Enter weight" required>
                    </div>
                    
                    <div class="dimensions-group">
                        <h5>${packlink_custom.dimensions_text || 'Dimensions (cm)'}</h5>
                        <div class="dimensions-inputs">
                            <div class="form-group">
                                <label for="length_${packageIndex}">${packlink_custom.length_text || 'Length'}</label>
                                <input type="number" name="length[]" id="length_${packageIndex}" min="1" placeholder="Length" required>
                            </div>
                            <div class="form-group">
                                <label for="width_${packageIndex}">${packlink_custom.width_text || 'Width'}</label>
                                <input type="number" name="width[]" id="width_${packageIndex}" min="1" placeholder="Width" required>
                            </div>
                            <div class="form-group">
                                <label for="height_${packageIndex}">${packlink_custom.height_text || 'Height'}</label>
                                <input type="number" name="height[]" id="height_${packageIndex}" min="1" placeholder="Height" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $("#packlink-packages-container").append(packageHtml);

        // Set default values if available
        if (packlink_custom.default_package_weight) {
            $("#weight_" + packageIndex).val(packlink_custom.default_package_weight);
        }

        if (packlink_custom.default_package_length) {
            $("#length_" + packageIndex).val(packlink_custom.default_package_length);
        }

        if (packlink_custom.default_package_width) {
            $("#width_" + packageIndex).val(packlink_custom.default_package_width);
        }

        if (packlink_custom.default_package_height) {
            $("#height_" + packageIndex).val(packlink_custom.default_package_height);
        }

        // Show the first package's remove button if we have more than one package
        if (packageCounter > 1) {
            $(".package").first().find(".btn-remove-package").show();
        }
    }

    // Remove a package
    function removePackage(packageElement) {
        packageElement.remove();

        // Renumber packages
        $(".package").each(function (index) {
            $(this).find("h4").text((packlink_custom.package_text || 'Package') + " " + (index + 1));
        });

        // Hide the remove button on the first package if it's the only one
        if ($(".package").length === 1) {
            $(".package").first().find(".btn-remove-package").hide();
        }
    }

    // Add package button click handler
    $("#add-package").on("click", function () {
        addPackage();
    });

    // Remove package button click handler (using event delegation)
    $("#packlink-packages-container").on("click", ".btn-remove-package", function () {
        removePackage($(this).closest(".package"));
    });

    // Connect locations checkbox handler
    $("#connect-locations").on("change", function () {
        // If checked, update all routes except the first one
        if ($(this).is(":checked")) {
            $(".route-set").each(function (index) {
                if (index > 0) {
                    const routeIndex = $(this).data("route-index");
                    const prevRouteIndex = $(".route-set").eq(index - 1).data("route-index");

                    // Copy destination of previous route to origin of this route
                    const prevDestCountry = $(`#destination_country_${prevRouteIndex}`).val();
                    const prevDestCountryIso = $(`#destination_country_${prevRouteIndex}`).data("iso");

                    if (prevDestCountry) {
                        $(`#origin_country_${routeIndex}`).val(prevDestCountry).trigger("change");
                        $(`#origin_country_${routeIndex}`).data("iso", prevDestCountryIso);
                    }

                    // Copy postal code and city
                    const prevDestPostal = $(`#destination_postal_code_${prevRouteIndex}`).val();
                    const prevDestCity = $(`#destination_city_${prevRouteIndex}`).val();

                    if (prevDestPostal) {
                        $(`#origin_postal_code_${routeIndex}`).val(prevDestPostal);
                    }

                    if (prevDestCity) {
                        $(`#origin_city_${routeIndex}`).val(prevDestCity);
                    }

                    // Copy address
                    const prevDestAddress = $(`#destination_address_${prevRouteIndex}`).val();

                    if (prevDestAddress) {
                        $(`#origin_address_${routeIndex}`).val(prevDestAddress);
                    }

                    // Update route info
                    updateRouteInfo(routeIndex);
                }
            });
        }
    });

    // Setup form navigation
    function setupFormNavigation() {
        // Next button click handler
        $(".btn-next").on("click", function () {
            const nextSectionId = $(this).data("next");
            const currentSection = $(this).closest(".form-section");

            // Special handling for shipping section
            if (nextSectionId === "shipping-section") {
                // Validate current section
                if (validateSection(currentSection)) {
                    // Hide current section
                    currentSection.removeClass("active");

                    // Show next section
                    $("#" + nextSectionId).addClass("active");

                    // Update progress tracker
                    updateProgressTracker(nextSectionId);

                    // Load shipping options for all routes
                    loadAllShippingOptions();

                    // Scroll to top
                    $('html, body').animate({
                        scrollTop: $(".packlink-custom-shipping-container").offset().top - 50
                    }, 500);
                }
                return;
            }

            // Validate current section
            if (validateSection(currentSection)) {
                // Hide current section
                currentSection.removeClass("active");

                // Show next section
                $("#" + nextSectionId).addClass("active");

                // Update progress tracker
                updateProgressTracker(nextSectionId);

                // If moving to review section, update summary
                if (nextSectionId === "review-section") {
                    updateReviewSummary();
                }

                // Scroll to top
                $('html, body').animate({
                    scrollTop: $(".packlink-custom-shipping-container").offset().top - 50
                }, 500);
            }
        });

        // Previous button click handler
        $(".btn-prev").on("click", function () {
            const prevSectionId = $(this).data("prev");
            const currentSection = $(this).closest(".form-section");

            // Hide current section
            currentSection.removeClass("active");

            // Show previous section
            $("#" + prevSectionId).addClass("active");

            // Update progress tracker
            updateProgressTracker(prevSectionId);

            // Scroll to top
            $('html, body').animate({
                scrollTop: $(".packlink-custom-shipping-container").offset().top - 50
            }, 500);
        });

        // Progress tracker click handler
        $(".progress-step").on("click", function () {
            const stepNumber = $(this).data("step");

            // Only allow clicking on completed steps or the current step
            if ($(this).hasClass("completed") || $(this).hasClass("active")) {
                const sectionMap = {
                    1: "route-section",
                    2: "packages-section",
                    3: "shipping-section",
                    4: "details-section",
                    5: "review-section"
                };

                const targetSectionId = sectionMap[stepNumber];

                // Hide all sections
                $(".form-section").removeClass("active");

                // Show target section
                $("#" + targetSectionId).addClass("active");

                // Update progress tracker
                updateProgressTracker(targetSectionId);
            }
        });
    }

    // Update progress tracker based on current section
    function updateProgressTracker(sectionId) {
        const stepMap = {
            "route-section": 1,
            "packages-section": 2,
            "shipping-section": 3,
            "details-section": 4,
            "review-section": 5
        };

        const currentStep = stepMap[sectionId];

        // Update progress steps
        $(".progress-step").each(function () {
            const stepNumber = $(this).data("step");

            if (stepNumber < currentStep) {
                $(this).addClass("completed").removeClass("active");
            } else if (stepNumber === currentStep) {
                $(this).addClass("active").removeClass("completed");
            } else {
                $(this).removeClass("active completed");
            }
        });
    }

    // Validate a form section
    function validateSection(section) {
        let isValid = true;

        // Check all required fields in this section
        section.find("input[required], select[required]").each(function () {
            if (!$(this).val()) {
                isValid = false;

                // Highlight the field
                $(this).addClass("error");

                // Add error message if it doesn't exist
                if (!$(this).next(".error-message").length) {
                    $(this).after('<div class="error-message">' + packlink_custom.please_fill_required_fields + '</div>');
                }
            } else {
                // Remove error highlighting
                $(this).removeClass("error");

                // Remove error message
                $(this).next(".error-message").remove();
            }
        });

        // Check number inputs for min values
        section.find("input[type='number']").each(function () {
            const min = parseFloat($(this).attr("min"));
            const val = parseFloat($(this).val());

            if (!isNaN(min) && !isNaN(val) && val < min) {
                isValid = false;

                // Highlight the field
                $(this).addClass("error");

                // Add error message if it doesn't exist
                if (!$(this).next(".error-message").length) {
                    $(this).after('<div class="error-message">' + packlink_custom.value_must_be_at_least + ' ' + min + '</div>');
                }
            }
        });

        return isValid;
    }

    // Load shipping options for all routes
    function loadAllShippingOptions() {
        // Clear shipping options container
        $("#shipping-options-container").empty();

        // Create a container for each route's shipping options
        $(".route-set").each(function () {
            const routeIndex = $(this).data("route-index");
            const routeNumber = $(".route-set").index(this) + 1;

            // Add a container for this route's shipping options
            $("#shipping-options-container").append(`
                <div class="route-shipping-options" data-route-index="${routeIndex}">
                    <h4>${packlink_custom.route_text || 'Route'} #${routeNumber} ${packlink_custom.shipping_options_text || 'Shipping Options'}</h4>
                    <div id="shipping-options-list-${routeIndex}">
                        <div class="packlink-loading">
                            <div class="packlink-loading-spinner"></div>
                            <p>${packlink_custom.loading_shipping_options || 'Loading shipping options...'}</p>
                        </div>
                    </div>
                </div>
            `);

            // Load shipping options for this route
            loadShippingOptions(routeIndex);
        });

        // Enable continue button if all routes have shipping options selected
        checkAllRoutesHaveShipping();
    }

    // Check if all routes have shipping options selected
    function checkAllRoutesHaveShipping() {
        let allRoutesHaveShipping = true;
        let allDropOffPointsSelected = true;

        $(".route-set").each(function () {
            const routeIndex = $(this).data("route-index");

            // Check if shipping option is selected
            if (!selectedShippingOptions[routeIndex]) {
                allRoutesHaveShipping = false;
            }

            // Check if drop-off point is selected for drop-off methods
            if (selectedShippingOptions[routeIndex] &&
                selectedShippingOptions[routeIndex].isDropOff &&
                !selectedDropOffPoints[routeIndex]) {
                allDropOffPointsSelected = false;
            }
        });

        // Enable/disable continue button
        $("#shipping-continue-btn").prop("disabled", !(allRoutesHaveShipping && allDropOffPointsSelected));
    }

    // Load shipping options for a specific route
    // Load shipping options for a route
    function loadShippingOptions(routeIndex) {
        // Show loading message
        $(`#shipping-options-route-${routeIndex}`).html(`
        <div class="packlink-loading">
            <div class="packlink-loading-spinner"></div>
            <p>${packlink_custom.loading_shipping_options || 'Loading shipping options...'}</p>
        </div>
    `);

        // Get route data
        const originCountry = $(`#origin_country_${routeIndex}`).data('iso');
        const originPostalCode = $(`#origin_postal_code_${routeIndex}`).data("postcode");
        const originCity = $(`#origin_city_${routeIndex}`).val();
        const originAddress = $(`#origin_address_${routeIndex}`).val();

        const destinationCountry = $(`#destination_country_${routeIndex}`).data('iso');
        const destinationPostalCode = $(`#destination_postal_code_${routeIndex}`).data("postcode");
        const destinationCity = $(`#destination_city_${routeIndex}`).val();
        const destinationAddress = $(`#destination_address_${routeIndex}`).val();

        const collectionDate = $(`#collection_date_${routeIndex}`).val();
        const collectionTime = $(`#collection_time_${routeIndex}`).val() || '12:00';

        // Get packages data
        let packages = [];
        $(".package").each(function () {
            const packageIndex = $(this).data("package-index");

            packages.push({
                weight: $("#weight_" + packageIndex).val(),
                length: $("#length_" + packageIndex).val(),
                width: $("#width_" + packageIndex).val(),
                height: $("#height_" + packageIndex).val()
            });
        });

        // Get sender and recipient data from the associated contact form if available
        let senderName = '';
        let senderEmail = '';
        let senderPhone = '';
        let senderCompany = '';
        let recipientName = '';
        let recipientEmail = '';
        let recipientPhone = '';
        let recipientCompany = '';

        // If the contact form for this route exists, use those values
        if ($(`#sender_name_${routeIndex}`).length) {
            senderName = $(`#sender_name_${routeIndex}`).val();
            senderEmail = $(`#sender_email_${routeIndex}`).val();
            senderPhone = $(`#sender_phone_${routeIndex}`).val();
            senderCompany = $(`#sender_company_${routeIndex}`).val();
            recipientName = $(`#recipient_name_${routeIndex}`).val();
            recipientEmail = $(`#recipient_email_${routeIndex}`).val();
            recipientPhone = $(`#recipient_phone_${routeIndex}`).val();
            recipientCompany = $(`#recipient_company_${routeIndex}`).val();
        }

        // Make AJAX request
        $.ajax({
            url: packlink_custom.ajax_url,
            type: "POST",
            data: {
                action: "get_packlink_shipping_rates",
                nonce: $("#packlink_nonce").val(),
                origin_country: originCountry,
                origin_postal_code: originPostalCode,
                origin_city: originCity,
                origin_address: originAddress,
                destination_country: destinationCountry,
                destination_postal_code: destinationPostalCode,
                destination_city: destinationCity,
                destination_address: destinationAddress,
                sender_name: senderName,
                sender_email: senderEmail,
                sender_phone: senderPhone,
                sender_company: senderCompany,
                recipient_name: recipientName,
                recipient_email: recipientEmail,
                recipient_phone: recipientPhone,
                recipient_company: recipientCompany,
                collection_date: collectionDate,
                collection_time: collectionTime,
                packages: JSON.stringify(packages)
            },
            success: function (response) {
                if (response.success) {
                    displayShippingOptions(routeIndex, response.data);
                } else {
                    $(`#shipping-options-route-${routeIndex}`).html(`
                    <div class="pl-error-message">
                        <p>${response.data.message || packlink_custom.no_shipping_options || 'No shipping options available for this route'}</p>
                    </div>
                `);
                }
            },
            error: function () {
                $(`#shipping-options-route-${routeIndex}`).html(`
                <div class="pl-error-message">
                    <p>${packlink_custom.error_connecting_server || 'Error connecting to server'}</p>
                </div>
            `);
            }
        });
    }


    // Show error message for a specific route
    function showError(message, routeIndex) {
        $(`#shipping-options-list-${routeIndex}`).html(
            '<div class="error-message">' + message + "</div>"
        );

        // Check if all routes have shipping options
        checkAllRoutesHaveShipping();
    }

    // Display shipping options for a specific route
    function displayShippingOptions(routeIndex, options) {
        if (options.length === 0) {
            showError(packlink_custom.no_shipping_options, routeIndex);
            return;
        }

        let html = '<ul class="shipping-options">';

        options.forEach(function (option) {
            // Format delivery time and first delivery date
            let deliveryInfo = "";
            if (option.deliveryTime) {
                deliveryInfo += packlink_custom.delivery_time + ": " + option.deliveryTime;
            }
            if (option.firstDeliveryDate) {
                const formattedDate = formatDate(option.firstDeliveryDate);
                if (deliveryInfo) deliveryInfo += " | ";
                deliveryInfo += packlink_custom.estimated_delivery + ": " + formattedDate;
            }

            // Format price
            const formattedPrice = formatPrice(option.price, option.currency);

            // Add drop-off indicator
            const dropOffIndicator = option.isDropOff
                ? '<span class="drop-off-indicator">' + packlink_custom.drop_off_point_required + '</span>'
                : '';

            // Check if this option is selected
            const isSelected = selectedShippingOptions[routeIndex] &&
                selectedShippingOptions[routeIndex].id === option.id;

            // Add selected class if this option is selected
            const selectedClass = isSelected ? 'selected' : '';

            // Update drop-off indicator if a drop-off point is selected
            let dropOffIndicatorHtml = dropOffIndicator;
            if (isSelected && option.isDropOff && selectedDropOffPoints[routeIndex]) {
                dropOffIndicatorHtml = '<span class="drop-off-indicator drop-off-selected">' +
                    (packlink_custom.drop_off_point_selected || 'Drop-off point selected') +
                    '</span>';
            }

            html += `
            <li data-is-dropoff="${option.isDropOff ? "yes" : "no"}" data-method-id="${option.id}" class="${selectedClass}">
                <div class="option-details">
                    <img src="${option.logoUrl || packlink_custom.default_carrier_logo}" alt="${option.carrierName}">
                    <div class="option-info">
                        <h4>${option.carrierName} - ${option.serviceName}</h4>
                        <p>${deliveryInfo}</p>
                        ${dropOffIndicatorHtml}
                    </div>
                    <div class="option-price">${formattedPrice}</div>
                </div>
                <button type="button" class="select-shipping" 
                        data-id="${option.id}" 
                        data-price="${option.price}"
                        data-carrier="${option.carrierName}"
                        data-service="${option.serviceName}"
                        data-delivery="${deliveryInfo}"
                        data-is-dropoff="${option.isDropOff ? "yes" : "no"}">
                    ${packlink_custom.select_button_text}
                </button>
            </li>
        `;
        });

        html += "</ul>";
        $(`#shipping-options-list-${routeIndex}`).html(html);

        // Add click handler for shipping selection
        $(`#shipping-options-list-${routeIndex} .select-shipping`).on("click", function (e) {
            // Prevent any default action
            e.preventDefault();
            e.stopPropagation();

            // Store selected shipping option
            selectedShippingOptions[routeIndex] = {
                id: $(this).data("id"),
                price: $(this).data("price"),
                carrier: $(this).data("carrier"),
                service: $(this).data("service"),
                delivery: $(this).data("delivery"),
                isDropOff: $(this).data("is-dropoff") === "yes"
            };

            // Highlight selected option
            $(`#shipping-options-list-${routeIndex} li`).removeClass("selected");
            $(this).closest("li").addClass("selected");

            // If this is a drop-off method, show the drop-off picker
            if (selectedShippingOptions[routeIndex].isDropOff) {
                showDropOffPicker(selectedShippingOptions[routeIndex].id, routeIndex);
            }

            // Check if all routes have shipping options
            checkAllRoutesHaveShipping();
        });

        // If there was a previously selected option, reselect it
        if (selectedShippingOptions[routeIndex]) {
            $(`#shipping-options-list-${routeIndex} li[data-method-id="${selectedShippingOptions[routeIndex].id}"]`).addClass("selected");
        }

        // Check if all routes have shipping options
        checkAllRoutesHaveShipping();
    }

    // Format price
    function formatPrice(price, currency) {
        if (typeof price !== "number") {
            price = parseFloat(price);
        }

        if (isNaN(price)) {
            return "";
        }

        // Format price based on currency
        return new Intl.NumberFormat(undefined, {
            style: "currency",
            currency: currency || "EUR",
        }).format(price);
    }

    // Format date for display
    function formatDate(dateString) {
        if (!dateString) return '';

        const date = new Date(dateString);
        return date.toLocaleDateString(undefined, {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    // Show drop-off picker
    function showDropOffPicker(shippingOptionId, routeIndex) {
        // Get destination country and postal code
        const country = $(`#destination_country_${routeIndex}`).data('iso');
        const postalCode = $(`#destination_postal_code_${routeIndex}`).data("postcode");

        // Get drop-off locations
        $.ajax({
            url: packlink_custom.ajax_url,
            type: "POST",
            data: {
                action: "get_packlink_drop_off_locations",
                nonce: $("#packlink_nonce").val(),
                shipping_method_id: shippingOptionId,
                country: country,
                postal_code: postalCode,
            },
            beforeSend: function () {
                $("#pl-drop-off-locations-container").html(
                    '<div class="packlink-loading"><div class="packlink-loading-spinner"></div><p>' +
                    packlink_custom.loading_drop_off_locations +
                    "</p></div>"
                );
                $("#pl-drop-off-picker-container").addClass("active");
            },
            success: function (response) {
                if (response.success && response.data.length > 0) {
                    renderDropOffLocations(response.data, routeIndex);
                } else {
                    $("#pl-drop-off-locations-container").html(
                        '<p class="error-message">' +
                        packlink_custom.no_drop_off_locations_message +
                        "</p>"
                    );
                }
            },
            error: function () {
                $("#pl-drop-off-locations-container").html(
                    '<p class="error-message">' + packlink_custom.error_loading_drop_off_locations + "</p>"
                );
            },
        });
    }

    // Render drop-off locations
    function renderDropOffLocations(locations, routeIndex) {
        let html = '<div class="pl-drop-off-locations">';

        locations.forEach(function (location) {
            html += `
                <div class="pl-drop-off-location" data-id="${location.id}">
                    <div class="pl-drop-off-location-name">${location.name}</div>
                    <div class="pl-drop-off-location-address">${location.address}</div>
                    <div class="pl-drop-off-location-city">${location.city}, ${location.zip}</div>
                    <div class="pl-drop-off-location-working-hours">
                        ${formatWorkingHours(location.workingHours)}
                    </div>
                    <button class="pl-select-drop-off" 
                            data-id="${location.id}" 
                            data-name="${location.name}" 
                            data-address="${location.address}" 
                            data-city="${location.city}" 
                            data-zip="${location.zip}"
                            data-state="${location.state || ""}"
                            data-route-index="${routeIndex}">
                        ${packlink_custom.select_location_button_text}
                    </button>
                </div>
            `;
        });

        html += "</div>";
        $("#pl-drop-off-locations-container").html(html);

        // Add click handler for drop-off selection
        $(".pl-select-drop-off").on("click", function () {
            const routeIndex = $(this).data("route-index");
            const dropOffId = $(this).data("id");
            const dropOffDetails = {
                name: $(this).data("name"),
                address: $(this).data("address"),
                city: $(this).data("city"),
                zip: $(this).data("zip"),
                state: $(this).data("state"),
            };

            // Store selected drop-off point for this route
            selectedDropOffPoints[routeIndex] = {
                id: dropOffId,
                details: dropOffDetails,
            };

            // Close picker
            $("#pl-drop-off-picker-container").removeClass("active");

            // Update the shipping option to indicate drop-off point is selected
            $(`#shipping-options-list-${routeIndex} li.selected .drop-off-indicator`).html(
                packlink_custom.drop_off_point_selected || 'Drop-off point selected'
            ).addClass('drop-off-selected');

            // Check if all routes have shipping options
            checkAllRoutesHaveShipping();
        });
    }

    // Format working hours
    function formatWorkingHours(workingHours) {
        if (!workingHours || !workingHours.length) {
            return packlink_custom.working_hours_not_available;
        }

        let html = '<div class="pl-working-hours">';

        workingHours.forEach(function (day) {
            html += `<div>${day.day}: ${day.periods.join(", ")}</div>`;
        });

        html += "</div>";
        return html;
    }

    // Update review summary
    function updateReviewSummary() {
        // Clear previous route summaries
        $("#summary-routes").empty();

        // Add summary for each route
        $(".route-set").each(function (index) {
            const routeIndex = $(this).data("route-index");
            const originCountry = $(`#origin_country_${routeIndex} option:selected`).text();
            const originPostal = $(`#origin_postal_code_${routeIndex}`).val();
            const originAddress = $(`#origin_address_${routeIndex}`).val();

            const destinationCountry = $(`#destination_country_${routeIndex} option:selected`).text();
            const destinationPostal = $(`#destination_postal_code_${routeIndex}`).val();
            const destinationAddress = $(`#destination_address_${routeIndex}`).val();

            const collectionDate = $(`#collection_date_${routeIndex}`).val();

            // Create route summary
            const routeSummary = $('<div class="route-summary"></div>');
            routeSummary.html(`
                <h5>${packlink_custom.route_text || 'Route'} #${index + 1}</h5>
                <p><strong>${packlink_custom.origin_text || 'Origin'}:</strong> ${originAddress}, ${originPostal}, ${originCountry}</p>
                <p><strong>${packlink_custom.destination_text || 'Destination'}:</strong> ${destinationAddress}, ${destinationPostal}, ${destinationCountry}</p>
                <p><strong>${packlink_custom.collection_date_text || 'Collection Date'}:</strong> ${formatDate(collectionDate)}</p>
                <p><strong>${packlink_custom.shipping_method_text || 'Shipping Method'}:</strong> ${selectedShippingOptions[routeIndex] ? selectedShippingOptions[routeIndex].carrier + ' - ' + selectedShippingOptions[routeIndex].service : 'Not selected'}</p>
                <p><strong>${packlink_custom.price_text || 'Price'}:</strong> ${selectedShippingOptions[routeIndex] ? formatPrice(selectedShippingOptions[routeIndex].price, 'EUR') : '€0.00'}</p>
            `);

            // Add contact information for this route
            routeSummary.append(`
                <div class="summary-contact">
                    <div class="summary-sender">
                        <h6>${packlink_custom.sender_text || 'Sender'}</h6>
                        <p>${$(`#sender_name_${routeIndex}`).val()}</p>
                        <p>${$(`#sender_email_${routeIndex}`).val()}</p>
                        <p>${$(`#sender_phone_${routeIndex}`).val()}</p>
                        ${$(`#sender_company_${routeIndex}`).val() ? '<p>' + $(`#sender_company_${routeIndex}`).val() + '</p>' : ''}
                    </div>
                    <div class="summary-recipient">
                        <h6>${packlink_custom.recipient_text || 'Recipient'}</h6>
                        <p>${$(`#recipient_name_${routeIndex}`).val()}</p>
                        <p>${$(`#recipient_email_${routeIndex}`).val()}</p>
                        <p>${$(`#recipient_phone_${routeIndex}`).val()}</p>
                        ${$(`#recipient_company_${routeIndex}`).val() ? '<p>' + $(`#recipient_company_${routeIndex}`).val() + '</p>' : ''}
                    </div>
                </div>
            `);

            // Add drop-off details if applicable
            if (selectedShippingOptions[routeIndex] && selectedShippingOptions[routeIndex].isDropOff && selectedDropOffPoints[routeIndex]) {
                const dropOffDetails = selectedDropOffPoints[routeIndex].details;
                routeSummary.append(`
                    <div class="drop-off-summary">
                        <p><strong>${packlink_custom.drop_off_point_text || 'Drop-off Point'}:</strong> ${dropOffDetails.name}</p>
                        <p>${dropOffDetails.address}, ${dropOffDetails.city}, ${dropOffDetails.zip}</p>
                    </div>
                `);
            }

            // Add to summary
            $("#summary-routes").append(routeSummary);
        });

        // Package summary
        const packagesContainer = $("#packlink-packages-container");
        const packageElements = packagesContainer.find(".package");
        const summaryPackages = $("#summary-packages");

        // Clear previous package summaries
        summaryPackages.html('');

        packageElements.each(function (index) {
            const packageIndex = $(this).data("package-index");
            const weight = $("#weight_" + packageIndex).val();
            const length = $("#length_" + packageIndex).val();
            const width = $("#width_" + packageIndex).val();
            const height = $("#height_" + packageIndex).val();

            const packageSummary = $('<div class="package-summary"></div>');
            packageSummary.html(`
                <h5>${packlink_custom.package_text || 'Package'} ${index + 1}</h5>
                <p>${packlink_custom.weight_text || 'Weight'}: ${weight} kg</p>
                <p>${packlink_custom.dimensions_text || 'Dimensions'}: ${length} × ${width} × ${height} cm</p>
            `);

            summaryPackages.append(packageSummary);
        });

        // Total price
        let totalPrice = 0;
        Object.keys(selectedShippingOptions).forEach(function (routeIndex) {
            totalPrice += parseFloat(selectedShippingOptions[routeIndex].price);
        });

        // Format total price
        $("#summary-total-price").text(formatPrice(totalPrice, 'EUR'));
    }
    // Setup form submission
    function setupFormSubmission() {
        $("#packlink-custom-shipping-form").on("submit", function (e) {
            e.preventDefault();

            // Validate form
            if (!validateForm()) {
                return false;
            }

            // Show loading state
            $(".btn-submit").prop("disabled", true).text(packlink_custom.processing_text || 'Processing...');

            // Prepare shipping data
            const shippingDetails = prepareShippingData();

            // Log the data being sent
            console.log("Shipping data:", shippingDetails);

            // Submit form
            $.ajax({
                url: packlink_custom.ajax_url,
                type: "POST",
                data: {
                    action: "add_packlink_shipping_to_cart",
                    nonce: $("#packlink_nonce").val(),
                    shipping_details: JSON.stringify(shippingDetails)
                },
                success: function (response) {
                    if (response.success) {
                        // Show success message
                        $("#success-modal").css("display", "flex");

                        // Redirect after delay
                        setTimeout(function () {
                            window.location.href = response.data.redirect;
                        }, 2000);
                    } else {
                        alert(response.data.message || packlink_custom.error_adding_to_cart || 'Error adding shipping to cart');
                        $(".btn-submit").prop("disabled", false).text(packlink_custom.submit_button_text || 'Submit');
                    }
                },
                error: function () {
                    alert(packlink_custom.error_connecting_server || 'Error connecting to server');
                    $(".btn-submit").prop("disabled", false).text(packlink_custom.submit_button_text || 'Submit');
                }
            });
        });
    }

    // Prepare shipping data with per-route contact information
    function prepareShippingData() {
        // Prepare routes data
        const routes = [];
        $(".route-set").each(function () {
            const routeIndex = $(this).data("route-index");

            // Get the selected shipping option
            const selectedOption = selectedShippingOptions[routeIndex];
            if (!selectedOption) return;

            // Create route data object
            const routeData = {
                origin_country: $(`#origin_country_${routeIndex}`).data('iso'),
                origin_postal_code: $(`#origin_postal_code_${routeIndex}`).data('postcode'),
                origin_city: $(`#origin_city_${routeIndex}`).val(),
                origin_address: $(`#origin_address_${routeIndex}`).val(),
                destination_country: $(`#destination_country_${routeIndex}`).data('iso'),
                destination_postal_code: $(`#destination_postal_code_${routeIndex}`).data('postcode'),
                destination_city: $(`#destination_city_${routeIndex}`).val(),
                destination_address: $(`#destination_address_${routeIndex}`).val(),
                collection_date: $(`#collection_date_${routeIndex}`).val(),
                collection_time: $(`#collection_time_${routeIndex}`).val() || '12:00',
                shipping_option_id: selectedOption.id,
                price: selectedOption.price,
                // Adding per-route contact information
                sender_name: $(`#sender_name_${routeIndex}`).val(),
                sender_email: $(`#sender_email_${routeIndex}`).val(),
                sender_phone: $(`#sender_phone_${routeIndex}`).val(),
                sender_company: $(`#sender_company_${routeIndex}`).val(),
                recipient_name: $(`#recipient_name_${routeIndex}`).val(),
                recipient_email: $(`#recipient_email_${routeIndex}`).val(),
                recipient_phone: $(`#recipient_phone_${routeIndex}`).val(),
                recipient_company: $(`#recipient_company_${routeIndex}`).val()
            };

            // Add drop-off point data if selected
            if (selectedOption.isDropOff && selectedDropOffPoints[routeIndex]) {
                routeData.drop_off_id = selectedDropOffPoints[routeIndex].id;
                routeData.drop_off_details = selectedDropOffPoints[routeIndex].details;
            }

            // Add to routes array
            routes.push(routeData);
        });

        // Prepare packages data
        const packages = [];
        $(".package").each(function () {
            const packageIndex = $(this).data("package-index");

            packages.push({
                weight: $("#weight_" + packageIndex).val(),
                length: $("#length_" + packageIndex).val(),
                width: $("#width_" + packageIndex).val(),
                height: $("#height_" + packageIndex).val()
            });
        });

        // Calculate total price
        let totalPrice = 0;
        Object.keys(selectedShippingOptions).forEach(function (routeIndex) {
            totalPrice += parseFloat(selectedShippingOptions[routeIndex].price);
        });

        // Create shipping details object
        return {
            routes: routes,
            packages: packages,
            total_price: totalPrice.toFixed(2),
            currency: 'EUR'
        };
    };

    function validateForm() {
        let isValid = true;

        // Validate routes section
        if ($("#routes-container .route-set").length === 0) {
            alert(packlink_custom.please_add_route || 'Please add at least one route');
            return false;
        }

        $(".route-set").each(function () {
            const routeIndex = $(this).data("route-index");

            // Check required fields for origin
            if (!$(`#origin_country_${routeIndex}`).val()) {
                $(`#origin_country_${routeIndex}`).addClass("error");
                isValid = false;
            } else {
                $(`#origin_country_${routeIndex}`).removeClass("error");
            }

            if (!$(`#origin_postal_code_${routeIndex}`).val()) {
                $(`#origin_postal_code_${routeIndex}`).addClass("error");
                isValid = false;
            } else {
                $(`#origin_postal_code_${routeIndex}`).removeClass("error");
            }

            if (!$(`#origin_address_${routeIndex}`).val()) {
                $(`#origin_address_${routeIndex}`).addClass("error");
                isValid = false;
            } else {
                $(`#origin_address_${routeIndex}`).removeClass("error");
            }

            // Check required fields for destination
            if (!$(`#destination_country_${routeIndex}`).val()) {
                $(`#destination_country_${routeIndex}`).addClass("error");
                isValid = false;
            } else {
                $(`#destination_country_${routeIndex}`).removeClass("error");
            }

            if (!$(`#destination_postal_code_${routeIndex}`).val()) {
                $(`#destination_postal_code_${routeIndex}`).addClass("error");
                isValid = false;
            } else {
                $(`#destination_postal_code_${routeIndex}`).removeClass("error");
            }

            if (!$(`#destination_address_${routeIndex}`).val()) {
                $(`#destination_address_${routeIndex}`).addClass("error");
                isValid = false;
            } else {
                $(`#destination_address_${routeIndex}`).removeClass("error");
            }

            // Check collection date
            if (!$(`#collection_date_${routeIndex}`).val()) {
                $(`#collection_date_${routeIndex}`).addClass("error");
                isValid = false;
            } else {
                $(`#collection_date_${routeIndex}`).removeClass("error");
            }

            // Validate per-route contact information
            if ($(`#sender_name_${routeIndex}`).length) {
                if (!$(`#sender_name_${routeIndex}`).val()) {
                    $(`#sender_name_${routeIndex}`).addClass("error");
                    isValid = false;
                } else {
                    $(`#sender_name_${routeIndex}`).removeClass("error");
                }

                if (!$(`#sender_email_${routeIndex}`).val() || !validateEmail($(`#sender_email_${routeIndex}`).val())) {
                    $(`#sender_email_${routeIndex}`).addClass("error");
                    isValid = false;
                } else {
                    $(`#sender_email_${routeIndex}`).removeClass("error");
                }

                if (!$(`#sender_phone_${routeIndex}`).val()) {
                    $(`#sender_phone_${routeIndex}`).addClass("error");
                    isValid = false;
                } else {
                    $(`#sender_phone_${routeIndex}`).removeClass("error");
                }

                if (!$(`#recipient_name_${routeIndex}`).val()) {
                    $(`#recipient_name_${routeIndex}`).addClass("error");
                    isValid = false;
                } else {
                    $(`#recipient_name_${routeIndex}`).removeClass("error");
                }

                if (!$(`#recipient_email_${routeIndex}`).val() || !validateEmail($(`#recipient_email_${routeIndex}`).val())) {
                    $(`#recipient_email_${routeIndex}`).addClass("error");
                    isValid = false;
                } else {
                    $(`#recipient_email_${routeIndex}`).removeClass("error");
                }

                if (!$(`#recipient_phone_${routeIndex}`).val()) {
                    $(`#recipient_phone_${routeIndex}`).addClass("error");
                    isValid = false;
                } else {
                    $(`#recipient_phone_${routeIndex}`).removeClass("error");
                }
            }
        });

        // Validate packages
        if ($("#packlink-packages-container .package").length === 0) {
            alert(packlink_custom.please_add_package || 'Please add at least one package');
            return false;
        }

        $(".package").each(function () {
            const packageIndex = $(this).data("package-index");

            // Check weight
            if (!$("#weight_" + packageIndex).val() || parseFloat($("#weight_" + packageIndex).val()) <= 0) {
                $("#weight_" + packageIndex).addClass("error");
                isValid = false;
            } else {
                $("#weight_" + packageIndex).removeClass("error");
            }

            // Check dimensions
            if (!$("#length_" + packageIndex).val() || parseFloat($("#length_" + packageIndex).val()) <= 0) {
                $("#length_" + packageIndex).addClass("error");
                isValid = false;
            } else {
                $("#length_" + packageIndex).removeClass("error");
            }

            if (!$("#width_" + packageIndex).val() || parseFloat($("#width_" + packageIndex).val()) <= 0) {
                $("#width_" + packageIndex).addClass("error");
                isValid = false;
            } else {
                $("#width_" + packageIndex).removeClass("error");
            }

            if (!$("#height_" + packageIndex).val() || parseFloat($("#height_" + packageIndex).val()) <= 0) {
                $("#height_" + packageIndex).addClass("error");
                isValid = false;
            } else {
                $("#height_" + packageIndex).removeClass("error");
            }
        });

        // Validate shipping options
        const routeCount = $(".route-set").length;
        let optionsSelected = 0;

        for (let i = 0; i < routeCount; i++) {
            if (selectedShippingOptions[i]) {
                optionsSelected++;
            }
        }

        if (optionsSelected < routeCount) {
            alert(packlink_custom.please_select_shipping_option || 'Please select a shipping option for each route');
            return false;
        }

        // Check if drop-off points are selected where required
        for (let i = 0; i < routeCount; i++) {
            if (selectedShippingOptions[i] && selectedShippingOptions[i].isDropOff && !selectedDropOffPoints[i]) {
                alert(packlink_custom.please_select_drop_off_point || 'Please select a drop-off point for each route that requires it');
                return false;
            }
        }

        return isValid;
    }

    // Helper function to validate email format
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    // Close drop-off picker when clicking the close button
    $(".pl-close-picker").on("click", function () {
        $("#pl-drop-off-picker-container").removeClass("active");
    });

    // Close drop-off picker when clicking outside the picker
    $("#pl-drop-off-picker-container").on("click", function (e) {
        if (e.target === this) {
            $(this).removeClass("active");
        }
    });

    // Success modal close button
    $(".btn-close-modal").on("click", function () {
        $("#success-modal").removeClass("active");
    });
});