import { validateForm } from '../utils/validation.js';

export function initForm() {
  const form = document.getElementById('shipping-form');
  const formSections = document.querySelectorAll('.form-section');
  const progressSteps = document.querySelectorAll('.progress-step');
  const successModal = document.getElementById('success-modal');
  
  if (!form) return;

  // Setup navigation buttons
  setupNavigationButtons();
  
  // Setup form submission
  form.addEventListener('submit', handleFormSubmit);
  
  // Setup modal close button
  const closeModalBtn = document.querySelector('.btn-close-modal');
  if (closeModalBtn) {
    closeModalBtn.addEventListener('click', () => {
      successModal.classList.remove('active');
    });
  }

  function setupNavigationButtons() {
    const nextButtons = document.querySelectorAll('.btn-next');
    const prevButtons = document.querySelectorAll('.btn-prev');
    
    nextButtons.forEach(button => {
      button.addEventListener('click', () => {
        const currentSection = button.closest('.form-section');
        const nextSectionId = button.dataset.next;
        const nextSection = document.getElementById(nextSectionId);
        
        if (!nextSection) return;
        
        // Validate the current section before proceeding
        if (validateSectionFields(currentSection)) {
          navigateToSection(nextSection);
          
          // Update progress tracker
          updateProgressTracker(nextSectionId);
          
          // Update review summary when navigating to review section
          if (nextSectionId === 'review-section') {
            updateReviewSummary();
          }
        }
      });
    });
    
    prevButtons.forEach(button => {
      button.addEventListener('click', () => {
        const prevSectionId = button.dataset.prev;
        const prevSection = document.getElementById(prevSectionId);
        
        if (prevSection) {
          navigateToSection(prevSection);
          
          // Update progress tracker
          updateProgressTracker(prevSectionId);
        }
      });
    });
  }
  
  function navigateToSection(section) {
    formSections.forEach(s => {
      s.classList.remove('active');
    });
    
    section.classList.add('active');
    
    // Scroll to top of the form
    section.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
  
  function updateProgressTracker(sectionId) {
    const stepNumber = {
      'route-section': 1,
      'details-section': 2,
      'packages-section': 3,
      'review-section': 4
    };
    
    const currentStep = stepNumber[sectionId];
    
    progressSteps.forEach(step => {
      const stepNum = parseInt(step.dataset.step);
      
      if (stepNum < currentStep) {
        step.classList.add('completed');
        step.classList.remove('active');
      } else if (stepNum === currentStep) {
        step.classList.add('active');
      } else {
        step.classList.remove('active', 'completed');
      }
    });
  }
  
  function validateSectionFields(section) {
    const inputs = section.querySelectorAll('input, select');
    let isValid = true;
    
    inputs.forEach(input => {
      if (input.required && !input.value) {
        isValid = false;
        highlightInvalidField(input);
      } else {
        removeFieldHighlight(input);
      }
    });
    
    return isValid;
  }
  
  function highlightInvalidField(field) {
    field.classList.add('invalid');
    field.style.borderColor = 'var(--color-error)';
    
    const label = field.previousElementSibling;
    if (label && label.tagName === 'LABEL') {
      label.style.color = 'var(--color-error)';
    }
    
    field.addEventListener('input', function onInput() {
      removeFieldHighlight(field);
      field.removeEventListener('input', onInput);
    });
  }
  
  function removeFieldHighlight(field) {
    field.classList.remove('invalid');
    field.style.borderColor = '';
    
    const label = field.previousElementSibling;
    if (label && label.tagName === 'LABEL') {
      label.style.color = '';
    }
  }
  
  function updateReviewSummary() {
    // Origin and destination summary
    const originCountry = document.getElementById('origin-country');
    const originPostal = document.getElementById('origin-postal');
    const originAddress = document.getElementById('origin-address');
    
    const destinationCountry = document.getElementById('destination-country');
    const destinationPostal = document.getElementById('destination-postal');
    const destinationAddress = document.getElementById('destination-address');
    
    const collectionDate = document.getElementById('collection-date');
    
    document.getElementById('summary-origin').textContent = `${originAddress.value}, ${originPostal.value}, ${getCountryName(originCountry.value)}`;
    document.getElementById('summary-destination').textContent = `${destinationAddress.value}, ${destinationPostal.value}, ${getCountryName(destinationCountry.value)}`;
    document.getElementById('summary-date').textContent = formatDate(collectionDate.value);
    document.getElementById('summary-transport-type').textContent = document.querySelector('.route-type .value').textContent;
    
    // Sender and recipient summary
    document.getElementById('summary-sender-name').textContent = document.getElementById('sender-name').value;
    document.getElementById('summary-sender-email').textContent = document.getElementById('sender-email').value;
    document.getElementById('summary-sender-phone').textContent = document.getElementById('sender-phone').value;
    document.getElementById('summary-sender-company').textContent = document.getElementById('sender-company').value || 'N/A';
    
    document.getElementById('summary-recipient-name').textContent = document.getElementById('recipient-name').value;
    document.getElementById('summary-recipient-email').textContent = document.getElementById('recipient-email').value;
    document.getElementById('summary-recipient-phone').textContent = document.getElementById('recipient-phone').value;
    document.getElementById('summary-recipient-company').textContent = document.getElementById('recipient-company').value || 'N/A';
    
    // Package summary
    const packagesContainer = document.getElementById('packages-container');
    const packageElements = packagesContainer.querySelectorAll('.package');
    const summaryPackages = document.getElementById('summary-packages');
    
    // Clear previous package summaries
    summaryPackages.innerHTML = '';
    
    packageElements.forEach((packageElement, index) => {
      const packageId = packageElement.dataset.packageId;
      const weight = document.getElementById(`weight-${packageId}`).value;
      const length = document.getElementById(`length-${packageId}`).value;
      const width = document.getElementById(`width-${packageId}`).value;
      const height = document.getElementById(`height-${packageId}`).value;
      
      const packageSummary = document.createElement('div');
      packageSummary.classList.add('package-summary');
      packageSummary.innerHTML = `
        <h5>Package #${index + 1}</h5>
        <p>Weight: ${weight} kg</p>
        <p>Dimensions: ${length} x ${width} x ${height} cm</p>
      `;
      
      summaryPackages.appendChild(packageSummary);
    });
  }
  
  function handleFormSubmit(event) {
    event.preventDefault();
    
    if (validateForm()) {
      // Show success modal
      if (successModal) {
        successModal.classList.add('active');
      }
      
      // In a real application, you would submit the form data to the server here
      console.log('Form submitted successfully');
    }
  }
  
  function getCountryName(countryCode) {
    const countries = {
      'US': 'United States',
      'CA': 'Canada',
      'UK': 'United Kingdom',
      'AU': 'Australia',
      'DE': 'Germany',
      'FR': 'France'
    };
    
    return countries[countryCode] || countryCode;
  }
  
  function formatDate(dateString) {
    if (!dateString) return '';
    
    const date = new Date(dateString);
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    return date.toLocaleDateString('en-US', options);
  }
}