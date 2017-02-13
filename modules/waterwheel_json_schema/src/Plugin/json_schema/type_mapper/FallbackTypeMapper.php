<?php

namespace Drupal\waterwheel_json_schema\Plugin\json_schema\type_mapper;

use Drupal\waterwheel_json_schema\Plugin\TypeMapperBase;
use Drupal\Core\TypedData\DataDefinitionInterface;

/**
 * The fallback type mapper, explicitly called if none other is applicable.
 *
 * @TypeMapper(
 *  id = "fallback"
 * )
 */
class FallbackTypeMapper extends TypeMapperBase {

  /**
   * {@inheritdoc}
   */
  public function getMappedValue(DataDefinitionInterface $property) {
    $value = parent::getMappedValue($property);
    $value['type'] = $property->getDataType();
    return $value;
  }

}
