<?php

namespace Drupal\appointment\Form;

use Drupal\appointment\Entity\AppointmentEntity;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use DateTimeZone;

class BookingForm extends FormBase {

  protected PrivateTempStoreFactory $tempStoreFactory;

  protected EntityTypeManagerInterface $entityTypeManager;

  protected MailManagerInterface $mailManager;

  protected MessengerInterface $messengerService;

  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $entity_type_manager, MailManagerInterface $mail_manager, ConfigFactoryInterface $config_factory, MessengerInterface $messenger) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->mailManager = $mail_manager;
    // Property is defined on FormBase; just assign it here.
    $this->configFactory = $config_factory;
    $this->messengerService = $messenger;
  }

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('tempstore.private'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.mail'),
      $container->get('config.factory'),
      $container->get('messenger'),
    );
  }

  public function getFormId() {
    return 'booking_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#theme'] = 'appointment_booking_form';
    $form['#attached']['library'][] = 'appointment/booking_form';
    $form['#prefix'] = '<div id="appointment-booking-form-wrapper">';
    $form['#suffix'] = '</div>';

    $step = (int) ($form_state->get('step') ?? 1);
    if ($step < 1 || $step > 6) {
      $step = 1;
      $form_state->set('step', 1);
    }

    $form['current_step'] = [
      '#type' => 'hidden',
      '#value' => $step,
    ];

    $form['steps_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'booking-steps-wrapper'],
      '#tree' => TRUE,
    ];

    $store = $this->tempStoreFactory->get('appointment_booking');

    // Render only the current step. User advances via the Next button.
    if ($step === 1) {
      $form['steps_wrapper']['agency'] = [
        '#type' => 'select',
        '#title' => $this->t('Choisir une agence'),
        '#options' => $this->getAgencies(),
        '#required' => TRUE,
        '#default_value' => $form_state->getValue(['steps_wrapper', 'agency']) ?: $store->get('agency') ?: NULL,
      ];
    }
    elseif ($step === 2) {
      $form['steps_wrapper'] += $this->stepTwo($form_state);
    }
    elseif ($step === 3) {
      $form['steps_wrapper'] += $this->stepThree($form_state);
    }
    elseif ($step === 4) {
      $form['steps_wrapper'] += $this->stepFour($form_state);
    }
    elseif ($step === 5) {
      $form['steps_wrapper'] += $this->stepFive($form_state);
    }
    else {
      $form['steps_wrapper'] += $this->stepSix($form_state);
    }

    $form['actions']['#type'] = 'actions';
    if ($step > 1) {
      $form['actions']['back'] = [
        '#type' => 'submit',
        '#value' => $this->t('Back'),
        '#submit' => ['::backSubmit'],
        '#limit_validation_errors' => [],
        '#ajax' => [
          'callback' => '::ajaxRefresh',
          'wrapper' => 'appointment-booking-form-wrapper',
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
          'wrapper' => 'appointment-booking-form-wrapper',
        ],
      ];

      // Validate only current step elements when clicking Next.
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
      // Final submit.
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Confirm'),
      ];
    }

    return $form;
  }

  protected function getAgencies() {
    // Fetch agencies from custom Agency entity.
    $agencies = $this->entityTypeManager->getStorage('agency')->loadMultiple();
    $options = [];
    foreach ($agencies as $agency) {
      $options[$agency->id()] = $agency->label();
    }
    return $options;
  }

  public function ajaxRefresh(array &$form, FormStateInterface $form_state) {
    // Return the whole form wrapper so Twig re-renders step-based design.
    return $form;
  }

  protected function stepTwo(FormStateInterface $form_state) {
    $options = [];
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree('appointment_type');
    foreach ($terms as $term) {
      $options[$term->tid] = $term->name;
    }
  
    return [
      'appointment_type' => [
        '#type' => 'select',
        '#title' => $this->t('Type de rendez-vous'),
        '#options' => $options,
        '#required' => TRUE,
        '#default_value' => $form_state->getValue(['steps_wrapper', 'appointment_type'])
          ?: $this->tempStoreFactory->get('appointment_booking')->get('appointment_type')
          ?: NULL,
      ],
    ];
  }

  protected function stepThree(FormStateInterface $form_state) {
    $agency = $this->tempStoreFactory->get('appointment_booking')->get('agency');
    $advisers = $this->getAdvisersByAgency($agency);
  
    return [
      'adviser' => [
        '#type' => 'select',
        '#title' => $this->t('Choisir le conseiller'),
        '#options' => $advisers,
        '#required' => TRUE,
        '#default_value' => $form_state->getValue(['steps_wrapper', 'adviser'])
          ?: $this->tempStoreFactory->get('appointment_booking')->get('adviser')
          ?: NULL,
      ],
    ];
  }

  protected function stepFour(FormStateInterface $form_state) {
    $store = $this->tempStoreFactory->get('appointment_booking');
    $adviser = $form_state->getValue(['steps_wrapper', 'adviser']) ?: $store->get('adviser');
    $stored_date = $store->get('date');
    $stored_time = $store->get('time');
  
    return [
      'calendar' => [
        '#type' => 'view',
        '#name' => 'available_appointments',
        '#display_id' => 'block_1',
        // Pass adviser ID as argument if the View has a contextual filter.
        '#arguments' => $adviser ? [$adviser] : [],
      ],
      // Date is selected from the calendar; stored in a hidden field.
      'date' => [
        '#type' => 'hidden',
        '#default_value' => $form_state->getValue(['steps_wrapper', 'date']) ?: $stored_date ?: '',
      ],
      'selected_date' => [
        '#type' => 'item',
        '#title' => $this->t('Date sélectionnée'),
        '#markup' => '<div class="appointment-booking__selected-date" data-selected-date="1">' . ($form_state->getValue(['steps_wrapper', 'date']) ?: $stored_date ?: '-') . '</div>',
      ],
      'time' => [
        '#type' => 'select',
        '#title' => $this->t('Choisir l’heure'),
        '#options' => $this->getAvailableTimes(
          $adviser,
          $form_state->getValue(['steps_wrapper', 'date']) ?: $stored_date
        ),
        '#required' => TRUE,
        '#default_value' => $form_state->getValue(['steps_wrapper', 'time']) ?: $stored_time ?: NULL,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $step = (int) ($form_state->get('step') ?? 1);
    if ($step === 4) {
      $date = (string) ($form_state->getValue(['steps_wrapper', 'date']) ?? '');
      $time = (string) ($form_state->getValue(['steps_wrapper', 'time']) ?? '');
      if ($date === '') {
        $form_state->setErrorByName('steps_wrapper][date', $this->t('Veuillez choisir une date depuis le calendrier.'));
      }
      if ($time === '') {
        $form_state->setErrorByName('steps_wrapper][time', $this->t('Veuillez choisir une heure.'));
      }
    }
  }

  protected function stepFive(FormStateInterface $form_state) {
    $store = $this->tempStoreFactory->get('appointment_booking');
    $account = \Drupal::currentUser();
    $default_name = $store->get('customer_name');
    $default_email = $store->get('customer_email');
    $default_phone = $store->get('customer_phone');

    // If user is authenticated, prefer their account info as defaults.
    if ($account->isAuthenticated()) {
      $user = $this->entityTypeManager->getStorage('user')->load($account->id());
      if ($user) {
        $default_name = $default_name ?: $user->getDisplayName();
        $default_email = $default_email ?: $user->getEmail();
      }
    }
    return [
      'personal' => [
        'name' => [
          '#type' => 'textfield',
          '#title' => $this->t('Nom complet'),
          '#required' => TRUE,
          '#default_value' => $form_state->getValue(['steps_wrapper', 'personal', 'name']) ?: $default_name ?: NULL,
        ],
        'email' => [
          '#type' => 'email',
          '#title' => $this->t('Email'),
          '#required' => TRUE,
          '#default_value' => $form_state->getValue(['steps_wrapper', 'personal', 'email']) ?: $default_email ?: NULL,
        ],
        'phone' => [
          '#type' => 'tel',
          '#title' => $this->t('Numéro de téléphone'),
          '#required' => TRUE,
          '#default_value' => $form_state->getValue(['steps_wrapper', 'personal', 'phone']) ?: $default_phone ?: NULL,
        ],
      ],
    ];
  }

  protected function stepSix(FormStateInterface $form_state) {
    return [
      '#theme' => 'item_list',
      '#attributes' => ['class' => ['appointment-booking__confirm']],
      '#items' => $this->buildSummaryItems(),
    ];
  }

  public function nextSubmit(array &$form, FormStateInterface $form_state): void {
    $step = (int) ($form_state->get('step') ?? 1);
    $store = $this->tempStoreFactory->get('appointment_booking');

    // Persist key choices as we go (useful later for availability logic).
    if ($step === 1) {
      $store->set('agency', $form_state->getValue(['steps_wrapper', 'agency']));
    }
    if ($step === 2) {
      $store->set('appointment_type', $form_state->getValue(['steps_wrapper', 'appointment_type']));
    }
    if ($step === 3) {
      $store->set('adviser', $form_state->getValue(['steps_wrapper', 'adviser']));
    }
    if ($step === 4) {
      $store->set('date', $form_state->getValue(['steps_wrapper', 'date']));
      $store->set('time', $form_state->getValue(['steps_wrapper', 'time']));
    }
    if ($step === 5) {
      $store->set('customer_name', $form_state->getValue(['steps_wrapper', 'personal', 'name']));
      $store->set('customer_email', $form_state->getValue(['steps_wrapper', 'personal', 'email']));
      $store->set('customer_phone', $form_state->getValue(['steps_wrapper', 'personal', 'phone']));
    }

    $form_state->set('step', min(6, $step + 1));
    $form_state->setRebuild();
  }

  public function backSubmit(array &$form, FormStateInterface $form_state): void {
    $step = (int) ($form_state->get('step') ?? 1);
    $form_state->set('step', max(1, $step - 1));
    $form_state->setRebuild();
  }

  /**
   * Placeholder: advisers by agency.
   */
  protected function getAdvisersByAgency($agency_id): array {
    $storage = $this->entityTypeManager->getStorage('user');
    $query = $storage->getQuery()
      ->condition('status', 1)
      ->condition('roles', 'adviser')
      ->accessCheck(TRUE);

    // If users are linked to an agency via field_agency, filter by it,
    // but only if that field actually exists on the user entity.
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

  /**
   * Placeholder: available times for adviser/date.
   */
  protected function getAvailableTimes($adviser_id, $date): array {
    // Simple example of fixed slots, could be replaced by real availability.
    $slots = [
      '09:00',
      '10:00',
      '11:00',
      '14:00',
      '15:00',
    ];

    $options = [];
    foreach ($slots as $slot) {
      $options[$slot] = $this->t('@time', ['@time' => $slot]);
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   *
   * For now this just shows a confirmation message. In a later step we can
   * create an Appointment entity from the collected values.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $store = $this->tempStoreFactory->get('appointment_booking');

    $agency = (int) $store->get('agency');
    $type = (int) $store->get('appointment_type');
    $adviser = (int) $store->get('adviser');
    $date = (string) $store->get('date');
    $time = (string) $store->get('time');
    $customer_name = (string) $store->get('customer_name');
    $customer_email = (string) $store->get('customer_email');
    $customer_phone = (string) $store->get('customer_phone');

    // Basic guard (should already be validated by steps).
    if (!$agency || !$type || !$adviser || !$date || !$time || !$customer_name || !$customer_email || !$customer_phone) {
      $this->messenger()->addError($this->t('Veuillez compléter toutes les étapes.'));
      $form_state->set('step', 1)->setRebuild();
      return;
    }

    // Convert from site timezone to UTC for storage.
    $site_timezone = $this->configFactory->get('system.date')->get('timezone.default') ?: 'UTC';
    $date_time = new DrupalDateTime($date . ' ' . $time, new DateTimeZone($site_timezone));
    $now = new DrupalDateTime('now', new DateTimeZone($site_timezone));

    // Basic validation: the chosen slot must be in the future.
    if ($date_time <= $now) {
      $this->messengerService->addError($this->t('Veuillez choisir un créneau futur.'));
      $form_state->set('step', 4)->setRebuild();
      return;
    }

    $date_time->setTimezone(new DateTimeZone('UTC'));
    $appointment_date = $date_time->format('Y-m-d\TH:i:s');

    // Prevent double booking for same adviser and timeslot.
    $storage = $this->entityTypeManager->getStorage('appointment');
    $exists = $storage->getQuery()
      ->condition('adviser', $adviser)
      ->condition('appointment_date', $appointment_date)
      ->condition('status', 'cancelled', '!=')
      ->accessCheck(TRUE)
      ->range(0, 1)
      ->execute();
    if ($exists) {
      $this->messenger()->addError($this->t('Ce créneau est déjà réservé pour ce conseiller. Veuillez choisir une autre heure.'));
      $form_state->set('step', 4)->setRebuild();
      return;
    }

    /** @var \Drupal\appointment\Entity\AppointmentEntity $appointment */
    $appointment = AppointmentEntity::create([
      'title' => 'Rendez-vous',
      'appointment_date' => $appointment_date,
      'agency' => $agency,
      'appointment_type' => $type,
      'adviser' => $adviser,
      'customer_name' => $customer_name,
      'customer_email' => $customer_email,
      'customer_phone' => $customer_phone,
      'status' => 'pending',
    ]);
    $appointment->save();

    // Send confirmation email with a unique reference if available.
    $reference = $appointment->hasField('reference') ? $appointment->get('reference')->value : $appointment->id();
    $site_mail = $this->configFactory->get('system.site')->get('mail');
    $langcode = $this->configFactory->get('system.site')->get('langcode') ?: 'fr';

    $params = [
      'appointment' => $appointment,
      'reference' => $reference,
    ];
    $this->mailManager->mail('appointment', 'booking_confirmation', $customer_email, $langcode, $params, $site_mail, TRUE);

    // Clear tempstore.
    foreach ([
      'agency',
      'appointment_type',
      'adviser',
      'date',
      'time',
      'customer_name',
      'customer_email',
      'customer_phone',
    ] as $key) {
      $store->delete($key);
    }

    $this->messenger()->addStatus($this->t('Votre rendez-vous a bien été enregistré.'));
    $form_state->setRedirect('appointment.booking_manage');
  }

  /**
   * Builds the confirmation summary items from tempstore.
   */
  protected function buildSummaryItems(): array {
    $store = $this->tempStoreFactory->get('appointment_booking');

    $agency_id = $store->get('agency');
    $type_id = $store->get('appointment_type');
    $adviser_id = $store->get('adviser');

    $agency_label = $agency_id ? $this->entityTypeManager->getStorage('agency')->load($agency_id)?->label() : NULL;
    $type_label = $type_id ? $this->entityTypeManager->getStorage('taxonomy_term')->load($type_id)?->label() : NULL;
    $adviser_label = $adviser_id ? $this->entityTypeManager->getStorage('user')->load($adviser_id)?->label() : NULL;

    $items = [];
    $items[] = $this->t('Agence: @v', ['@v' => $agency_label ?: '-']);
    $items[] = $this->t('Type: @v', ['@v' => $type_label ?: '-']);
    $items[] = $this->t('Conseiller: @v', ['@v' => $adviser_label ?: '-']);
    $items[] = $this->t('Date: @v', ['@v' => trim(($store->get('date') ?: '-') . ' ' . ($store->get('time') ?: ''))]);
    $items[] = $this->t('Nom: @v', ['@v' => $store->get('customer_name') ?: '-']);
    $items[] = $this->t('Email: @v', ['@v' => $store->get('customer_email') ?: '-']);
    $items[] = $this->t('Téléphone: @v', ['@v' => $store->get('customer_phone') ?: '-']);

    return $items;
  }
}