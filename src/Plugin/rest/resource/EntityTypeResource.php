<?php

namespace Drupal\waterwheel\Plugin\rest\resource;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\waterwheel\Plugin\rest\EntityTypeResourceBase;
use Drupal\rest\Plugin\Type\ResourcePluginManager;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @todo Can the resource be removed?
 * Provides a resource to get information about an entity type.
 *
 * @RestResource(
 *   id = "entity_type_resource",
 *   label = @Translation("Entity type resource"),
 *   uri_paths = {
 *     "canonical" = "/entity/types/{entity_type}"
 *   }
 * )
 */
class EntityTypeResource extends EntityTypeResourceBase {

  use StringTranslationTrait;
  

  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @param $entity_type_id
   *   The entity type id for the request.
   *
   * @return \Drupal\rest\ResourceResponse Throws exception expected.
   * Throws exception expected.
   */
  public function get($entity_type_id) {
    parent::checkAccess();

    return new ResourceResponse($this->getEntityTypeInfo($entity_type_id));
  }

  /**
   * Gets the type information for an entity type.
   *
   * @param string $entity_type_id
   *   The entity type id for the entity to get information for.
   *
   * @return array
   *   The entity type info.
   */
  protected function getEntityTypeInfo($entity_type_id) {
    // @todo Load entity type in route system?
    if (!$this->entityTypeManager->hasDefinition($entity_type_id)) {
      throw new NotFoundHttpException($this->t('No entity type found: @type', ['@type' => $entity_type_id]));
    }
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);

    $info = [
      'label' => $entity_type->getLabel(),
      'type' => $this->getMetaEntityType($entity_type),
    ];

    $info['methods'] = $this->getEntityMethods($entity_type_id);
    if ($entity_type instanceof ContentEntityTypeInterface) {
      $info['fields'] = $this->getFields($entity_type_id);
    }
    return $info;
  }

  /**
   * Gets the information about fields for the entity type.
   *
   * //@ todo currently it only retrieves the base fields. Should it get user configurable fields.
   *
   * @param string $entity_type_id
   *   The entity type containing the fields.
   *
   * @return array
   *   The fields.
   */
  protected function getFields($entity_type_id) {
    $fields = [];
    $field_definitions = $this->fieldManager->getBaseFieldDefinitions($entity_type_id);

    foreach ($field_definitions as $field_name => $field_definition) {
      $fields[$field_name] = [
        'label' => $field_definition->getLabel(),
        'type' => $field_definition->getType(),
        'required' => $field_definition->isRequired(),
        'readonly' => $field_definition->isReadOnly(),
        'cardinality' => $field_definition->getFieldStorageDefinition()->getCardinality(),
      ];
    }
    return $fields;
  }

}
