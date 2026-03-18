<?php

namespace Drupal\appointment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Admin listing of advisers.
 */
class AdviserAdminController extends ControllerBase {

  public function list(): array {
    $storage = $this->entityTypeManager()->getStorage('user');
    $ids = $storage->getQuery()
      ->condition('status', 1)
      ->condition('roles', 'adviser')
      ->accessCheck(TRUE)
      ->sort('name', 'ASC')
      ->execute();

    $accounts = $storage->loadMultiple($ids);

    $rows = [];
    foreach ($accounts as $account) {
      /** @var \Drupal\user\UserInterface $account */
      $agency = $account->hasField('field_agency') ? $account->get('field_agency')->entity?->label() : NULL;
      $hours = '';
      if ($account->hasField('field_workday_start') || $account->hasField('field_workday_end')) {
        $start = $account->hasField('field_workday_start') ? (string) $account->get('field_workday_start')->value : '';
        $end = $account->hasField('field_workday_end') ? (string) $account->get('field_workday_end')->value : '';
        $hours = trim($start . ' - ' . $end, ' -');
      }

      $specs = [];
      if ($account->hasField('field_specializations')) {
        foreach ($account->get('field_specializations')->referencedEntities() as $term) {
          $specs[] = $term->label();
        }
      }

      $rows[] = [
        'name' => $account->label(),
        'email' => $account->getEmail(),
        'agency' => $agency ?: '-',
        'specializations' => $specs ? implode(', ', $specs) : '-',
        'hours' => $hours ?: '-',
        // Render to HTML string to avoid Url objects leaking into attributes.
        'edit' => Link::fromTextAndUrl($this->t('Edit'), Url::fromRoute('entity.user.edit_form', ['user' => $account->id()]))->toString(),
      ];
    }

    return [
      '#attached' => [
        'library' => ['appointment/booking_form'],
      ],
      '#prefix' => '<div class="appointment-admin">',
      '#suffix' => '</div>',
      '#type' => 'table',
      '#attributes' => ['class' => ['appointment-admin__table']],
      '#header' => [
        $this->t('Name'),
        $this->t('Email'),
        $this->t('Agency'),
        $this->t('Specializations'),
        $this->t('Working hours'),
        $this->t('Actions'),
      ],
      '#rows' => array_map(function (array $row) {
        return [
          $row['name'],
          $row['email'],
          $row['agency'],
          $row['specializations'],
          $row['hours'],
          ['data' => ['#markup' => $row['edit']]],
        ];
      }, $rows),
      '#empty' => $this->t('No advisers found.'),
    ];
  }

}

