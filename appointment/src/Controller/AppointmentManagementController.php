<?php

namespace Drupal\appointment\Controller;

use Drupal\appointment\Entity\AppointmentEntity;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\appointment\Form\AppointmentPhoneVerifyForm;
use Drupal\appointment\Form\AppointmentModifyForm;

/**
 * Handles appointment modification and cancellation by users.
 */
class AppointmentManagementController extends ControllerBase {

  /**
   * Tempstore used to persist phone verification for anonymous users.
   */
  protected function manageStore() {
    return \Drupal::service('tempstore.private')->get('appointment_manage');
  }

  /**
   * Whether the current visitor can manage this appointment.
   *
   * All visitors (including logged-in users) must have verified phone
   * in this session, and it must match the appointment's customer phone.
   */
  protected function canManage(AppointmentEntity $appointment): bool {
    $verified_phone = (string) ($this->manageStore()->get('verified_phone') ?? '');
    $customer_phone = (string) $appointment->get('customer_phone')->value;
    return $verified_phone !== '' && $customer_phone !== '' && $verified_phone === $customer_phone;
  }

  /**
   * Returns the site timezone (used for display / user-entered dates).
   */
  protected function siteTimezone(): string {
    return $this->config('system.date')->get('timezone.default') ?: 'UTC';
  }

  /**
   * Simple time slots list (can be replaced by real availability).
   */
  protected function getAvailableTimes(): array {
    $slots = [
      '09:00',
      '10:00',
      '11:00',
      '14:00',
      '15:00',
    ];
    $options = [];
    foreach ($slots as $slot) {
      $options[$slot] = $this->t('@t', ['@t' => $slot]);
    }
    return $options;
  }

  /**
   * Sends an email for appointment lifecycle events.
   */
  protected function sendAppointmentMail(string $key, AppointmentEntity $appointment): void {
    /** @var \Drupal\Core\Mail\MailManagerInterface $mail_manager */
    $mail_manager = \Drupal::service('plugin.manager.mail');
    $site_mail = $this->config('system.site')->get('mail');
    $langcode = $this->config('system.site')->get('langcode') ?: 'fr';

    $reference = $appointment->hasField('reference') ? ($appointment->get('reference')->value ?: $appointment->id()) : $appointment->id();
    $params = [
      'appointment' => $appointment,
      'reference' => $reference,
    ];

    $mail_manager->mail('appointment', $key, $appointment->get('customer_email')->value, $langcode, $params, $site_mail, TRUE);
  }

  /**
   * Step 1: Lookup by phone number.
   */
  public function lookup(Request $request): array {
    $results = [];
    $destination = (string) $request->query->get('destination', '');
    $phone = (string) $request->query->get('phone', '');

    if ($phone) {
      $storage = $this->entityTypeManager()->getStorage('appointment');
      $ids = $storage->getQuery()
        ->condition('customer_phone', $phone)
        ->sort('appointment_date', 'ASC')
        ->accessCheck(TRUE)
        ->execute();

      $appointments = $storage->loadMultiple($ids);
      foreach ($appointments as $appointment) {
        /** @var \Drupal\appointment\Entity\AppointmentEntity $appointment */
        $date = new DrupalDateTime($appointment->get('appointment_date')->value);
        $results[] = [
          'label' => $appointment->label(),
          'date' => $date->format('d/m/Y H:i'),
          'modify_url' => Url::fromRoute('appointment.manage_modify', ['appointment' => $appointment->id()])->toString(),
          'cancel_url' => Url::fromRoute('appointment.manage_cancel', ['appointment' => $appointment->id()])->toString(),
        ];
      }
    }

    return [
      '#title' => $this->t('Modifier un rendez-vous'),
      'form' => $this->formBuilder()->getForm(AppointmentPhoneVerifyForm::class, $destination),
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
  public function modify(AppointmentEntity $appointment, Request $request) {
    if (!$this->canManage($appointment)) {
      $this->messenger()->addError($this->t('Veuillez vérifier votre numéro de téléphone pour modifier ce rendez-vous.'));
      return $this->redirect('appointment.manage_lookup');
    }

    return $this->formBuilder()->getForm(AppointmentModifyForm::class, $appointment);
  }

  /**
   * Step 3: Cancel appointment.
   */
  public function cancel(AppointmentEntity $appointment, Request $request) {
    if (!$this->canManage($appointment)) {
      $this->messenger()->addError($this->t('Veuillez vérifier votre numéro de téléphone pour supprimer ce rendez-vous.'));
      return $this->redirect('appointment.manage_lookup');
    }

    if ($request->getMethod() === 'POST') {
      // Send the email before deleting, since the entity will no longer exist.
      $this->sendAppointmentMail('booking_cancelled', $appointment);
      $appointment->set('status', 'cancelled');
      $appointment->save();
      $this->messenger()->addStatus($this->t('Votre rendez-vous a été supprimé.'));
      return $this->redirect('appointment.booking_manage');
    }

    $site_tz = $this->siteTimezone();
    $date = new DrupalDateTime($appointment->get('appointment_date')->value, new \DateTimeZone('UTC'));
    $date->setTimezone(new \DateTimeZone($site_tz));

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
          'back' => [
            '#type' => 'link',
            '#title' => $this->t('Retour'),
            '#url' => Url::fromRoute('appointment.booking_manage'),
            '#attributes' => ['class' => ['button']],
          ],
        ],
      ],
    ];
  }

}

