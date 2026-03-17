<?php

namespace Drupal\appointment\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Appointment entity.
 *
 * @ContentEntityType(
 *   id = "appointment",
 *   label = @Translation("Appointment"),
 *   base_table = "appointment",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *   },
 *   handlers = {
 *     "access" = "Drupal\appointment\AppointmentAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\appointment\Form\AppointmentEntityForm",
 *       "delete" = "Drupal\appointment\Form\AppointmentDeleteForm",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   links = {
 *     "canonical" = "/appointment/{appointment}",
 *     "edit-form" = "/appointment/{appointment}/edit",
 *     "delete-form" = "/appointment/{appointment}/delete",
 *   },
 * )
 */
class AppointmentEntity extends ContentEntityBase {

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Title
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 255])
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 0]);

    // Date & Time
    $fields['appointment_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Date and Time'))
      ->setRequired(TRUE)
      ->setSettings(['datetime_type' => 'datetime'])
      ->setDisplayOptions('form', ['type' => 'datetime_default', 'weight' => 1]);

    // Agency reference (entity reference to Agency entity).
    $fields['agency'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Agency'))
      ->setRequired(TRUE)
      ->setSettings([
        'target_type' => 'agency',   // must match id = "agency" in AgencyEntity annotation
        'handler' => 'default',
      ])
      ->setDisplayOptions('form', ['type' => 'entity_reference_autocomplete', 'weight' => 2]);

    // Appointment type (taxonomy term).
    $fields['appointment_type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Appointment type'))
      ->setRequired(TRUE)
      ->setSettings([
        'target_type' => 'taxonomy_term',
        'handler' => 'default',
        'handler_settings' => [
          'target_bundles' => [
            'appointment_type' => 'appointment_type',
          ],
        ],
      ])
      ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => 3]);

    // Adviser reference (entity reference to User with adviser role).
    $fields['adviser'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Adviser'))
      ->setRequired(TRUE)
      ->setSettings([
        'target_type' => 'user',
        'handler' => 'default:user',
        'handler_settings' => ['filter' => ['role' => 'adviser']],
      ])
      ->setDisplayOptions('form', ['type' => 'entity_reference_autocomplete', 'weight' => 4]);

    // Customer Name
    $fields['customer_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Customer Name'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 255])
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 5]);

    // Customer Email
    $fields['customer_email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Customer Email'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['type' => 'email_default', 'weight' => 5]);

    // Customer Phone
    $fields['customer_phone'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Customer Phone'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 20])
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 6]);

    // Public reference code shown to the user.
    $fields['reference'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Reference'))
      ->setRequired(FALSE)
      ->setSettings(['max_length' => 32])
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 6]);

    // Status
    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setRequired(TRUE)
      ->setSettings([
        'allowed_values' => [
          'pending'   => t('Pending'),
          'confirmed' => t('Confirmed'),
          'cancelled' => t('Cancelled'),
        ],
      ])
      ->setDefaultValue('pending')
      ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => 7]);

    // Notes
    $fields['notes'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Notes'))
      ->setRequired(FALSE)
      ->setDisplayOptions('form', ['type' => 'text_textarea', 'weight' => 8]);

    // Timestamps
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }
}
