<?php

namespace Drupal\waterwheel\Plugin\rest\resource;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\rest\ResourceResponse;
use Drupal\waterwheel\Plugin\rest\EntityTypeResourceBase;
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

  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @param string $entity_type_id
   *   The entity type id for the request.
   * @param string $bundle_name
   *   The bundle machine name.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The resource response.
   */
  public function get($entity_type_id, $bundle_name) {
    parent::checkAccess();
    return new ResourceResponse($this->getBundleInfo($entity_type_id, $bundle_name));
  }

  /**
   * Gets information about the bundle.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle_name
   *   The bundle name.
   *
   * @return array
   *   The bundle info.
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
      $bundle_info['label'] = $entity_type->getLabel();
      $bundle_info['fields'] = $this->getBundleFields($entity_type_id, $bundle_name);
      return $bundle_info;
    }
  }

  /**
   * Determines if a field is a reference type field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   *
   * @return bool
   *   True if the field is a reference field.
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
