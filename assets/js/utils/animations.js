export function initAnimations() {
  // jQuery is loaded for animations
  if (typeof $ === 'undefined') {
    console.warn('jQuery is not loaded. Some animations may not work.');
    return;
  }

  // Form field focus animations
  $('input, select').on('focus', function() {
    $(this).parent('.form-group').addClass('focused');
  }).on('blur', function() {
    if (!$(this).val()) {
      $(this).parent('.form-group').removeClass('focused');
    }
  });

  // Pre-fill any fields that have values
  $('input, select').each(function() {
    if ($(this).val()) {
      $(this).parent('.form-group').addClass('focused');
    }
  });

  // Progress tracker animations
  $('.progress-step').on('click', function() {
    const stepNumber = $(this).data('step');
    const sectionMap = {
      1: 'route-section',
      2: 'details-section',
      3: 'packages-section',
      4: 'review-section'
    };
    
    const targetSection = sectionMap[stepNumber];
    
    if (targetSection && $(this).hasClass('completed') || $(this).hasClass('active')) {
      $('.form-section').removeClass('active');
      $(`#${targetSection}`).addClass('active');
      
      // Update progress tracker
      updateProgressUI(stepNumber);
    }
  });

  // Form navigation animations
  $('.btn-next, .btn-prev').on('click', function() {
    // Add click animation
    $(this).addClass('btn-clicked');
    setTimeout(() => {
      $(this).removeClass('btn-clicked');
    }, 300);
  });

  // Map container hover effect
  $('.map-container').hover(
    function() {
      $(this).addClass('map-hover');
    },
    function() {
      $(this).removeClass('map-hover');
    }
  );

  // Add smooth transitions between form sections
  function updateProgressUI(currentStep) {
    $('.progress-step').each(function() {
      const stepNum = parseInt($(this).data('step'));
      
      if (stepNum < currentStep) {
        $(this).addClass('completed').removeClass('active');
      } else if (stepNum === currentStep) {
        $(this).addClass('active').removeClass('completed');
      } else {
        $(this).removeClass('active completed');
      }
    });
  }

  // Animate form fields when they come into view
  animateFormFields();

  function animateFormFields() {
    $('.form-section.active .form-group').each(function(index) {
      const delay = 100 + (index * 50);
      $(this).css({
        'opacity': 0,
        'transform': 'translateY(20px)'
      });
      
      setTimeout(() => {
        $(this).css({
          'opacity': 1,
          'transform': 'translateY(0)',
          'transition': 'all 0.5s ease'
        });
      }, delay);
    });
  }

  // Re-run animations when switching sections
  const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      if (mutation.attributeName === 'class') {
        const element = mutation.target;
        if (element.classList.contains('active') && element.classList.contains('form-section')) {
          animateFormFields();
        }
      }
    });
  });

  document.querySelectorAll('.form-section').forEach(section => {
    observer.observe(section, { attributes: true });
  });

  // Add pulse animation to the submit button
  setInterval(() => {
    $('.btn-submit').addClass('pulse-animation');
    setTimeout(() => {
      $('.btn-submit').removeClass('pulse-animation');
    }, 1000);
  }, 5000);
}