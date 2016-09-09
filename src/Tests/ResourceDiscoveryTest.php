<?php

namespace Drupal\waterwheel\Tests;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\rest\Tests\RESTTestBase;
use Drupal\serialization\Encoder\JsonEncoder;
use GuzzleHttp\Cookie\CookieJar;
use Symfony\Component\Serializer\Serializer;

/**
 * Tests resource discovery routes.
 *
 * @group waterwheel
 */
class ResourceDiscoveryTest extends RESTTestBase {
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
  protected $guzzleCookies;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'node',
    'taxonomy',
    'serialization',
    'rest',
    'waterwheel',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->defaultFormat = 'json';
    $this->defaultMimeType = 'application/json';
    $this->guzzleCookies = new CookieJar();
    $encoders = [new JsonEncoder()];
    $this->serializer = new Serializer([], $encoders);

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Page']);

  }

  /**
   * Tests discovering resources.
   */
  public function testResourceDiscovery() {
    // Test for access denied to test permission logic in
    // \Drupal\waterwheel\Plugin\rest\EntityTypeResourceBase::getBaseRoute()
    $access_possibilities = [TRUE, FALSE];
    foreach ($access_possibilities as $access_possibility) {
      if ($access_possibility) {
        $user = $this->drupalCreateUser(['waterwheel GET site configuration']);
        $status_code = 200;
      }
      else {
        $user = $this->drupalCreateUser();
        $status_code = 403;
      }
      $this->drupalLogin($user);
      $enable_entity_types = [
        'node' => ['GET', 'POST', 'PATCH', 'DELETE'],
        'user' => ['GET'],
        'taxonomy_vocabulary' => ['GET'],
      ];
      foreach ($enable_entity_types as $entity_type_id => $methods) {
        foreach ($methods as $method) {
          $this->enableService("entity:$entity_type_id", $method, 'cookie');
        }
      }

      $result = $this->loginRequest($user->getAccountName(), $user->pass_raw);
      $this->assertEqual(200, $result->getStatusCode());

      $url = Url::fromRoute('rest.entity_types_list_resource.GET.json')->setRouteParameter('_format', 'json');
      if ($access_possibility) {
        $expected = $this->getExpectedResources();
      }
      else {
        $expected = NULL;
      }
      $this->assertHttpResponse($url, 'GET', $status_code, $expected, 'Resource list correct');

      $url = Url::fromRoute('rest.bundle_type_resource.GET.json');
      $url->setRouteParameters(
        [
          '_format' => 'json',
          'entity_type' => 'node',
          'bundle' => 'page',
        ]
      );
      if ($access_possibility) {
        $expected = $this->getExpectedBundle('node', 'page');
      }
      else {
        $expected = NULL;
      }
      $this->assertHttpResponse($url, 'GET', $status_code, $expected, 'Page bundle information correct.');

      $url = Url::fromRoute('rest.bundle_type_resource.GET.json');
      $url->setRouteParameters(
        [
          '_format' => 'json',
          'entity_type' => 'user',
          'bundle' => 'user',
        ]
      );
      if ($access_possibility) {
        $expected = $this->getExpectedBundle('user', 'user');
      }
      else {
        $expected = NULL;
      }
      $this->assertHttpResponse($url, 'GET', $status_code, $expected, 'User bundle information correct.');

      $url = Url::fromRoute('rest.entity_type_resource.GET.json');
      $url->setRouteParameters(
        [
          '_format' => 'json',
          'entity_type' => 'taxonomy_vocabulary',
        ]
      );
      if ($access_possibility) {
        $expected = $this->getExpectedEntityType('taxonomy_vocabulary');
      }
      else {
        $expected = NULL;
      }
      $this->assertHttpResponse($url, 'GET', $status_code, $expected, 'Vocabulary information correct.');
    }
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
      'guzzle_cookies' => $this->guzzleCookies,
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
      'user' =>
        [
          'label' => 'User',
          'type' => 'content',
          'methods' =>
            [
              'GET' => '/user/{user}',
            ],
          'more' => 'entity/types/user/user',
        ],
      'taxonomy_vocabulary' =>
        array(
          'label' => 'Taxonomy vocabulary',
          'type' => 'config',
          'methods' =>
            array(
              'GET' => '/entity/taxonomy_vocabulary/{taxonomy_vocabulary}',
            ),
          'more' => 'entity/types/taxonomy_vocabulary',
        ),
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
          'bundles' => ['page', 'resttest'],
          'more' => '/entity/types/node/{bundle}',
        ],
    ];
    return $expected_resources;
  }

  /**
   * The expected results for bundle info requests.
   *
   * @param string $entity_type_id
   *   The id of the entity to get expected results for.
   * @param string $bundle_name
   *   The name of the bundle to get expected results for.
   *
   * @return array
   *   The array of expected results.
   *
   * @throws \Exception
   *   If entity_type_id and bundle name are not supported.
   */
  protected function getExpectedBundle($entity_type_id, $bundle_name) {
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
    $expected['user']['user'] = array(
      'label' => 'User',
      'fields' =>
        array(
          'uid' =>
            array(
              'label' => 'User ID',
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
          'langcode' =>
            array(
              'label' => 'Language code',
              'type' => 'language',
              'data_type' => 'list',
              'required' => FALSE,
              'readonly' => FALSE,
              'cardinality' => 1,
              'settings' =>
                array(),
              'is_reference' => FALSE,
            ),
          'preferred_langcode' =>
            array(
              'label' => 'Preferred language code',
              'type' => 'language',
              'data_type' => 'list',
              'required' => FALSE,
              'readonly' => FALSE,
              'cardinality' => 1,
              'settings' =>
                array(),
              'is_reference' => FALSE,
            ),
          'preferred_admin_langcode' =>
            array(
              'label' => 'Preferred admin language code',
              'type' => 'language',
              'data_type' => 'list',
              'required' => FALSE,
              'readonly' => FALSE,
              'cardinality' => 1,
              'settings' =>
                array(),
              'is_reference' => FALSE,
            ),
          'name' =>
            array(
              'label' => 'Name',
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
          'pass' =>
            array(
              'label' => 'Password',
              'type' => 'password',
              'data_type' => 'list',
              'required' => FALSE,
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
          'mail' =>
            array(
              'label' => 'Email',
              'type' => 'email',
              'data_type' => 'list',
              'required' => FALSE,
              'readonly' => FALSE,
              'cardinality' => 1,
              'settings' =>
                array(),
              'is_reference' => FALSE,
            ),
          'timezone' =>
            array(
              'label' => 'Timezone',
              'type' => 'string',
              'data_type' => 'list',
              'required' => FALSE,
              'readonly' => FALSE,
              'cardinality' => 1,
              'settings' =>
                array(
                  'max_length' => 32,
                  'is_ascii' => FALSE,
                  'case_sensitive' => FALSE,
                ),
              'is_reference' => FALSE,
            ),
          'status' =>
            array(
              'label' => 'User status',
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
              'label' => 'Created',
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
          'access' =>
            array(
              'label' => 'Last access',
              'type' => 'timestamp',
              'data_type' => 'list',
              'required' => FALSE,
              'readonly' => FALSE,
              'cardinality' => 1,
              'settings' =>
                array(),
              'is_reference' => FALSE,
            ),
          'login' =>
            array(
              'label' => 'Last login',
              'type' => 'timestamp',
              'data_type' => 'list',
              'required' => FALSE,
              'readonly' => FALSE,
              'cardinality' => 1,
              'settings' =>
                array(),
              'is_reference' => FALSE,
            ),
          'init' =>
            array(
              'label' => 'Initial email',
              'type' => 'email',
              'data_type' => 'list',
              'required' => FALSE,
              'readonly' => FALSE,
              'cardinality' => 1,
              'settings' =>
                array(),
              'is_reference' => FALSE,
            ),
          'roles' =>
            array(
              'label' => 'Roles',
              'type' => 'entity_reference',
              'data_type' => 'list',
              'required' => FALSE,
              'readonly' => FALSE,
              'cardinality' => -1,
              'settings' =>
                array(
                  'target_type' => 'user_role',
                  'handler' => 'default',
                  'handler_settings' =>
                    array(),
                ),
              'is_reference' => TRUE,
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
        ),
    );
    if (isset($expected[$entity_type_id][$bundle_name])) {
      return $expected[$entity_type_id][$bundle_name];
    }
    throw new \Exception("Unknown resource: $entity_type_id:$bundle_name");
  }

  /**
   * Asserts that HTTP response has expected response code and response body.
   *
   * @param \Drupal\Core\Url $url
   *   The Url object.
   * @param string $method
   *   The HTTP method.
   * @param int $status_code
   *   The status code.
   * @param array $expected_result
   *   The expected result, un-encoded.
   * @param string $message
   *   The message to display if body is not expected result.
   */
  protected function assertHttpResponse(Url $url, $method, $status_code, $expected_result, $message = '') {
    $response = $this->httpRequest($url, $method);
    $this->assertResponse($status_code);
    if ($expected_result !== NULL) {
      $data = Json::decode($response);
      $this->assertEqual($data, $expected_result, $message);
    }
  }

  /**
   * Gets the expected results for an entity type.
   *
   * @param string $entity_type_id
   *   The entity type id.
   *
   * @return array
   *   Expected information for entity type.
   *
   * @throws \Exception
   *   If unknown resource.
   */
  protected function getExpectedEntityType($entity_type_id) {
    $entity_types = [
      'taxonomy_vocabulary' => array(
        'label' => 'Taxonomy vocabulary',
        'type' => 'config',
        'methods' =>
          array(
            'GET' => '/entity/taxonomy_vocabulary/{taxonomy_vocabulary}',
          ),
      ),
    ];

    if (isset($entity_types[$entity_type_id])) {
      return $entity_types[$entity_type_id];
    }
    throw new \Exception("Unknown resource: $entity_type_id");
  }

}
