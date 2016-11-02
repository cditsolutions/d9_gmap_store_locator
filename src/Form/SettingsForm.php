<?php

namespace Drupal\store_locator\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\store_locator\StoreLocatorStorage;
use Drupal\file\Entity\File;
use Drupal\Core\Url;

/**
 * Class SettingsForm.
 *
 * @package Drupal\store_locator\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['store_locator.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'store_locator.settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $google_api = Url::fromUri('https://developers.google.com/maps/documentation/javascript/get-api-key', [
      'attributes' => ['target' => '_blank'],
    ]);

    $config = $this->config('store_locator.settings');
    $marker = $config->get('marker');
    $form['marker'] = array(
      '#type' => 'details',
      '#title' => $this->t('Add Marker'),
      '#open' => TRUE,
    );
    $form['marker']['icon'] = array(
      '#type' => 'managed_file',
      '#title' => $this->t('Marker Icon'),
      '#description' => t('Supported formats are: gif png jpg jpeg'),
      '#upload_validators' => array(
        'file_validate_extensions' => array('gif png jpg jpeg'),
        'file_validate_size' => array(500000),
      ),
      '#default_value' => $marker ? array($marker) : NULL,
      '#upload_location' => 'public://marker',
    );

    $form['marker']['width'] = array(
      '#type' => 'textfield',
      '#title' => t('Max Width'),
      '#size' => 10,
      '#maxlength' => 3,
      '#default_value' => !empty($config->get('marker_width')) ? $config->get('marker_width') : '25',
      '#description' => t('Enter the width in <em>px</em>'),
    );
    $form['marker']['height'] = array(
      '#type' => 'textfield',
      '#title' => t('Max Height'),
      '#size' => 10,
      '#maxlength' => 3,
      '#default_value' => !empty($config->get('marker_height')) ? $config->get('marker_height') : '35',
      '#description' => t('Enter the height in <em>px</em>'),
    );

    $form['map_api'] = array(
      '#type' => 'details',
      '#title' => $this->t('Google Map API'),
      '#open' => TRUE,
    );
    $form['map_api']['api_key'] = array(
      '#type' => 'textfield',
      '#title' => t('Google Maps API Key'),
      '#size' => 60,
      '#required' => TRUE,
      '#default_value' => $config->get('api_key'),
      '#description' => t('A free API key is needed to use the Google Maps. @click here to generate the API key', array(
        '@click' => \Drupal::l(t('Click here'), $google_api),
      )),
    );
    $form = SettingsForm::mapSettings($form, $form_state, 'infowindow');
    $form = SettingsForm::mapSettings($form, $form_state, 'list');

    $form['message'] = array(
      '#type' => 'details',
      '#title' => $this->t('Label & Message'),
      '#open' => TRUE,
    );
    $form['message']['store_label'] = array(
      '#type' => 'textfield',
      '#title' => t('Locator Title'),
      '#size' => 60,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $config->get('title'),
      '#description' => t('Title will be display in <em>store-locator</em> page.'),
    );
    $form['message']['store_text'] = array(
      '#type' => 'textarea',
      '#title' => t('No Record Message'),
      '#rows' => 3,
      '#required' => TRUE,
      '#default_value' => $config->get('message'),
      '#description' => t('Message will be diplay when no record added in store locator page.'),
    );

    $form['style'] = array(
      '#type' => 'details',
      '#title' => $this->t('Logo Style'),
      '#open' => TRUE,
    );
    $form['style']['logo'] = array(
      '#type' => 'select',
      '#title' => t('Available Styles'),
      '#options' => StoreLocatorStorage::getAvailableStyle(),
      '#default_value' => !empty($config->get('logo_style')) ? $config->get('logo_style') : 'thumbnail',
      '#description' => t('Select logo style to apply in map infowindow'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * Generate the List data.
   */
  public function mapSettings(array &$form, FormStateInterface $form_state, $type) {
    $config = $this->config('store_locator.settings');

    if ($type == 'infowindow') {
      $lbl = $this->t('Map InfoWindow Fields');
      $field_name = 'setting_infowindow';
      $field_title = $this->t('Select the field to display in infowindow.');
      $items = $config->get('infowindow');
      $results = StoreLocatorStorage::getAvailableFields($items);
    }
    else {
      $lbl = $this->t('Map List Fields');
      $field_name = 'setting_list';
      $field_title = $this->t('Select the field to display in list.');
      $items = $config->get('list');
      $results = StoreLocatorStorage::getAvailableFields($items);
    }

    $form[$type] = array(
      '#type' => 'details',
      '#title' => $lbl,
      '#description' => $field_title,
      '#open' => TRUE,
    );
    $form[$type][$field_name] = array(
      '#type' => 'table',
      '#header' => array(t('Order'), t('Status'), t('Weight')),
      '#tableselect' => FALSE,
      '#tabledrag' => array(
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'map-field-order-weight',
        ),
      ),
    );
    foreach ($results as $key => $value) {
      $form[$type][$field_name][$key]['#attributes']['class'][] = 'draggable';
      $form[$type][$field_name][$key]['id'] = array(
        '#plain_text' => $value[$key],
      );

      $form[$type][$field_name][$key][$key] = array(
        '#type' => 'checkbox',
        '#default_value' => !empty($value['weight']) ? TRUE : FALSE,
      );

      $form[$type][$field_name][$key]['weight'] = array(
        '#type' => 'weight',
        '#default_value' => $value['weight'],
        '#attributes' => array('class' => array('map-field-order-weight')),
      );
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $values = $form_state->getValues();
    if (empty($values['api_key'])) {
      $form_state->setErrorByName($values['api_key'], $this->t('Please Enter the Google Map API Key.'));
    }

    if (!empty($values['width']) && !ctype_digit($values['width'])) {
      $form_state->setErrorByName($values['width'], $this->t('Please Enter the digit in Marker width field.'));
    }

    if (!empty($values['height']) && !ctype_digit($values['height'])) {
      $form_state->setErrorByName($values['height'], $this->t('Please Enter the digit in Marker height field.'));
    }

    if (isset($values['icon']) && !empty($values['icon'])) {
      if (!empty($values['width']) && !empty($values['height'])) {
        $fid = current($values['icon']);
        $file = File::load($fid);
        $image = \Drupal::service('image.factory')->get($file->getFileUri());
        if ($image->isValid()) {
          if ($image->getWidth() > $values['width'] || $image->getHeight() > $values['height']) {
            $form_state->setErrorByName($values['width'], $this->t('Uploaded Image having @width x @height px which is not matching with the specified Width & Height.', array(
              '@width' => $image->getWidth(),
              '@height' => $image->getHeight(),
            )));
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $fid = NULL;
    if (!empty($values['icon'])) {
      $fid = current($values['icon']);
      $file = File::load($fid);
      $file_usage = \Drupal::service('file.usage');
      $file_usage->add($file, 'store_locator', 'module', 1);
      $file->save();
    }
    $this->config('store_locator.settings')->set('marker', $fid)->save();
    $this->config('store_locator.settings')->set('marker_width', $values['width'])->save();
    $this->config('store_locator.settings')->set('marker_height', $values['height'])->save();
    $this->config('store_locator.settings')->set('api_key', $values['api_key'])->save();
    $this->config('store_locator.settings')->set('infowindow', $values['setting_infowindow'])->save();
    $this->config('store_locator.settings')->set('list', $values['setting_list'])->save();
    $this->config('store_locator.settings')->set('title', $values['store_label'])->save();
    $this->config('store_locator.settings')->set('message', $values['store_text'])->save();
    $this->config('store_locator.settings')->set('logo_style', $values['logo'])->save();
    parent::submitForm($form, $form_state);
  }

}
