<?php

namespace Drupal\waterwheel\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

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
  public function bundleResource($entity_type = NULL, $bundle_name = NULL) {
    $json_url = Url::fromRoute(
      'waterwheel.swagger.bundle',
      [
        'entity_type' => $entity_type,
        'bundle_name' => $bundle_name,
      ]
    );
    $build = $this->swaggerUI($json_url);
    return $build;
  }

  public function nonEntityResources() {
    $json_url = Url::fromRoute(
      'waterwheel.swagger.non_entity'
    );
    $build = $this->swaggerUI($json_url);
    return $build;
  }


  /**
   * REST resources overview.
   */
  public function overview() {

  }

  /**
   *
   * @param \Drupal\Core\Url $json_url
   *
   * @return array
   */
  protected function swaggerUI(Url $json_url) {
    $json_url->setOption('query', ['_format' => 'json']);
    $build = [
      '#theme' => 'swagger_ui',
      '#attached' => [
        'library' => [
          'waterwheel/swagger_ui_integration',
          'waterwheel/swagger_ui',
        ],
        'drupalSettings' => [
          'waterwheel' => [
            'swagger_json_url' => $json_url->toString(),
          ],
        ],
      ],
    ];
    return $build;
  }

}
