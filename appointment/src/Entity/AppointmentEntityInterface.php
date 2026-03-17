<?php

namespace Drupal\appointment\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for Appointment entities.
 */
interface AppointmentEntityInterface extends ContentEntityInterface {

  public function getTitle(): string;
  public function getAppointmentDate(): string;
  public function getAgencyId(): int;
  public function getAdviserId(): int;
  public function getCustomerName(): string;
  public function getCustomerEmail(): string;
  public function getCustomerPhone(): string;
  public function getStatus(): string;
  public function setStatus(string $status): self;
  public function getNotes(): string;

}
