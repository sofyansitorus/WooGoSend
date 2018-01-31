(function($) {
	"use strict";

	var wooGoSend = {
		inputLatId: "woocommerce_woogosend_origin_lat",
		inputLngId: "woocommerce_woogosend_origin_lng",
		mapWrapperId: "woogosend-map-wrapper",
		mapSearchId: "woogosend-map-search",
		mapCanvasId: "woogosend-map-canvas",
		zoomLevel: 16,
		init: function() {
			var self = this;

			if (woogosend_params.show_settings) {
				setTimeout(function() {
					// Try show settings modal on settings page.
					var isMethodAdded = false;
					var methods = $(document).find(".wc-shipping-zone-method-type");
					for (var i = 0; i < methods.length; i++) {
						var method = methods[i];
						if ($(method).text() == woogosend_params.method_title) {
							$(method)
								.closest("tr")
								.find(".row-actions .wc-shipping-zone-method-settings")
								.trigger("click");
							isMethodAdded = true;
							return;
						}
					}

					// Show Add shipping method modal if the shipping is not added.
					if (!isMethodAdded) {
						$(".wc-shipping-zone-add-method").trigger("click");
						$("select[name='add_method_id']")
							.val(woogosend_params.method_id)
							.trigger("change");
					}
				}, 200);
			}

			$(document).on("click", ".wc-shipping-zone-method-settings", function() {
				if (
					$(this)
						.closest("tr")
						.find(".wc-shipping-zone-method-type")
						.text() === woogosend_params.method_title
				) {
					self._initGoogleMaps();
				}
			});
		},
		_initGoogleMaps: function() {
			var self = this;
			try {
				if (
					typeof google === "undefined" ||
					typeof google.maps === "undefined"
				) {
					throw "google is not defined";
				}

				self._buildGoogleMaps();
			} catch (error) {
				$.getScript(
					"https://maps.googleapis.com/maps/api/js?key=&libraries=geometry,places",
					function() {
						self._buildGoogleMaps();
					}
				);
			}
		},
		_buildGoogleMaps: function() {
			var self = this;

			var curLat = $("#" + self.inputLatId).val();
			var curLng = $("#" + self.inputLngId).val();

			curLat = curLat.length ? parseFloat(curLat) : -6.175392;
			curLng = curLng.length ? parseFloat(curLng) : 106.827153;

			var curLatLng = { lat: curLat, lng: curLng };

			var tmplMapCanvas = wp.template(self.mapCanvasId);
			var tmplMapSearch = wp.template(self.mapSearchId);

			if (!$("#" + self.mapCanvasId).length) {
				$("#" + self.mapWrapperId).append(
					tmplMapCanvas({
						map_canvas_id: self.mapCanvasId
					})
				);
			}

			var markers = [];

			var map = new google.maps.Map(document.getElementById(self.mapCanvasId), {
				center: curLatLng,
				zoom: self.zoomLevel,
				mapTypeId: "roadmap"
			});

			var marker = new google.maps.Marker({
				map: map,
				position: curLatLng,
				draggable: true,
				icon: woogosend_params.marker
			});

			var infowindow = new google.maps.InfoWindow({
				maxWidth: 350,
				content: woogosend_params.txt.drag_marker
			});

			infowindow.open(map, marker);

			google.maps.event.addListener(marker, "dragstart", function(event) {
				infowindow.close();
			});

			google.maps.event.addListener(marker, "dragend", function(event) {
				self._setLatLng(event.latLng, marker, map, infowindow);
			});

			markers.push(marker);

			if (!$("#" + self.mapSearchId).length) {
				$("#" + self.mapWrapperId).append(
					tmplMapSearch({
						map_search_id: self.mapSearchId
					})
				);
			}

			// Create the search box and link it to the UI element.
			var inputAddress = document.getElementById(self.mapSearchId);
			var searchBox = new google.maps.places.SearchBox(inputAddress);
			map.controls[google.maps.ControlPosition.TOP_LEFT].push(inputAddress);

			// Bias the SearchBox results towards current map's viewport.
			map.addListener("bounds_changed", function() {
				searchBox.setBounds(map.getBounds());
			});

			// Listen for the event fired when the user selects a prediction and retrieve more details for that place.
			searchBox.addListener("places_changed", function() {
				var places = searchBox.getPlaces();

				if (places.length == 0) {
					return;
				}

				// Clear out the old markers.
				markers.forEach(function(marker) {
					marker.setMap(null);
				});

				markers = [];

				// For each place, get the icon, name and location.
				var bounds = new google.maps.LatLngBounds();
				places.forEach(function(place) {
					if (!place.geometry) {
						console.log("Returned place contains no geometry");
						return;
					}

					marker = new google.maps.Marker({
						map: map,
						position: place.geometry.location,
						draggable: true,
						icon: woogosend_params.marker
					});

					self._setLatLng(place.geometry.location, marker, map, infowindow);

					google.maps.event.addListener(marker, "dragstart", function(event) {
						infowindow.close();
					});

					google.maps.event.addListener(marker, "dragend", function(event) {
						self._setLatLng(event.latLng, marker, map, infowindow);
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
				map.setZoom(self.zoomLevel);
				map.fitBounds(bounds);
			});
		},
		_setLatLng: function(location, marker, map, infowindow) {
			var self = this;

			var geocoder = new google.maps.Geocoder();

			geocoder.geocode(
				{
					latLng: location
				},
				function(results, status) {
					if (status == google.maps.GeocoderStatus.OK) {
						if (results[0]) {
							infowindow.setContent(results[0].formatted_address);
							infowindow.open(map, marker);
							$("#" + self.inputLatId).val(location.lat());
							$("#" + self.inputLngId).val(location.lng());
						} else {
							$("#" + self.inputLatId).val("");
							$("#" + self.inputLngId).val("");
						}
					}
				}
			);
			map.setCenter(location);
		}
	};

	$(document).ready(function() {
		wooGoSend.init();
	});
})(jQuery);
