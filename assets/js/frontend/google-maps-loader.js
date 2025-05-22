// Google Maps API Loader
(function() {
    // Load Google Maps API
    function loadGoogleMapsAPI() {
        const apiKey = 'AIzaSyDz2XaTRDQWceGDq5bXHbcx2tzV8WTKmMw';
        const script = document.createElement('script');
        script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=geometry&callback=initGoogleMap`;
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);
    }

    // Check if the map container exists before loading the API
    if (document.getElementById('map')) {
        // Wait for the DOM to be fully loaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', loadGoogleMapsAPI);
        } else {
            loadGoogleMapsAPI();
        }
    }
})();