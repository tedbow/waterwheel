<?php

namespace Drupal\waterwheel\Plugin\rest\resource;


use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\rest\Plugin\Type\ResourcePluginManager;
use Drupal\rest\ResourceResponse;
use Drupal\waterwheel\Plugin\rest\EntityTypeResourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a resource to get information about an bundle type.
 *
 * @RestResource(
 *   id = "bundle_type_resource",
 *   label = @Translation("Bundle type resource"),
 *   uri_paths = {
 *     "canonical" = "/entity/types/{entity_type}/{bundle}"
 *   }
 * )
 */
class BundleTypeResource extends EntityTypeResourceBase {

  use StringTranslationTrait;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * EntityTypeResource constructor.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param array $serializer_formats
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\rest\Plugin\Type\ResourcePluginManager $resource_manager
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager, ResourcePluginManager $resource_manager, EntityFieldManagerInterface $field_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger, $current_user, $entity_type_manager, $resource_manager);
    $this->fieldManager = $field_manager;
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
      $container->get('plugin.manager.rest'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @param string $entity_type_id
   *   The entity type id for the request.
   *
   * @param string $bundle_name
   *   The bundle machine name.
   *
   * @return \Drupal\rest\ResourceResponse
   *
   * Throws exception expected.
   */
  public function get($entity_type_id, $bundle_name) {
    parent::checkAccess();
    return new ResourceResponse($this->getBundleInfo($entity_type_id, $bundle_name));
  }

  /**
   * Gets information about the bundle.
   *
   * @param string $entity_type_id
   * @param string $bundle_name
   *
   * @return mixed
   */
  protected function getBundleInfo($entity_type_id, $bundle_name) {
    // @todo Load entity type in route system?
    if (!$this->entityTypeManager->hasDefinition($entity_type_id)) {
      throw new NotFoundHttpException($this->t('No entity type found: @type', ['@type' => $entity_type_id]));
    }
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    if ($bundle_entity_type_id = $entity_type->getBundleEntityType()) {
      $bundle = $this->entityTypeManager->getStorage($bundle_entity_type_id)->load($bundle_name);
      $bundle_info['label'] = $bundle->label();
      $bundle_info['fields'] = $this->getBundleFields($entity_type_id, $bundle_name);
      return $bundle_info;
    }
    else {
      throw new NotFoundHttpException($this->t('Entity type <em>@type</em> does not support bundles.', ['@type' => $entity_type_id]));
    }
  }

  /**
   * Gets information on all the fields on the bundle.
   *
   * @param string $entity_type_id
   * @param string $bundle_name
   *
   * @return array
   */
  protected function getBundleFields($entity_type_id, $bundle_name) {
    $fields = [];
    $field_definitions = $this->fieldManager->getFieldDefinitions($entity_type_id, $bundle_name);
    foreach ($field_definitions as $field_name => $field_definition) {
      $field_type = $field_definition->getType();

      $field_info = [
        'label' => $field_definition->getLabel(),
        'type' => $field_type,
        'data_type' => $field_definition->getDataType(),
        'required' => $field_definition->isRequired(),
        'readonly' => $field_definition->isReadOnly(),
        'cardinality' => $field_definition->getFieldStorageDefinition()->getCardinality(),
        'settings' => $field_definition->getSettings(),
      ];
      if ($this->isReferenceField($field_definition)) {
        $field_info['is_reference']  = TRUE;
        // @todo Pull reference entity type and bundles out of settings for easier access?
      }
      else {
        $field_info['is_reference']  = FALSE;
      }
      $fields[$field_name] = $field_info;
    }
    return $fields;
  }

  /**
   * Determines if a field is a reference type field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *
   * @return bool
   */
  protected function isReferenceField(FieldDefinitionInterface $field_definition) {
    // @todo Is there an easier to check if field is reference
    // @todo Dependency injection
    /** @var \Drupal\Core\Field\FieldTypePluginManagerInterface $field_manager */
    $field_manager = \Drupal::getContainer()->get('plugin.manager.field.field_type');
    $plugin_definition = $field_manager->getDefinition($field_definition->getType());
    $class = $plugin_definition['class'];
    $reference_class = 'Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem';
    if (is_subclass_of($class, $reference_class) || $class == $reference_class) {
      return TRUE;
    }
    return FALSE;
  }

}
