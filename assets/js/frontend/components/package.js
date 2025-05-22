export function initPackages() {
  const addPackageBtn = document.getElementById('add-package');
  const packagesContainer = document.getElementById('packages-container');
  
  if (!addPackageBtn || !packagesContainer) return;
  
  // Add event listener to the add package button
  addPackageBtn.addEventListener('click', addNewPackage);
  
  // Initial package removal button setup
  setupPackageRemovalButtons();
  
  function addNewPackage() {
    const packages = packagesContainer.querySelectorAll('.package');
    const packageCount = packages.length;
    const newPackageId = packageCount + 1;
    
    const newPackage = document.createElement('div');
    newPackage.classList.add('package');
    newPackage.dataset.packageId = newPackageId;
    
    newPackage.innerHTML = `
      <div class="package-header">
        <h4>Package #${newPackageId}</h4>
        <button type="button" class="btn-remove-package">
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
          <label for="weight-${newPackageId}">Weight (KG)</label>
          <input type="number" id="weight-${newPackageId}" name="weight-${newPackageId}" min="0.1" step="0.1" placeholder="Enter weight" required>
        </div>
        
        <div class="dimensions-group">
          <h5>Dimensions (cm)</h5>
          <div class="dimensions-inputs">
            <div class="form-group">
              <label for="length-${newPackageId}">Length</label>
              <input type="number" id="length-${newPackageId}" name="length-${newPackageId}" min="1" placeholder="Length" required>
            </div>
            <div class="form-group">
              <label for="width-${newPackageId}">Width</label>
              <input type="number" id="width-${newPackageId}" name="width-${newPackageId}" min="1" placeholder="Width" required>
            </div>
            <div class="form-group">
              <label for="height-${newPackageId}">Height</label>
              <input type="number" id="height-${newPackageId}" name="height-${newPackageId}" min="1" placeholder="Height" required>
            </div>
          </div>
        </div>
      </div>
    `;
    
    packagesContainer.appendChild(newPackage);
    
    // Enable all remove buttons when there are multiple packages
    updateRemoveButtons();
    
    // Add animation to the new package
    newPackage.classList.add('new-package');
    setTimeout(() => {
      newPackage.classList.remove('new-package');
    }, 500);
    
    // Focus the first input of the new package
    setTimeout(() => {
      const firstInput = newPackage.querySelector('input');
      if (firstInput) firstInput.focus();
    }, 100);
  }
  
  function setupPackageRemovalButtons() {
    const removeButtons = document.querySelectorAll('.btn-remove-package');
    
    removeButtons.forEach(button => {
      button.addEventListener('click', removePackage);
    });
  }
  
  function updateRemoveButtons() {
    const packages = packagesContainer.querySelectorAll('.package');
    const removeButtons = packagesContainer.querySelectorAll('.btn-remove-package');
    
    removeButtons.forEach(button => {
      if (packages.length > 1) {
        button.disabled = false;
        button.addEventListener('click', removePackage);
      } else {
        button.disabled = true;
      }
    });
  }
  
  function removePackage(event) {
    const packageElement = event.currentTarget.closest('.package');
    
    // Fade out animation
    packageElement.style.opacity = '0';
    packageElement.style.transform = 'translateY(-10px)';
    packageElement.style.transition = 'all 0.3s ease';
    
    setTimeout(() => {
      packageElement.remove();
      
      // Re-number remaining packages
      renumberPackages();
      
      // Update remove buttons
      updateRemoveButtons();
    }, 300);
  }
  
  function renumberPackages() {
    const packages = packagesContainer.querySelectorAll('.package');
    
    packages.forEach((packageElement, index) => {
      const packageNumber = index + 1;
      const packageHeader = packageElement.querySelector('.package-header h4');
      
      if (packageHeader) {
        packageHeader.textContent = `Package #${packageNumber}`;
      }
    });
  }
}