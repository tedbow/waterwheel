<?php

namespace Drupal\waterwheel_docs\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\waterwheel\Controller\RestInspectionTrait;
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
    $return['pages_heading'] = [
      '#type' => 'markup',
      '#markup' => '<h2>' . $this->t('Documentation Pages') . '</h2>',
    ];
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
            'level' => 'h3',
          ],
        ];
      }
    }
    $return['direct_download'] = [
      '#type' => 'markup',
      '#markup' => '<h2>' . $this->t('Open API files') . '</h2>' .
      // @todo Which page should the docs link to?
      '<p>' . $this->t('The following links provide the REST API resources documented in <a href=":open_api_spec">Open API(fka Swagger)</a> format.', [':open_api_spec' => 'https://github.com/OAI/OpenAPI-Specification/tree/OpenAPI.next']) . ' ' .
      $this->t('This JSON file can be used in tools such as the <a href=":swagger_editor">Swagger Editor</a> to provide a more detailed version of the API documentation.', [':swagger_editor' => 'http://editor.swagger.io/#/']) . '</p>',
    ];
    $open_api_links['entities'] = [
      'url' => Url::fromRoute('waterwheel.swagger.entities', [], ['query' => ['_format' => 'json']]),
      'title' => $this->t('Open API: Entities'),
    ];
    $open_api_links['other'] = [
      'url' => Url::fromRoute('waterwheel.swagger.non_entity', [], ['query' => ['_format' => 'json']]),
      'title' => $this->t('Open API: Other resources'),
    ];
    $return['direct_download']['links'] = [
      '#theme' => 'links',
      '#links' => $open_api_links,
    ];

    return $return;
  }

  /**
   * Creates documentations page for non-entity resources.
   *
   * @return array
   *   Render array for documentations page.
   */
  public function nonEntityResources() {
    $json_url = Url::fromRoute(
      'waterwheel.swagger.non_entity'
    );
    $build = $this->swaggerUI($json_url);
    return $build;
  }

  /**
   * Creates render array for documentation page for a given resource url.
   *
   * @param \Drupal\Core\Url $json_url
   *   The resource file needed to create the documentation page.
   *
   * @return array
   *   The render array.
   */
  protected function swaggerUI(Url $json_url) {
    $json_url->setOption('query', ['_format' => 'json']);
    $build = [
      '#theme' => 'swagger_ui',
      '#attached' => [
        'library' => [
          'waterwheel_docs/swagger_ui_integration',
          'waterwheel_docs/swagger_ui',
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
