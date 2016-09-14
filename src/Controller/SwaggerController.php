<?php

namespace Drupal\waterwheel\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\rest\Plugin\rest\resource\EntityResource;
use Drupal\rest\Plugin\Type\ResourcePluginManager;
use Drupal\rest\RestResourceConfigInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Route;

/**
 * Routes for Swagger json spec and Swagger UI.
 */
class SwaggerController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The plugin manager for REST plugins.
   *
   * @var \Drupal\rest\Plugin\Type\ResourcePluginManager
   */
  protected $manager;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * Constructs a new SwaggerController object.
   *
   * @param \Drupal\rest\Plugin\Type\ResourcePluginManager $manager
   *   The resource plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager
   */
  public function __construct(ResourcePluginManager $manager, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $field_manager) {
    $this->manager = $manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->fieldManager = $field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.rest'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * Output Swagger compatible API spec.
   */
  public function swaggerAPI() {
    $spec = [
      'swagger' => "2.0",
      'schemes' => ['http'],
      'info' => $this->getInfo(),
      'paths' => $this->getPaths(),
      'host' => \Drupal::request()->getHost(),
      'basePath' => \Drupal::request()->getBasePath(),

    ];
    $response = new JsonResponse($spec);
    return $response;

  }

  /**
   * Creates the 'info' portion of the API.
   *
   * @return array
   *   The info elements.
   */
  protected function getInfo() {
    return [
      'description' => '@todo update',
      'title' => '@todo update title',
    ];
  }

  /**
   * Returns the paths information.
   *
   * @return array
   *   The info elements.
   */
  protected function getPaths() {
    $api_paths = [];
    /** @var \Drupal\rest\Entity\RestResourceConfig[] $resource_configs */
    $resource_configs = $this->entityTypeManager()
      ->getStorage('rest_resource_config')
      ->loadMultiple();

    foreach ($resource_configs as $id => $resource_config) {
      /** @var \Drupal\rest\Plugin\ResourceBase $plugin */
      $resource_plugin = $resource_config->getResourcePlugin();
      foreach ($resource_config->getMethods() as $method) {
        if ($route = $this->getRouteForResourceMethod($resource_config, $method)) {
          $swagger_method = strtolower($method);
          $path = $route->getPath();
          $path_method_spec = [];
          $formats = $resource_config->getFormats($method);
          $format_parameter = [
            'name' => '_format',
            'in' => 'query',
            'enum' => $formats,
            'required' => TRUE,
          ];
          if (count($formats) == 1) {
            $format_parameter['default'] = $formats[0];
          }
          $path_method_spec['parameters'][] = $format_parameter;
          if ($resource_plugin instanceof EntityResource) {

            $entity_type = $this->entityTypeManager->getDefinition($resource_plugin->getPluginDefinition()['entity_type']);
            $path_method_spec['summary'] = $this->t('@method a @entity_type', [
              '@method' => ucfirst($swagger_method),
              '@entity_type' => $entity_type->getLabel(),
            ]);

            $path_method_spec['consumes'] = ['application/json'];
            $path_method_spec['produces'] = ['application/json'];
            $path_method_spec['parameters'] += $this->getEntityParameters($entity_type, $method);

          }
          else {
            $path_method_spec['summary'] = $resource_plugin->getPluginDefinition()['label'];
          }

          $path_method_spec['operationId'] = $resource_plugin->getPluginId();
          $path_method_spec['schemes'] = ['http'];
          $path_method_spec['parameters'] = array_merge($path_method_spec['parameters'], $this->getRouteParameters($route));
          $path_method_spec['security'] = $this->getSecurity($resource_config, $method);
          $api_paths[$path][$swagger_method] = $path_method_spec;
        }
      }
    }
    return $api_paths;
  }

  /**
   * Get parameters for an entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   * @param $method
   *
   * @return array
   */
  protected function getEntityParameters(EntityTypeInterface $entity_type, $method) {
    $parameters = [];
    if (in_array($method, ['GET', 'DELETE', 'PATCH'])) {
      $keys = $entity_type->getKeys();
      $parameters[] = [
        'name' => $entity_type->id(),
        'in' => 'path',
        'default' => '',
        'description' => $this->t('The @id(id) of the @type.', [
          '@id' => $keys['id'],
          '@type' => $entity_type->id(),
        ]),
      ];
    }
    if (in_array($method, ['POST', 'PATCH'])) {
      if ($entity_type instanceof ContentEntityTypeInterface) {
        $base_fields = $this->fieldManager->getBaseFieldDefinitions($entity_type->id());
        foreach ($base_fields as $field_name => $base_field) {
          $parameters[] = $this->getSwaggerFieldParameter($base_field);
        }
      }
    }
    return $parameters;
  }

  /**
   * Gets the a Swagger parameter for a field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field
   *
   * @return array
   */
  protected function getSwaggerFieldParameter(FieldDefinitionInterface $field) {
    $parameter = [
      'name' => $field->getName(),
      'required' => $field->isRequired(),
    ];
    $type = $field->getType();
    $date_types = ['changed', 'created'];
    if (in_array($type, $date_types)) {
      $parameter['type'] = 'string';
      $parameter['format'] = 'date-time';
    }
    else {
      $string_types = ['string_long', 'uuid'];
      if (in_array($type, $string_types)) {
        $parameter['type'] = 'string';
      }
    }
    $parameter['default'] = '';
    return $parameter;

  }

  /**
   * The Swagger UI page.
   *
   * @return array
   */
  public function swaggerUiPage() {
    $build = [
      '#theme' => 'swagger_ui',
      '#attached' => [
        'library' => [
          'waterwheel/swagger_ui_integration',
          'waterwheel/swagger_ui',
        ],
      ],
    ];

    return $build;
  }

  /**
   * Get Swagger parameters for a route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *
   * @return array
   */
  protected function getRouteParameters(Route $route) {
    $parameters = [];
    $vars = $route->compile()->getPathVariables();
    foreach ($vars as $var) {
      $parameters[] = [
        'name' => $var,
        'type' => 'string',
        'in' => 'path',
      ];
    }
    return $parameters;
  }

  /**
   * Gets the matching for route for the resource and method.
   *
   * @param $resource_config
   * @param $method
   *
   * @return \Symfony\Component\Routing\Route
   */
  protected function getRouteForResourceMethod(RestResourceConfigInterface $resource_config, $method) {
    $resource_plugin = $resource_config->getResourcePlugin();
    foreach ($resource_plugin->routes() as $route) {
      $methods = $route->getMethods();
      if (array_search($method, $methods) !== FALSE) {
        return $route;
      }
    };
  }

  /**
   * Get the security information for the a resource.
   *
   * @see http://swagger.io/specification/#securityDefinitionsObject
   *
   * @param \Drupal\rest\RestResourceConfigInterface $resource_config
   * @param $method
   *
   * @return array
   */
  protected function getSecurity(RestResourceConfigInterface $resource_config, $method) {
    $security = [];
    foreach ($resource_config->getAuthenticationProviders($method) as $auth) {
      switch ($auth) {
        case 'basic_auth':
          $security['basic_auth'] = [
            'type' => 'basic',
          ];
      }
    }
    // @todo Handle tokens that need to be set in headers.
    return $security;
  }

}