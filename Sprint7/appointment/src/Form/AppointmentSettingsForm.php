<?php

namespace Drupal\appointment\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Admin settings for appointment booking rules.
 */
class AppointmentSettingsForm extends ConfigFormBase {

  public const SETTINGS = 'appointment.settings';

  public function getFormId(): string {
    return 'appointment_settings_form';
  }

  protected function getEditableConfigNames(): array {
    return [self::SETTINGS];
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config(self::SETTINGS);

    $form['slot_minutes'] = [
      '#type' => 'number',
      '#title' => $this->t('Slot duration (minutes)'),
      '#min' => 5,
      '#max' => 240,
      '#default_value' => (int) ($config->get('slot_minutes') ?: 60),
      '#required' => TRUE,
    ];

    $form['day_start'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default working day start (HH:MM)'),
      '#default_value' => (string) ($config->get('day_start') ?: '09:00'),
      '#required' => TRUE,
    ];

    $form['day_end'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default working day end (HH:MM)'),
      '#default_value' => (string) ($config->get('day_end') ?: '17:00'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    foreach (['day_start', 'day_end'] as $key) {
      $value = (string) $form_state->getValue($key);
      if (!preg_match('/^\d{2}:\d{2}$/', $value)) {
        $form_state->setErrorByName($key, $this->t('Use HH:MM format.'));
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->configFactory->getEditable(self::SETTINGS)
      ->set('slot_minutes', (int) $form_state->getValue('slot_minutes'))
      ->set('day_start', (string) $form_state->getValue('day_start'))
      ->set('day_end', (string) $form_state->getValue('day_end'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}

