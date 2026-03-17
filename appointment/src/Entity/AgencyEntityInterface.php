<?php

namespace Drupal\appointment\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for Agency entities.
 */
interface AgencyEntityInterface extends ContentEntityInterface {

  public function getName(): string;
  public function getAddress(): string;
  public function getContactInfo(): string;
  public function getOperatingHours(): string;

}
