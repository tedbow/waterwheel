<?php

namespace Drupal\waterwheel\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the Swagger UI page callbacks.
 */
class SwaggerUIController extends ControllerBase {

  use RestInspectionTrait;

  /**
   * Constructs a new SwaggerController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.

   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

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

  /**
   * List all REST Doc pages.
   */
  public function listResources() {
    $return['other_resources'] = [
      '#type' => 'link',
      '#url' => Url::fromRoute('waterwheel.swaggerUI.non_entity'),
      '#title' => $this->t('Non bundle resources'),
    ];
    foreach ($this->getRestEnabledEntityTypes() as $entity_type_id => $entity_type) {
      if ($bundle_type = $entity_type->getBundleEntityType()) {
        $bundle_storage = $this->entityTypeManager()
          ->getStorage($bundle_type);
        /** @var \Drupal\Core\Config\Entity\ConfigEntityBundleBase[] $bundles */
        $bundles = $bundle_storage->loadMultiple();
        $bundle_links = [];
        foreach ($bundles as $bundle_name => $bundle) {
          $bundle_links[$bundle_name] = [
            'title' => $bundle->label(),
            'url' => Url::fromRoute('waterwheel.swaggerUI.bundle', [
              'entity_type' => $entity_type_id,
              'bundle_name' => $bundle_name,
            ]),
          ];
        }
        $return[$entity_type_id] = [
          '#theme' => 'links',
          '#links' => $bundle_links,
          '#heading' => [
            'text' => $this->t('@entity_type bundles', ['@entity_type' => $entity_type->getLabel()]),
          ],
        ];
      }
    }

    return $return;
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
