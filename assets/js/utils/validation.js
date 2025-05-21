export function validateForm() {
  const form = document.getElementById('shipping-form');
  if (!form) return false;
  
  const formFields = form.querySelectorAll('input, select');
  let isValid = true;
  
  // Clear all previous errors
  clearFormErrors();
  
  // Validate each required field
  formFields.forEach(field => {
    if (field.required && !field.value) {
      showError(field, 'This field is required');
      isValid = false;
    } else if (field.type === 'email' && field.value && !isValidEmail(field.value)) {
      showError(field, 'Please enter a valid email address');
      isValid = false;
    } else if (field.type === 'tel' && field.value && !isValidPhone(field.value)) {
      showError(field, 'Please enter a valid phone number');
      isValid = false;
    } else if (field.type === 'number' && field.value) {
      if (parseFloat(field.value) < parseFloat(field.min)) {
        showError(field, `Value must be at least ${field.min}`);
        isValid = false;
      }
    }
  });

  if (isValid) {
    // Collect form data
    const formData = {
      origin: {
        country: document.getElementById('origin-country').value,
        postalCode: document.getElementById('origin-postal').value,
        address: document.getElementById('origin-address').value
      },
      destination: {
        country: document.getElementById('destination-country').value,
        postalCode: document.getElementById('destination-postal').value,
        address: document.getElementById('destination-address').value
      },
      collectionDate: document.getElementById('collection-date').value,
      sender: {
        name: document.getElementById('sender-name').value,
        email: document.getElementById('sender-email').value,
        phone: document.getElementById('sender-phone').value,
        company: document.getElementById('sender-company').value
      },
      recipient: {
        name: document.getElementById('recipient-name').value,
        email: document.getElementById('recipient-email').value,
        phone: document.getElementById('recipient-phone').value,
        company: document.getElementById('recipient-company').value
      },
      packages: []
    };

    // Collect package data
    const packages = document.querySelectorAll('.package');
    packages.forEach(pkg => {
      const packageId = pkg.dataset.packageId;
      formData.packages.push({
        weight: document.getElementById(`weight-${packageId}`).value,
        dimensions: {
          length: document.getElementById(`length-${packageId}`).value,
          width: document.getElementById(`width-${packageId}`).value,
          height: document.getElementById(`height-${packageId}`).value
        }
      });
    });

    // Log form data
    console.log('Form Data:', formData);
  }
  
  return isValid;
}

function showError(field, message) {
  // Add error class to the field
  field.classList.add('error');
  field.style.borderColor = 'var(--color-error)';
  
  // Create error message element if it doesn't exist
  let errorElement = field.nextElementSibling;
  if (!errorElement || !errorElement.classList.contains('error-message')) {
    errorElement = document.createElement('div');
    errorElement.classList.add('error-message');
    field.parentNode.insertBefore(errorElement, field.nextSibling);
  }
  
  // Set error message text
  errorElement.textContent = message;
  errorElement.style.color = 'var(--color-error)';
  errorElement.style.fontSize = '0.75rem';
  errorElement.style.marginTop = '0.25rem';
  
  // Animate error message
  errorElement.style.opacity = '0';
  errorElement.style.transform = 'translateY(-5px)';
  
  setTimeout(() => {
    errorElement.style.opacity = '1';
    errorElement.style.transform = 'translateY(0)';
    errorElement.style.transition = 'all 0.3s ease';
  }, 10);
  
  // Add error event listener to clear error on input
  field.addEventListener('input', clearError);
}

function clearError(event) {
  const field = event.target;
  
  // Remove error class
  field.classList.remove('error');
  field.style.borderColor = '';
  
  // Remove error message
  const errorElement = field.nextElementSibling;
  if (errorElement && errorElement.classList.contains('error-message')) {
    errorElement.style.opacity = '0';
    errorElement.style.transform = 'translateY(-5px)';
    
    setTimeout(() => {
      errorElement.remove();
    }, 300);
  }
  
  // Remove event listener
  field.removeEventListener('input', clearError);
}

function clearFormErrors() {
  const errorMessages = document.querySelectorAll('.error-message');
  const errorFields = document.querySelectorAll('.error');
  
  errorMessages.forEach(element => {
    element.remove();
  });
  
  errorFields.forEach(field => {
    field.classList.remove('error');
    field.style.borderColor = '';
  });
}

function isValidEmail(email) {
  const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return regex.test(email);
}

function isValidPhone(phone) {
  // This is a simple validation - in production, you might want to use a more sophisticated approach
  const regex = /^[+]?[(]?[0-9]{3}[)]?[-\s.]?[0-9]{3}[-\s.]?[0-9]{4,6}$/;
  return regex.test(phone);
}