// Map initialization and functionality for multiple routes
let maps = {};
let directionsServices = {};
let directionsRenderers = {};
let originMarkers = {};
let destinationMarkers = {};
let geocoders = {};
let routePaths = {};

// Initialize the map when Google Maps API is loaded
window.initGoogleMap = function() {
  // Find all map containers
  const mapElements = document.querySelectorAll('.route-map');
  if (mapElements.length === 0) return;
    
  // Initialize Google Maps for each route
  mapElements.forEach(mapElement => {
      const routeIndex = mapElement.dataset.routeIndex;
        
      // Create the map
      maps[routeIndex] = new google.maps.Map(mapElement, {
          center: { lat: 20, lng: 0 },
          zoom: 2,
          mapTypeControl: false,
          streetViewControl: false,
          fullscreenControl: false
      });

      // Initialize directions services
      directionsServices[routeIndex] = new google.maps.DirectionsService();
      directionsRenderers[routeIndex] = new google.maps.DirectionsRenderer({
          map: maps[routeIndex],
          suppressMarkers: true, // We'll add custom markers
          polylineOptions: {
              strokeColor: '#3E92CC',
              strokeWeight: 5,
              strokeOpacity: 0.7
          }
      });

      // Initialize geocoder for address lookups
      geocoders[routeIndex] = new google.maps.Geocoder();

      // Create markers (initially hidden)
      originMarkers[routeIndex] = new google.maps.Marker({
          map: maps[routeIndex],
          icon: {
              url: 'https://maps.google.com/mapfiles/ms/icons/green-dot.png',
              scaledSize: new google.maps.Size(32, 32)
          },
          visible: false,
          animation: google.maps.Animation.DROP
      });

      destinationMarkers[routeIndex] = new google.maps.Marker({
          map: maps[routeIndex],
          icon: {
              url: 'https://maps.google.com/mapfiles/ms/icons/red-dot.png',
              scaledSize: new google.maps.Size(32, 32)
          },
          visible: false,
          animation: google.maps.Animation.DROP
      });
  });

  // Setup address listeners
  setupAddressListeners();
};

function setupAddressListeners() {
  // Find all route sets
  const routeSets = document.querySelectorAll('.route-set');
    
  routeSets.forEach(routeSet => {
      const routeIndex = routeSet.dataset.routeIndex;
        
      const originCountry = document.getElementById(`origin_country_${routeIndex}`);
      const originPostal = document.getElementById(`origin_postal_code_${routeIndex}`);
      const originAddress = document.getElementById(`origin_address_${routeIndex}`);
      const destCountry = document.getElementById(`destination_country_${routeIndex}`);
      const destPostal = document.getElementById(`destination_postal_code_${routeIndex}`);
      const destAddress = document.getElementById(`destination_address_${routeIndex}`);

      const addressFields = [
          originCountry, originPostal, originAddress,
          destCountry, destPostal, destAddress
      ];

      addressFields.forEach(field => {
          if (field) {
              field.addEventListener('change', () => updateRoute(routeIndex));
          }
      });
  });
}

function updateRoute(routeIndex) {
  const originCountry = document.getElementById(`origin_country_${routeIndex}`);
  const originPostal = document.getElementById(`origin_postal_code_${routeIndex}`);
  const originAddress = document.getElementById(`origin_address_${routeIndex}`);
  const destCountry = document.getElementById(`destination_country_${routeIndex}`);
  const destPostal = document.getElementById(`destination_postal_code_${routeIndex}`);
  const destAddress = document.getElementById(`destination_address_${routeIndex}`);

  if (!originCountry || !destCountry || !originPostal || !destPostal) {
      return;
  }

  const originCountryValue = originCountry.options[originCountry.selectedIndex]?.text;
  const destCountryValue = destCountry.options[destCountry.selectedIndex]?.text;
    
  // Check if we have enough information to calculate a route
  if (!originCountryValue || !destCountryValue || !originPostal.value || !destPostal.value) {
      return;
  }

  const originAddressText = `${originAddress.value}, ${originPostal.value}, ${originCountryValue}`;
  const destinationAddressText = `${destAddress.value}, ${destPostal.value}, ${destCountryValue}`;

  // Determine transportation mode based on countries
  const isSameCountry = originCountry.value === destCountry.value;
  const transportMode = isSameCountry ? 'DRIVING' : 'FLYING';
  const transportType = isSameCountry ? 'Road Transport' : 'Air Transport';
  const routeTypeElement = document.querySelector(`.route-type-${routeIndex} .value`);
    
  if (routeTypeElement) {
      routeTypeElement.textContent = transportType;
        
      // Update icon based on transport type
      const routeIcon = document.querySelector(`.route-type-${routeIndex} .icon svg`);
      if (routeIcon) {
          // Change SVG based on transport type
          if (transportMode === 'FLYING') {
              routeIcon.outerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-plane">
                  <path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"></path>
              </svg>`;
          } else {
              routeIcon.outerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-truck">
                  <path d="M5 18H3c-.6 0-1-.4-1-1V7c0-.6.4-1 1-1h10c.6 0 1 .4 1 1v11"></path>
                  <path d="M14 9h4l4 4v4c0 .6-.4 1-1 1h-2"></path>
                  <circle cx="7" cy="18" r="2"></circle>
                  <circle cx="17" cy="18" r="2"></circle>
              </svg>`;
          }
      }
  }

  // First, geocode both addresses to place markers
  geocodeAddresses(routeIndex, originAddressText, destinationAddressText, function(originLocation, destLocation) {
      // If we have both locations, draw the shortest path (straight line)
      if (originLocation && destLocation) {
          // Calculate direct distance
          const directDistance = google.maps.geometry.spherical.computeDistanceBetween(originLocation, destLocation);
            
          // Draw the shortest path
          drawShortestPath(routeIndex, originLocation, destLocation, transportMode);
            
          // For flying mode, we just show the direct distance
          if (transportMode === 'FLYING') {
              updateDistanceDisplay(routeIndex, directDistance);
          } else {
              // For driving mode, calculate the actual driving route
              calculateRoute(routeIndex, originAddressText, destinationAddressText, transportMode, directDistance);
          }
      } else {
          // Reset distance display if we don't have both locations
          updateDistanceDisplay(routeIndex, 0);
      }
  });
}

function geocodeAddresses(routeIndex, originAddress, destinationAddress, callback) {
  if (!geocoders[routeIndex]) return;
    
  let originLocation = null;
  let destLocation = null;
    
  // Geocode origin address
  geocoders[routeIndex].geocode({ 'address': originAddress }, function(originResults, originStatus) {
      if (originStatus === 'OK' && originResults[0]) {
          originLocation = originResults[0].geometry.location;
            
          // Set origin marker
          originMarkers[routeIndex].setPosition(originLocation);
          originMarkers[routeIndex].setVisible(true);
            
          // If we only have origin, zoom to it
          if (!destinationAddress) {
              maps[routeIndex].setCenter(originLocation);
              maps[routeIndex].setZoom(14); // Closer zoom for single point
              if (callback) callback(originLocation, null);
              return;
          }
            
          // Geocode destination address
          geocoders[routeIndex].geocode({ 'address': destinationAddress }, function(destResults, destStatus) {
              if (destStatus === 'OK' && destResults[0]) {
                  destLocation = destResults[0].geometry.location;
                    
                  // Set destination marker
                  destinationMarkers[routeIndex].setPosition(destLocation);
                  destinationMarkers[routeIndex].setVisible(true);
                    
                  // Calculate distance between points
                  const distance = google.maps.geometry.spherical.computeDistanceBetween(originLocation, destLocation);
                    
                  // Create bounds to contain both markers
                  const bounds = new google.maps.LatLngBounds();
                  bounds.extend(originLocation);
                  bounds.extend(destLocation);
                    
                  // Fit map to bounds
                  maps[routeIndex].fitBounds(bounds);
                    
                  // Adjust zoom based on distance
                  const zoomAdjustment = calculateZoomAdjustment(distance);
                    
                  // Wait for bounds_changed event to complete
                  google.maps.event.addListenerOnce(maps[routeIndex], 'bounds_changed', function() {
                      // Get current zoom
                      const currentZoom = maps[routeIndex].getZoom();
                        
                      // Apply zoom adjustment if needed
                      if (currentZoom > zoomAdjustment) {
                          maps[routeIndex].setZoom(zoomAdjustment);
                      }
                        
                      // Proceed with callback
                      if (callback) callback(originLocation, destLocation);
                  });
              } else {
                  // If destination geocoding fails, just focus on origin
                  maps[routeIndex].setCenter(originLocation);
                  maps[routeIndex].setZoom(14);
                  console.error('Geocode destination failed:', destStatus);
                  if (callback) callback(originLocation, null);
              }
          });
      } else {
          console.error('Geocode origin failed:', originStatus);
          if (callback) callback(null, null);
      }
  });
}

// Draw the shortest path (straight line) between two points
function drawShortestPath(routeIndex, originLocation, destLocation, transportMode) {
  // Remove existing path if any
  if (routePaths[routeIndex]) {
      routePaths[routeIndex].setMap(null);
  }
    
  // Create the path coordinates
  const pathCoordinates = [
      originLocation,
      destLocation
  ];
    
  // Create the polyline
  routePaths[routeIndex] = new google.maps.Polyline({
      path: pathCoordinates,
      geodesic: true, // Follow the curvature of the earth
      strokeColor: transportMode === 'FLYING' ? '#FF5722' : '#4CAF50', // Orange for flying, green for driving
      strokeOpacity: 0.8,
      strokeWeight: 4,
      icons: [{
          icon: {
              path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
              scale: 3,
              strokeWeight: 2,
              fillColor: transportMode === 'FLYING' ? '#FF5722' : '#4CAF50',
              fillOpacity: 1
          },
          offset: '50%',
          repeat: '100px'
      }]
  });
    
  // Add the polyline to the map
  routePaths[routeIndex].setMap(maps[routeIndex]);
    
  // Animate the line
  animateShortestPath(routePaths[routeIndex]);
}

// Animate the shortest path with moving arrows
function animateShortestPath(line) {
  let count = 0;
    
  // Clear any existing animation interval
  if (window.pathAnimationInterval) {
      clearInterval(window.pathAnimationInterval);
  }
    
  // Set new animation interval
  window.pathAnimationInterval = window.setInterval(() => {
      count = (count + 1) % 200;
        
      const icons = line.get('icons');
      icons[0].offset = (count / 2) + '%';
      line.set('icons', icons);
  }, 50);
}

// Calculate appropriate zoom level based on distance
function calculateZoomAdjustment(distance) {
  // Distance is in meters
  if (distance < 1000) { // Less than 1km
      return 15; // Very close zoom
  } else if (distance < 5000) { // 1-5km
      return 13;
  } else if (distance < 20000) { // 5-20km
      return 11;
  } else if (distance < 100000) { // 20-100km
      return 9;
  } else if (distance < 500000) { // 100-500km
      return 7;
  } else if (distance < 2000000) { // 500-2000km
      return 5;
  } else {
      return 4; // Very far apart
  }
}

function calculateRoute(routeIndex, origin, destination, mode, directDistance) {
  if (!directionsServices[routeIndex]) return;
    
  // For driving mode, we'll show the actual driving route
  if (mode === 'DRIVING') {
      const request = {
          origin: origin,
          destination: destination,
          travelMode: google.maps.TravelMode.DRIVING
      };

      directionsServices[routeIndex].route(request, (result, status) => {
          if (status === 'OK') {
              // Set directions on the map
              directionsRenderers[routeIndex].setDirections(result);
                
              // Get route details
              const route = result.routes[0];
              const leg = route.legs[0];
                
              // Update distance display with actual driving distance
              const drivingDistance = leg.distance.value; // in meters
              updateDistanceDisplay(routeIndex, drivingDistance);
                
              // Add info windows to markers with distance comparison
              if (originMarkers[routeIndex] && destinationMarkers[routeIndex]) {
                  // Format distances for display
                  const directDistanceFormatted = formatDistance(directDistance);
                  const drivingDistanceFormatted = formatDistance(drivingDistance);
                    
                  // Create info windows with distance comparison
                  const originInfo = new google.maps.InfoWindow({
                      content: `<div class="map-info-window">
                                  <strong>Origin</strong><br>
                                  ${leg.start_address}<br>
                                  <span style="font-size: 0.9em; color: #666;">
                                    Direct distance: ${directDistanceFormatted}<br>
                                    Driving distance: ${drivingDistanceFormatted}
                                  </span>
                                </div>`
                  });
                    
                  const destInfo = new google.maps.InfoWindow({
                      content: `<div class="map-info-window">
                                  <strong>Destination</strong><br>
                                  ${leg.end_address}<br>
                                  <span style="font-size: 0.9em; color: #666;">
                                    Direct distance: ${directDistanceFormatted}<br>
                                    Driving distance: ${drivingDistanceFormatted}
                                  </span>
                                </div>`
                  });
                    
                  // Add click listeners to markers
                  google.maps.event.addListener(originMarkers[routeIndex], 'click', function() {
                      originInfo.open(maps[routeIndex], originMarkers[routeIndex]);
                  });
                    
                  google.maps.event.addListener(destinationMarkers[routeIndex], 'click', function() {
                      destInfo.open(maps[routeIndex], destinationMarkers[routeIndex]);
                  });
              }
                
              // Animate route
              animateRoute(routeIndex);
          } else {
              // Fallback for when route calculation fails - use direct distance
              console.error('Directions request failed:', status);
              updateDistanceDisplay(routeIndex, directDistance);
              animateRoute(routeIndex);
          }
      });
  }
}

// Format distance in meters to a human-readable string
function formatDistance(meters) {
  if (typeof meters !== 'number' || isNaN(meters)) {
      return '0 km';
  }
    
  if (meters < 1000) {
      return Math.round(meters) + ' m';
  } else {
      // For distances over 1km, show with 1 decimal place
      return (meters / 1000).toFixed(1) + ' km';
  }
}

function updateDistanceDisplay(routeIndex, distanceInMeters) {
  // Format distance for display
  const formattedDistance = formatDistance(distanceInMeters);
    
  // Update distance display
  const distanceElement = document.querySelector(`.route-distance-${routeIndex} .value`);
  if (distanceElement) {
      distanceElement.textContent = formattedDistance;
  }
}

function animateRoute(routeIndex) {
  // Simulate route animation
  const mapContainer = document.querySelector(`.map-container-${routeIndex}`);
  if (mapContainer) {
      mapContainer.classList.add('route-animated');
      setTimeout(() => {
          mapContainer.classList.remove('route-animated');
      }, 1500);
  }
}