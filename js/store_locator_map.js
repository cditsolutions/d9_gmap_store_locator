/**
 * @file
 * Contains the definition of the behaviour storeLocatorMap.
 */

(function ($, Drupal, drupalSettings) {
  "use strict";
  /**
   * Attaches the Store Locator Behaviour.
   */
  Drupal.behaviors.storeLocatorMap = {
    attach: function (context, settings) {
      if (typeof drupalSettings.store_locator.latlng != 'undefined') {
        var lt = drupalSettings.store_locator.latlng.lat;
        var lg = drupalSettings.store_locator.latlng.lng;
        initMap(lt, lg);
      }

      jQuery.fn.init_map = function (lt, lg) {
        initMap(parseFloat(lt), parseFloat(lg));
      };

      var markers = new Array();
      var map, gmarker, content;
      var bounds = new google.maps.LatLngBounds();

      var mapOptions = {
        mapTypeId: 'roadmap'
      };

      var data = drupalSettings.store_locator.data;
      var marker_icon = drupalSettings.store_locator.markericon.icon;

      map = new google.maps.Map(document.getElementById('map'),
      mapOptions);
      map.setTilt(45);
      var infoWindow = new google.maps.InfoWindow(),
        key;
      var check = jQuery(this);
      jQuery.each(
      data,

      function (index, marker) {
        var position = new google.maps.LatLng(
        marker.latitude, marker.longitude);
        bounds.extend(position);
        gmarker = new google.maps.Marker({
          position: position,
          map: map,
          id: index,
          title: marker.name,
          icon: marker_icon,
          animation: google.maps.Animation.DROP
        });

        google.maps.event.addListener(
        gmarker,
          'click', (function (gmarker, index) {
          return function () {
            content = '';
            jQuery.each(
            marker,

            function (
            key,
            value) {
              if (value != null && key !== 'latitude' && key !== 'longitude') {

                if (key == 'website') {
                  var web = '<a href="' + value + '" target="_blank">' + value + '</a>';
                  content += '<div class="loc-' + key + '">' + web + '</div>';
                }
                else {
                  content += '<div class="loc-' + key + '">' + value + '</div>';
                }
              }
            });

            infoWindow.setContent(content);
            infoWindow.open(map, gmarker);

            jQuery(
              ".list-wrapper li")
              .removeClass(
              'highlight');
            jQuery(
              ".list-wrapper li")
              .eq(index)
              .addClass(
              'highlight');

            var container = jQuery('#location-list-wrapper'),
              scrollTo = jQuery(
                ".list-wrapper li")
                .eq(index);

            container.animate({
              scrollTop: scrollTo.offset().top - container.offset().top + container.scrollTop()
            }, 1500);

          }
        })(gmarker, index));
        markers.push(gmarker);
        map.fitBounds(bounds);
      });

      jQuery('.list-marker-id').on(
        'click',

      function (event) {
        event.preventDefault();
        google.maps.event.trigger(markers[jQuery(this).data(
          'markerid')], 'click');
      });

    }
  };
})(jQuery, Drupal, drupalSettings);

function filter(element) {
  var value = jQuery(element).val();
  jQuery(".list-wrapper li").each(function () {
    if (jQuery(this).text().search(new RegExp(value, "i")) > -1) {
      jQuery(this).show();
    }
    else {
      jQuery(this).hide();
    }
  });
}

function initMap(lt, lg) {
  console.log(lt);
  var latlng = {
    lat: lt,
    lng: lg
  };
  var map = new google.maps.Map(document.getElementById('map'), {
    center: latlng,
    'zoom': 18,
    'mapTypeId': google.maps.MapTypeId.ROADMAP
  });

  var marker = new google.maps.Marker({
    position: latlng,
    map: map,
  });

  marker.addListener('click', function () {
    infowindow.open(map, marker);
  });

  google.maps.event.addListener(map, 'click', function (event) {
    jQuery("input[name='latitude[0][value]']").val(event.latLng.lat());
    jQuery("input[name='longitude[0][value]']").val(event.latLng.lng());
    placeMarker(event.latLng, map);
  })

  google.maps.event.addListener(map, 'mousemove', function (event) {
    var u = event.latLng.lat() + ', ' + event.latLng.lng();
  });

  function placeMarker(position, map) {
    var marker = new google.maps.Marker({
      position: position,
      map: map
    });
    map.panTo(position);
  }
}
