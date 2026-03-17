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
class AppointmentPhoneVerifyForm extends FormBase {

  protected PrivateTempStoreFactory $tempStoreFactory;

  protected EntityTypeManagerInterface $entityTypeManager;

  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('tempstore.private'),
      $container->get('entity_type.manager'),
    );
  }

  public function getFormId(): string {
    return 'appointment_phone_verify_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, string $destination = ''): array {
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

  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $phone = (string) $form_state->getValue('phone');
    $destination = (string) $form_state->getValue('destination');

    if ($destination === '' || UrlHelper::isExternal($destination)) {
      // No destination: we validate by ensuring at least one appointment exists.
      $has_any = (bool) $this->entityTypeManager->getStorage('appointment')->getQuery()
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
    if (preg_match('#^/(?:[a-z]{2}/)?(modifier-un-rendez-vous|annuler-un-rendez-vous)/(\d+)$#i', $destination, $m)) {
      $appointment_id = (int) $m[2];
      $appointment = $this->entityTypeManager->getStorage('appointment')->load($appointment_id);
      if (!$appointment) {
        $form_state->setErrorByName('phone', $this->t('Rendez-vous introuvable.'));
        return;
      }
      $expected_phone = (string) $appointment->get('customer_phone')->value;
      if ($expected_phone === '' || $phone !== $expected_phone) {
        $form_state->setErrorByName('phone', $this->t('Le numéro de téléphone est incorrect.'));
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $phone = (string) $form_state->getValue('phone');
    $destination = (string) $form_state->getValue('destination');

    // Persist verification in session tempstore.
    $this->tempStoreFactory->get('appointment_manage')->set('verified_phone', $phone);

    if ($destination !== '' && !UrlHelper::isExternal($destination)) {
      $form_state->setResponse(new RedirectResponse(Url::fromUserInput($destination)->toString()));
      return;
    }

    // No destination: rebuild the lookup page with the phone in query.
    $form_state->setRedirect('appointment.manage_lookup', [], ['query' => ['phone' => $phone]]);
  }

}

