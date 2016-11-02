<?php

namespace Drupal\store_locator\Geocode;

use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the GeostoreConsumer Class, for return Latitude & Longitude.
 */
class GeostoreConsumer {

  /**
   * Return json data of latitude & longitude.
   *
   * @param string $address
   *          The address query to get the latitude & longitude.
   *
   * @return array
   *   An array of latitude & longitude.
   */
  public function geoLatLong($address) {
    $client = \Drupal::httpClient();
    $query = ['address' => $address, 'sensor' => 'false'];
    $uri = 'http://maps.googleapis.com/maps/api/geocode/json';
    $response = $client->request('GET', $uri, ['query' => $query]);

    if (empty($response->error)) {
      $data = json_decode($response->getBody());

      if ($data->status == 'OK') {
        $lat = $data->results[0]->geometry->location->lat;
        $lng = $data->results[0]->geometry->location->lng;
        $matches = array('latitude' => $lat, 'longitude' => $lng);
      }
    }
    return $matches;
  }

}
