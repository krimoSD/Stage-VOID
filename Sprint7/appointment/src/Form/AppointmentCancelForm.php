<?php

namespace Drupal\appointment\Form;

use Drupal\appointment\Entity\AppointmentEntity;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;

/**
 * Form to cancel (delete logically) an appointment.
 */
class AppointmentCancelForm extends FormBase
{

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ConfigFactoryInterface $systemConfigFactory,
    protected MailManagerInterface $mailManager,
    protected PrivateTempStoreFactory $tempStoreFactory,
    )
  {
  }

  public static function create(\Symfony\Component\DependencyInjection\ContainerInterface $container): static
  {
    return new static (
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('plugin.manager.mail'),
      $container->get('tempstore.private'),
      );
  }

  public function getFormId(): string
  {
    return 'appointment_cancel_form';
  }

  protected function siteTimezone(): string
  {
    return $this->systemConfigFactory->get('system.date')->get('timezone.default') ?: 'UTC';
  }

  protected function canManageAppointment(AppointmentEntity $appointment): bool
  {
    // 1. Phone check.
    if (!$appointment->hasField('customer_phone')) {
      return FALSE;
    }

    $store = $this->tempStoreFactory->get('appointment_manage');
    $verified_phone = trim((string)($store->get('verified_phone') ?? ''));
    if ($verified_phone === '') {
      return FALSE;
    }

    $customer_phone = trim((string)($appointment->get('customer_phone')->value ?? ''));
    if ($customer_phone === '' || !hash_equals($verified_phone, $customer_phone)) {
      return FALSE;
    }

    // 2. Strict ID whitelist check.
    $allowed_ids = $store->get('allowed_appointment_ids');
    if (!is_array($allowed_ids) || !in_array((int)$appointment->id(), array_map('intval', $allowed_ids), TRUE)) {
      return FALSE;
    }

    return TRUE;
  }

  public function buildForm(array $form, FormStateInterface $form_state, ?AppointmentEntity $appointment = NULL): array
  {
    if (!$appointment) {
      $form['#markup'] = $this->t('Rendez-vous introuvable.');
      return $form;
    }

    // Early security check.
    if (!$this->canManageAppointment($appointment)) {
      $form['#markup'] = $this->t('Accès refusé. Veuillez vérifier votre numéro de téléphone.');
      return $form;
    }

    $form_state->set('appointment_id', $appointment->id());

    $site_tz = $this->siteTimezone();
    $date = new DrupalDateTime($appointment->get('appointment_date')->value, new \DateTimeZone('UTC'));
    $date->setTimezone(new \DateTimeZone($site_tz));

    $form['#attached'] = [
      'library' => ['appointment/booking_form'],
    ];

    $form['summary'] = [
      '#markup' => $this->t('Rendez-vous le @date.', [
        '@date' => $date->format('d/m/Y H:i'),
      ]),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Confirmer l\'annulation'),
    ];
    $form['actions']['back'] = [
      '#type' => 'link',
      '#title' => $this->t('Retour'),
      '#url' => Url::fromRoute('appointment.booking_manage'),
      '#attributes' => ['class' => ['button']],
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    $appointment_id = (int)$form_state->get('appointment_id');
    /** @var \Drupal\appointment\Entity\AppointmentEntity|null $appointment */
    $appointment = $this->entityTypeManager->getStorage('appointment')->load($appointment_id);
    if (!$appointment) {
      $this->messenger()->addError($this->t('Rendez-vous introuvable.'));
      $form_state->setRedirect('appointment.manage_lookup');
      return;
    }

    if (!$this->canManageAppointment($appointment)) {
      $this->messenger()->addError($this->t('Veuillez vérifier votre numéro de téléphone pour supprimer ce rendez-vous.'));
      $form_state->setRedirect('appointment.manage_lookup');
      return;
    }

    // Send email before persisting status change.
    $reference = $appointment->hasField('reference') ? ($appointment->get('reference')->value ?: $appointment->id()) : $appointment->id();
    $params = [
      'appointment' => $appointment,
      'reference' => $reference,
    ];

    $site_mail = $this->systemConfigFactory->get('system.site')->get('mail');
    $langcode = $this->systemConfigFactory->get('system.site')->get('langcode') ?: 'fr';
    $this->mailManager->mail('appointment', 'booking_cancelled', $appointment->get('customer_email')->value, $langcode, $params, $site_mail, TRUE);

    $appointment->set('status', 'cancelled');
    $appointment->save();

    // Clear phone verification after successful cancellation.
    $store = $this->tempStoreFactory->get('appointment_manage');
    $store->delete('verified_phone');
    $store->delete('allowed_appointment_ids');

    $this->messenger()->addStatus($this->t('Votre rendez-vous a été supprimé.'));
    $form_state->setRedirect('appointment.booking_manage');
  }
}