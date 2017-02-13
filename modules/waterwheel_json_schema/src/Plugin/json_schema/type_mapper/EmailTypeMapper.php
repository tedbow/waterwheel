<?php

namespace Drupal\waterwheel_json_schema\Plugin\json_schema\type_mapper;

use Drupal\waterwheel_json_schema\Plugin\TypeMapperBase;
use Drupal\Core\TypedData\DataDefinitionInterface;

/**
 * Converts Data Definition properties of the email to JSON Schema.
 *
 * @TypeMapper(
 *  id = "email"
 * )
 */
class EmailTypeMapper extends TypeMapperBase {

  /**
   * {@inheritdoc}
   */
  public function getMappedValue(DataDefinitionInterface $property) {
    $value = parent::getMappedValue($property);
    $value['type'] = 'string';
    $value['format'] = 'email';
    return $value;
  }

}
