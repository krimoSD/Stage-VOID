<?php

namespace Drupal\appointment\Controller;

use Drupal\appointment\Entity\AppointmentEntity;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handles appointment modification and cancellation by users.
 */
class AppointmentManagementController extends ControllerBase {

  /**
   * Step 1: Lookup by phone number.
   */
  public function lookup(Request $request): array {
    $results = [];
    $phone = $request->getMethod() === 'POST'
      ? $request->request->get('phone')
      : '';

    if ($phone) {
      $storage = $this->entityTypeManager()->getStorage('appointment');
      $ids = $storage->getQuery()
        ->condition('customer_phone', $phone)
        ->condition('status', 'cancelled', '!=')
        ->sort('appointment_date', 'ASC')
        ->execute();

      $appointments = $storage->loadMultiple($ids);
      foreach ($appointments as $appointment) {
        /** @var \Drupal\appointment\Entity\AppointmentEntity $appointment */
        $date = new DrupalDateTime($appointment->get('appointment_date')->value);
        $results[] = [
          'id' => $appointment->id(),
          'label' => $appointment->label(),
          'date' => $date->format('d/m/Y H:i'),
          'modify_url' => Url::fromRoute('appointment.manage_modify', ['appointment' => $appointment->id()])->toString(),
          'cancel_url' => Url::fromRoute('appointment.manage_cancel', ['appointment' => $appointment->id()])->toString(),
        ];
      }
    }

    return [
      '#title' => $this->t('Modifier un rendez-vous'),
      'form' => [
        '#type' => 'form',
        '#attributes' => ['method' => 'post'],
        'phone' => [
          '#type' => 'textfield',
          '#title' => $this->t('Numéro de téléphone'),
          '#required' => TRUE,
        ],
        'actions' => [
          '#type' => 'actions',
          'submit' => [
            '#type' => 'submit',
            '#value' => $this->t('Valider'),
          ],
        ],
      ],
      'list' => [
        '#theme' => 'table',
        '#header' => [
          $this->t('Rendez-vous'),
          $this->t('Date'),
          $this->t('Actions'),
        ],
        '#rows' => array_map(function ($row) {
          return [
            $row['label'],
            $row['date'],
            [
              'data' => [
                '#type' => 'inline_template',
                '#template' => '<a href="{{ modify }}">{{ "Modifier"|t }}</a> | <a href="{{ cancel }}">{{ "Supprimer"|t }}</a>',
                '#context' => [
                  'modify' => $row['modify_url'],
                  'cancel' => $row['cancel_url'],
                ],
              ],
            ],
          ];
        }, $results),
        '#empty' => $phone ? $this->t('Aucun rendez-vous trouvé pour ce numéro.') : '',
      ],
    ];
  }

  /**
   * Step 2: Modify selected appointment (date/time only for now).
   */
  public function modify(AppointmentEntity $appointment, Request $request): array {
    if ($request->getMethod() === 'POST') {
      $value = $request->request->get('date');
      if ($value) {
        $date = new DrupalDateTime($value);
        $appointment->set('appointment_date', $date->format(DATE_ATOM));
        $appointment->save();
        $this->messenger()->addStatus($this->t('Votre rendez-vous a été mis à jour.'));
      }
    }

    $current = new DrupalDateTime($appointment->get('appointment_date')->value);

    return [
      '#title' => $this->t('Modifier le rendez-vous'),
      'form' => [
        '#type' => 'form',
        '#attributes' => ['method' => 'post'],
        'date' => [
          '#type' => 'datetime',
          '#title' => $this->t('Nouvelle date et heure'),
          '#default_value' => $current,
          '#required' => TRUE,
        ],
        'actions' => [
          '#type' => 'actions',
          'submit' => [
            '#type' => 'submit',
            '#value' => $this->t('Enregistrer'),
          ],
        ],
      ],
    ];
  }

  /**
   * Step 3: Cancel appointment.
   */
  public function cancel(AppointmentEntity $appointment, Request $request): array {
    if ($request->getMethod() === 'POST') {
      $appointment->set('status', 'cancelled');
      $appointment->save();
      $this->messenger()->addStatus($this->t('Votre rendez-vous a été annulé.'));
    }

    $date = new DrupalDateTime($appointment->get('appointment_date')->value);

    return [
      '#title' => $this->t('Annuler le rendez-vous'),
      'summary' => [
        '#markup' => $this->t('Rendez-vous le @date.', [
          '@date' => $date->format('d/m/Y H:i'),
        ]),
      ],
      'form' => [
        '#type' => 'form',
        '#attributes' => ['method' => 'post'],
        'actions' => [
          '#type' => 'actions',
          'submit' => [
            '#type' => 'submit',
            '#value' => $this->t('Confirmer l\'annulation'),
          ],
        ],
      ],
    ];
  }

}

