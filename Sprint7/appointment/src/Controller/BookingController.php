<?php

namespace Drupal\appointment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * User-facing appointment listing controller.
 */
class BookingController extends ControllerBase {

  public function __construct(
    protected AccountProxyInterface $currentUserAccount,
  ) {}

  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('current_user'),
    );
  }

  /**
   * User appointment list page.
   */
  public function userAppointments(): array {
    $account = $this->currentUserAccount;
    if (!$account->isAuthenticated()) {
      return [
        '#title' => $this->t('Mes rendez-vous'),
        '#markup' => $this->t('Veuillez vous connecter pour voir vos rendez-vous.'),
      ];
    }

    $storage = $this->entityTypeManager()->getStorage('appointment');
    $ids = $storage->getQuery()
      ->condition('customer_email', $account->getEmail())
      ->accessCheck(TRUE)
      ->execute();
    $appointments = $storage->loadMultiple($ids);

    $rows = [];
    foreach ($appointments as $appointment) {
      $agency_label = $appointment->get('agency')->entity?->label() ?? '-';
      $adviser_label = $appointment->get('adviser')->entity?->label() ?? '-';
      $type_label = $appointment->get('appointment_type')->entity?->label() ?? '-';

      $status_value = (string) ($appointment->get('status')->value ?? '');
      $allowed = $appointment->getFieldDefinition('status')->getSetting('allowed_values') ?? [];
      $status_label = $allowed[$status_value] ?? $status_value ?: '-';

      $date_display = '-';
      $date_value = (string) ($appointment->get('appointment_date')->value ?? '');
      if ($date_value !== '') {
        $dt = new DrupalDateTime($date_value, new \DateTimeZone('UTC'));
        $site_tz = (string) ($this->config('system.date')->get('timezone.default') ?: 'UTC');
        $dt->setTimezone(new \DateTimeZone($site_tz));
        $date_display = $dt->format('d/m/Y H:i');
      }

      $operations = [];
      if ($appointment->access('update', $account)) {
        $dest = Url::fromRoute('appointment.manage_modify', ['appointment' => $appointment->id()])->toString();
        $operations[] = Link::fromTextAndUrl(
          $this->t('Modifier'),
          Url::fromRoute('appointment.manage_lookup', [], ['query' => ['destination' => $dest]])
        )->toRenderable();
      }
      if ($appointment->access('delete', $account)) {
        $dest = Url::fromRoute('appointment.manage_cancel', ['appointment' => $appointment->id()])->toString();
        $operations[] = Link::fromTextAndUrl(
          $this->t('Supprimer'),
          Url::fromRoute('appointment.manage_lookup', [], ['query' => ['destination' => $dest]])
        )->toRenderable();
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