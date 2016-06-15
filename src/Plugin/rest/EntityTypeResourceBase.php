<?php

namespace Drupal\waterwheel\Plugin\rest;

use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\Plugin\Type\ResourcePluginManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * The base class for resources returning entity type information.
 */
abstract class EntityTypeResourceBase extends ResourceBase {
  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The rest resource manager.
   *
   * @var \Drupal\rest\Plugin\Type\ResourcePluginManager
   */
  protected $resourceManager;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\rest\Plugin\Type\ResourcePluginManager $resource_manager
   *   The rest resource manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager, ResourcePluginManager $resource_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->resourceManager = $resource_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('waterwheel'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.rest')
    );
  }

  /**
   * Checks if the current user has access to view site configuration.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function checkAccess() {
    if (!$this->currentUser->hasPermission('view site configuration')) {
      throw new AccessDeniedHttpException();
    }
  }

  /**
   * Get the meta type of the entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type to check.
   *
   * @return string The type of entity either content, config, or other.
   *   The type of entity either content, config, or other.
   */
  protected function getMetaEntityType(EntityTypeInterface $entity_type) {
    if ($entity_type instanceof ContentEntityTypeInterface) {
      $meta_type = 'content';
      return $meta_type;
    }
    elseif ($entity_type instanceof ConfigEntityTypeInterface) {
      $meta_type = 'config';
      return $meta_type;
    }
    else {
      $meta_type = 'other';
      return $meta_type;
    }
  }

}
