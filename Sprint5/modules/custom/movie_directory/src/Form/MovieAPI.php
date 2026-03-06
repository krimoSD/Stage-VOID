<?php

namespace Drupal\movie_directory\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for the Movie API settings.
 *
 * This form lets site administrators:
 * - Set the base URL for the movie API.
 * - Store the API key used for authentication.
 * Values are stored using Drupal's state system and read by MovieAPIConnector.
 */
class MovieAPI extends FormBase {

  /**
   * State key used to store configuration values.
   */
  const MOVIE_API_CONFIG_PAGE = 'movie_api_config_page_values';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'movie_api_config_page';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Load any previously saved values from state.
    $values = \Drupal::state()->get(self::MOVIE_API_CONFIG_PAGE) ?: [];

    $form = [];

    // Base URL input for the external movie API.
    $form['api_base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Base URL'),
      '#description' => $this->t('This is the API base URL.'),
      '#required' => TRUE,
      '#default_value' => isset($values['api_base_url']) ? $values['api_base_url'] : '',
    ];

    // API key input used for authenticating requests.
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#description' => $this->t('This is the API key that will be used to access the API.'),
      '#required' => TRUE,
      '#default_value' => isset($values['api_key']) ? $values['api_key'] : '',
    ];

    // Standard Drupal actions container and submit button.
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Clean and store submitted values in Drupal state.
    $submitted_values = $form_state->cleanValues()->getValues();
    \Drupal::state()->set(self::MOVIE_API_CONFIG_PAGE, $submitted_values);

    // Provide feedback to the administrator.
    $messenger = \Drupal::service('messenger');
    $messenger->addMessage($this->t('Your new configuration has been saved.'));
  }

}
