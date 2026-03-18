<?php

namespace Drupal\appointment\Form;

use Drupal\appointment\Entity\AppointmentEntity;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\Html;
use Symfony\Component\DependencyInjection\ContainerInterface;
use DateTimeZone;

/**
 * Form to modify an existing appointment (booking-style wizard).
 */
class AppointmentModifyForm extends FormBase {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ConfigFactoryInterface $systemConfigFactory,
    protected MailManagerInterface $mailManager,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('plugin.manager.mail'),
    );
  }

  public function getFormId(): string {
    return 'appointment_modify_form';
  }

  protected function siteTimezone(): string {
    return $this->systemConfigFactory->get('system.date')->get('timezone.default') ?: 'UTC';
  }

  protected function getAvailableTimes(int $adviser_id, string $date, ?int $exclude_appointment_id = NULL): array {
    if ($adviser_id <= 0 || $date === '') {
      return [];
    }

    $site_tz = $this->siteTimezone();
    $settings = $this->systemConfigFactory->get('appointment.settings');
    $slot_minutes = (int) ($settings->get('slot_minutes') ?: 60);
    $default_day_start = trim((string) ($settings->get('day_start') ?: '09:00'));
    $default_day_end = trim((string) ($settings->get('day_end') ?: '17:00'));

    $day_start = $default_day_start;
    $day_end = $default_day_end;
    $adviser = $this->entityTypeManager->getStorage('user')->load($adviser_id);
    if ($adviser) {
      if ($adviser->hasField('field_workday_start') && (string) $adviser->get('field_workday_start')->value !== '') {
        $day_start = trim((string) $adviser->get('field_workday_start')->value);
      }
      if ($adviser->hasField('field_workday_end') && (string) $adviser->get('field_workday_end')->value !== '') {
        $day_end = trim((string) $adviser->get('field_workday_end')->value);
      }
    }

    if (!preg_match('/^\d{2}:\d{2}$/', $day_start)) {
      $day_start = $default_day_start;
    }
    if (!preg_match('/^\d{2}:\d{2}$/', $day_end)) {
      $day_end = $default_day_end;
    }

    try {
      $start_local = new DrupalDateTime($date . ' ' . $day_start, new DateTimeZone($site_tz));
      $end_local = new DrupalDateTime($date . ' ' . $day_end, new DateTimeZone($site_tz));
    }
    catch (\Exception $e) {
      return [];
    }

    if ($end_local <= $start_local) {
      return [];
    }

    $start_utc = clone $start_local;
    $end_utc = clone $end_local;
    $start_utc->setTimezone(new DateTimeZone('UTC'));
    $end_utc->setTimezone(new DateTimeZone('UTC'));

    $storage = $this->entityTypeManager->getStorage('appointment');
    $query = $storage->getQuery()
      ->condition('adviser', $adviser_id)
      ->condition('appointment_date', $start_utc->format('Y-m-d\TH:i:s'), '>=')
      ->condition('appointment_date', $end_utc->format('Y-m-d\TH:i:s'), '<=')
      ->condition('status', 'cancelled', '!=')
      ->accessCheck(TRUE)
      ;
    if (!empty($exclude_appointment_id)) {
      $query->condition('id', (int) $exclude_appointment_id, '!=');
    }

    $booked_ids = $query->execute();

    $booked = [];
    if ($booked_ids) {
      $entities = $storage->loadMultiple($booked_ids);
      foreach ($entities as $appt) {
        $booked[(string) $appt->get('appointment_date')->value] = TRUE;
      }
    }

    $options = [];
    $cursor = clone $start_local;
    while ($cursor < $end_local) {
      $slot_utc = clone $cursor;
      $slot_utc->setTimezone(new DateTimeZone('UTC'));
      $storage_value = $slot_utc->format('Y-m-d\TH:i:s');

      if (empty($booked[$storage_value])) {
        $label = $cursor->format('H:i');
        $options[$label] = $this->t('@t', ['@t' => $label]);
      }

      $cursor->modify('+' . max(5, $slot_minutes) . ' minutes');
    }

    return $options;
  }

  protected function getAgencies(): array {
    $agencies = $this->entityTypeManager->getStorage('agency')->loadMultiple();
    $options = [];
    foreach ($agencies as $agency) {
      $options[$agency->id()] = $agency->label();
    }
    return $options;
  }

  protected function getAppointmentTypes(): array {
    $options = [];
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree('appointment_type');
    foreach ($terms as $term) {
      $options[$term->tid] = $term->name;
    }
    return $options;
  }

  protected function getAdvisersByAgencyAndType(?int $agency_id, ?int $type_id): array {
    $storage = $this->entityTypeManager->getStorage('user');
    $query = $storage->getQuery()
      ->condition('status', 1)
      ->condition('roles', 'adviser')
      ->accessCheck(TRUE);

    if (!empty($agency_id)) {
      $field_definitions = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions('user');
      if (isset($field_definitions['field_agency'])) {
        $query->condition('field_agency.target_id', (int) $agency_id);
      }
    }

    $type_id = (int) ($type_id ?? 0);
    if ($type_id > 0) {
      $field_definitions = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions('user');
      if (isset($field_definitions['field_specializations'])) {
        $query->condition('field_specializations.target_id', $type_id);
      }
      else {
        return [];
      }
    }

    $ids = $query->execute();
    $accounts = $storage->loadMultiple($ids);
    $options = [];
    foreach ($accounts as $account) {
      $options[$account->id()] = $account->label();
    }
    return $options;
  }

  protected function initDefaults(FormStateInterface $form_state, AppointmentEntity $appointment): void {
    if ($form_state->get('defaults_initialized')) {
      return;
    }

    $site_tz = $this->siteTimezone();
    $current_utc = (string) $appointment->get('appointment_date')->value;
    $current_dt = new DrupalDateTime($current_utc, new DateTimeZone('UTC'));
    $current_dt->setTimezone(new DateTimeZone($site_tz));

    $form_state->set('defaults_initialized', TRUE);
    $form_state->set('step', 1);
    $form_state->set('defaults', [
      'agency' => (int) $appointment->get('agency')->target_id,
      'appointment_type' => (int) $appointment->get('appointment_type')->target_id,
      'adviser' => (int) $appointment->get('adviser')->target_id,
      'date' => $current_dt->format('Y-m-d'),
      'time' => $current_dt->format('H:i'),
      'customer_name' => (string) $appointment->get('customer_name')->value,
      'customer_email' => (string) $appointment->get('customer_email')->value,
      'customer_phone' => (string) $appointment->get('customer_phone')->value,
    ]);

    // Seed wizard data with current entity values so later steps still have
    // values even though we only render one step at a time.
    $form_state->set('wizard', [
      'agency' => (int) $appointment->get('agency')->target_id,
      'appointment_type' => (int) $appointment->get('appointment_type')->target_id,
      'adviser' => (int) $appointment->get('adviser')->target_id,
      'date' => $current_dt->format('Y-m-d'),
      'time' => $current_dt->format('H:i'),
      'personal' => [
        'name' => (string) $appointment->get('customer_name')->value,
        'email' => (string) $appointment->get('customer_email')->value,
        'phone' => (string) $appointment->get('customer_phone')->value,
      ],
    ]);
  }

  protected function wizardGet(FormStateInterface $form_state): array {
    return (array) ($form_state->get('wizard') ?? []);
  }

  protected function wizardSet(FormStateInterface $form_state, array $data): void {
    $form_state->set('wizard', $data);
  }

  public function buildForm(array $form, FormStateInterface $form_state, ?AppointmentEntity $appointment = NULL): array {
    if (!$appointment) {
      $form['#markup'] = $this->t('Rendez-vous introuvable.');
      return $form;
    }

    $form_state->set('appointment_id', $appointment->id());
    $this->initDefaults($form_state, $appointment);

    $step = (int) ($form_state->get('step') ?? 1);
    if ($step < 1 || $step > 6) {
      $step = 1;
      $form_state->set('step', 1);
    }

    $defaults = (array) ($form_state->get('defaults') ?? []);
    $wizard = $this->wizardGet($form_state);

    $form['#theme'] = 'appointment_modify_form';
    $form['#prefix'] = '<div id="appointment-modify-form-wrapper">';
    $form['#suffix'] = '</div>';

    $form['current_step'] = [
      '#type' => 'hidden',
      '#value' => $step,
    ];

    $form['steps_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'appointment-modify-steps-wrapper'],
      '#tree' => TRUE,
    ];

    if ($step === 1) {
      $form['steps_wrapper']['agency'] = [
        '#type' => 'select',
        '#title' => $this->t('Choisir une agence'),
        '#options' => $this->getAgencies(),
        '#required' => TRUE,
        '#default_value' => $form_state->getValue(['steps_wrapper', 'agency'])
          ?? $wizard['agency']
          ?? $defaults['agency']
          ?? NULL,
      ];
    }
    elseif ($step === 2) {
      $form['steps_wrapper']['appointment_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Type de rendez-vous'),
        '#options' => $this->getAppointmentTypes(),
        '#required' => TRUE,
        '#default_value' => $form_state->getValue(['steps_wrapper', 'appointment_type'])
          ?? $wizard['appointment_type']
          ?? $defaults['appointment_type']
          ?? NULL,
      ];
    }
    elseif ($step === 3) {
      $agency_id = (int) ($wizard['agency'] ?? $defaults['agency'] ?? 0);
      $type_id = (int) ($wizard['appointment_type'] ?? $defaults['appointment_type'] ?? 0);
      $form['steps_wrapper']['adviser'] = [
        '#type' => 'select',
        '#title' => $this->t('Choisir le conseiller'),
        '#options' => $this->getAdvisersByAgencyAndType($agency_id ?: NULL, $type_id ?: NULL),
        '#required' => TRUE,
        '#default_value' => $form_state->getValue(['steps_wrapper', 'adviser'])
          ?? $wizard['adviser']
          ?? $defaults['adviser']
          ?? NULL,
        '#empty_option' => $this->t('- Aucun conseiller disponible -'),
      ];
    }
    elseif ($step === 4) {
      $adviser_id = (int) ($wizard['adviser'] ?? $defaults['adviser'] ?? 0);
      $picked_date = (string) ($wizard['date'] ?? $defaults['date'] ?? '');
    $picked_time = (string) ($wizard['time'] ?? $defaults['time'] ?? '');
      $exclude_id = (int) ($form_state->get('appointment_id') ?? 0);

      $form['steps_wrapper']['calendar'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['appointment-fullcalendar'],
          'data-adviser' => (string) ($adviser_id ?: ''),
          'data-events-url' => $adviser_id ? Url::fromRoute('appointment.fullcalendar_events', ['adviser' => $adviser_id])->toString() : '',
        ],
      ];

      $form['steps_wrapper']['date'] = [
        '#type' => 'hidden',
        '#default_value' => $picked_date,
      ];

      // Refresh button is placed in actions to ensure AJAX binding works.

      $form['steps_wrapper']['selected_date'] = [
        '#type' => 'item',
        '#title' => $this->t('Date sélectionnée'),
        '#markup' => '<div class="appointment-booking__selected-date" data-selected-date="1">' . Html::escape((string) ($picked_date ?: '-')) . '</div>',
      ];

      $form['steps_wrapper']['time_wrapper'] = [
        '#type' => 'container',
        '#attributes' => ['id' => 'appointment-modify-time-wrapper'],
        'time' => [
          '#type' => 'select',
          '#title' => $this->t('Choisir l’heure'),
          '#options' => $this->getAvailableTimes($adviser_id, $picked_date, $exclude_id ?: NULL),
          '#required' => TRUE,
          '#default_value' => $form_state->getValue(['steps_wrapper', 'time_wrapper', 'time'])
            ?? $form_state->getValue(['steps_wrapper', 'time'])
            ?? $picked_time
            ?? NULL,
          '#empty_option' => $this->t('- Choisir -'),
        ],
      ];

      // Keep wizard time in sync when the time wrapper is used.
      if ($form_state->hasValue(['steps_wrapper', 'time_wrapper', 'time'])) {
        $wizard['time'] = (string) $form_state->getValue(['steps_wrapper', 'time_wrapper', 'time']);
        $this->wizardSet($form_state, $wizard);
      }
    }
    elseif ($step === 5) {
      $form['steps_wrapper']['personal'] = [
        'name' => [
          '#type' => 'textfield',
          '#title' => $this->t('Nom complet'),
          '#required' => TRUE,
          '#default_value' => $form_state->getValue(['steps_wrapper', 'personal', 'name'])
            ?? ($wizard['personal']['name'] ?? $defaults['customer_name'] ?? ''),
        ],
        'email' => [
          '#type' => 'email',
          '#title' => $this->t('Email'),
          '#required' => TRUE,
          '#default_value' => $form_state->getValue(['steps_wrapper', 'personal', 'email'])
            ?? ($wizard['personal']['email'] ?? $defaults['customer_email'] ?? ''),
        ],
        'phone' => [
          '#type' => 'tel',
          '#title' => $this->t('Numéro de téléphone'),
          '#required' => TRUE,
          '#default_value' => $form_state->getValue(['steps_wrapper', 'personal', 'phone'])
            ?? ($wizard['personal']['phone'] ?? $defaults['customer_phone'] ?? ''),
        ],
      ];
    }
    else {
      $form['steps_wrapper']['summary'] = [
        '#theme' => 'item_list',
        '#items' => $this->buildSummaryItems($form_state),
      ];
    }

    $form['actions'] = ['#type' => 'actions'];
    if ($step > 1) {
      $form['actions']['back'] = [
        '#type' => 'submit',
        '#value' => $this->t('Back'),
        '#submit' => ['::backSubmit'],
        '#limit_validation_errors' => [],
        '#ajax' => [
          'callback' => '::ajaxRefresh',
          'wrapper' => 'appointment-modify-form-wrapper',
        ],
      ];
    }

    // Hidden AJAX refresh for time slots (Step 4 calendar click).
    if ($step === 4) {
      $form['actions']['refresh_times'] = [
        '#type' => 'submit',
        '#value' => $this->t('Refresh times'),
        '#submit' => ['::refreshTimesSubmit'],
        '#limit_validation_errors' => [],
        '#ajax' => [
          'callback' => '::ajaxTimeRefresh',
          'wrapper' => 'appointment-modify-time-wrapper',
        ],
        '#attributes' => [
          'style' => 'display:none',
          'data-appointment-modify-refresh-times' => '1',
        ],
      ];
    }

    if ($step < 6) {
      $form['actions']['next'] = [
        '#type' => 'submit',
        '#value' => $this->t('Next'),
        '#submit' => ['::nextSubmit'],
        '#ajax' => [
          'callback' => '::ajaxRefresh',
          'wrapper' => 'appointment-modify-form-wrapper',
        ],
      ];

      $form['actions']['next']['#limit_validation_errors'] = match ($step) {
        1 => [['steps_wrapper', 'agency']],
        2 => [['steps_wrapper', 'appointment_type']],
        3 => [['steps_wrapper', 'adviser']],
        4 => [['steps_wrapper', 'date'], ['steps_wrapper', 'time_wrapper', 'time']],
        5 => [['steps_wrapper', 'personal']],
        default => [],
      };
    }
    else {
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Confirm'),
      ];
    }

    return $form;
  }

  public function ajaxRefresh(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * AJAX callback to refresh time slots when date changes.
   */
  public function ajaxTimeRefresh(array &$form, FormStateInterface $form_state) {
    return $form['steps_wrapper']['time_wrapper'];
  }

  public function refreshTimesSubmit(array &$form, FormStateInterface $form_state): void {
    $form_state->setRebuild();
  }

  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // Final submit does full validation from persisted wizard data
    // (since we only render one step at a time).
    $wizard = $this->wizardGet($form_state);
    $date = (string) ($wizard['date'] ?? '');
    $time = (string) ($wizard['time'] ?? '');
    if ($date === '' || $time === '') {
      $form_state->setErrorByName('steps_wrapper][date', $this->t('Veuillez choisir un créneau.'));
    }
  }

  public function nextSubmit(array &$form, FormStateInterface $form_state): void {
    $step = (int) ($form_state->get('step') ?? 1);
    $wizard = $this->wizardGet($form_state);

    // Persist values from the current step before moving on.
    if ($step === 1) {
      $wizard['agency'] = (int) $form_state->getValue(['steps_wrapper', 'agency']);
    }
    elseif ($step === 2) {
      $wizard['appointment_type'] = (int) $form_state->getValue(['steps_wrapper', 'appointment_type']);
    }
    elseif ($step === 3) {
      $wizard['adviser'] = (int) $form_state->getValue(['steps_wrapper', 'adviser']);
    }
    elseif ($step === 4) {
      $wizard['date'] = (string) $form_state->getValue(['steps_wrapper', 'date']);
      $wizard['time'] = (string) ($form_state->getValue(['steps_wrapper', 'time_wrapper', 'time']) ?? $form_state->getValue(['steps_wrapper', 'time']));
    }
    elseif ($step === 5) {
      $wizard['personal'] = [
        'name' => (string) $form_state->getValue(['steps_wrapper', 'personal', 'name']),
        'email' => (string) $form_state->getValue(['steps_wrapper', 'personal', 'email']),
        'phone' => (string) $form_state->getValue(['steps_wrapper', 'personal', 'phone']),
      ];
    }
    $this->wizardSet($form_state, $wizard);

    $form_state->set('step', min(6, $step + 1));
    $form_state->setRebuild();
  }

  public function backSubmit(array &$form, FormStateInterface $form_state): void {
    $step = (int) ($form_state->get('step') ?? 1);
    $form_state->set('step', max(1, $step - 1));
    $form_state->setRebuild();
  }

  protected function buildSummaryItems(FormStateInterface $form_state): array {
    $wizard = $this->wizardGet($form_state);

    $agency_id = (int) ($wizard['agency'] ?? 0);
    $type_id = (int) ($wizard['appointment_type'] ?? 0);
    $adviser_id = (int) ($wizard['adviser'] ?? 0);
    $date = (string) ($wizard['date'] ?? '');
    $time = (string) ($wizard['time'] ?? '');
    $name = (string) ($wizard['personal']['name'] ?? '');
    $email = (string) ($wizard['personal']['email'] ?? '');
    $phone = (string) ($wizard['personal']['phone'] ?? '');

    $agency_label = $agency_id ? $this->entityTypeManager->getStorage('agency')->load($agency_id)?->label() : NULL;
    $type_label = $type_id ? $this->entityTypeManager->getStorage('taxonomy_term')->load($type_id)?->label() : NULL;
    $adviser_label = $adviser_id ? $this->entityTypeManager->getStorage('user')->load($adviser_id)?->label() : NULL;

    return [
      $this->t('Agence: @v', ['@v' => $agency_label ?: '-']),
      $this->t('Type: @v', ['@v' => $type_label ?: '-']),
      $this->t('Conseiller: @v', ['@v' => $adviser_label ?: '-']),
      $this->t('Date: @v', ['@v' => trim(($date ?: '-') . ' ' . ($time ?: ''))]),
      $this->t('Nom: @v', ['@v' => $name ?: '-']),
      $this->t('Email: @v', ['@v' => $email ?: '-']),
      $this->t('Téléphone: @v', ['@v' => $phone ?: '-']),
    ];
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $appointment_id = (int) $form_state->get('appointment_id');
    /** @var \Drupal\appointment\Entity\AppointmentEntity|null $appointment */
    $appointment = $this->entityTypeManager->getStorage('appointment')->load($appointment_id);
    if (!$appointment) {
      $this->messenger()->addError($this->t('Rendez-vous introuvable.'));
      return;
    }

    $wizard = $this->wizardGet($form_state);

    $agency_id = (int) ($wizard['agency'] ?? 0);
    $type_id = (int) ($wizard['appointment_type'] ?? 0);
    $adviser_id = (int) ($wizard['adviser'] ?? 0);
    $picked_date = (string) ($wizard['date'] ?? '');
    $picked_time = (string) ($wizard['time'] ?? '');
    $name = (string) ($wizard['personal']['name'] ?? '');
    $email = (string) ($wizard['personal']['email'] ?? '');
    $phone = (string) ($wizard['personal']['phone'] ?? '');

    if (!$agency_id || !$type_id || !$adviser_id || $picked_date === '' || $picked_time === '' || $name === '' || $email === '' || $phone === '') {
      $this->messenger()->addError($this->t('Veuillez compléter toutes les étapes.'));
      $form_state->set('step', 1);
      $form_state->setRebuild();
      return;
    }

    $site_tz = $this->siteTimezone();
    $new_dt_local = new DrupalDateTime($picked_date . ' ' . $picked_time, new DateTimeZone($site_tz));
    $now_local = new DrupalDateTime('now', new DateTimeZone($site_tz));
    if ($new_dt_local <= $now_local) {
      $this->messenger()->addError($this->t('Veuillez choisir un créneau futur.'));
      $form_state->set('step', 4);
      $form_state->setRebuild();
      return;
    }

    $new_dt_utc = clone $new_dt_local;
    $new_dt_utc->setTimezone(new DateTimeZone('UTC'));
    // Match ContentEntity datetime storage: UTC without offset.
    $new_value_utc = $new_dt_utc->format('Y-m-d\TH:i:s');

    // Prevent double booking for same adviser and timeslot (excluding this appt).
    $exists = $this->entityTypeManager->getStorage('appointment')->getQuery()
      ->condition('id', $appointment->id(), '!=')
      ->condition('adviser', $adviser_id)
      ->condition('appointment_date', $new_value_utc)
      ->accessCheck(TRUE)
      ->range(0, 1)
      ->execute();
    if ($exists) {
      $this->messenger()->addError($this->t('Ce créneau est déjà réservé. Veuillez choisir une autre date/heure.'));
      $form_state->set('step', 4);
      $form_state->setRebuild();
      return;
    }

    $appointment->set('appointment_date', $new_value_utc);
    $appointment->set('agency', $agency_id);
    $appointment->set('appointment_type', $type_id);
    $appointment->set('adviser', $adviser_id);
    $appointment->set('customer_name', $name);
    $appointment->set('customer_email', $email);
    $appointment->set('customer_phone', $phone);
    $appointment->save();

    // Send modification confirmation.
    $reference = $appointment->hasField('reference') ? ($appointment->get('reference')->value ?: $appointment->id()) : $appointment->id();
    $site_mail = $this->systemConfigFactory->get('system.site')->get('mail');
    $langcode = $this->systemConfigFactory->get('system.site')->get('langcode') ?: 'fr';
    $params = ['appointment' => $appointment, 'reference' => $reference];
    $this->mailManager->mail('appointment', 'booking_modified', $appointment->get('customer_email')->value, $langcode, $params, $site_mail, TRUE);

    $this->messenger()->addStatus($this->t('Votre rendez-vous a été mis à jour.'));
    $form_state->setRedirect('appointment.booking_manage');
  }

}

