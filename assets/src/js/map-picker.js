/**
 * Map Picker
 */
var woogosendMapPicker = {
  params: {},
  origin_lat: '',
  origin_lng: '',
  origin_address: '',
  zoomLevel: 16,
  apiKeyErrorCheckInterval: null,
  apiKeyError: '',
  editingAPIKey: false,
  editingAPIKeyPicker: false,
  init: function (params) {
    woogosendMapPicker.params = params;
    woogosendMapPicker.apiKeyError = '';
    woogosendMapPicker.editingAPIKey = false;
    woogosendMapPicker.editingAPIKeyPicker = false;

    ConsoleListener.on('error', function (errorMessage) {
      if (errorMessage.toLowerCase().indexOf('google') !== -1) {
        woogosendMapPicker.apiKeyError = errorMessage;
      }

      if ($('.gm-err-message').length) {
        $('.gm-err-message').replaceWith('<p style="text-align:center">' + woogosendMapPicker.convertError(errorMessage) + '</p>');
      }
    });

    // Edit Api Key
    $(document).off('click', '.woogosend-edit-api-key', woogosendMapPicker.editApiKey);
    $(document).on('click', '.woogosend-edit-api-key', woogosendMapPicker.editApiKey);

    // Show Store Location Picker
    $(document).off('click', '.woogosend-edit-location-picker', woogosendMapPicker.showLocationPicker);
    $(document).on('click', '.woogosend-edit-location-picker', woogosendMapPicker.showLocationPicker);

    // Hide Store Location Picker
    $(document).off('click', '#woogosend-btn--map-cancel', woogosendMapPicker.hideLocationPicker);
    $(document).on('click', '#woogosend-btn--map-cancel', woogosendMapPicker.hideLocationPicker);

    // Apply Store Location
    $(document).off('click', '#woogosend-btn--map-apply', woogosendMapPicker.applyLocationPicker);
    $(document).on('click', '#woogosend-btn--map-apply', woogosendMapPicker.applyLocationPicker);

    // Toggle Map Search Panel
    $(document).off('click', '#woogosend-map-search-panel-toggle', woogosendMapPicker.toggleMapSearch);
    $(document).on('click', '#woogosend-map-search-panel-toggle', woogosendMapPicker.toggleMapSearch);
  },
  validateAPIKeyBothSide: function (apiKey, $input, $link) {
    woogosendMapPicker.validateAPIKeyServerSide(apiKey, $input, $link, woogosendMapPicker.validateAPIKeyBrowserSide);
  },
  validateAPIKeyBrowserSide: function (apiKey, $input, $link) {
    woogosendMapPicker.apiKeyError = '';

    woogosendMapPicker.initMap(apiKey, function () {
      var geocoderArgs = {
        latLng: new google.maps.LatLng(parseFloat(woogosendMapPicker.params.defaultLat), parseFloat(woogosendMapPicker.params.defaultLng)),
      };

      var geocoder = new google.maps.Geocoder();

      geocoder.geocode(geocoderArgs, function (results, status) {
        if (status.toLowerCase() === 'ok') {
          console.log('validateAPIKeyBrowserSide', results);

          $link.removeClass('editing').data('value', '');
          $input.prop('readonly', true);

          woogosendMapPicker.apiKeyError = '';
          woogosendMapPicker.editingAPIKeyPicker = false;
        }
      });

      clearInterval(woogosendMapPicker.apiKeyErrorCheckInterval);

      woogosendMapPicker.apiKeyErrorCheckInterval = setInterval(function () {
        if (!$link.hasClass('editing') || woogosendMapPicker.apiKeyError) {
          clearInterval(woogosendMapPicker.apiKeyErrorCheckInterval);

          $link.removeClass('loading').attr('disabled', false);

          if (woogosendMapPicker.apiKeyError) {
            woogosendMapPicker.showError($input, woogosendMapPicker.apiKeyError);
          }
        }
      }, 300);
    });
  },
  validateAPIKeyServerSide: function (apiKey, $input, $link, onSuccess) {
    $.ajax({
      method: 'POST',
      url: woogosendMapPicker.params.ajax_url,
      data: {
        action: 'woogosend_validate_api_key_server',
        nonce: woogosendMapPicker.params.validate_api_key_nonce,
        key: apiKey
      }
    }).done(function (response) {
      console.log('validateAPIKeyServerSide', response);
      woogosendMapPicker.editingAPIKey = false;

      if (typeof onSuccess === 'function') {
        onSuccess(apiKey, $input, $link);
      } else {
        $link.removeClass('editing').data('value', '');
        $input.prop('readonly', true);
      }
    }).fail(function (error) {
      if (error.responseJSON && error.responseJSON.data) {
        woogosendMapPicker.showError($input, error.responseJSON.data);
      } else if (error.statusText) {
        woogosendMapPicker.showError($input, error.statusText);
      } else {
        woogosendMapPicker.showError($input, 'Google Distance Matrix API error: Uknown');
      }
    }).always(function () {
      $link.removeClass('loading').attr('disabled', false);
    });
  },
  showError: function ($input, errorMessage) {
    $('<div class="error notice woogosend-error-box"><p>' + woogosendMapPicker.convertError(errorMessage) + '</p></div>')
      .hide()
      .appendTo($input.closest('td'))
      .slideDown();
  },
  removeError: function ($input) {
    $input.closest('td')
      .find('.woogosend-error-box')
      .remove();
  },
  convertError: function (text) {
    var exp = /(\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/ig;
    return text.replace(exp, "<a href='$1' target='_blank'>$1</a>");
  },
  editApiKey: function (e) {
    e.preventDefault();

    var $link = $(e.currentTarget);
    var $input = $link.closest('tr').find('input[type=text]');
    var apiKey = $input.val();

    woogosendMapPicker.removeError($input);

    if ($link.hasClass('editing')) {

      if (apiKey && apiKey !== $link.data('value')) {
        $link.addClass('loading').attr('disabled', true);

        if ($link.attr('id') === 'api_key') {
          woogosendMapPicker.validateAPIKeyServerSide(apiKey, $input, $link);
        } else {
          woogosendMapPicker.validateAPIKeyBrowserSide(apiKey, $input, $link);
        }
      } else {
        $link.removeClass('editing').data('value', '');
        $input.prop('readonly', true);

        if ($link.attr('id') === 'api_key') {
          woogosendMapPicker.editingAPIKey = false;
        } else {
          woogosendMapPicker.listenConsole = false;
          woogosendMapPicker.editingAPIKeyPicker = false;
        }
      }
    } else {
      $link.addClass('editing').data('value', apiKey);
      $input.prop('readonly', false);

      if ($link.attr('id') === 'api_key') {
        woogosendMapPicker.editingAPIKey = $input;
      } else {
        woogosendMapPicker.editingAPIKeyPicker = $input;
      }
    }
  },
  showLocationPicker: function (e) {
    e.preventDefault();

    $('.modal-close-link').hide();

    woogosendToggleButtons({
      left: {
        id: 'map-cancel',
        label: 'Cancel',
        icon: 'undo'
      },
      right: {
        id: 'map-apply',
        label: 'Apply Changes',
        icon: 'editor-spellcheck'
      }
    });

    $('#woogosend-field-group-wrap--location_picker').fadeIn().siblings().hide();

    var $subTitle = $('#woogosend-field-group-wrap--location_picker').find('.wc-settings-sub-title').first().addClass('woogosend-hidden');

    $('.wc-backbone-modal-header').find('h1').append('<span>' + $subTitle.text() + '</span>');

    woogosendMapPicker.initMap($('#woocommerce_woogosend_api_key_picker').val(), woogosendMapPicker.renderMap);
  },
  hideLocationPicker: function (e) {
    e.preventDefault();

    woogosendMapPicker.destroyMap();

    $('.modal-close-link').show();

    woogosendToggleButtons();

    $('#woogosend-field-group-wrap--location_picker').find('.wc-settings-sub-title').first().removeClass('woogosend-hidden');

    $('.wc-backbone-modal-header').find('h1 span').remove();

    $('#woogosend-field-group-wrap--location_picker').hide().siblings().not('.woogosend-hidden').fadeIn();
  },
  applyLocationPicker: function (e) {
    e.preventDefault();

    if (!woogosendMapPicker.apiKeyError) {
      $('#woocommerce_woogosend_origin_lat').val(woogosendMapPicker.origin_lat);
      $('#woocommerce_woogosend_origin_lng').val(woogosendMapPicker.origin_lng);
      $('#woocommerce_woogosend_origin_address').val(woogosendMapPicker.origin_address);
    }

    woogosendMapPicker.hideLocationPicker(e);
  },
  toggleMapSearch: function (e) {
    e.preventDefault();

    $('#woogosend-map-search-panel').toggleClass('expanded');
  },
  initMap: function (apiKey, callback) {
    woogosendMapPicker.destroyMap();

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

    setTimeout(function () {
      $('#woogosend-map-search-panel').removeClass('woogosend-hidden');
    }, 500);
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
            woogosendMapPicker.params.i18n.latitude + ': ' + location.lat().toString(),
            woogosendMapPicker.params.i18n.longitude + ': ' + location.lng().toString()
          ];

          infowindow.setContent(infowindowContents.join('<br />'));
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
