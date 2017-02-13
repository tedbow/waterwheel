<?php

namespace Drupal\waterwheel_json_schema\Plugin\rest\resource;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "schemata_entity_base",
 *   label = @Translation("Schemata Base Entity"),
 *   serialization_class = "Drupal\waterwheel_json_schema\Schema\Schema",
 *   uri_paths = {
 *     "canonical" = "/schemata/{entity_type}",
 *     "https://www.drupal.org/link-relations/describes" = "/entity/{entity_type}"
 *   }
 * )
 */
class SchemataBaseEntityResource extends SchemataResourceBase {

}
