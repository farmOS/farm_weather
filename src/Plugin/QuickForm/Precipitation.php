<?php

namespace Drupal\farm_weather\Plugin\QuickForm;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\farm_quick\Plugin\QuickForm\QuickFormBase;
use Drupal\farm_quick\Traits\QuickLogTrait;
use Drupal\farm_quick\Traits\QuickStringTrait;
use Psr\Container\ContainerInterface;

/**
 * Precipitation observation quick form.
 *
 * @QuickForm(
 *   id = "precipitation",
 *   label = @Translation("Precipitation"),
 *   description = @Translation("Record a precipitation weather event."),
 *   helpText = @Translation("This form will create an observation log to represent a precipitation weather event."),
 *   permissions = {
 *     "create observation log",
 *   }
 * )
 */
class Precipitation extends QuickFormBase {

  use QuickLogTrait;
  use QuickStringTrait;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Current user object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a QuickFormBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MessengerInterface $messenger, ConfigFactoryInterface $config_factory, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $messenger);
    $this->messenger = $messenger;
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('messenger'),
      $container->get('config.factory'),
      $container->get('current_user'),
    );
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
   *   Returns `us` or `metric`
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
