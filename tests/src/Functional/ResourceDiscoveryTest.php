<?php

namespace Drupal\Tests\waterwheel\Functional;

use Drupal\Core\Url;
use Drupal\serialization\Encoder\JsonEncoder;
use Drupal\Tests\BrowserTestBase;
use GuzzleHttp\Cookie\CookieJar;
use Masterminds\HTML5\Exception;
use Symfony\Component\Serializer\Serializer;

/**
 * Tests resource discovery routes.
 *
 * @group waterwheel
 */
class ResourceDiscoveryTest extends BrowserTestBase {
  /**
   * The serializer.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * The cookie jar.
   *
   * @var \GuzzleHttp\Cookie\CookieJar
   */
  protected $cookies;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->cookies = new CookieJar();
    $encoders = [new JsonEncoder()];
    $this->serializer = new Serializer([], $encoders);

    /** @var \Drupal\Core\Extension\ModuleInstaller $module_installer */
    $module_installer = $this->container->get('module_installer');
    $module_installer->install(['serialization']);

    $module_installer->install(['rest']);
    $module_installer->install(['waterwheel']);

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Page']);
  }

  /**
   * Tests discovering resources.
   */
  public function testResourceDiscovery() {
    $user = $this->drupalCreateUser(
      [
        'view site configuration',
        'restful get entity_types_list_resource',
        'restful get bundle_type_resource',
      ]
    );
    $this->drupalLogin($user);

    $result = $this->loginRequest($user->getAccountName(), $user->passRaw);
    $this->assertEquals(200, $result->getStatusCode());

    $this->makeRequest('rest.entity_types_list_resource.GET.json', $this->getExpectedResources());
    $this->makeRequest('rest.bundle_type_resource.GET.json',
      $this->getExpectedBundle('node', 'page'),
      [
        'entity_type' => 'node',
        'bundle' => 'page',
      ]
    );



  }

  /**
   * Executes a login HTTP request.
   *
   * @param string $name
   *   The username.
   * @param string $pass
   *   The user password.
   * @param string $format
   *   The format to use to make the request.
   *
   * @return \Psr\Http\Message\ResponseInterface The HTTP response.
   *   The HTTP response.
   */
  protected function loginRequest($name, $pass, $format = 'json') {
    $user_login_url = Url::fromRoute('user.login.http')
      ->setRouteParameter('_format', $format)
      ->setAbsolute();

    $request_body = [];
    if (isset($name)) {
      $request_body['name'] = $name;
    }
    if (isset($pass)) {
      $request_body['pass'] = $pass;
    }

    $result = \Drupal::httpClient()->post($user_login_url->toString(), [
      'body' => $this->serializer->encode($request_body, $format),
      'headers' => [
        'Accept' => "application/$format",
      ],
      'http_errors' => FALSE,
      'cookies' => $this->cookies,
    ]);
    return $result;
  }

  /**
   * Returns expected resources list.
   *
   * @return array
   *   Expected resources
   */
  protected function getExpectedResources() {
    $expected_resources = [
      'node' =>
        [
          'label' => 'Content',
          'type' => 'content',
          'methods' =>
            [
              'GET' => '/node/{node}',
              'POST' => '/entity/node',
              'PATCH' => '/node/{node}',
              'DELETE' => '/node/{node}',
            ],
          'bundles' => ['page'],
          'more' => '/entity/types/node/{bundle}',
        ],
    ];
    return $expected_resources;
  }

  /**
   * @param $client
   *
   * @return mixed
   */
  protected function makeRequest($route_name, $expected_results, $route_parameters = []) {
    $url = Url::fromRoute($route_name);

    $route_parameters += ['_format' => 'json'];
    $url->setRouteParameters($route_parameters);

    $url_string = $url->setAbsolute()->toString();

    $result = \Drupal::httpClient()->get($url_string, ['cookies' => $this->cookies]);

    $this->assertEquals(200, $result->getStatusCode());
    $results_array = $this->serializer->decode($result->getBody(), 'json');

    $this->assertEquals($expected_results, $results_array);
    return $result;
  }

  protected function getExpectedBundle($entity_type, $bundle) {
    $expected['node']['page'] = array(
      'label' => 'Page',
      'fields' =>
        array(
          'nid' =>
            array(
              'label' => 'ID',
              'type' => 'integer',
              'data_type' => 'list',
              'required' => FALSE,
              'readonly' => TRUE,
              'cardinality' => 1,
              'settings' =>
                array(
                  'unsigned' => TRUE,
                  'size' => 'normal',
                  'min' => '',
                  'max' => '',
                  'prefix' => '',
                  'suffix' => '',
                ),
              'is_reference' => FALSE,
            ),
          'uuid' =>
            array(
              'label' => 'UUID',
              'type' => 'uuid',
              'data_type' => 'list',
              'required' => FALSE,
              'readonly' => TRUE,
              'cardinality' => 1,
              'settings' =>
                array(
                  'max_length' => 128,
                  'is_ascii' => TRUE,
                  'case_sensitive' => FALSE,
                ),
              'is_reference' => FALSE,
            ),
          'vid' =>
            array(
              'label' => 'Revision ID',
              'type' => 'integer',
              'data_type' => 'list',
              'required' => FALSE,
              'readonly' => TRUE,
              'cardinality' => 1,
              'settings' =>
                array(
                  'unsigned' => TRUE,
                  'size' => 'normal',
                  'min' => '',
                  'max' => '',
                  'prefix' => '',
                  'suffix' => '',
                ),
              'is_reference' => FALSE,
            ),
          'langcode' =>
            array(
              'label' => 'Language',
              'type' => 'language',
              'data_type' => 'list',
              'required' => FALSE,
              'readonly' => FALSE,
              'cardinality' => 1,
              'settings' =>
                array(),
              'is_reference' => FALSE,
            ),
          'type' =>
            array(
              'label' => 'Content type',
              'type' => 'entity_reference',
              'data_type' => 'list',
              'required' => TRUE,
              'readonly' => TRUE,
              'cardinality' => 1,
              'settings' =>
                array(
                  'target_type' => 'node_type',
                  'handler' => 'default',
                  'handler_settings' =>
                    array(),
                ),
              'is_reference' => TRUE,
            ),
          'title' =>
            array(
              'label' => 'Title',
              'type' => 'string',
              'data_type' => 'list',
              'required' => TRUE,
              'readonly' => FALSE,
              'cardinality' => 1,
              'settings' =>
                array(
                  'max_length' => 255,
                  'is_ascii' => FALSE,
                  'case_sensitive' => FALSE,
                ),
              'is_reference' => FALSE,
            ),
          'uid' =>
            array(
              'label' => 'Authored by',
              'type' => 'entity_reference',
              'data_type' => 'list',
              'required' => FALSE,
              'readonly' => FALSE,
              'cardinality' => 1,
              'settings' =>
                array(
                  'target_type' => 'user',
                  'handler' => 'default',
                  'handler_settings' =>
                    array(),
                ),
              'is_reference' => TRUE,
            ),
          'status' =>
            array(
              'label' => 'Publishing status',
              'type' => 'boolean',
              'data_type' => 'list',
              'required' => FALSE,
              'readonly' => FALSE,
              'cardinality' => 1,
              'settings' =>
                array(
                  'on_label' => 'On',
                  'off_label' => 'Off',
                ),
              'is_reference' => FALSE,
            ),
          'created' =>
            array(
              'label' => 'Authored on',
              'type' => 'created',
              'data_type' => 'list',
              'required' => FALSE,
              'readonly' => FALSE,
              'cardinality' => 1,
              'settings' =>
                array(),
              'is_reference' => FALSE,
            ),
          'changed' =>
            array(
              'label' => 'Changed',
              'type' => 'changed',
              'data_type' => 'list',
              'required' => FALSE,
              'readonly' => FALSE,
              'cardinality' => 1,
              'settings' =>
                array(),
              'is_reference' => FALSE,
            ),
          'promote' =>
            array(
              'label' => 'Promoted to front page',
              'type' => 'boolean',
              'data_type' => 'list',
              'required' => FALSE,
              'readonly' => FALSE,
              'cardinality' => 1,
              'settings' =>
                array(
                  'on_label' => 'On',
                  'off_label' => 'Off',
                ),
              'is_reference' => FALSE,
            ),
          'sticky' =>
            array(
              'label' => 'Sticky at top of lists',
              'type' => 'boolean',
              'data_type' => 'list',
              'required' => FALSE,
              'readonly' => FALSE,
              'cardinality' => 1,
              'settings' =>
                array(
                  'on_label' => 'On',
                  'off_label' => 'Off',
                ),
              'is_reference' => FALSE,
            ),
          'revision_timestamp' =>
            array(
              'label' => 'Revision timestamp',
              'type' => 'created',
              'data_type' => 'list',
              'required' => FALSE,
              'readonly' => FALSE,
              'cardinality' => 1,
              'settings' =>
                array(),
              'is_reference' => FALSE,
            ),
          'revision_uid' =>
            array(
              'label' => 'Revision user ID',
              'type' => 'entity_reference',
              'data_type' => 'list',
              'required' => FALSE,
              'readonly' => FALSE,
              'cardinality' => 1,
              'settings' =>
                array(
                  'target_type' => 'user',
                  'handler' => 'default',
                  'handler_settings' =>
                    array(),
                ),
              'is_reference' => TRUE,
            ),
          'revision_log' =>
            array(
              'label' => 'Revision log message',
              'type' => 'string_long',
              'data_type' => 'list',
              'required' => FALSE,
              'readonly' => FALSE,
              'cardinality' => 1,
              'settings' =>
                array(
                  'case_sensitive' => FALSE,
                ),
              'is_reference' => FALSE,
            ),
          'revision_translation_affected' =>
            array(
              'label' => 'Revision translation affected',
              'type' => 'boolean',
              'data_type' => 'list',
              'required' => FALSE,
              'readonly' => TRUE,
              'cardinality' => 1,
              'settings' =>
                array(
                  'on_label' => 'On',
                  'off_label' => 'Off',
                ),
              'is_reference' => FALSE,
            ),
          'default_langcode' =>
            array(
              'label' => 'Default translation',
              'type' => 'boolean',
              'data_type' => 'list',
              'required' => FALSE,
              'readonly' => FALSE,
              'cardinality' => 1,
              'settings' =>
                array(
                  'on_label' => 'On',
                  'off_label' => 'Off',
                ),
              'is_reference' => FALSE,
            ),
          'body' =>
            array(
              'label' => 'Body',
              'type' => 'text_with_summary',
              'data_type' => 'list',
              'required' => FALSE,
              'readonly' => FALSE,
              'cardinality' => 1,
              'settings' =>
                array(
                  'display_summary' => TRUE,
                ),
              'is_reference' => FALSE,
            ),
        ),
    );
    if (isset($expected[$entity_type][$bundle])) {
      return $expected[$entity_type][$bundle];
    }
    throw new \Exception("Unknown resource: $entity_type:$bundle");
  }

}
