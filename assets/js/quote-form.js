/**
 * Quote Form JavaScript
 *
 * Handles form submission and integrates with main form's country selection functionality
 */

class PacklinkQuoteForm {
  constructor() {
    this.form = document.getElementById("packlink-quote-form");

    this.init();
  }

  init() {
    if (!this.form) return;

    this.bindEvents();
    this.setupRealtimeValidation();
  }

  bindEvents() {
    // Form submission
    this.form.addEventListener("submit", (e) => {
      this.handleFormSubmit(e);
    });
  }

  setupRealtimeValidation() {
    // Clear validation errors on input
    const originSearchInput = document.getElementById(
      "quote-origin-country-search"
    );
    const destinationSearchInput = document.getElementById(
      "quote-destination-country-search"
    );

    if (originSearchInput) {
      originSearchInput.addEventListener("input", () => {
        this.clearFieldError(originSearchInput);
      });
    }

    if (destinationSearchInput) {
      destinationSearchInput.addEventListener("input", () => {
        this.clearFieldError(destinationSearchInput);
      });
    }

    // Clear validation errors when countries are selected
    const originHiddenInput = document.getElementById("quote-origin-country");
    const destinationHiddenInput = document.getElementById(
      "quote-destination-country"
    );

    if (originHiddenInput) {
      originHiddenInput.addEventListener("change", () => {
        this.clearFieldError(originSearchInput);
        this.checkBothFieldsFilled();
      });
    }

    if (destinationHiddenInput) {
      destinationHiddenInput.addEventListener("change", () => {
        this.clearFieldError(destinationSearchInput);
        this.checkBothFieldsFilled();
      });
    }
  }

  checkBothFieldsFilled() {
    const originInput = document.getElementById("quote-origin-country");
    const destinationInput = document.getElementById(
      "quote-destination-country"
    );

    if (
      originInput &&
      destinationInput &&
      originInput.value &&
      destinationInput.value
    ) {
      // Both fields are filled, clear all validation errors
      this.clearErrorMessages();
    }
  }

  clearFieldError(input) {
    if (input) {
      input.classList.remove("error");
      const errorMessage = input.parentNode.querySelector(".error-message");
      if (errorMessage) {
        errorMessage.remove();
      }
    }
  }

  handleFormSubmit(e) {
    e.preventDefault();

    if (!this.validateForm()) {
      return;
    }

    // Get selected countries from hidden inputs (contains country IDs)
    const originCountryId = document.getElementById(
      "quote-origin-country"
    ).value;
    const destinationCountryId = document.getElementById(
      "quote-destination-country"
    ).value;

    // Get country codes from data attributes
    const originCountryCode = document
      .getElementById("quote-origin-country")
      .getAttribute("data-iso");
    const destinationCountryCode = document
      .getElementById("quote-destination-country")
      .getAttribute("data-iso");

    // Get country names from search inputs
    const originCountryName = document.getElementById(
      "quote-origin-country-search"
    ).value;
    const destinationCountryName = document.getElementById(
      "quote-destination-country-search"
    ).value;

    // Get redirect URL
    const redirectUrl = this.form.querySelector(
      'input[name="redirect_url"]'
    ).value;

    // Build redirect URL with parameters
    const urlParams = new URLSearchParams();
    urlParams.set("quote_redirect", "1");
    urlParams.set("origin_country", originCountryName);
    urlParams.set("origin_country_code", originCountryCode || originCountryId);
    urlParams.set("destination_country", destinationCountryName);
    urlParams.set(
      "destination_country_code",
      destinationCountryCode || destinationCountryId
    );

    const finalRedirectUrl = `${redirectUrl}${
      redirectUrl.includes("?") ? "&" : "?"
    }${urlParams.toString()}`;

    // Show loading state
    this.showLoadingState();

    // Redirect to the main form
    window.location.href = finalRedirectUrl;
  }

  validateForm() {
    this.clearErrorMessages();
    let isValid = true;

    // Validate origin country
    const originInput = document.getElementById("quote-origin-country");
    const originSearchInput = document.getElementById(
      "quote-origin-country-search"
    );

    if (
      !originInput ||
      !originInput.value ||
      !originSearchInput ||
      !originSearchInput.value.trim()
    ) {
      if (originSearchInput) {
        this.showFieldError(
          originSearchInput,
          "Please select an origin country"
        );
      }
      isValid = false;
    }

    // Validate destination country
    const destinationInput = document.getElementById(
      "quote-destination-country"
    );
    const destinationSearchInput = document.getElementById(
      "quote-destination-country-search"
    );

    if (
      !destinationInput ||
      !destinationInput.value ||
      !destinationSearchInput ||
      !destinationSearchInput.value.trim()
    ) {
      if (destinationSearchInput) {
        this.showFieldError(
          destinationSearchInput,
          "Please select a destination country"
        );
      }
      isValid = false;
    }

    // Note: Removed "Origin and destination must be different" validation as per user request

    return isValid;
  }

  showFieldError(input, message) {
    if (!input) return;

    input.classList.add("error");

    // Remove existing error message
    const existingError = input.parentNode.querySelector(".error-message");
    if (existingError) {
      existingError.remove();
    }

    // Add new error message
    const errorElement = document.createElement("div");
    errorElement.className = "error-message";
    errorElement.textContent = message;
    input.parentNode.appendChild(errorElement);
  }

  clearErrorMessages() {
    // Remove error classes
    const errorInputs = this.form.querySelectorAll(".error");
    errorInputs.forEach((input) => input.classList.remove("error"));

    // Remove error messages
    const errorMessages = this.form.querySelectorAll(".error-message");
    errorMessages.forEach((message) => message.remove());
  }

  showLoadingState() {
    this.form.classList.add("loading");
    const submitBtn = this.form.querySelector(".get-quote-btn");
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML = "Getting Quote...";
    }
  }
}

// Initialize when DOM is ready
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", function () {
    new PacklinkQuoteForm();
  });
} else {
  new PacklinkQuoteForm();
}
