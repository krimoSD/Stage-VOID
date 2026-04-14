<?php

namespace Drupal\custom_dynamic_fields\Plugin\vactory_dynamic_field\Platform;

use Drupal\vactory_dynamic_field\VactoryDynamicFieldPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Custom DFs provider plugin.
 *
 * @PlatformProvider(
 *   id = "capital_azur_dynamic_fields",
 *   title = @Translation("Capital Azur Dynamic Fields")
 * )
 */
class CustomDynamicFields extends VactoryDynamicFieldPluginBase {

  /**
   * Extension path resolver service.
   *
   * @var \Drupal\Core\Extension\ExtensionPathResolver
   */
  protected $extensionPathResolver;

  /**
   * {@inheritDoc }
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->extensionPathResolver = $container->get('extension.path.resolver');
    $instance->setWidgetsPath($instance->extensionPathResolver->getPath('module', 'custom_dynamic_fields') . '/widgets');
    return $instance;
  }

}
