<?php
/**
 * @file
 * Farm weather quick forms.
 */

/**
 * Implements hook_farm_quick_forms().
 */
function farm_weather_farm_quick_forms() {
  return array(
    'weather' => array(
      'label' => t('Weather'),
      'permission' => 'create farm_observation log entities',
      'form' => 'farm_weather_form',
      'file' => 'farm_weather.farm_quick.inc',
    ),
  );
}

/**
 * Form for adding weather logs.
 */
function farm_weather_form($form, &$form_state) {

  // Wrapper fieldset.
  $form['weather'] = array(
    '#type' => 'fieldset',
    '#title' => t('Record a weather event'),
    '#description' => t('Use this form to record an observed weather event. An observation log will be created with standard details filled in.'),
    '#tree' => TRUE,
  );

  // Date select (default to now).
  $form['weather']['timestamp'] = array(
    '#type' => 'date_select',
    '#title' => t('Date'),
    '#date_format' => 'M j Y H:i',
    '#date_type' => DATE_FORMAT_UNIX,
    '#date_year_range' => '-10:+3',
    '#default_value' => REQUEST_TIME,
    '#required' => TRUE,
  );

  // Load the system of measurement.
  $system = farm_quantity_system_of_measurement();

  // Set the precipitation units based on the system of measurement.
  $precipitation_units = t('centimeters');
  if ($system == 'us') {
    $precipitation_units = t('inches');
  }

  // Precipitation amount.
  $form['weather']['precipitation_amount'] = array(
    '#type' => 'textfield',
    '#title' => t('Precipitation amount (in @units)', array('@units' => $precipitation_units)),
    '#element_validate' => array('element_validate_number'),
  );

  // Precipitation units (hidden).
  $form['weather']['precipitation_units'] = array(
    '#type' => 'hidden',
    '#value' => $precipitation_units,
  );

  // Precipitation type.
  $form['weather']['precipitation_type'] = array(
    '#type' => 'radios',
    '#title' => t('Precipitation type'),
    '#options' => array(
      'rain' => t('Rain'),
      'snow' => t('Snow'),
    ),
  );

  // Birth notes.
  $form['weather']['notes'] = array(
    '#type' => 'text_format',
    '#title' => t('Notes'),
    '#format' => 'farm_format',
  );

  // Submit button.
  $form['weather']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save log'),
  );

  // Return the form.
  return $form;
}

/**
 * Submit function for weather quick form.
 */
function farm_weather_form_submit($form, &$form_state) {

  // The log type will be an observation.
  $log_type = 'farm_observation';

  // Summarize the observation.
  $summary_args = array(
    '@qty' => $form_state['values']['weather']['precipitation_amount'],
    '@units' => $form_state['values']['weather']['precipitation_units'],
    '@type' => $form_state['values']['weather']['precipitation_type'],
  );
  $summary = trim(t('@qty @units @type', $summary_args));

  // Set log name.
  $log_name = t('Weather observation: @summary', array('@summary' => $summary));

  // Get the timestamp.
  $timestamp = strtotime($form_state['values']['weather']['timestamp']);

  // Assemble an array of measurements.
  $measurements = array(
    array(
      'measure' => 'length',
      'value' => $form_state['values']['weather']['precipitation_amount'],
      'units' => $form_state['values']['weather']['precipitation_units'],
      'label' => $form_state['values']['weather']['precipitation_type'],
    ),
  );

  // Get the notes.
  $notes = $form_state['values']['weather']['notes']['value'];

  // Add the "Weather" category to the log.
  $categories = array(
    t('Weather'),
  );

  // Create a new farm quantity log.
  $log = farm_quantity_log_create($log_type, $log_name, $timestamp, TRUE, array(), $measurements, $notes, $categories);

  // Link the log to the quick form.
  farm_quick_entity_link('farm_weather_form', 'log', $log);
}
