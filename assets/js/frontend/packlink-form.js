jQuery(document).ready(function ($) {
  // Store selected drop-off points for each route
  let selectedDropOffPoints = {};

  // Store selected shipping options for each route
  let selectedShippingOptions = {};

  // Route counter
  let routeCounter = 0;

  // Package counter
  let packageCounter = 0;

  // ==================== STORAGE CONSTANTS ====================
  // Storage key name (using Reluggz branding)
  const STORAGE_KEY = "reluggz_form_data";
  const SESSION_KEY = "reluggz_session_id";

  // Cache expiry time (4 hours instead of 24 to prevent stale data)
  const CACHE_EXPIRY_MS = 4 * 60 * 60 * 1000;

  // Debug logging helper - only logs when RELUGGZ_DEBUG is enabled
  function debugLog(...args) {
    if (window.RELUGGZ_DEBUG === true) {
      console.log('[Reluggz]', ...args);
    }
  }

  // Initialize form with default values
  function initializeForm() {
    // Check if coming from quote redirect at the very beginning
    const urlParams = new URLSearchParams(window.location.search);
    const isQuoteRedirect = urlParams.has("quote_redirect");

    if (isQuoteRedirect) {
      console.log("Quote redirect detected - preventing all default values");
      // Set global flag to prevent default values
      window.packlinkPreventDefaults = true;

      // Clear any existing data immediately (both old and new key names)
      clearFormFields();
      localStorage.removeItem("packlink_form_data"); // Legacy key
      localStorage.removeItem("reluggz_form_data");  // New key
      sessionStorage.removeItem("reluggz_session_id");
    }

    initializeContactsSection();
    initializePackages();
    setupFormNavigation();
    initializeLocalStorage();
    checkAndPreFillFromURL();
    setupFormSubmission();

    // Add route button click handler
    $("#add-route").on("click", function () {
      addRoute();
    });

    // Initialize first route
    addRoute();
  }

  // Initialize form
  initializeForm();

  // Initialize localStorage functionality
  initializeLocalStorage();

  // Check URL for pre-fill parameters from quote form
  function checkAndPreFillFromURL() {
    const urlParams = new URLSearchParams(window.location.search);
    const quoteRedirect = urlParams.get("quote_redirect");

    if (quoteRedirect === "1") {
      // Set flag to prevent defaults
      window.packlinkQuoteRedirect = true;

      // Clear localStorage and form fields
      clearSavedFormData();
      clearFormFields();
    }
  }

  // Pre-fill route countries from quote form
  function preFillRouteCountries(routeIndex, countries) {
    console.log("Pre-filling countries:", countries);

    // Wait for destinations to be available
    const checkDestinations = function () {
      if (
        typeof window.destinations === "undefined" ||
        !window.destinations ||
        window.destinations.length === 0
      ) {
        console.log("Destinations not ready, waiting...");
        setTimeout(checkDestinations, 200);
        return;
      }

      console.log("Destinations ready, proceeding with pre-fill");

      // Find the countries in the destinations array
      const originCountry = window.destinations.find(
        (dest) =>
          dest.isoCode === countries.origin.code ||
          dest.name === countries.origin.name ||
          dest.name.includes(countries.origin.name.split("(")[0].trim())
      );

      const destinationCountry = window.destinations.find(
        (dest) =>
          dest.isoCode === countries.destination.code ||
          dest.name === countries.destination.name ||
          dest.name.includes(countries.destination.name.split("(")[0].trim())
      );

      console.log("Found origin:", originCountry);
      console.log("Found destination:", destinationCountry);

      // Pre-fill origin country using selectCountry function
      if (originCountry) {
        const originSearchInput = $(`#origin_country_search_${routeIndex}`);
        const originHiddenInput = $(`#origin_country_${routeIndex}`);

        if (originSearchInput.length && originHiddenInput.length) {
          selectCountry(
            originCountry,
            originSearchInput,
            originHiddenInput,
            routeIndex,
            "origin"
          );
          console.log("Origin country selected:", originCountry.name);
        }
      }

      // Pre-fill destination country using selectCountry function
      if (destinationCountry) {
        const destinationSearchInput = $(
          `#destination_country_search_${routeIndex}`
        );
        const destinationHiddenInput = $(`#destination_country_${routeIndex}`);

        if (destinationSearchInput.length && destinationHiddenInput.length) {
          selectCountry(
            destinationCountry,
            destinationSearchInput,
            destinationHiddenInput,
            routeIndex,
            "destination"
          );
          console.log("Destination country selected:", destinationCountry.name);
        }
      }

      // Show success notification if both countries were found
      if (originCountry && destinationCountry) {
        // showPreFillNotification(originCountry.name, destinationCountry.name);
        console.log(
          "Countries successfully pre-filled:",
          originCountry.name,
          "to",
          destinationCountry.name
        );
      }
    };

    checkDestinations();
  }

  // Show notification that countries were pre-filled
  /*
  function showPreFillNotification(originCountry, destinationCountry) {
    const notification = $(`
      <div class="packlink-prefill-notification" style="
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #027361 0%, #20b2aa 100%);
        color: white;
        padding: 16px 24px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(2, 115, 97, 0.25);
        z-index: 10000;
        font-weight: 500;
        max-width: 300px;
        animation: slideInRight 0.5s ease-out;
      ">
        <div style="display: flex; align-items: center; gap: 8px;">
          <svg style="width: 20px; height: 20px; fill: currentColor;" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
          </svg>
          <div>
            <div style="font-weight: 600; margin-bottom: 4px;">Countries Pre-filled!</div>
            <div style="font-size: 0.9em; opacity: 0.9;">From ${originCountry} to ${destinationCountry}</div>
          </div>
        </div>
      </div>
    `);

    // Add CSS animation
    $("head").append(`
      <style>
        @keyframes slideInRight {
          from {
            transform: translateX(100%);
            opacity: 0;
          }
          to {
            transform: translateX(0);
            opacity: 1;
          }
        }
      </style>
    `);

    $("body").append(notification);

    // Remove notification after 4 seconds
    setTimeout(() => {
      notification.fadeOut(500, () => notification.remove());
    }, 4000);
  }
  */

  // Handle the Contact Information section
  function initializeContactsSection() {
    // Clear existing contact forms
    $("#route-contacts-container").empty();

    // Create contact form for each route
    const routeCount = $(".route-set").length;
    for (let i = 0; i < routeCount; i++) {
      addRouteContactForm(i);
    }

    // Apply connected routes logic if enabled
    if ($("#connect-locations").is(":checked")) {
      connectRouteContacts();
    }

    // Restore contact data from localStorage after initialization
    setTimeout(function () {
      const savedData = localStorage.getItem("packlink_form_data");
      if (savedData) {
        try {
          const formData = JSON.parse(savedData);
          if (formData.contacts) {
            restoreContactsData(formData.contacts);
          }
        } catch (error) {
          console.warn("Failed to restore contact data:", error);
        }
      }
    }, 200);

    // When connect-locations is toggled, update the contact forms
    $("#connect-locations").on("change", function () {
      if ($(this).is(":checked")) {
        connectRouteContacts();
      }
    });
  }

  // Add contact form for a specific route
  function addRouteContactForm(routeIndex) {
    const template = $("#route-contact-template").html();
    const routeNumber = routeIndex + 1;

    // Replace placeholders
    const html = template
      .replace(/{ROUTE_INDEX}/g, routeIndex)
      .replace(/{ROUTE_NUMBER}/g, routeNumber);

    $("#route-contacts-container").append(html);

    // Prefill with default values if available
    prefillContactForm(routeIndex);
  }

  // Prefill contact form with default values or user data
  function prefillContactForm(routeIndex) {
    // Default sender values
    if (routeIndex === 0) {
      // For first route, use user profile or default values
      const defaultSenderName = $("#sender_name_0").data("default") || "";
      const defaultSenderEmail = $("#sender_email_0").data("default") || "";
      const defaultSenderPhone = $("#sender_phone_0").data("default") || "";
      const defaultSenderCompany = $("#sender_company_0").data("default") || "";

      $("#sender_name_0").val(defaultSenderName);
      $("#sender_email_0").val(defaultSenderEmail);
      $("#sender_phone_0").val(defaultSenderPhone);
      $("#sender_company_0").val(defaultSenderCompany);
    }
  }

  // Connect route contacts when the checkbox is enabled
  function connectRouteContacts() {
    const routeCount = $(".route-contact-set").length;
    const isConnected = $("#connect-contacts").is(":checked");

    // Skip if there's only one route
    if (routeCount <= 1) return;

    // Remove previous event listeners by cloning and replacing elements
    for (let i = 1; i < routeCount; i++) {
      const prevIndex = i - 1;

      // Remove all event handlers from the previous recipient fields
      $(`#recipient_name_${prevIndex}`).off("change");
      $(`#recipient_email_${prevIndex}`).off("change");
      $(`#recipient_phone_${prevIndex}`).off("change");
      $(`#recipient_company_${prevIndex}`).off("change");
    }

    // For routes after the first one, copy recipient from previous route to sender
    if (isConnected) {
      for (let i = 1; i < routeCount; i++) {
        const prevIndex = i - 1;

        // Create change event handlers to sync data
        $(`#recipient_name_${prevIndex}`).on("change", function () {
          $(`#sender_name_${i}`).val($(this).val());
        });

        $(`#recipient_email_${prevIndex}`).on("change", function () {
          $(`#sender_email_${i}`).val($(this).val());
        });

        $(`#recipient_phone_${prevIndex}`).on("change", function () {
          $(`#sender_phone_${i}`).val($(this).val());
        });

        $(`#recipient_company_${prevIndex}`).on("change", function () {
          $(`#sender_company_${i}`).val($(this).val());
        });

        // Also copy current values
        $(`#sender_name_${i}`).val($(`#recipient_name_${prevIndex}`).val());
        $(`#sender_email_${i}`).val($(`#recipient_email_${prevIndex}`).val());
        $(`#sender_phone_${i}`).val($(`#recipient_phone_${prevIndex}`).val());
        $(`#sender_company_${i}`).val(
          $(`#recipient_company_${prevIndex}`).val()
        );
      }
    } else {
      // If connection is disabled, reset sender info for routes after the first one
      for (let i = 1; i < routeCount; i++) {
        // Reset to default data values if they exist
        const defaultName = $(`#sender_name_${i}`).data("default") || "";
        const defaultEmail = $(`#sender_email_${i}`).data("default") || "";
        const defaultPhone = $(`#sender_phone_${i}`).data("default") || "";
        const defaultCompany = $(`#sender_company_${i}`).data("default") || "";

        $(`#sender_name_${i}`).val(defaultName);
        $(`#sender_email_${i}`).val(defaultEmail);
        $(`#sender_phone_${i}`).val(defaultPhone);
        $(`#sender_company_${i}`).val(defaultCompany);
      }
    }
  }

  // Add change handler for the connect-contacts switch
  $("#connect-contacts").on("change", function () {
    connectRouteContacts();
  });

  // When routes are added or removed, update contact connections if enabled
  $("#add-route, .btn-remove-route").on("click", function () {
    setTimeout(function () {
      // Only update if we're on the details section
      if ($("#details-section").is(":visible")) {
        connectRouteContacts();
      }
    }, 100);
  });

  // Update the section navigation to initialize contacts when reaching that section
  $(".btn-next").on("click", function () {
    const nextSection = $(this).data("next");

    if (nextSection === "details-section") {
      initializeContactsSection();
    }
  });

  // When adding or removing routes, update the contact section
  $("#add-route").on("click", function () {
    // Wait for the new route to be added
    setTimeout(function () {
      if ($("#details-section").is(":visible")) {
        initializeContactsSection();
      }
    }, 100);
  });

  $(document).on("click", ".btn-remove-route", function () {
    // Wait for the route to be removed
    setTimeout(function () {
      if ($("#details-section").is(":visible")) {
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
        const prevDestCountry = $(
          `#destination_country_${prevRouteIndex}`
        ).val();
        const prevDestCountryIso = $(
          `#destination_country_${prevRouteIndex}`
        ).data("iso");

        console.log({
          prevDestCountry,
          prevDestCountryIso,
        });
        if (prevDestCountry) {
          $(`#origin_country_${routeIndex}`)
            .val(prevDestCountry)
            .trigger("change");
          $(`#origin_country_${routeIndex}`).data("iso", prevDestCountryIso);
        }

        // Copy postal code and city
        const prevDestPostalVal = $(
          `#destination_postal_code_${prevRouteIndex}`
        ).val();
        const prevDestPostcode = $(
          `#destination_postal_code_${prevRouteIndex}`
        ).data("postcode");
        const prevDestCity = $(`#destination_city_${prevRouteIndex}`).val();

        if (prevDestPostalVal) {
          $(`#origin_postal_code_${routeIndex}`).val(prevDestPostalVal);
          $(`#origin_postal_code_${routeIndex}`).data(
            "postcode",
            prevDestPostcode
          );
        }

        if (prevDestCity) {
          $(`#origin_city_${routeIndex}`).val(prevDestCity);
        }

        // Copy address
        const prevDestAddress = $(
          `#destination_address_${prevRouteIndex}`
        ).val();

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
    $(
      `#routes-container .route-set[data-route-index="${routeIndex}"] .btn-remove-route`
    ).on("click", function () {
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
      $(this)
        .find("h4")
        .text(`${packlink_custom.route_text || "Route"} #${index + 1}`);
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
    const formattedDate = tomorrow.toISOString().split("T")[0];

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
        alert(
          packlink_custom.collection_date_future_message ||
          "Collection date must be at least one day in the future."
        );
        $(this).val(formattedDate);
      }
    });
  }

  // Load destinations for a route
  function loadDestinations(routeIndex) {
    $.ajax({
      url: packlink_custom.ajax_url,
      type: "GET",
      data: {
        action: "packlink_get_destinations",
        security: $("#packlink-ajax-nonce").val(),
      },
      success: function (response) {
        if (response.success && Array.isArray(response.data)) {
          populateDestinationDropdowns(response.data, routeIndex);
        }
      },
      error: function () {
        console.error("Failed to load destinations");
      },
    });
  }

  // Populate destination dropdowns for a route
  function populateDestinationDropdowns(destinations, routeIndex) {
    const originSearch = $(`#origin_country_search_${routeIndex}`);
    const destinationSearch = $(`#destination_country_search_${routeIndex}`);
    const originHidden = $(`#origin_country_${routeIndex}`);
    const destinationHidden = $(`#destination_country_${routeIndex}`);
    const originOptions = $(`#origin_country_options_${routeIndex}`);
    const destinationOptions = $(`#destination_country_options_${routeIndex}`);

    // Store destinations data
    originSearch.data("destinations", destinations);
    destinationSearch.data("destinations", destinations);

    // Setup search functionality for origin
    setupCountrySearch(
      originSearch,
      originHidden,
      originOptions,
      destinations,
      routeIndex,
      "origin"
    );

    // Setup search functionality for destination
    setupCountrySearch(
      destinationSearch,
      destinationHidden,
      destinationOptions,
      destinations,
      routeIndex,
      "destination"
    );

    // Set default country values if available (but only if not coming from quote redirect)
    const urlParams = new URLSearchParams(window.location.search);
    const isQuoteRedirect =
      urlParams.has("quote_redirect") || window.packlinkPreventDefaults;

    if (!isQuoteRedirect && packlink_custom.default_origin_country) {
      const defaultOrigin = destinations.find(
        (d) => d.isoCode === packlink_custom.default_origin_country
      );
      if (defaultOrigin) {
        selectCountry(
          defaultOrigin,
          originSearch,
          originHidden,
          routeIndex,
          "origin"
        );
      }
    }

    if (!isQuoteRedirect && packlink_custom.default_destination_country) {
      const defaultDest = destinations.find(
        (d) => d.isoCode === packlink_custom.default_destination_country
      );
      if (defaultDest) {
        selectCountry(
          defaultDest,
          destinationSearch,
          destinationHidden,
          routeIndex,
          "destination"
        );
      }
    }

    // Set global destinations variable for pre-fill function
    if (routeIndex === 0) {
      window.destinations = destinations;

      // Trigger pre-fill if URL parameters exist
      const urlParams = new URLSearchParams(window.location.search);
      const quoteRedirect = urlParams.get("quote_redirect");

      if (quoteRedirect === "1") {
        const originCountry = urlParams.get("origin_country");
        const destinationCountry = urlParams.get("destination_country");

        if (originCountry && destinationCountry) {
          console.log("Destinations loaded, triggering pre-fill...");
          setTimeout(() => {
            const originCountryCode = urlParams.get("origin_country_code");
            const destinationCountryCode = urlParams.get(
              "destination_country_code"
            );

            preFillRouteCountries(0, {
              origin: {
                name: originCountry,
                code: originCountryCode,
              },
              destination: {
                name: destinationCountry,
                code: destinationCountryCode,
              },
            });
          }, 200);
        }
      }
    }
  }

  // Setup country search functionality
  function setupCountrySearch(
    searchInput,
    hiddenInput,
    optionsContainer,
    destinations,
    routeIndex,
    type
  ) {
    // Search input handler
    searchInput.on("input", function () {
      const query = $(this).val().toLowerCase();
      const filtered = destinations.filter(
        (d) =>
          d.name.toLowerCase().includes(query) ||
          d.isoCode.toLowerCase().includes(query)
      );

      renderCountryOptions(
        filtered,
        optionsContainer,
        searchInput,
        hiddenInput,
        routeIndex,
        type
      );
      optionsContainer.addClass("active");
    });

    // Show options on focus
    searchInput.on("focus", function () {
      const query = $(this).val().toLowerCase();
      const filtered = destinations.filter(
        (d) =>
          d.name.toLowerCase().includes(query) ||
          d.isoCode.toLowerCase().includes(query)
      );
      renderCountryOptions(
        filtered,
        optionsContainer,
        searchInput,
        hiddenInput,
        routeIndex,
        type
      );
      optionsContainer.addClass("active");
    });

    // Handle click outside
    $(document).on("click", function (e) {
      if (!$(e.target).closest(".country-search-wrapper").length) {
        optionsContainer.removeClass("active");
      }
    });
  }

  // Render country options
  function renderCountryOptions(
    countries,
    container,
    searchInput,
    hiddenInput,
    routeIndex,
    type
  ) {
    container.empty();

    countries.sort((a, b) => {
      if (a.name < b.name) return -1;
      if (a.name > b.name) return 1;
      return 0;
    });

    countries.forEach((country) => {
      const option = $("<div>")
        .addClass("country-option")
        .text(`${country.name} (${country.isoCode})`)
        .on("click", function () {
          selectCountry(country, searchInput, hiddenInput, routeIndex, type);
          container.removeClass("active");
        });
      container.append(option);
    });
  }

  // Select a country
  function selectCountry(country, searchInput, hiddenInput, routeIndex, type) {
    searchInput.val(`${country.name} (${country.isoCode})`);
    hiddenInput.val(country.id).attr("data-iso", country.isoCode);
    hiddenInput.trigger("change");

    // Update route information
    updateRouteInfo(routeIndex);
  }

  // Initialize postal code autocomplete for a route
  function initPostalCodeAutocomplete(routeIndex) {
    // Setup autocomplete for origin postal code
    setupPostalCodeAutocomplete(
      `#origin_postal_code_${routeIndex}`,
      `#origin_country_${routeIndex}`,
      `#origin_city_${routeIndex}`,
      routeIndex,
      "origin"
    );

    // Setup autocomplete for destination postal code
    setupPostalCodeAutocomplete(
      `#destination_postal_code_${routeIndex}`,
      `#destination_country_${routeIndex}`,
      `#destination_city_${routeIndex}`,
      routeIndex,
      "destination"
    );

    // Add change event to country selects to reset postal code field
    $(`#origin_country_${routeIndex}, #destination_country_${routeIndex}`).on(
      "change",
      function () {
        const isOrigin = $(this).attr("id").includes("origin");
        const postalCodeId = isOrigin
          ? `#origin_postal_code_${routeIndex}`
          : `#destination_postal_code_${routeIndex}`;
        $(postalCodeId).val("");
      }
    );
  }

  // Setup postal code autocomplete for a specific field
  function setupPostalCodeAutocomplete(
    postalCodeSelector,
    countrySelector,
    citySelector,
    routeIndex,
    type
  ) {
    // Create a loading indicator element after the postal code field
    const $postalCodeField = $(postalCodeSelector);
    const loadingIndicatorId = postalCodeSelector.replace("#", "") + "_loading";

    // Add the loading indicator if it doesn't exist
    if ($("#" + loadingIndicatorId).length === 0) {
      $postalCodeField.after(
        '<div id="' +
        loadingIndicatorId +
        '" class="packlink-loading-indicator" style="display:none;"><div class="packlink-loading-spinner"></div></div>'
      );
    }

    $(postalCodeSelector)
      .autocomplete({
        minLength: 2,
        source: function (request, response) {
          const country = $(countrySelector).val();

          if (!country) {
            response([]);
            return;
          }

          // Show loading indicator
          $("#" + loadingIndicatorId).show();

          $.ajax({
            url: packlink_custom.ajax_url,
            dataType: "json",
            data: {
              action: "packlink_suggest_postal_codes",
              security: $("#packlink-ajax-nonce").val(),
              term: request.term,
              country: country,
            },
            success: function (data) {
              // Hide loading indicator
              $("#" + loadingIndicatorId).hide();
              response(data);
            },
            error: function () {
              // Hide loading indicator
              $("#" + loadingIndicatorId).hide();
              response([]);
            },
          });
        },
        select: function (event, ui) {
          $(postalCodeSelector)
            .val(ui.item.label)
            .attr("data-postcode", ui.item.value);
          $(citySelector).val(ui.item.city);

          // Update route information
          updateRouteInfo(routeIndex);

          return false;
        },
      })
      .autocomplete("instance")._renderItem = function (ul, item) {
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

    if (
      !originCountry ||
      !originPostal ||
      !destinationCountry ||
      !destinationPostal
    ) {
      return;
    }

    // Determine if it's domestic or international
    const isSameCountry =
      $(`#origin_country_${routeIndex}`).data("iso") ===
      $(`#destination_country_${routeIndex}`).data("iso");
    const transportType = isSameCountry ? "Road Transport" : "Air Transport";

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
    const distance =
      Math.floor(Math.random() * (maxDistance - minDistance + 1)) + minDistance;

    $(`#route-info-${routeIndex} .route-distance .value`).text(
      distance + " km"
    );
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
                    <h4>${packlink_custom.package_text || "Package"} ${packageIndex + 1
      }</h4>
                    <button type="button" class="btn-remove-package" ${isFirstPackage ? 'style="display: none;"' : ""
      }>
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
                        <label for="weight_${packageIndex}">${packlink_custom.weight_text || "Weight (kg)"
      }</label>
                        <input type="number" name="weight[]" id="weight_${packageIndex}" step="0.01" min="0.01" placeholder="Enter weight" required>
                    </div>
                    
                    <div class="dimensions-group">
                        <h5>${packlink_custom.dimensions_text || "Dimensions (cm)"
      }</h5>
                        <div class="dimensions-inputs">
                            <div class="form-group">
                                <label for="length_${packageIndex}">${packlink_custom.length_text || "Length"
      }</label>
                                <input type="number" name="length[]" id="length_${packageIndex}" min="1" placeholder="Length" required>
                            </div>
                            <div class="form-group">
                                <label for="width_${packageIndex}">${packlink_custom.width_text || "Width"
      }</label>
                                <input type="number" name="width[]" id="width_${packageIndex}" min="1" placeholder="Width" required>
                            </div>
                            <div class="form-group">
                                <label for="height_${packageIndex}">${packlink_custom.height_text || "Height"
      }</label>
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
      $(this)
        .find("h4")
        .text((packlink_custom.package_text || "Package") + " " + (index + 1));
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
  $("#packlink-packages-container").on(
    "click",
    ".btn-remove-package",
    function () {
      removePackage($(this).closest(".package"));
    }
  );

  // Connect locations checkbox handler
  $("#connect-locations").on("change", function () {
    // If checked, update all routes except the first one
    if ($(this).is(":checked")) {
      $(".route-set").each(function (index) {
        if (index > 0) {
          const routeIndex = $(this).data("route-index");
          const prevRouteIndex = $(".route-set")
            .eq(index - 1)
            .data("route-index");

          // Copy destination of previous route to origin of this route
          const prevDestCountry = $(
            `#destination_country_${prevRouteIndex}`
          ).val();
          const prevDestCountryIso = $(
            `#destination_country_${prevRouteIndex}`
          ).data("iso");

          if (prevDestCountry) {
            $(`#origin_country_${routeIndex}`)
              .val(prevDestCountry)
              .trigger("change");
            $(`#origin_country_${routeIndex}`).data("iso", prevDestCountryIso);
          }

          // Copy postal code and city
          const prevDestPostal = $(
            `#destination_postal_code_${prevRouteIndex}`
          ).val();
          const prevDestCity = $(`#destination_city_${prevRouteIndex}`).val();

          if (prevDestPostal) {
            $(`#origin_postal_code_${routeIndex}`).val(prevDestPostal);
          }

          if (prevDestCity) {
            $(`#origin_city_${routeIndex}`).val(prevDestCity);
          }

          // Copy address
          const prevDestAddress = $(
            `#destination_address_${prevRouteIndex}`
          ).val();

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
          $("html, body").animate(
            {
              scrollTop:
                $(".packlink-custom-shipping-container").offset().top - 50,
            },
            500
          );
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
        $("html, body").animate(
          {
            scrollTop:
              $(".packlink-custom-shipping-container").offset().top - 50,
          },
          500
        );
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
      $("html, body").animate(
        {
          scrollTop: $(".packlink-custom-shipping-container").offset().top - 50,
        },
        500
      );
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
          5: "review-section",
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
      "review-section": 5,
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
          $(this).after(
            '<div class="error-message">' +
            packlink_custom.please_fill_required_fields +
            "</div>"
          );
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
          $(this).after(
            '<div class="error-message">' +
            packlink_custom.value_must_be_at_least +
            " " +
            min +
            "</div>"
          );
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
                    <h4>${packlink_custom.route_text || "Route"
        } #${routeNumber} ${packlink_custom.shipping_options_text || "Shipping Options"}</h4>
                    <div id="shipping-options-list-${routeIndex}">
                        <div class="packlink-loading">
                            <div class="packlink-loading-spinner"></div>
                            <p>${packlink_custom.loading_shipping_options ||
        "Loading shipping options..."
        }</p>
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
      if (
        selectedShippingOptions[routeIndex] &&
        selectedShippingOptions[routeIndex].isDropOff &&
        !selectedDropOffPoints[routeIndex]
      ) {
        allDropOffPointsSelected = false;
      }
    });

    // Enable/disable continue button
    $("#shipping-continue-btn").prop(
      "disabled",
      !(allRoutesHaveShipping && allDropOffPointsSelected)
    );
  }

  // Load shipping options for a specific route
  function loadShippingOptions(routeIndex) {
    // Show loading message
    $(`#shipping-options-route-${routeIndex}`).html(`
        <div class="packlink-loading">
            <div class="packlink-loading-spinner"></div>
            <p>${packlink_custom.loading_shipping_options ||
      "Loading shipping options..."
      }</p>
        </div>
    `);

    // Get route data
    const originCountry = $(`#origin_country_${routeIndex}`).data("iso");
    const originPostalCode = $(`#origin_postal_code_${routeIndex}`).data(
      "postcode"
    );
    const originCity = $(`#origin_city_${routeIndex}`).val();
    const originAddress = $(`#origin_address_${routeIndex}`).val();

    const destinationCountry = $(`#destination_country_${routeIndex}`).data(
      "iso"
    );
    const destinationPostalCode = $(
      `#destination_postal_code_${routeIndex}`
    ).data("postcode");
    const destinationCity = $(`#destination_city_${routeIndex}`).val();
    const destinationAddress = $(`#destination_address_${routeIndex}`).val();

    const collectionDate = $(`#collection_date_${routeIndex}`).val();
    const collectionTime = $(`#collection_time_${routeIndex}`).val() || "12:00";

    // Get packages data
    let packages = [];
    $(".package").each(function () {
      const packageIndex = $(this).data("package-index");

      packages.push({
        weight: $("#weight_" + packageIndex).val(),
        length: $("#length_" + packageIndex).val(),
        width: $("#width_" + packageIndex).val(),
        height: $("#height_" + packageIndex).val(),
      });
    });

    // Get sender and recipient data from the associated contact form if available
    let senderName = "";
    let senderEmail = "";
    let senderPhone = "";
    let senderCompany = "";
    let recipientName = "";
    let recipientEmail = "";
    let recipientPhone = "";
    let recipientCompany = "";

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
        packages: JSON.stringify(packages),
      },
      success: function (response) {
        if (response.success) {
          displayShippingOptions(routeIndex, response.data);
        } else {
          $(`#shipping-options-route-${routeIndex}`).html(`
                    <div class="pl-error-message">
                        <p>${response.data.message ||
            packlink_custom.no_shipping_options ||
            "No shipping options available for this route"
            }</p>
                    </div>
                `);
        }
      },
      error: function () {
        $(`#shipping-options-route-${routeIndex}`).html(`
                <div class="pl-error-message">
                    <p>${packlink_custom.error_connecting_server ||
          "Error connecting to server"
          }</p>
                </div>
            `);
      },
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
      showError(
        packlink_custom.no_shipping_options || "No shipping options available",
        routeIndex
      );
      return;
    }

    // Get the correct container
    const container = document.querySelector(
      `#shipping-options-list-${routeIndex}`
    );
    if (!container) return;

    // Clear existing options
    container.innerHTML = "";

    // Create shipping options list
    const optionsList = document.createElement("ul");
    optionsList.className = "shipping-options";

    options.forEach((option, index) => {
      const li = document.createElement("li");
      li.className = index >= 6 ? "hidden-option" : "";

      // Format delivery time and first delivery date
      let deliveryInfo = "";
      if (option.deliveryTime) {
        deliveryInfo += `<p>Delivery time: ${option.deliveryTime}</p>`;
      }
      if (option.firstDeliveryDate) {
        const formattedDate = formatDate(option.firstDeliveryDate);
        deliveryInfo += `<p>Estimated delivery: ${formattedDate}</p>`;
      }

      // Create option content
      li.innerHTML = `
                <div class="option-details">
                    <img src="${option.logoUrl || packlink_custom.default_carrier_logo
        }" alt="${option.carrierName}">
                    <div class="option-info">
                        <h4>${option.carrierName}</h4>
                        <p>${option.serviceName}</p>
                        ${deliveryInfo}
                        ${option.isDropOff
          ? '<span class="drop-off-indicator">Drop-off required</span>'
          : ""
        }
                    </div>
                </div>
                <div class="option-price">${formatPriceWithCurrency(
          option
        )}</div>
                <button type="button" class="select-shipping" 
                    data-id="${option.id}"
                    data-price="${option.price}"
                    data-carrier="${option.carrierName}"
                    data-service="${option.serviceName}"
                    data-is-dropoff="${option.isDropOff ? "yes" : "no"}">
                    ${packlink_custom.select_button_text ||
        "Select this service"
        }
                </button>
            `;

      // Add click handler for shipping selection
      const selectButton = li.querySelector(".select-shipping");
      selectButton.addEventListener("click", () => {
        // Store selected shipping option
        selectedShippingOptions[routeIndex] = {
          id: option.id,
          price: option.price,
          carrier: option.carrierName,
          service: option.serviceName,
          isDropOff: option.isDropOff,
          currency: option.currency,
          currency_info: option.currency_info,
        };

        // Remove selected class from all options
        optionsList
          .querySelectorAll("li")
          .forEach((opt) => opt.classList.remove("selected"));
        // Add selected class to clicked option
        li.classList.add("selected");

        if (option.isDropOff) {
          showDropOffPicker(option.id, routeIndex);
        }

        // Check if all routes have shipping options
        checkAllRoutesHaveShipping();
      });

      optionsList.appendChild(li);
    });

    container.appendChild(optionsList);

    // Add "Show More" button if there are more than 6 options
    if (options.length > 6) {
      const showMoreButton = document.createElement("button");
      showMoreButton.className = "show-more-shipping";
      showMoreButton.type = "button"; // Add this to prevent form submission
      showMoreButton.innerHTML = `
                ${packlink_custom.show_more_text || "Show More Options"}
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            `;

      let expanded = false;
      showMoreButton.addEventListener("click", (e) => {
        // Prevent default behavior and stop propagation
        e.preventDefault();
        e.stopPropagation();

        const hiddenOptions = container.querySelectorAll(".hidden-option");
        hiddenOptions.forEach((option) => option.classList.toggle("show"));
        expanded = !expanded;
        showMoreButton.classList.toggle("expanded");
        showMoreButton.innerHTML = expanded
          ? `
                    ${packlink_custom.show_less_text || "Show Less Options"}
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                `
          : `
                    ${packlink_custom.show_more_text || "Show More Options"}
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                `;

        return false; // Add this to ensure the event doesn't bubble up
      });

      container.appendChild(showMoreButton);
    }

    // If there was a previously selected option, reselect it
    if (selectedShippingOptions[routeIndex]) {
      const selectedOption = optionsList.querySelector(
        `[data-id="${selectedShippingOptions[routeIndex].id}"]`
      );
      if (selectedOption) {
        selectedOption.closest("li").classList.add("selected");
      }
    }
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

  // Format price with currency information from shipping option
  function formatPriceWithCurrency(option) {
    if (!option) return "";

    // Check if currency info exists (from multi-currency implementation)
    if (option.currency_info && option.currency_info.is_multi_currency_active) {
      const currency =
        option.currency_info.current_currency || option.currency || "EUR";
      return formatPrice(option.price, currency);
    }

    // Fallback to standard formatting
    return formatPrice(option.price, option.currency || "EUR");
  }

  // Format date for display
  function formatDate(dateString) {
    if (!dateString) return "";

    const date = new Date(dateString);
    return date.toLocaleDateString(undefined, {
      weekday: "long",
      year: "numeric",
      month: "long",
      day: "numeric",
    });
  }

  // Show drop-off picker
  function showDropOffPicker(shippingOptionId, routeIndex) {
    // Get destination country and postal code
    const country = $(`#destination_country_${routeIndex}`).data("iso");
    const postalCode = $(`#destination_postal_code_${routeIndex}`).data(
      "postcode"
    );

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
          '<p class="error-message">' +
          packlink_custom.error_loading_drop_off_locations +
          "</p>"
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
                    <div class="pl-drop-off-location-name">${location.name
        }</div>
                    <div class="pl-drop-off-location-address">${location.address
        }</div>
                    <div class="pl-drop-off-location-city">${location.city}, ${location.zip
        }</div>
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
      $(`#shipping-options-list-${routeIndex} li.selected .drop-off-indicator`)
        .html(
          packlink_custom.drop_off_point_selected || "Drop-off point selected"
        )
        .addClass("drop-off-selected");

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
      const originCountry = $(
        `#origin_country_${routeIndex} option:selected`
      ).text();
      const originPostal = $(`#origin_postal_code_${routeIndex}`).val();
      const originAddress = $(`#origin_address_${routeIndex}`).val();

      const destinationCountry = $(
        `#destination_country_${routeIndex} option:selected`
      ).text();
      const destinationPostal = $(
        `#destination_postal_code_${routeIndex}`
      ).val();
      const destinationAddress = $(`#destination_address_${routeIndex}`).val();

      const collectionDate = $(`#collection_date_${routeIndex}`).val();

      // Create route summary
      const routeSummary = $('<div class="route-summary"></div>');
      routeSummary.html(`
                <h5>${packlink_custom.route_text || "Route"} #${index + 1}</h5>
                <p><strong>${packlink_custom.origin_text || "Origin"
        }:</strong> ${originAddress}, ${originPostal}, ${originCountry}</p>
                <p><strong>${packlink_custom.destination_text || "Destination"
        }:</strong> ${destinationAddress}, ${destinationPostal}, ${destinationCountry}</p>
                <p><strong>${packlink_custom.collection_date_text || "Collection Date"
        }:</strong> ${formatDate(collectionDate)}</p>
                <p><strong>${packlink_custom.shipping_method_text || "Shipping Method"
        }:</strong> ${selectedShippingOptions[routeIndex] ? selectedShippingOptions[routeIndex].carrier + " - " + selectedShippingOptions[routeIndex].service : "Not selected"}</p>
                <p><strong>${packlink_custom.price_text || "Price"
        }:</strong> ${selectedShippingOptions[routeIndex] ? formatPriceWithCurrency(selectedShippingOptions[routeIndex]) : "€0.00"}</p>
            `);

      // Add contact information for this route
      routeSummary.append(`
                <div class="summary-contact">
                    <div class="summary-sender">
                        <h6>${packlink_custom.sender_text || "Sender"}</h6>
                        <p>${$(`#sender_name_${routeIndex}`).val()}</p>
                        <p>${$(`#sender_email_${routeIndex}`).val()}</p>
                        <p>${$(`#sender_phone_${routeIndex}`).val()}</p>
                        ${$(`#sender_company_${routeIndex}`).val()
          ? "<p>" +
          $(`#sender_company_${routeIndex}`).val() +
          "</p>"
          : ""
        }
                    </div>
                    <div class="summary-recipient">
                        <h6>${packlink_custom.recipient_text || "Recipient"
        }</h6>
                        <p>${$(`#recipient_name_${routeIndex}`).val()}</p>
                        <p>${$(`#recipient_email_${routeIndex}`).val()}</p>
                        <p>${$(`#recipient_phone_${routeIndex}`).val()}</p>
                        ${$(`#recipient_company_${routeIndex}`).val()
          ? "<p>" +
          $(`#recipient_company_${routeIndex}`).val() +
          "</p>"
          : ""
        }
                    </div>
                </div>
            `);

      // Add drop-off details if applicable
      if (
        selectedShippingOptions[routeIndex] &&
        selectedShippingOptions[routeIndex].isDropOff &&
        selectedDropOffPoints[routeIndex]
      ) {
        const dropOffDetails = selectedDropOffPoints[routeIndex].details;
        routeSummary.append(`
                    <div class="drop-off-summary">
                        <p><strong>${packlink_custom.drop_off_point_text ||
          "Drop-off Point"
          }:</strong> ${dropOffDetails.name}</p>
                        <p>${dropOffDetails.address}, ${dropOffDetails.city}, ${dropOffDetails.zip
          }</p>
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
    summaryPackages.html("");

    packageElements.each(function (index) {
      const packageIndex = $(this).data("package-index");
      const weight = $("#weight_" + packageIndex).val();
      const length = $("#length_" + packageIndex).val();
      const width = $("#width_" + packageIndex).val();
      const height = $("#height_" + packageIndex).val();

      const packageSummary = $('<div class="package-summary"></div>');
      packageSummary.html(`
                <h5>${packlink_custom.package_text || "Package"
        } ${index + 1}</h5>
                <p>${packlink_custom.weight_text || "Weight"}: ${weight} kg</p>
                <p>${packlink_custom.dimensions_text || "Dimensions"
        }: ${length} × ${width} × ${height} cm</p>
            `);

      summaryPackages.append(packageSummary);
    });

    // Total price
    let totalPrice = 0;
    let currency = "EUR"; // Default currency
    Object.keys(selectedShippingOptions).forEach(function (routeIndex) {
      const option = selectedShippingOptions[routeIndex];
      totalPrice += parseFloat(option.price);

      // Get currency from the first shipping option (assuming all have same currency)
      if (option.currency_info && option.currency_info.current_currency) {
        currency = option.currency_info.current_currency;
      } else if (option.currency) {
        currency = option.currency;
      }
    });

    // Format total price
    $("#summary-total-price").text(formatPrice(totalPrice, currency));
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
      $(".btn-submit")
        .prop("disabled", true)
        .text(packlink_custom.processing_text || "Processing...");

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
          shipping_details: JSON.stringify(shippingDetails),
        },
        success: function (response) {
          if (response.success) {
            // Clear localStorage on successful submission
            localStorage.removeItem("packlink_form_data");
            console.log(
              "Form data cleared from localStorage after successful submission"
            );

            // Redirect immediately
            window.location.href = response.data.redirect;
          } else {
            alert(
              response.data.message ||
              packlink_custom.error_adding_to_cart ||
              "Error adding shipping to cart"
            );
            $(".btn-submit")
              .prop("disabled", false)
              .text(packlink_custom.submit_button_text || "Submit");
          }
        },
        error: function () {
          alert(
            packlink_custom.error_connecting_server ||
            "Error connecting to server"
          );
          $(".btn-submit")
            .prop("disabled", false)
            .text(packlink_custom.submit_button_text || "Submit");
        },
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
        origin_country: $(`#origin_country_${routeIndex}`).data("iso"),
        origin_postal_code: $(`#origin_postal_code_${routeIndex}`).data(
          "postcode"
        ),
        origin_city: $(`#origin_city_${routeIndex}`).val(),
        origin_address: $(`#origin_address_${routeIndex}`).val(),
        destination_country: $(`#destination_country_${routeIndex}`).data(
          "iso"
        ),
        destination_postal_code: $(
          `#destination_postal_code_${routeIndex}`
        ).data("postcode"),
        destination_city: $(`#destination_city_${routeIndex}`).val(),
        destination_address: $(`#destination_address_${routeIndex}`).val(),
        collection_date: $(`#collection_date_${routeIndex}`).val(),
        collection_time: $(`#collection_time_${routeIndex}`).val() || "12:00",
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
        recipient_company: $(`#recipient_company_${routeIndex}`).val(),
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
        height: $("#height_" + packageIndex).val(),
      });
    });

    // Calculate total price
    let totalPrice = 0;
    let currency = "EUR"; // Default currency
    Object.keys(selectedShippingOptions).forEach(function (routeIndex) {
      const option = selectedShippingOptions[routeIndex];
      totalPrice += parseFloat(option.price);

      // Get currency from the first shipping option (assuming all have same currency)
      if (option.currency_info && option.currency_info.current_currency) {
        currency = option.currency_info.current_currency;
      } else if (option.currency) {
        currency = option.currency;
      }
    });

    // Create shipping details object
    return {
      routes: routes,
      packages: packages,
      total_price: totalPrice.toFixed(2),
      currency: currency,
    };
  }

  function validateForm() {
    let isValid = true;

    // Validate routes section
    if ($("#routes-container .route-set").length === 0) {
      alert(
        packlink_custom.please_add_route || "Please add at least one route"
      );
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

        if (
          !$(`#sender_email_${routeIndex}`).val() ||
          !validateEmail($(`#sender_email_${routeIndex}`).val())
        ) {
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

        if (
          !$(`#recipient_email_${routeIndex}`).val() ||
          !validateEmail($(`#recipient_email_${routeIndex}`).val())
        ) {
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
      alert(
        packlink_custom.please_add_package || "Please add at least one package"
      );
      return false;
    }

    $(".package").each(function () {
      const packageIndex = $(this).data("package-index");

      // Check weight
      if (
        !$("#weight_" + packageIndex).val() ||
        parseFloat($("#weight_" + packageIndex).val()) <= 0
      ) {
        $("#weight_" + packageIndex).addClass("error");
        isValid = false;
      } else {
        $("#weight_" + packageIndex).removeClass("error");
      }

      // Check dimensions
      if (
        !$("#length_" + packageIndex).val() ||
        parseFloat($("#length_" + packageIndex).val()) <= 0
      ) {
        $("#length_" + packageIndex).addClass("error");
        isValid = false;
      } else {
        $("#length_" + packageIndex).removeClass("error");
      }

      if (
        !$("#width_" + packageIndex).val() ||
        parseFloat($("#width_" + packageIndex).val()) <= 0
      ) {
        $("#width_" + packageIndex).addClass("error");
        isValid = false;
      } else {
        $("#width_" + packageIndex).removeClass("error");
      }

      if (
        !$("#height_" + packageIndex).val() ||
        parseFloat($("#height_" + packageIndex).val()) <= 0
      ) {
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
      alert(
        packlink_custom.please_select_shipping_option ||
        "Please select a shipping option for each route"
      );
      return false;
    }

    // Check if drop-off points are selected where required
    for (let i = 0; i < routeCount; i++) {
      if (
        selectedShippingOptions[i] &&
        selectedShippingOptions[i].isDropOff &&
        !selectedDropOffPoints[i]
      ) {
        alert(
          packlink_custom.please_select_drop_off_point ||
          "Please select a drop-off point for each route that requires it"
        );
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

  // ==================== LOCALSTORAGE FUNCTIONALITY ====================

  // Get or create a session ID for this browser session
  function getOrCreateSessionId() {
    let sessionId = sessionStorage.getItem(SESSION_KEY);
    if (!sessionId) {
      sessionId = 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
      sessionStorage.setItem(SESSION_KEY, sessionId);
    }
    return sessionId;
  }

  // Check if saved data belongs to current session
  function isCurrentSession(savedSessionId) {
    const currentSessionId = sessionStorage.getItem(SESSION_KEY);
    return currentSessionId && savedSessionId === currentSessionId;
  }

  // Initialize localStorage functionality
  // NOTE: localStorage caching has been DISABLED to fix stale route data issue
  // The form now always starts fresh without restoring previous data
  function initializeLocalStorage() {
    // Clear any existing cached data immediately to prevent stale data
    try {
      localStorage.removeItem(STORAGE_KEY);
      localStorage.removeItem("packlink_form_data"); // Legacy key
      sessionStorage.removeItem(SESSION_KEY);
      debugLog("Cleared all cached form data on initialization");
    } catch (error) {
      debugLog("Failed to clear cached data:", error);
    }

    // Auto-save and restore functionality disabled
    // setupAutoSave() - disabled
    // restoreFormData() - disabled

    // Clear localStorage on successful form submission (keep this for cleanup)
    setupClearOnSuccess();
  }

  // Check for and clear stale session data
  function checkAndClearStaleSession() {
    try {
      const savedData = localStorage.getItem(STORAGE_KEY);
      if (!savedData) return;

      const formData = JSON.parse(savedData);

      // If data has a sessionId and it doesn't match current session, clear it
      if (formData.sessionId && !isCurrentSession(formData.sessionId)) {
        debugLog('Clearing stale data from different session');
        localStorage.removeItem(STORAGE_KEY);
        return;
      }

      // Also check for expired data
      if (Date.now() - formData.timestamp > CACHE_EXPIRY_MS) {
        debugLog('Clearing expired form data');
        localStorage.removeItem(STORAGE_KEY);
      }
    } catch (error) {
      debugLog('Error checking session data:', error);
      localStorage.removeItem(STORAGE_KEY);
    }
  }

  // Save form data to localStorage
  // NOTE: DISABLED - localStorage caching removed to fix stale route data issue
  function saveFormData() {
    // No-op: Auto-save disabled to prevent stale data issues
    // Form data is no longer persisted to localStorage
    debugLog("saveFormData called but disabled - not saving to localStorage");
    return;
  }

  // Restore form data from localStorage
  // NOTE: DISABLED - localStorage caching removed to fix stale route data issue
  function restoreFormData() {
    // No-op: Form restoration disabled to prevent stale data issues
    // Form always starts fresh without any previously saved data
    debugLog("restoreFormData called but disabled - not restoring from localStorage");
    return;
  }

  // Collect routes data
  function collectRoutesData() {
    const routes = [];
    $(".route-set").each(function () {
      const routeIndex = $(this).data("route-index");
      const routeData = {
        origin_country_search: $(`#origin_country_search_${routeIndex}`).val(),
        origin_country: $(`#origin_country_${routeIndex}`).val(),
        origin_country_iso: $(`#origin_country_${routeIndex}`).data("iso"),
        origin_postal_code: $(`#origin_postal_code_${routeIndex}`).val(),
        origin_postal_code_data: $(`#origin_postal_code_${routeIndex}`).data(
          "postcode"
        ),
        origin_city: $(`#origin_city_${routeIndex}`).val(),
        origin_address: $(`#origin_address_${routeIndex}`).val(),

        destination_country_search: $(
          `#destination_country_search_${routeIndex}`
        ).val(),
        destination_country: $(`#destination_country_${routeIndex}`).val(),
        destination_country_iso: $(`#destination_country_${routeIndex}`).data(
          "iso"
        ),
        destination_postal_code: $(
          `#destination_postal_code_${routeIndex}`
        ).val(),
        destination_postal_code_data: $(
          `#destination_postal_code_${routeIndex}`
        ).data("postcode"),
        destination_city: $(`#destination_city_${routeIndex}`).val(),
        destination_address: $(`#destination_address_${routeIndex}`).val(),

        collection_date: $(`#collection_date_${routeIndex}`).val(),
        collection_time: $(`#collection_time_${routeIndex}`).val(),
      };
      routes.push(routeData);
    });
    return routes;
  }

  // Collect packages data
  function collectPackagesData() {
    const packages = [];
    $(".package").each(function () {
      const packageIndex = $(this).data("package-index");
      const packageData = {
        weight: $(`#weight_${packageIndex}`).val(),
        length: $(`#length_${packageIndex}`).val(),
        width: $(`#width_${packageIndex}`).val(),
        height: $(`#height_${packageIndex}`).val(),
      };
      packages.push(packageData);
    });
    return packages;
  }

  // Collect contacts data
  function collectContactsData() {
    const contacts = [];
    $(".route-contact-set").each(function () {
      const routeIndex = $(this).data("route-index");
      const contactData = {
        sender_name: $(`#sender_name_${routeIndex}`).val(),
        sender_email: $(`#sender_email_${routeIndex}`).val(),
        sender_phone: $(`#sender_phone_${routeIndex}`).val(),
        sender_company: $(`#sender_company_${routeIndex}`).val(),

        recipient_name: $(`#recipient_name_${routeIndex}`).val(),
        recipient_email: $(`#recipient_email_${routeIndex}`).val(),
        recipient_phone: $(`#recipient_phone_${routeIndex}`).val(),
        recipient_company: $(`#recipient_company_${routeIndex}`).val(),
      };
      contacts.push(contactData);
    });
    return contacts;
  }

  // Restore routes data
  function restoreRoutesData(routes) {
    routes.forEach((routeData, index) => {
      // Make sure route exists
      if ($(`#origin_country_search_${index}`).length === 0) return;

      // Restore origin data
      if (routeData.origin_country_search) {
        $(`#origin_country_search_${index}`).val(
          routeData.origin_country_search
        );
      }
      if (routeData.origin_country) {
        $(`#origin_country_${index}`).val(routeData.origin_country);
      }
      if (routeData.origin_country_iso) {
        $(`#origin_country_${index}`).data("iso", routeData.origin_country_iso);
      }
      if (routeData.origin_postal_code) {
        $(`#origin_postal_code_${index}`).val(routeData.origin_postal_code);
      }
      if (routeData.origin_postal_code_data) {
        $(`#origin_postal_code_${index}`).data(
          "postcode",
          routeData.origin_postal_code_data
        );
      }
      if (routeData.origin_city) {
        $(`#origin_city_${index}`).val(routeData.origin_city);
      }
      if (routeData.origin_address) {
        $(`#origin_address_${index}`).val(routeData.origin_address);
      }

      // Restore destination data
      if (routeData.destination_country_search) {
        $(`#destination_country_search_${index}`).val(
          routeData.destination_country_search
        );
      }
      if (routeData.destination_country) {
        $(`#destination_country_${index}`).val(routeData.destination_country);
      }
      if (routeData.destination_country_iso) {
        $(`#destination_country_${index}`).data(
          "iso",
          routeData.destination_country_iso
        );
      }
      if (routeData.destination_postal_code) {
        $(`#destination_postal_code_${index}`).val(
          routeData.destination_postal_code
        );
      }
      if (routeData.destination_postal_code_data) {
        $(`#destination_postal_code_${index}`).data(
          "postcode",
          routeData.destination_postal_code_data
        );
      }
      if (routeData.destination_city) {
        $(`#destination_city_${index}`).val(routeData.destination_city);
      }
      if (routeData.destination_address) {
        $(`#destination_address_${index}`).val(routeData.destination_address);
      }

      // Restore collection data
      if (routeData.collection_date) {
        $(`#collection_date_${index}`).val(routeData.collection_date);
      }
      if (routeData.collection_time) {
        $(`#collection_time_${index}`).val(routeData.collection_time);
      }
    });
  }

  // Restore packages data
  function restorePackagesData(packages) {
    packages.forEach((packageData, index) => {
      // Make sure package exists
      if ($(`#weight_${index}`).length === 0) return;

      if (packageData.weight) {
        $(`#weight_${index}`).val(packageData.weight);
      }
      if (packageData.length) {
        $(`#length_${index}`).val(packageData.length);
      }
      if (packageData.width) {
        $(`#width_${index}`).val(packageData.width);
      }
      if (packageData.height) {
        $(`#height_${index}`).val(packageData.height);
      }
    });
  }

  // Restore contacts data
  function restoreContactsData(contacts) {
    contacts.forEach((contactData, index) => {
      // Make sure contact form exists
      if ($(`#sender_name_${index}`).length === 0) return;

      // Restore sender data
      if (contactData.sender_name) {
        $(`#sender_name_${index}`).val(contactData.sender_name);
      }
      if (contactData.sender_email) {
        $(`#sender_email_${index}`).val(contactData.sender_email);
      }
      if (contactData.sender_phone) {
        $(`#sender_phone_${index}`).val(contactData.sender_phone);
      }
      if (contactData.sender_company) {
        $(`#sender_company_${index}`).val(contactData.sender_company);
      }

      // Restore recipient data
      if (contactData.recipient_name) {
        $(`#recipient_name_${index}`).val(contactData.recipient_name);
      }
      if (contactData.recipient_email) {
        $(`#recipient_email_${index}`).val(contactData.recipient_email);
      }
      if (contactData.recipient_phone) {
        $(`#recipient_phone_${index}`).val(contactData.recipient_phone);
      }
      if (contactData.recipient_company) {
        $(`#recipient_company_${index}`).val(contactData.recipient_company);
      }
    });
  }

  // Setup auto-save on input changes
  // NOTE: DISABLED - localStorage caching removed to fix stale route data issue
  function setupAutoSave() {
    // No-op: Auto-save disabled to prevent stale data issues
    // Form changes are no longer automatically saved to localStorage
    debugLog("setupAutoSave called but disabled - auto-save not active");
    return;
  }

  // Setup clear localStorage on successful submission
  function setupClearOnSuccess() {
    // Monitor for successful form submissions
    $(document).on("ajax:success", function () {
      localStorage.removeItem(STORAGE_KEY);
      sessionStorage.removeItem(SESSION_KEY);
      debugLog("Form data cleared from localStorage after successful submission");
    });

    // Also clear on window unload if redirect is happening
    $(window).on("beforeunload", function () {
      // Only clear if we're being redirected after success
      if (
        document.referrer.includes("success") ||
        window.location.href.includes("checkout")
      ) {
        localStorage.removeItem(STORAGE_KEY);
        sessionStorage.removeItem(SESSION_KEY);
      }
    });
  }

  // Manual function to clear saved data (can be called from console)
  function clearSavedFormData() {
    localStorage.removeItem(STORAGE_KEY);
    debugLog("Saved form data cleared manually");
  }

  // Force start a new session - clears all state and resets the form
  function forceNewSession() {
    debugLog("Starting new session - clearing all form data");

    // Clear storage
    sessionStorage.removeItem(SESSION_KEY);
    localStorage.removeItem(STORAGE_KEY);

    // Reset internal state
    selectedShippingOptions = {};
    selectedDropOffPoints = {};
    routeCounter = 0;
    packageCounter = 0;

    // Clear form fields
    clearFormFields();

    // Clear existing routes
    $("#routes-container").empty();

    // Re-add first route
    addRoute();

    // Reinitialize packages
    initializePackages();

    // Generate new session ID
    getOrCreateSessionId();

    // Scroll to top of form
    $('html, body').animate({
      scrollTop: $(".packlink-custom-shipping-container").offset().top - 50,
    }, 300);
  }

  // Expose functions globally for user-triggered resets
  window.clearReluggzFormData = clearSavedFormData;
  window.startNewReluggzSession = forceNewSession;

  // Keep old name for backwards compatibility
  window.clearPacklinkFormData = clearSavedFormData;

  // ==================== END LOCALSTORAGE FUNCTIONALITY ====================

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

  // Function to clear all form fields to prevent cache/default interference
  function clearFormFields() {
    debugLog("Clearing all form fields to prevent cache interference");

    // Clear all country search inputs
    $('input[id*="country_search"]').val("");

    // Clear all hidden country inputs
    $('input[id*="country_"][type="hidden"]')
      .val("")
      .removeData("iso")
      .removeAttr("data-iso");

    // Clear any error states
    $(".error").removeClass("error");
    $(".error-message").remove();
    $(".valid").removeClass("valid");

    // Clear any dropdown selections
    $(".country-option.selected").removeClass("selected");
    $(".country-options.active").removeClass("active");
  }
});
