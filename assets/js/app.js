import { initMap } from './components/map.js';
import { initForm } from './components/form.js';
import { initPackages } from './components/package.js';
import { initAnimations } from './utils/animations.js';

document.addEventListener('DOMContentLoaded', () => {
  // Initialize all components
  initMap();
  initForm();
  initPackages();
  initAnimations();
});