<?php

namespace Drupal\caching\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Cache\CacheableJsonResponse;
class ArticlesController extends ControllerBase {

  public function getArticles() {
    $hardcoded_nids = [1, 2, 3];

    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadMultiple($hardcoded_nids);

    $data = [];
    foreach ($nodes as $node) {
      $data[] = [
        'nid' => (int) $node->id(),
        'title' => $node->getTitle(),
      ];
    }

    $response = new CacheableJsonResponse($data);

    // max-age caching
    $response->getCacheableMetadata()->setCacheMaxAge(3600);

    // tags
    $response->addCacheableDependency($nodes);

    return $response;

    // return new JsonResponse($data);
  }
}
