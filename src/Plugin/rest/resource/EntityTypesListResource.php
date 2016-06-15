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
    /** @var \Drupal\waterwheel\Plugin\rest\resource\EntityTypeResource $bundle_resource */
    $bundle_resource = $this->resourceManager->createInstance('bundle_type_resource');
    /** @var \Symfony\Component\Routing\Route $route */
    $route = $bundle_resource->routes()->getIterator()->current();
    $path = $route->getPath();
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      $type_infos[$entity_type_id] = [
        'label' => $entity_type->getLabel(),
        'type' => $this->getMetaEntityType($entity_type),
        // @todo Should we only returns entities that have methods enabled.
        'methods' => $this->getEntityMethods($entity_type_id),
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

  /**
   * Gets the REST methods and their paths for the entity type.
   *
   * @param string $entity_type_id
   *   The entity type id.
   *
   * @return array
   *   The REST methods.
   *
   *   The keys are the REST methods and the values are the paths.
   */
  protected function getEntityMethods($entity_type_id) {
    $resource_methods = [];
    $enabled_resources = \Drupal::config('rest.settings')->get('resources');
    $entity_resource_key = "entity:$entity_type_id";
    if (isset($enabled_resources[$entity_resource_key])) {
      $enabled_methods = array_keys($enabled_resources[$entity_resource_key]);
      /** @var \Drupal\rest\Plugin\rest\resource\EntityResource $entity_resource */
      $entity_resource = $this->resourceManager->createInstance($entity_resource_key);
      /** @var \Symfony\Component\Routing\RouteCollection $routes */
      $routes = $entity_resource->routes();

      foreach ($enabled_methods as $method) {
        /** @var \Symfony\Component\Routing\Route $route */
        foreach ($routes as $route) {
          if (in_array($method, $route->getMethods())) {
            $resource_methods[$method] = $route->getPath();
            break;
          }
        }
      }
    }

    return $resource_methods;
  }

}
