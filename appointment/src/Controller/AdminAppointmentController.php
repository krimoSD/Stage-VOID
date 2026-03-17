<?php

namespace Drupal\appointment\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Simple admin listing controller for appointments.
 */
class AdminAppointmentController extends ControllerBase {

  /**
   * Lists all appointments for admins.
   */
  public function list(): array {
    $storage = $this->entityTypeManager()->getStorage('appointment');
    $ids = $storage->getQuery()
      ->sort('appointment_date', 'DESC')
      ->pager(50)
      ->execute();

    $appointments = $storage->loadMultiple($ids);

    $rows = [];
    foreach ($appointments as $appointment) {
      $rows[] = [
        $appointment->id(),
        $appointment->label(),
        $appointment->get('appointment_date')->value,
        $appointment->get('status')->value,
      ];
    }

    $build['#attached']['library'][] = 'appointment/booking_form';

    $build['wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['appointment-admin']],
    ];

    $build['wrapper']['table'] = [
      '#type' => 'table',
      '#attributes' => ['class' => ['appointment-admin__table']],
      '#header' => [
        $this->t('ID'),
        $this->t('Titre'),
        $this->t('Date'),
        $this->t('Statut'),
      ],
      '#rows' => $rows,
      '#empty' => $this->t('Aucun rendez-vous trouvé.'),
    ];

    $build['wrapper']['pager'] = ['#type' => 'pager'];

    return $build;
  }

}

