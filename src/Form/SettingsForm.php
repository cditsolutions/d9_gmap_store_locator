<?php

namespace Drupal\store_locator\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\store_locator\StoreLocatorStorage;
use Drupal\file\Entity\File;
use Drupal\Core\Url;
use Drupal\file\FileUsage\DatabaseFileUsageBackend;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Utility\LinkGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\store_locator\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The file usage service variable.
   *
   * @var \Drupal\file\FileUsage\DatabaseFileUsageBackend
   */
  protected $dbFileUsage;

  /**
   * The image factory service variable.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * The link generator service variable.
   *
   * @var \Drupal\Core\Utility\LinkGenerator
   */
  protected $linkGenerator;

  /**
   * Constructs DatabaseFileUsageBackend, ImageFactory & LinkGenerator object.
   *
   * @param \Drupal\file\FileUsage\DatabaseFileUsageBackend $db_file_usage
   *   Database file usage service.
   * @param \Drupal\Core\Image\ImageFactory $image_factory
   *   Image Factory Service.
   * @param \Drupal\Core\Utility\LinkGenerator $link_generator
   *   Link Generator Service.
   */
  public function __construct(DatabaseFileUsageBackend $db_file_usage, ImageFactory $image_factory, LinkGenerator $link_generator) {
    $this->dbFileUsage = $db_file_usage;
    $this->imageFactory = $image_factory;
    $this->linkGenerator = $link_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file.usage'),
      $container->get('image.factory'),
      $container->get('link_generator')
    );
  }

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
    $api_link = $this->linkGenerator->generate($this->t('Click here'), $google_api);

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
      '#description' => $this->t('Supported formats are: gif png jpg jpeg'),
      '#upload_validators' => array(
        'file_validate_extensions' => array('gif png jpg jpeg'),
        'file_validate_size' => array(500000),
      ),
      '#default_value' => $marker ? array($marker) : NULL,
      '#upload_location' => 'public://marker',
    );

    $form['marker']['width'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Max Width'),
      '#size' => 10,
      '#maxlength' => 3,
      '#default_value' => !empty($config->get('marker_width')) ? $config->get('marker_width') : '25',
      '#description' => $this->t('Enter the width in <em>px</em>'),
    );
    $form['marker']['height'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Max Height'),
      '#size' => 10,
      '#maxlength' => 3,
      '#default_value' => !empty($config->get('marker_height')) ? $config->get('marker_height') : '35',
      '#description' => $this->t('Enter the height in <em>px</em>'),
    );

    $form['map_api'] = array(
      '#type' => 'details',
      '#title' => $this->t('Google Map API'),
      '#open' => TRUE,
    );
    $form['map_api']['api_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Google Maps API Key'),
      '#size' => 60,
      '#required' => TRUE,
      '#default_value' => $config->get('api_key'),
      '#description' => $this->t('A free API key is needed to use the Google Maps. @click here to generate the API key', array(
        '@click' => $api_link,
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
      '#title' => $this->t('Locator Title'),
      '#size' => 60,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $config->get('title'),
      '#description' => $this->t('Title will be display in <em>store-locator</em> page.'),
    );
    $form['message']['store_text'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('No Record Message'),
      '#rows' => 3,
      '#required' => TRUE,
      '#default_value' => $config->get('message'),
      '#description' => $this->t('Message will be diplay when no record added in store locator page.'),
    );

    $form['style'] = array(
      '#type' => 'details',
      '#title' => $this->t('Logo Style'),
      '#open' => TRUE,
    );
    $form['style']['logo'] = array(
      '#type' => 'select',
      '#title' => $this->t('Available Styles'),
      '#options' => StoreLocatorStorage::getAvailableStyle(),
      '#default_value' => !empty($config->get('logo_style')) ? $config->get('logo_style') : 'thumbnail',
      '#description' => $this->t('Select logo style to apply in map infowindow'),
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
      '#header' => array(
        $this->t('Order'),
        $this->t('Status'),
        $this->t('Weight'),
      ),
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
        $image = $this->imageFactory->get($file->getFileUri());
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
      $this->dbFileUsage->add($file, 'store_locator', 'module', 1);
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
