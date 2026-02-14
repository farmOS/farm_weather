<?php

namespace Drupal\farm_weather\Plugin\QuickForm;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\farm_quick\Attribute\QuickForm;
use Drupal\farm_quick\Plugin\QuickForm\QuickFormBase;
use Drupal\farm_quick\Traits\QuickLogTrait;
use Drupal\farm_quick\Traits\QuickStringTrait;

/**
 * Precipitation observation quick form.
 */
#[QuickForm(
  id: 'precipitation',
  label: new TranslatableMarkup('Precipitation'),
  description: new TranslatableMarkup('Record a precipitation weather event.'),
  helpText: new TranslatableMarkup('This form will create an observation log to represent a precipitation weather event.'),
  permissions: [
    'create observation log',
  ],
)]
class Precipitation extends QuickFormBase {

  use QuickLogTrait;
  use QuickStringTrait;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    AccountInterface $current_user,
    protected ConfigFactoryInterface $configFactory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $current_user);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;

    // Date.
    $form['date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Date'),
      '#default_value' => new DrupalDateTime('now', $this->currentUser->getTimeZone()),
      '#required' => TRUE,
    ];

    // Quantity.
    $form['quantity'] = [
      '#type' => 'number',
      '#title' => $this->t('Precipitation amount (in @units)', ['@units' => $this->precipitationUnits()]),
      '#min' => 0,
      '#step' => 0.01,
    ];

    // Notes.
    $form['notes'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Notes'),
      '#format' => 'default',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Load the precipitation quantity value and units.
    $quantity_value = $form_state->getValue('quantity');
    $quantity_units = $this->precipitationUnits();

    // Generate a name for the log.
    $log_name = $this->t('Precipitation: @value @units', ['@value' => $quantity_value, '@units' => $quantity_units]);

    // Create a quantity.
    $quantity = [
      'label' => 'Precipitation',
      'value' => $quantity_value,
      'units' => $quantity_units,
    ];

    // Create the log.
    $this->createLog([
      'type' => 'observation',
      'name' => $log_name,
      'timestamp' => strtotime($form_state->getValue('date')),
      'quantity' => [$quantity],
      'notes' => $form_state->getValue('notes'),
      'status' => 'done',
    ]);
  }

  /**
   * Helper function for determining the precipitation quantity units.
   *
   * @return string
   *   Returns `cm` or `inches`
   */
  protected function precipitationUnits() {
    $system_of_measurement = $this->configFactory->get('quantity.settings')->get('system_of_measurement');
    $units = 'cm';
    if ($system_of_measurement == 'us') {
      $units = 'inches';
    }
    return $units;
  }

}
