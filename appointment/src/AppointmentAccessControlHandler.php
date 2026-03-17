<?php

namespace Drupal\appointment;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for the Appointment entity.
 */
class AppointmentAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // Owners (customer_email == user email) can manage their own appointments.
    $is_owner = $account->isAuthenticated()
      && $entity->hasField('customer_email')
      && (string) $entity->get('customer_email')->value !== ''
      && strcasecmp((string) $entity->get('customer_email')->value, (string) $account->getEmail()) === 0;

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'access content');

      case 'update':
      case 'delete':
        if ($account->hasPermission('administer appointments')) {
          return AccessResult::allowed();
        }
        return AccessResult::allowedIf($is_owner)->cachePerPermissions()->cachePerUser();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    // Appointments are created through the booking form, not via UI by users.
    return AccessResult::allowedIfHasPermission($account, 'administer appointments');
  }

}

