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

  protected function getAvailableTimes(): array {
    $slots = ['09:00', '10:00', '11:00', '14:00', '15:00'];
    $options = [];
    foreach ($slots as $slot) {
      $options[$slot] = $this->t('@t', ['@t' => $slot]);
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

  protected function getAdvisersByAgency(?int $agency_id): array {
    $storage = $this->entityTypeManager->getStorage('user');
    $query = $storage->getQuery()
      ->condition('status', 1)
      ->condition('roles', 'adviser')
      ->accessCheck(TRUE);

    if (!empty($agency_id)) {
      $field_definitions = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions('user');
      if (isset($field_definitions['field_agency'])) {
        $query->condition('field_agency', $agency_id);
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
    $form['#attached']['library'][] = 'appointment/booking_form';
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
      $form['steps_wrapper']['adviser'] = [
        '#type' => 'select',
        '#title' => $this->t('Choisir le conseiller'),
        '#options' => $this->getAdvisersByAgency($agency_id ?: NULL),
        '#required' => TRUE,
        '#default_value' => $form_state->getValue(['steps_wrapper', 'adviser'])
          ?? $wizard['adviser']
          ?? $defaults['adviser']
          ?? NULL,
      ];
    }
    elseif ($step === 4) {
      $adviser_id = (int) ($wizard['adviser'] ?? $defaults['adviser'] ?? 0);
      $picked_date = (string) ($wizard['date'] ?? $defaults['date'] ?? '');
      $picked_time = (string) ($wizard['time'] ?? $defaults['time'] ?? '');

      $form['steps_wrapper']['calendar'] = [
        '#type' => 'view',
        '#name' => 'available_appointments',
        '#display_id' => 'block_1',
        '#arguments' => $adviser_id ? [$adviser_id] : [],
      ];

      $form['steps_wrapper']['date'] = [
        '#type' => 'hidden',
        '#default_value' => $picked_date,
      ];

      $form['steps_wrapper']['selected_date'] = [
        '#type' => 'item',
        '#title' => $this->t('Date sélectionnée'),
        '#markup' => '<div class="appointment-booking__selected-date" data-selected-date="1">' . ($picked_date ?: '-') . '</div>',
      ];

      $form['steps_wrapper']['time'] = [
        '#type' => 'select',
        '#title' => $this->t('Choisir l’heure'),
        '#options' => $this->getAvailableTimes(),
        '#required' => TRUE,
        '#default_value' => $form_state->getValue(['steps_wrapper', 'time'])
          ?? $picked_time
          ?? NULL,
      ];
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
        4 => [['steps_wrapper', 'date'], ['steps_wrapper', 'time']],
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
      $wizard['time'] = (string) $form_state->getValue(['steps_wrapper', 'time']);
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

