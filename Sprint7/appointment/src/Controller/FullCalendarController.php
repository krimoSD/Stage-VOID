<?php

namespace Drupal\appointment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use DateTimeZone;

/**
 * JSON endpoints for FullCalendar.
 */
class FullCalendarController extends ControllerBase {

  public function __construct(
    protected EntityTypeManagerInterface $appointmentEntityTypeManager,
    protected ConfigFactoryInterface $systemConfigFactory,
  ) {}

  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
    );
  }

  /**
   * Returns booked appointment slots as FullCalendar events.
   */
  public function events(int $adviser, Request $request): JsonResponse {
    $start = (string) $request->query->get('start', '');
    $end = (string) $request->query->get('end', '');

    if ($adviser <= 0 || $start === '' || $end === '') {
      return new JsonResponse([]);
    }

    try {
      $start_dt = new DrupalDateTime($start, new DateTimeZone('UTC'));
      $end_dt = new DrupalDateTime($end, new DateTimeZone('UTC'));
    }
    catch (\Exception $e) {
      return new JsonResponse([]);
    }

    $storage = $this->appointmentEntityTypeManager->getStorage('appointment');
    $ids = $storage->getQuery()
      ->condition('adviser', $adviser)
      ->condition('status', 'cancelled', '!=')
      ->condition('appointment_date', $start_dt->format('Y-m-d\TH:i:s'), '>=')
      ->condition('appointment_date', $end_dt->format('Y-m-d\TH:i:s'), '<=')
      ->accessCheck(TRUE)
      ->execute();

    $default_minutes = (int) ($this->systemConfigFactory->get('appointment.settings')->get('slot_minutes') ?: 60);
    $default_minutes = max(5, $default_minutes);

    $events = [];
    if ($ids) {
      $appointments = $storage->loadMultiple($ids);
      foreach ($appointments as $appointment) {
        $start_value = (string) $appointment->get('appointment_date')->value;
        if ($start_value === '') {
          continue;
        }
        $minutes = $default_minutes;
        if ($appointment->hasField('duration_minutes') && (int) $appointment->get('duration_minutes')->value > 0) {
          $minutes = (int) $appointment->get('duration_minutes')->value;
        }
        $start_event = new DrupalDateTime($start_value, new DateTimeZone('UTC'));
        $end_event = new DrupalDateTime($start_value, new DateTimeZone('UTC'));
        $end_event->modify('+' . max(5, $minutes) . ' minutes');

        $events[] = [
          'id' => (string) $appointment->id(),
          'title' => $this->t('Réservé'),
          'start' => $start_event->format('Y-m-d\TH:i:s') . 'Z',
          'end' => $end_event->format('Y-m-d\TH:i:s') . 'Z',
        ];
      }
    }

    return new JsonResponse($events);
  }

}

