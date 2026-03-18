<?php

namespace Drupal\appointment\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Agency entity.
 *
 * @ContentEntityType(
 *   id = "agency",
 *   label = @Translation("Agency"),
 *   label_collection = @Translation("Agencies"),
 *   base_table = "agency",
 *   admin_permission = "administer appointments",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *   },
 *   handlers = {
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   links = {
 *     "canonical" = "/agency/{agency}",
 *     "collection" = "/admin/structure/agencies",
 *     "add-form" = "/agency/add",
 *     "edit-form" = "/agency/{agency}/edit",
 *     "delete-form" = "/agency/{agency}/delete",
 *   },
 * )
 */
class AgencyEntity extends ContentEntityBase {

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Name
    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 255])
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 0]);

    // Address
    $fields['address'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Address'))
      ->setRequired(FALSE)
      ->setDisplayOptions('form', ['type' => 'string_textarea', 'weight' => 1]);

    // Contact Info
    $fields['contact_info'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Contact Information'))
      ->setRequired(FALSE)
      ->setSettings(['max_length' => 255])
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 2]);

    // Operating Hours
    $fields['operating_hours'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Operating Hours'))
      ->setRequired(FALSE)
      ->setDisplayOptions('form', ['type' => 'string_textarea', 'weight' => 3]);

    // Timestamps
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }

}
