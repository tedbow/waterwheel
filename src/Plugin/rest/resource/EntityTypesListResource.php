<?php

namespace Drupal\waterwheel\Plugin\rest\resource;

use Drupal\waterwheel\Plugin\rest\EntityTypeResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * Provides a resource to get a list of entity types.
 *
 * @RestResource(
 *   id = "entity_types_list_resource",
 *   label = @Translation("Entity types list resource"),
 *   uri_paths = {
 *     "canonical" = "/entity/types"
 *   }
 * )
 */
class EntityTypesListResource extends EntityTypeResourceBase {

  /**
   * Returns information about all entity types on the systems.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get() {
    parent::checkAccess();
    return new ResourceResponse($this->getEntityTypesData());
  }

  /**
   * Gets the information about entity types on the site.
   *
   * @return array
   *   Information about entity types.
   */
  protected function getEntityTypesData() {
    $type_infos = [];
    /** @var \Drupal\waterwheel\Plugin\rest\resource\EntityTypeResource $type_resource */
    $type_resource = $this->resourceManager->createInstance('entity_type_resource');
    /** @var \Symfony\Component\Routing\Route $route */
    $route = $type_resource->routes()->getIterator()->current();
    $path = $route->getPath();
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      $type_infos[$entity_type_id] = [
        'label' => $entity_type->getLabel(),
        'type' => $this->getMetaEntityType($entity_type),
        'more' => str_replace('{entity_type}', $entity_type_id, $path),
        // @todo What other info?
      ];
      if ($bundle_entity_type_id = $entity_type->getBundleEntityType()) {
        $bundles = $this->entityTypeManager->getStorage($bundle_entity_type_id)->loadMultiple();
        $type_infos[$entity_type_id]['bundles'] = array_keys($bundles);
      }
    }

    return $type_infos;
  }

}
