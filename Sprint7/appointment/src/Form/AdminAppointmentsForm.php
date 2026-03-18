<?php

namespace Drupal\appointment\Form;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;
use DateTimeZone;

/**
 * Admin dashboard: list/filter/sort/export appointments.
 */
class AdminAppointmentsForm extends FormBase {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected FileSystemInterface $fileSystem,
    protected PrivateTempStoreFactory $tempStoreFactory,
    protected ConfigFactoryInterface $systemConfigFactory,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('file_system'),
      $container->get('tempstore.private'),
      $container->get('config.factory'),
    );
  }

  public function getFormId(): string {
    return 'appointment_admin_appointments_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#attached']['library'][] = 'appointment/booking_form';
    $form['#prefix'] = '<div class="appointment-admin">';
    $form['#suffix'] = '</div>';

    $request = $this->getRequest();
    $query = $request->query;

    $agency_id = (int) $query->get('agency', 0);
    $adviser_id = (int) $query->get('adviser', 0);
    $date_from = (string) $query->get('date_from', '');
    $date_to = (string) $query->get('date_to', '');
    $status = (string) $query->get('status', '');

    $sort = (string) $query->get('sort', 'appointment_date');
    $order = strtoupper((string) $query->get('order', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

    $form['filters'] = [
      '#type' => 'details',
      '#title' => $this->t('Filtres'),
      '#open' => TRUE,
    ];

    $form['filters']['row'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['appointment-admin__filters-row']],
    ];

    $form['filters']['row']['date_from'] = [
      '#type' => 'date',
      '#title' => $this->t('Date (du)'),
      '#default_value' => $date_from ?: NULL,
    ];
    $form['filters']['row']['date_to'] = [
      '#type' => 'date',
      '#title' => $this->t('Date (au)'),
      '#default_value' => $date_to ?: NULL,
    ];

    $form['filters']['row']['agency'] = [
      '#type' => 'select',
      '#title' => $this->t('Agence'),
      '#options' => [0 => $this->t('- Toutes -')] + $this->getAgencies(),
      '#default_value' => $agency_id ?: 0,
    ];

    $form['filters']['row']['adviser'] = [
      '#type' => 'select',
      '#title' => $this->t('Conseiller'),
      '#options' => [0 => $this->t('- Tous -')] + $this->getAdvisers(),
      '#default_value' => $adviser_id ?: 0,
    ];

    $form['filters']['row']['status'] = [
      '#type' => 'select',
      '#title' => $this->t('Statut'),
      '#options' => [
        '' => $this->t('- Tous -'),
        'pending' => $this->t('Pending'),
        'confirmed' => $this->t('Confirmed'),
        'cancelled' => $this->t('Cancelled'),
      ],
      '#default_value' => $status,
    ];

    $form['filters']['row']['actions'] = [
      '#type' => 'actions',
      '#attributes' => ['class' => ['appointment-admin__filters-actions']],
    ];
    $form['filters']['row']['actions']['apply'] = [
      '#type' => 'submit',
      '#value' => $this->t('Appliquer'),
      '#submit' => ['::applyFiltersSubmit'],
    ];
    $form['filters']['row']['actions']['reset'] = [
      '#type' => 'submit',
      '#value' => $this->t('Réinitialiser'),
      '#submit' => ['::resetFiltersSubmit'],
    ];

    $form['export'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['appointment-admin__export']],
    ];
    $form['export']['export_csv'] = [
      '#type' => 'submit',
      '#value' => $this->t('Exporter CSV'),
      '#submit' => ['::exportCsvSubmit'],
    ];

    $header = [
      'id' => [
        'data' => $this->t('ID'),
        'field' => 'id',
      ],
      'date' => [
        'data' => $this->t('Date'),
        'field' => 'appointment_date',
      ],
      'agency' => [
        'data' => $this->t('Agence'),
        'field' => 'agency',
      ],
      'adviser' => [
        'data' => $this->t('Conseiller'),
        'field' => 'adviser',
      ],
      'type' => [
        'data' => $this->t('Type'),
        'field' => 'appointment_type',
      ],
      'status' => [
        'data' => $this->t('Statut'),
        'field' => 'status',
      ],
      'operations' => $this->t('Actions'),
    ];

    // Whitelist sorting fields.
    $allowed_sort = ['id', 'appointment_date', 'agency', 'adviser', 'appointment_type', 'status'];
    if (!in_array($sort, $allowed_sort, TRUE)) {
      $sort = 'appointment_date';
    }

    $ids = $this->buildAppointmentQuery($agency_id, $adviser_id, $date_from, $date_to, $status)
      ->sort($sort, $order)
      ->pager(50)
      ->execute();

    $storage = $this->entityTypeManager->getStorage('appointment');
    $appointments = $storage->loadMultiple($ids);

    $site_tz = $this->systemConfigFactory->get('system.date')->get('timezone.default') ?: 'UTC';
    $rows = [];
    foreach ($appointments as $appointment) {
      $date_value = (string) $appointment->get('appointment_date')->value;
      $date_display = $date_value;
      if ($date_value) {
        $dt = new DrupalDateTime($date_value, new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone($site_tz));
        $date_display = $dt->format('Y-m-d H:i');
      }

      $edit_link = Link::fromTextAndUrl($this->t('Edit'), Url::fromRoute('entity.appointment.edit_form', ['appointment' => $appointment->id()]))->toRenderable();

      $rows[] = [
        'id' => $appointment->id(),
        'date' => $date_display,
        'agency' => $appointment->get('agency')->entity?->label() ?? '-',
        'adviser' => $appointment->get('adviser')->entity?->label() ?? '-',
        'type' => $appointment->get('appointment_type')->entity?->label() ?? '-',
        'status' => (string) $appointment->get('status')->value,
        'operations' => [
          'data' => [
            '#theme' => 'item_list',
            '#items' => [$edit_link],
            '#attributes' => ['class' => ['appointment-admin__ops']],
          ],
        ],
      ];
    }

    $form['table'] = [
      '#type' => 'table',
      '#attributes' => ['class' => ['appointment-admin__table']],
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('Aucun rendez-vous trouvé.'),
    ];

    $form['pager'] = ['#type' => 'pager'];

    // Persist current query so export uses the same filters.
    $form_state->set('current_filters', [
      'agency' => $agency_id,
      'adviser' => $adviser_id,
      'date_from' => $date_from,
      'date_to' => $date_to,
      'status' => $status,
      'sort' => $sort,
      'order' => $order,
    ]);

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // No-op: specific submit handlers used.
  }

  public function applyFiltersSubmit(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();
    $form_state->setRedirect('appointment.admin_dashboard', [], [
      'query' => [
        'date_from' => $values['date_from'] ?? '',
        'date_to' => $values['date_to'] ?? '',
        'agency' => (int) ($values['agency'] ?? 0),
        'adviser' => (int) ($values['adviser'] ?? 0),
        'status' => (string) ($values['status'] ?? ''),
      ],
    ]);
  }

  public function resetFiltersSubmit(array &$form, FormStateInterface $form_state): void {
    $form_state->setRedirect('appointment.admin_dashboard');
  }

  public function exportCsvSubmit(array &$form, FormStateInterface $form_state): void {
    $filters = (array) $form_state->get('current_filters');

    $ids = $this->buildAppointmentQuery(
      (int) ($filters['agency'] ?? 0),
      (int) ($filters['adviser'] ?? 0),
      (string) ($filters['date_from'] ?? ''),
      (string) ($filters['date_to'] ?? ''),
      (string) ($filters['status'] ?? '')
    )->execute();

    $token = bin2hex(random_bytes(16));
    $uri = 'temporary://appointment-export-' . date('Ymd-His') . '-' . $token . '.csv';

    $store = $this->tempStoreFactory->get('appointment_admin_export');
    $store->set($token, [
      'uri' => $uri,
      'created' => time(),
      'status' => 'building',
    ]);

    $chunks = array_chunk(array_values($ids), 500);
    $builder = (new BatchBuilder())
      ->setTitle($this->t('Export CSV'))
      ->setInitMessage($this->t('Préparation de l’export…'))
      ->setProgressMessage($this->t('Export en cours… (@current/@total)'))
      ->setErrorMessage($this->t('Erreur pendant l’export.'))
      ->setFinishCallback([static::class, 'exportFinished']);

    // Write header first.
    $builder->addOperation([static::class, 'exportWriteHeader'], [$uri, $token]);

    foreach ($chunks as $chunk) {
      $builder->addOperation([static::class, 'exportWriteChunk'], [$uri, $chunk]);
    }

    $batch = $builder->toArray();
    batch_set($batch);
  }

  public static function exportWriteHeader(string $uri, string $token, array &$context): void {
    $path = \Drupal::service('file_system')->realpath($uri);
    if (!$path) {
      \Drupal::service('file_system')->saveData('', $uri, FileSystemInterface::EXISTS_REPLACE);
      $path = \Drupal::service('file_system')->realpath($uri);
    }
    $fh = fopen($path, 'w');
    fputcsv($fh, ['ID', 'Date (UTC)', 'Agency', 'Adviser', 'Type', 'Status', 'Customer name', 'Customer email', 'Customer phone']);
    fclose($fh);

    $context['results']['token'] = $token;
  }

  public static function exportWriteChunk(string $uri, array $ids, array &$context): void {
    $storage = \Drupal::entityTypeManager()->getStorage('appointment');
    $appointments = $storage->loadMultiple($ids);

    $path = \Drupal::service('file_system')->realpath($uri);
    $fh = fopen($path, 'a');

    foreach ($appointments as $appointment) {
      fputcsv($fh, [
        $appointment->id(),
        (string) $appointment->get('appointment_date')->value,
        $appointment->get('agency')->entity?->label() ?? '',
        $appointment->get('adviser')->entity?->label() ?? '',
        $appointment->get('appointment_type')->entity?->label() ?? '',
        (string) $appointment->get('status')->value,
        (string) $appointment->get('customer_name')->value,
        (string) $appointment->get('customer_email')->value,
        (string) $appointment->get('customer_phone')->value,
      ]);
    }

    fclose($fh);
  }

  public static function exportFinished(bool $success, array $results, array $operations): void {
    $token = $results['token'] ?? NULL;
    if (!$token) {
      return;
    }

    $store = \Drupal::service('tempstore.private')->get('appointment_admin_export');
    $data = (array) ($store->get($token) ?? []);
    if ($data) {
      $data['status'] = 'ready';
      $store->set($token, $data);
    }

    $link = Link::fromTextAndUrl(t('Télécharger le CSV'), Url::fromRoute('appointment.admin_export_download', ['token' => $token]))->toString();
    \Drupal::messenger()->addStatus(t('Export prêt. @link', ['@link' => $link]));
  }

  protected function buildAppointmentQuery(int $agency_id, int $adviser_id, string $date_from, string $date_to, string $status) {
    $query = $this->entityTypeManager->getStorage('appointment')->getQuery()
      ->accessCheck(TRUE);

    if ($agency_id > 0) {
      $query->condition('agency', $agency_id);
    }
    if ($adviser_id > 0) {
      $query->condition('adviser', $adviser_id);
    }
    if ($status !== '') {
      $query->condition('status', $status);
    }

    $site_tz = $this->systemConfigFactory->get('system.date')->get('timezone.default') ?: 'UTC';
    if ($date_from !== '') {
      $from_local = new DrupalDateTime($date_from . ' 00:00', new DateTimeZone($site_tz));
      $from_utc = clone $from_local;
      $from_utc->setTimezone(new DateTimeZone('UTC'));
      $query->condition('appointment_date', $from_utc->format('Y-m-d\TH:i:s'), '>=');
    }
    if ($date_to !== '') {
      $to_local = new DrupalDateTime($date_to . ' 23:59', new DateTimeZone($site_tz));
      $to_utc = clone $to_local;
      $to_utc->setTimezone(new DateTimeZone('UTC'));
      $query->condition('appointment_date', $to_utc->format('Y-m-d\TH:i:s'), '<=');
    }

    return $query;
  }

  protected function getAgencies(): array {
    $options = [];
    $entities = $this->entityTypeManager->getStorage('agency')->loadMultiple();
    foreach ($entities as $agency) {
      $options[$agency->id()] = $agency->label();
    }
    return $options;
  }

  protected function getAdvisers(): array {
    $storage = $this->entityTypeManager->getStorage('user');
    $ids = $storage->getQuery()
      ->condition('status', 1)
      ->condition('roles', 'adviser')
      ->accessCheck(TRUE)
      ->execute();
    $accounts = $storage->loadMultiple($ids);
    $options = [];
    foreach ($accounts as $account) {
      $options[$account->id()] = $account->label();
    }
    return $options;
  }

}

