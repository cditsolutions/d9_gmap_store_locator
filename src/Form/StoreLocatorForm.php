<?php

namespace Drupal\store_locator\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\AlertCommand;
use Drupal\store_locator\Geocode\GeostoreConsumer;

/**
 * Form controller for Store locator edit forms.
 *
 * @ingroup store_locator
 */
class StoreLocatorForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['add'] = [
      '#type' => 'button',
      '#value' => $this->t('Calculate Lat/Long'),
      '#ajax' => [
        'callback' => '::ajaxContentSubmitForm',
        'event' => 'click',
      ],
      '#prefix' => '<div id="add-button-wrapper">',
      '#suffix' => '</div>',
    ];
    $form['gmap'] = array(
      '#type' => 'container',
      '#weight' => 90,
      '#prefix' => '<div id="map">',
      '#suffix' => '</div>',
    );
    $lat = $form['latitude']['widget'][0]['value']['#default_value'];
    $lng = $form['longitude']['widget'][0]['value']['#default_value'];
    if (!empty($lat) || !empty($lng)) {
      $lat_lng = array('lat' => (float) $lat, 'lng' => (float) $lng);
    }
    else {
      $lat_lng = array('lat' => (float) 19.1586639, 'lng' => (float) 72.994035);
    }
    $form['#attached']['drupalSettings']['store_locator']['latlng'] = $lat_lng;
    $form['#attached']['library'][] = 'store_locator/store_locator.page';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Store locator.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Store locator.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.store_locator.canonical', [
      'store_locator' => $entity->id(),
    ]);
  }

  /**
   * Get the Latitude & Longitude of the entered location.
   */
  public function ajaxContentSubmitForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $values = $form_state->getValues();

    $city = $values['city'][0]['value'];
    $address_one = $values['address_one'][0]['value'];
    $address_two = $values['address_two'][0]['value'];
    $postcode = $values['postcode'][0]['value'];

    if (empty($city) && empty($address_one) && empty($postcode)) {
      $response->addCommand(new AlertCommand(t('Enter the city or address one or postcode.')));
      return $response;
    }
    $address = "$city $address_one $address_two $postcode";
    $data = GeostoreConsumer::geoLatLong($address);

    $response->addCommand(new InvokeCommand("input[name='latitude[0][value]']", 'val', array(
      $data['latitude'],
    )));
    $response->addCommand(new InvokeCommand("input[name='longitude[0][value]']", 'val', array(
      $data['longitude'],
    )));
    $response->addCommand(new InvokeCommand('', 'init_map', array(
      $data['latitude'],
      $data['longitude'],
    )));

    return $response;
  }

}
