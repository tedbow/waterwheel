<?php

namespace Drupal\waterwheel\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for the Swagger UI page callbacks.
 */
class SwaggerUIController extends ControllerBase {

  /**
   * The Swagger UI page.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle_name
   *   The bundle.
   *
   * @return array The Swagger UI render array.
   *   The Swagger UI render array.
   */
  public function swaggerUiPage($entity_type = NULL, $bundle_name = NULL) {
    $build = [
      '#theme' => 'swagger_ui',
      '#attached' => [
        'library' => [
          'waterwheel/swagger_ui_integration',
          'waterwheel/swagger_ui',
        ],
        'drupalSettings' => [
          'waterwheel' => [
            'swagger_ui' => [
              'entity_type' => $entity_type,
              'bundle_name' => $bundle_name,
            ],
          ],
        ],
      ],
    ];
    return $build;
  }

}
