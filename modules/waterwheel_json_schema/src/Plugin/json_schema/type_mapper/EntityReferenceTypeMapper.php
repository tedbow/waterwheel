<?php

namespace Drupal\waterwheel_json_schema\Plugin\json_schema\type_mapper;

use Drupal\waterwheel_json_schema\Plugin\TypeMapperBase;
use Drupal\Core\TypedData\DataDefinitionInterface;

/**
 * Converts Data Definition properties of entity_reference type to JSON Schema.
 *
 * @TypeMapper(
 *  id = "entity_reference"
 * )
 */
class EntityReferenceTypeMapper extends TypeMapperBase {

  /**
   * {@inheritdoc}
   */
  public function getMappedValue(DataDefinitionInterface $property) {
    $value = parent::getMappedValue($property);
    $value['type'] = 'object';
    return $value;
  }

}
