
// Taking Over window.console.error
var isMapError = undefined, isMapErrorInterval;

var windowConsoleError = window.console.error;

window.console.error = function () {
    if (arguments[0].toLowerCase().indexOf('google') !== -1) {
        isMapError = arguments[0];
    }

    windowConsoleError.apply(windowConsoleError, arguments);
};

/**
 * Map Picker
 */
var woogosendMapPicker = {
    params: {},
    origin_lat: '',
    origin_lng: '',
    origin_address: '',
    zoomLevel: 16,
    apiKeyBrowser: '',
    init: function (params) {
        'use strict';

        woogosendMapPicker.params = params;

        // Edit Api Key
        $(document).off('click', '.woogosend-edit-api-key', woogosendMapPicker.editApiKey);
        $(document).on('click', '.woogosend-edit-api-key', woogosendMapPicker.editApiKey);

        // Get API Key
        $(document).off('click', '#woogosend-btn--get-api-key', woogosendMapPicker.getApiKey);
        $(document).on('click', '#woogosend-btn--get-api-key', woogosendMapPicker.getApiKey);

        // Show Store Location Picker
        $(document).off('click', '.woogosend-edit-location', woogosendMapPicker.showStoreLocationPicker);
        $(document).on('click', '.woogosend-edit-location', woogosendMapPicker.showStoreLocationPicker);

        // Hide Store Location Picker
        $(document).off('click', '#woogosend-btn--map-cancel', woogosendMapPicker.hideStoreLocationPicker);
        $(document).on('click', '#woogosend-btn--map-cancel', woogosendMapPicker.hideStoreLocationPicker);

        // Apply Store Location
        $(document).off('click', '#woogosend-btn--map-apply', woogosendMapPicker.applyStoreLocation);
        $(document).on('click', '#woogosend-btn--map-apply', woogosendMapPicker.applyStoreLocation);

        // Toggle Map Search Panel
        $(document).off('click', '#woogosend-map-search-panel-toggle', woogosendMapPicker.toggleMapSearch);
        $(document).on('click', '#woogosend-map-search-panel-toggle', woogosendMapPicker.toggleMapSearch);
    },
    testDistanceMatrix: function () {
        var origin = new google.maps.LatLng(parseFloat(woogosendMapPicker.params.defaultLat), parseFloat(woogosendMapPicker.params.defaultLng));
        var destination = new google.maps.LatLng(parseFloat(woogosendMapPicker.params.testLat), parseFloat(woogosendMapPicker.params.testLng));
        var service = new google.maps.DistanceMatrixService();

        service.getDistanceMatrix(
            {
                origins: [origin],
                destinations: [destination],
                travelMode: 'DRIVING',
                unitSystem: google.maps.UnitSystem.METRIC
            }, function (response, status) {
                if (status.toLowerCase() === 'ok') {
                    isMapError = false;
                } else {
                    if (response.error_message) {
                        isMapError = response.error_message;
                    } else {
                        isMapError = 'Error: ' + status;
                    }
                }
            });
    },
    editApiKey: function (e) {
        'use strict';

        e.preventDefault();

        var $link = $(e.currentTarget);
        var $input = $link.closest('tr').find('input[type=hidden]');
        var $inputDummy = $link.closest('tr').find('input[type=text]');
        var apiKey = $input.val();
        var apiKeyDummy = $inputDummy.val();

        if ($link.hasClass('editing')) {
            if (apiKey !== apiKeyDummy) {
                $link.addClass('loading').attr('disabled', true);

                switch ($link.attr('id')) {
                    case 'api_key': {
                        woogosendMapPicker.initMap(apiKeyDummy, woogosendMapPicker.testDistanceMatrix);

                        clearInterval(isMapErrorInterval);

                        isMapErrorInterval = setInterval(function () {
                            if (typeof isMapError !== 'undefined') {
                                clearInterval(isMapErrorInterval);

                                if (isMapError) {
                                    $inputDummy.val(apiKey);
                                    window.alert(isMapError);
                                } else {
                                    $input.val(apiKeyDummy);
                                }

                                $link.removeClass('loading editing').attr('disabled', false);
                                $inputDummy.prop('readonly', true);
                            }
                        }, 100);
                        break;
                    }

                    default: {
                        $.ajax({
                            method: "POST",
                            url: woogosendMapPicker.params.ajax_url,
                            data: {
                                action: "woogosend_validate_api_key_server",
                                nonce: woogosendMapPicker.params.validate_api_key_nonce,
                                key: apiKeyDummy,
                            }
                        }).done(function () {
                            // Set new API Key value
                            $input.val(apiKeyDummy);
                        }).fail(function (error) {
                            // Restore existing API Key value
                            $inputDummy.val(apiKey);

                            // Show error
                            if (error.responseJSON && error.responseJSON.data) {
                                return window.alert(error.responseJSON.data);
                            }

                            if (error.statusText) {
                                return window.alert(error.statusText);
                            }

                            window.alert('Error');
                        }).always(function () {
                            $link.removeClass('loading editing').attr('disabled', false);
                            $inputDummy.prop('readonly', true);
                        });
                    }
                }
            } else {
                $link.removeClass('editing');
                $inputDummy.prop('readonly', true);
            }
        } else {
            $link.addClass('editing');
            $inputDummy.prop('readonly', false);
        }
    },
    getApiKey: function (e) {
        'use strict';

        e.preventDefault();

        window.open('https://cloud.google.com/maps-platform/#get-started', '_blank').focus();
    },
    showStoreLocationPicker: function (e) {
        'use strict';

        e.preventDefault();

        $('.modal-close-link').hide();

        toggleBottons({
            left: {
                id: 'map-cancel',
                label: 'Back',
                icon: 'undo'
            },
            right: {
                id: 'map-apply',
                label: 'Apply Changes',
                icon: 'editor-spellcheck'
            }
        });

        $('#woogosend-field-group-wrap--location_picker').fadeIn().siblings().hide();

        woogosendMapPicker.initMap($('#woocommerce_woogosend_api_key').val(), woogosendMapPicker.renderMap);
    },
    hideStoreLocationPicker: function (e) {
        'use strict';

        e.preventDefault();

        woogosendMapPicker.destroyMap();

        $('.modal-close-link').show();

        toggleBottons();

        $('#woogosend-field-group-wrap--location_picker').hide().siblings().not('.woogosend-hidden').fadeIn();
    },
    applyStoreLocation: function (e) {
        'use strict';

        e.preventDefault();

        if (isMapError) {
            return;
        }

        woogosendMapPicker.initMap($('#woocommerce_woogosend_api_key').val(), woogosendMapPicker.testDistanceMatrix);

        clearInterval(isMapErrorInterval);

        isMapErrorInterval = setInterval(function () {
            if (typeof isMapError !== 'undefined') {
                clearInterval(isMapErrorInterval);

                if (isMapError) {
                    window.alert(isMapError);
                } else {
                    $('#woocommerce_woogosend_origin_lat').val(woogosendMapPicker.origin_lat);
                    $('#woocommerce_woogosend_origin_lng').val(woogosendMapPicker.origin_lng);
                    $('#woocommerce_woogosend_origin_address').val(woogosendMapPicker.origin_address);
                    woogosendMapPicker.hideStoreLocationPicker(e);
                }
            }
        }, 100);
    },
    toggleMapSearch: function (e) {
        'use strict';

        e.preventDefault();

        $("#woogosend-map-search-panel")
            .toggleClass('expanded')
            .find('.dashicons')
            .toggleClass('dashicons-dismiss dashicons-search');
    },
    initMap: function (apiKey, callback) {
        woogosendMapPicker.destroyMap();

        isMapError = undefined;

        if (_.isEmpty(apiKey)) {
            apiKey = 'InvalidKey';
        }

        $.getScript('https://maps.googleapis.com/maps/api/js?libraries=geometry,places&key=' + apiKey, callback);
    },
    renderMap: function () {
        woogosendMapPicker.origin_lat = $('#woocommerce_woogosend_origin_lat').val();
        woogosendMapPicker.origin_lng = $('#woocommerce_woogosend_origin_lng').val();

        var currentLatLng = {
            lat: _.isEmpty(woogosendMapPicker.origin_lat) ? parseFloat(woogosendMapPicker.params.defaultLat) : parseFloat(woogosendMapPicker.origin_lat),
            lng: _.isEmpty(woogosendMapPicker.origin_lng) ? parseFloat(woogosendMapPicker.params.defaultLng) : parseFloat(woogosendMapPicker.origin_lng)
        };

        var map = new google.maps.Map(
            document.getElementById('woogosend-map-canvas'),
            {
                mapTypeId: 'roadmap',
                center: currentLatLng,
                zoom: woogosendMapPicker.zoomLevel,
                streetViewControl: false,
                mapTypeControl: false
            }
        );

        var marker = new google.maps.Marker({
            map: map,
            position: currentLatLng,
            draggable: true,
            icon: woogosendMapPicker.params.marker
        });

        var infowindow = new google.maps.InfoWindow({ maxWidth: 350 });

        if (_.isEmpty(woogosendMapPicker.origin_lat) || _.isEmpty(woogosendMapPicker.origin_lng)) {
            infowindow.setContent(woogosendMapPicker.params.i18n.drag_marker);
            infowindow.open(map, marker);
        } else {
            woogosendMapPicker.setLatLng(marker.position, marker, map, infowindow);
        }

        google.maps.event.addListener(marker, 'dragstart', function () {
            infowindow.close();
        });

        google.maps.event.addListener(marker, 'dragend', function (event) {
            woogosendMapPicker.setLatLng(event.latLng, marker, map, infowindow);
        });

        $('#woogosend-map-wrap').prepend(wp.template('woogosend-map-search-panel')());
        map.controls[google.maps.ControlPosition.TOP_LEFT].push(document.getElementById('woogosend-map-search-panel'));

        $('#woogosend-map-search-panel').removeClass('woogosend-hidden');

        var mapSearchBox = new google.maps.places.SearchBox(document.getElementById('woogosend-map-search-input'));

        // Bias the SearchBox results towards current map's viewport.
        map.addListener('bounds_changed', function () {
            mapSearchBox.setBounds(map.getBounds());
        });

        var markers = [];

        // Listen for the event fired when the user selects a prediction and retrieve more details for that place.
        mapSearchBox.addListener('places_changed', function () {
            var places = mapSearchBox.getPlaces();
            if (places.length === 0) {
                return;
            }

            // Clear out the old markers.
            markers.forEach(function (marker) {
                marker.setMap(null);
            });

            markers = [];

            // For each place, get the icon, name and location.
            var bounds = new google.maps.LatLngBounds();

            places.forEach(function (place) {
                if (!place.geometry) {
                    console.log('Returned place contains no geometry');
                    return;
                }

                marker = new google.maps.Marker({
                    map: map,
                    position: place.geometry.location,
                    draggable: true,
                    icon: woogosendMapPicker.params.marker
                });

                woogosendMapPicker.setLatLng(place.geometry.location, marker, map, infowindow);

                google.maps.event.addListener(marker, 'dragstart', function () {
                    infowindow.close();
                });

                google.maps.event.addListener(marker, 'dragend', function (event) {
                    woogosendMapPicker.setLatLng(event.latLng, marker, map, infowindow);
                });

                // Create a marker for each place.
                markers.push(marker);

                if (place.geometry.viewport) {
                    // Only geocodes have viewport.
                    bounds.union(place.geometry.viewport);
                } else {
                    bounds.extend(place.geometry.location);
                }
            });

            map.fitBounds(bounds);
        });
    },
    destroyMap: function () {
        if (window.google) {
            window.google = undefined;
        }

        $('#woogosend-map-canvas').empty();
        $('#woogosend-map-search-panel').remove();
    },
    setLatLng: function (location, marker, map, infowindow) {
        var geocoder = new google.maps.Geocoder();

        geocoder.geocode(
            {
                latLng: location
            },
            function (results, status) {
                if (status === google.maps.GeocoderStatus.OK && results[0]) {
                    var infowindowContents = [
                        '<span class="woogosend-map-pin-label">' + woogosendMapPicker.params.i18n.latitude + '</span><span class="woogosend-map-pin-value">' + location.lat().toString() + '</span>',
                        '<span class="woogosend-map-pin-label">' + woogosendMapPicker.params.i18n.longitude + '</span><span class="woogosend-map-pin-value">' + location.lng().toString() + '</span>'
                    ];

                    infowindow.setContent('<div class="woogosend-map-pin-info">' + infowindowContents.join('</div><div class="woogosend-map-pin-info">') + '</div>');
                    infowindow.open(map, marker);

                    marker.addListener('click', function () {
                        infowindow.open(map, marker);
                    });

                    $('#woogosend-map-search-input').val(results[0].formatted_address);

                    woogosendMapPicker.origin_lat = location.lat();
                    woogosendMapPicker.origin_lng = location.lng();
                    woogosendMapPicker.origin_address = results[0].formatted_address;
                }
            }
        );

        map.setCenter(location);
    }
};
