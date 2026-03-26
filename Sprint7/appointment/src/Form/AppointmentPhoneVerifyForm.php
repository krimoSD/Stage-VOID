<?php

namespace Drupal\appointment\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Phone verification form used before managing an appointment.
 */
class AppointmentPhoneVerifyForm extends FormBase
{

  protected PrivateTempStoreFactory $tempStoreFactory;

  protected EntityTypeManagerInterface $entityTypeManager;

  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $entity_type_manager)
  {
    $this->tempStoreFactory = $temp_store_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  public static function create(ContainerInterface $container): static
  {
    return new static (
      $container->get('tempstore.private'),
      $container->get('entity_type.manager'),
      );
  }

  public function getFormId(): string
  {
    return 'appointment_phone_verify_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, string $destination = ''): array
  {
    $form['destination'] = [
      '#type' => 'hidden',
      '#default_value' => $destination,
    ];

    $form['phone'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Numéro de téléphone'),
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Valider'),
      ],
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state): void
  {
    $phone = (string)$form_state->getValue('phone');
    $destination = (string)$form_state->getValue('destination');

    if ($destination === '' || UrlHelper::isExternal($destination)) {
      // No destination: we validate by ensuring at least one appointment exists.
      $has_any = (bool)$this->entityTypeManager->getStorage('appointment')->getQuery()
        ->condition('customer_phone', $phone)
        ->accessCheck(TRUE)
        ->range(0, 1)
        ->execute();
      if (!$has_any) {
        $form_state->setErrorByName('phone', $this->t('Le numéro de téléphone est incorrect.'));
      }
      return;
    }

    // Destination to an appointment action: validate phone matches that appointment.
    $appointment_id = $this->parseAppointmentIdFromDestination($destination);
    if ($appointment_id === NULL) {
      // If the destination is internal
      //  but not in an expected format, treat it as invalid.
      $form_state->setErrorByName('phone', $this->t('Destination invalide.'));
      return;
    }

    $appointment = $this->entityTypeManager->getStorage('appointment')->load($appointment_id);
    if (!$appointment) {
      $form_state->setErrorByName('phone', $this->t('Rendez-vous introuvable.'));
      return;
    }

    $expected_phone = trim((string)($appointment->get('customer_phone')->value ?? ''));
    if ($expected_phone === '' || $phone !== $expected_phone) {
      $form_state->setErrorByName('phone', $this->t('Le numéro de téléphone est incorrect.'));
    }
  }

  /**
   * Extracts appointment id from an internal destination path.
   *
   * @return int|null
   *   Appointment id when the destination is one of our supported routes,
   *   NULL otherwise.
   */
  protected function parseAppointmentIdFromDestination(string $destination): ?int
  {
    if (UrlHelper::isExternal($destination) || $destination === '') {
      return NULL;
    }
    if (UrlHelper::isExternal($destination) || $destination === '') {
      return NULL;
    }
    if (!preg_match('#^/(?:[a-z]{2}/)?(modifier-un-rendez-vous|annuler-un-rendez-vous)/(\d+)$#i', $destination, $m)) {
      return NULL;
    }
    return (int)$m[2];
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    $phone = (string)$form_state->getValue('phone');
    $destination = (string)$form_state->getValue('destination');

    // Persist verification in session tempstore.
    $store = $this->tempStoreFactory->get('appointment_manage');
    $store->set('verified_phone', $phone);

    // Bind the verification to specific appointment ids to prevent ID swapping.
    $appointment_id = $this->parseAppointmentIdFromDestination($destination);
    if ($appointment_id !== NULL) {
      $store->set('allowed_appointment_ids', [$appointment_id]);
    }
    else {
      // No destination (or it was empty): allow all appointments matching this phone.
      $ids = $this->entityTypeManager->getStorage('appointment')->getQuery()
        ->condition('customer_phone', $phone)
        ->accessCheck(TRUE)
        ->execute();
      $store->set('allowed_appointment_ids', array_map('intval', $ids ?: []));
    }

    if ($destination !== '' && !UrlHelper::isExternal($destination)) {
      $form_state->setResponse(new RedirectResponse(Url::fromUserInput($destination)->toString()));
      return;
    }

    // No destination: rebuild the lookup page with the phone in query.
    $form_state->setRedirect('appointment.manage_lookup', [], ['query' => ['phone' => $phone]]);
  }

}