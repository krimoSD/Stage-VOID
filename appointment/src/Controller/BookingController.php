<?php

namespace Drupal\appointment\Controller;

use Drupal\appointment\Entity\AppointmentEntity;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles the public multi-step booking flow.
 */
class BookingController extends ControllerBase {

  /**
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $store;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  public function __construct(PrivateTempStoreFactory $temp_store_factory, AccountProxyInterface $current_user, RequestStack $request_stack) {
    $this->store = $temp_store_factory->get('appointment_booking');
    $this->currentUser = $current_user;
    $this->requestStack = $request_stack;
  }

  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('tempstore.private'),
      $container->get('current_user'),
      $container->get('request_stack'),
    );
  }

  /**
   * Entry point for the booking wizard.
   */
  public function step(Request $request) {
    $step = (int) $request->query->get('step', 1);
    $data = $this->store->get('data') ?? [];

    // Also accept selections via query params so the wizard works even if
    // tempstore/session state is not persisted (e.g., anonymous + caching).
    if ($request->query->has('agency')) {
      $data['agency'] = (int) $request->query->get('agency');
    }
    if ($request->query->has('type')) {
      $data['appointment_type'] = (int) $request->query->get('type');
    }
    if ($request->query->has('adviser')) {
      $data['adviser'] = (int) $request->query->get('adviser');
    }
    if ($request->query->has('date')) {
      $data['appointment_date'] = (string) $request->query->get('date');
    }

    $this->store->set('data', $data);

    switch ($step) {
      case 1:
        return $this->buildAgencyStep($data);

      case 2:
        if (empty($data['agency'])) {
          return $this->redirectToStep(1, $data);
        }
        return $this->buildTypeStep($data);

      case 3:
        if (empty($data['appointment_type'])) {
          return $this->redirectToStep(2, $data);
        }
        return $this->buildAdviserStep($data);

      case 4:
        if (empty($data['adviser'])) {
          return $this->redirectToStep(3, $data);
        }
        return $this->buildDateStep($data);

      case 5:
        if (empty($data['appointment_date'])) {
          return $this->redirectToStep(4, $data);
        }
        return $this->buildPersonalStep($data);

      case 6:
        if (empty($data['customer'])) {
          return $this->redirectToStep(5, $data);
        }
        return $this->buildConfirmationStep($data);
    }

    return $this->redirectToStep(1, $data);
  }

  protected function redirectToStep(int $step, array $data = []): RedirectResponse {
    $query = ['step' => $step];
    if (!empty($data['agency'])) {
      $query['agency'] = (int) $data['agency'];
    }
    if (!empty($data['appointment_type'])) {
      $query['type'] = (int) $data['appointment_type'];
    }
    if (!empty($data['adviser'])) {
      $query['adviser'] = (int) $data['adviser'];
    }
    if (!empty($data['appointment_date'])) {
      $query['date'] = (string) $data['appointment_date'];
    }
    $url = Url::fromRoute('appointment.booking_step', [], ['query' => $query]);
    return new RedirectResponse($url->toString());
  }

  /**
   * Step 1: Agency selection.
   */
  protected function buildAgencyStep(array $data): array {
    $agencies = $this->entityTypeManager()->getStorage('agency')->loadMultiple();

    $items = [];
    foreach ($agencies as $agency) {
      $url = Url::fromRoute('appointment.booking_step', [], [
        'query' => [
          // Still on step 1; we only select the agency here.
          'step' => 1,
          'agency' => $agency->id(),
        ],
      ]);
      $items[] = Link::fromTextAndUrl($agency->label(), $url)->toRenderable();
    }

    $selected = (int) $this->requestStack->getCurrentRequest()->query->get('agency');
    if ($selected) {
      $data['agency'] = $selected;
      $this->store->set('data', $data);
      return $this->redirectToStep(2, $data);
    }

    return [
      '#theme' => 'item_list',
      '#title' => $this->t('Choisissez une agence'),
      '#items' => $items,
    ];
  }

  /**
   * Step 2: Appointment type (taxonomy) selection.
   */
  protected function buildTypeStep(array $data): array {
    $vocabulary = 'appointment_type';
    $terms = $this->entityTypeManager()->getStorage('taxonomy_term')
      ->loadTree($vocabulary);

    $items = [];
    foreach ($terms as $term) {
      $url = Url::fromRoute('appointment.booking_step', [], [
        'query' => [
          // Still on step 2; we only select the type here.
          'step' => 2,
          'type' => $term->tid,
        ],
      ]);
      $items[] = Link::fromTextAndUrl($term->name, $url)->toRenderable();
    }

    $selected = (int) $this->requestStack->getCurrentRequest()->query->get('type');
    if ($selected) {
      $data['appointment_type'] = $selected;
      $this->store->set('data', $data);
      return $this->redirectToStep(3, $data);
    }

    return [
      '#theme' => 'item_list',
      '#title' => $this->t('Choisissez le type de rendez-vous'),
      '#items' => $items,
    ];
  }

  /**
   * Step 3: Adviser selection.
   */
  protected function buildAdviserStep(array $data): array {
    $storage = $this->entityTypeManager()->getStorage('user');
    $query = $storage->getQuery()
      ->condition('status', 1)
      ->condition('roles', 'adviser');

    $adviser_ids = $query->execute();
    $advisers = $storage->loadMultiple($adviser_ids);

    $items = [];
    foreach ($advisers as $account) {
      $url = Url::fromRoute('appointment.booking_step', [], [
        'query' => [
          // Still on step 3; we only select the adviser here.
          'step' => 3,
          'adviser' => $account->id(),
        ],
      ]);
      $items[] = Link::fromTextAndUrl($account->label(), $url)->toRenderable();
    }

    $selected = (int) $this->requestStack->getCurrentRequest()->query->get('adviser');
    if ($selected) {
      $data['adviser'] = $selected;
      $this->store->set('data', $data);
      return $this->redirectToStep(4, $data);
    }

    return [
      '#theme' => 'item_list',
      '#title' => $this->t('Sélectionnez un conseiller'),
      '#items' => $items,
    ];
  }

  /**
   * Step 4: Date & time selection (simple datetime field for now).
   */
  protected function buildDateStep(array $data): array {
    $form = [
      '#title' => $this->t('Choisissez le jour et l’heure'),
      '#type' => 'container',
      'form' => [
        '#type' => 'form',
        '#attributes' => ['method' => 'get'],
        'step' => [
          '#type' => 'hidden',
          '#value' => 5,
        ],
        'date' => [
          '#type' => 'datetime',
          '#title' => $this->t('Date & heure'),
          '#required' => TRUE,
        ],
        'actions' => [
          '#type' => 'actions',
          'submit' => [
            '#type' => 'submit',
            '#value' => $this->t('Suivant'),
          ],
        ],
      ],
      '#attached' => [
        'library' => [],
      ],
    ];

    // Minimal submit handling via request.
    $request = $this->requestStack->getCurrentRequest();
    if ($request->getMethod() === 'POST' || $request->get('date')) {
      $value = $request->get('date');
      if ($value) {
        $datetime = new DrupalDateTime($value);
        $data['appointment_date'] = $datetime->format(DATE_ATOM);
        $this->store->set('data', $data);
        return $this->redirectToStep(5, $data);
      }
    }

    return $form;
  }

  /**
   * Step 5: Personal information form.
   */
  protected function buildPersonalStep(array $data): array {
    $request = $this->requestStack->getCurrentRequest();
    if ($request->getMethod() === 'POST') {
      $customer = [
        'name' => $request->request->get('customer_name'),
        'email' => $request->request->get('customer_email'),
        'phone' => $request->request->get('customer_phone'),
      ];
      if (!empty($customer['name']) && !empty($customer['email']) && !empty($customer['phone'])) {
        $data['customer'] = $customer;
        $this->store->set('data', $data);
        return $this->redirectToStep(6);
      }
    }

    return [
      '#title' => $this->t('Renseignez vos informations'),
      'form' => [
        '#type' => 'form',
        '#attributes' => ['method' => 'post'],
        'customer_name' => [
          '#type' => 'textfield',
          '#title' => $this->t('Nom complet'),
          '#required' => TRUE,
        ],
        'customer_email' => [
          '#type' => 'email',
          '#title' => $this->t('Email'),
          '#required' => TRUE,
        ],
        'customer_phone' => [
          '#type' => 'textfield',
          '#title' => $this->t('Téléphone'),
          '#required' => TRUE,
        ],
        'actions' => [
          '#type' => 'actions',
          'submit' => [
            '#type' => 'submit',
            '#value' => $this->t('Suivant'),
          ],
        ],
      ],
    ];
  }

  /**
   * Step 6: Confirmation Summary + entity creation.
   */
  protected function buildConfirmationStep(array $data): array {
    $request = $this->requestStack->getCurrentRequest();
    if ($request->getMethod() === 'POST') {
      // Prevent double booking for same adviser and timeslot.
      $storage = $this->entityTypeManager()->getStorage('appointment');
      $exists = $storage->getQuery()
        ->condition('adviser', $data['adviser'])
        ->condition('appointment_date', $data['appointment_date'])
        ->condition('status', 'cancelled', '!=')
        ->range(0, 1)
        ->execute();
      if ($exists) {
        $this->messenger()->addError($this->t('Ce créneau est déjà réservé pour ce conseiller. Veuillez choisir une autre heure.'));
        return $this->redirectToStep(4);
      }

      /** @var \Drupal\appointment\Entity\AppointmentEntity $appointment */
      $appointment = AppointmentEntity::create([
        'title' => 'Rendez-vous',
        'appointment_date' => $data['appointment_date'],
        'agency' => $data['agency'],
        'appointment_type' => $data['appointment_type'],
        'adviser' => $data['adviser'],
        'customer_name' => $data['customer']['name'],
        'customer_email' => $data['customer']['email'],
        'customer_phone' => $data['customer']['phone'],
        'status' => 'pending',
      ]);
      $appointment->save();

      $this->store->delete('data');
      $this->messenger()->addStatus($this->t('Votre rendez-vous a bien été enregistré.'));

      $url = Url::fromRoute('appointment.booking_manage');
      return new RedirectResponse($url->toString());
    }

    $appointment_date = new DrupalDateTime($data['appointment_date']);

    $items = [
      $this->t('Agence: @agency', ['@agency' => $data['agency']]),
      $this->t('Conseiller: @adviser', ['@adviser' => $data['adviser']]),
      $this->t('Date: @date', ['@date' => $appointment_date->format('d/m/Y H:i')]),
      $this->t('Nom: @name', ['@name' => $data['customer']['name']]),
      $this->t('Email: @email', ['@email' => $data['customer']['email']]),
      $this->t('Téléphone: @phone', ['@phone' => $data['customer']['phone']]),
    ];

    return [
      '#title' => $this->t('Confirmer votre rendez-vous'),
      'summary' => [
        '#theme' => 'item_list',
        '#items' => $items,
      ],
      'form' => [
        '#type' => 'form',
        '#attributes' => ['method' => 'post'],
        'actions' => [
          '#type' => 'actions',
          'submit' => [
            '#type' => 'submit',
            '#value' => $this->t('Confirmer'),
          ],
        ],
      ],
    ];
  }

  /**
   * User appointment list page.
   */
  public function userAppointments(): array {
    $account = $this->currentUser;
    if (!$account->isAuthenticated()) {
      return [
        '#title' => $this->t('Mes rendez-vous'),
        '#markup' => $this->t('Veuillez vous connecter pour voir vos rendez-vous.'),
      ];
    }

    $storage = $this->entityTypeManager()->getStorage('appointment');
    $query = $storage->getQuery()
      ->condition('customer_email', $this->currentUser->getEmail())
      ->accessCheck(TRUE);

    $ids = $query->execute();
    $appointments = $storage->loadMultiple($ids);

    $rows = [];
    foreach ($appointments as $appointment) {
      /** @var \Drupal\appointment\Entity\AppointmentEntity $appointment */
      $agency_label = $appointment->get('agency')->entity?->label() ?? '-';
      $adviser_label = $appointment->get('adviser')->entity?->label() ?? '-';
      $type_label = $appointment->get('appointment_type')->entity?->label() ?? '-';
      $status_value = (string) ($appointment->get('status')->value ?? '');
      $allowed = $appointment->getFieldDefinition('status')->getSetting('allowed_values') ?? [];
      $status_label = $allowed[$status_value] ?? $status_value ?: '-';

      $date_value = $appointment->get('appointment_date')->value;
      $date_display = '-';
      if (!empty($date_value)) {
        $dt = new DrupalDateTime($date_value);
        $site_tz = $this->config('system.date')->get('timezone.default') ?: 'UTC';
        $dt->setTimezone(new \DateTimeZone($site_tz));
        $date_display = $dt->format('d/m/Y H:i');
      }

      $operations = [];
      if ($appointment->access('update', $account)) {
        $dest = Url::fromRoute('appointment.manage_modify', ['appointment' => $appointment->id()])->toString();
        $operations[] = Link::fromTextAndUrl($this->t('Modifier'), Url::fromRoute('appointment.manage_lookup', [], ['query' => ['destination' => $dest]]))->toRenderable();
      }
      if ($appointment->access('delete', $account)) {
        $dest = Url::fromRoute('appointment.manage_cancel', ['appointment' => $appointment->id()])->toString();
        $operations[] = Link::fromTextAndUrl($this->t('Supprimer'), Url::fromRoute('appointment.manage_lookup', [], ['query' => ['destination' => $dest]]))->toRenderable();
      }

      $rows[] = [
        'id' => $appointment->id(),
        'date' => $date_display,
        'agency' => $agency_label,
        'adviser' => $adviser_label,
        'type' => $type_label,
        'status' => $status_label,
        'operations' => [
          'data' => [
            '#theme' => 'item_list',
            '#items' => $operations,
            '#attributes' => ['class' => ['appointment-user__ops']],
          ],
        ],
      ];
    }

    return [
      '#attached' => [
        'library' => ['appointment/booking_form'],
      ],
      '#cache' => [
        // This page is user-specific and should reflect immediate changes
        // (create/modify/delete) without requiring a global cache clear.
        'max-age' => 0,
        'contexts' => ['user'],
      ],
      '#prefix' => '<div class="appointment-user">',
      '#suffix' => '</div>',
      '#type' => 'table',
      '#attributes' => ['class' => ['appointment-user__table']],
      '#header' => [
        $this->t('ID'),
        $this->t('Date'),
        $this->t('Agence'),
        $this->t('Conseiller'),
        $this->t('Type de rendez-vous'),
        $this->t('Statut'),
        $this->t('Actions'),
      ],
      '#rows' => $rows,
      '#empty' => $this->t('Aucun rendez-vous.'),
    ];
  }

}

