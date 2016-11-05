<?php

namespace Drupal\store_locator\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for Store Locator.
 */
class StoreLocatorPage extends ControllerBase {

  /**
   * Render a list and Map.
   */
  public function page() {
    $content = array();
    $content['searchitem'] = [
      '#type' => 'textfield',
      '#attributes' => array(
        'onkeyup' => "filter(this);",
        'placeholder' => $this->t('Search keyword'),
      ),
    ];
    // Preprocesses the Results.
    return array(
      '#theme' => 'location_data',
      '#location_data_var' => $content,
    );
  }

}
