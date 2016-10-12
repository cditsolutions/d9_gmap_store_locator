<?php

namespace Drupal\store_locator\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\store_locator\StoreLocatorStorage;

/**
 * Provides a 'Store Locator' block.
 *
 * @Block(
 * id = "store_locator",
 * admin_label = @Translation("Store Locator")
 * )
 */
class StoreLocatorBlock extends BlockBase {

  /**
   *
   * {@inheritdoc}
   *
   */
  public function build() {
    $content = array();
    $content['mp'] = ['#markup' => '<div id="map" class="bh-sl-map"></div>'];
    $location_data = StoreLocatorStorage::loadInfowindow('infowindow');
    $content['#attached']['drupalSettings']['store_locator']['data'] = $location_data['itemlist'];
    $content['#attached']['drupalSettings']['store_locator']['markericon'] = $location_data['marker'];
    $content['#attached']['library'][] = 'store_locator/store_locator.page';

    return $content;
  }
}
