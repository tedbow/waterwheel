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
  protected $guzzle_cookies;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'node', 'serialization', 'rest', 'waterwheel'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->defaultFormat = 'json';
    $this->defaultMimeType = 'application/json';
    $this->guzzle_cookies = new CookieJar();
    $encoders = [new JsonEncoder()];
    $this->serializer = new Serializer([], $encoders);

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

    $result = $this->loginRequest($user->getAccountName(), $user->pass_raw);
    $this->assertEqual(200, $result->getStatusCode());


    $url = Url::fromRoute('rest.entity_types_list_resource.GET.json')->setRouteParameter('_format', $this->defaultFormat);
    $response = $this->httpRequest($url, 'GET');
    $this->assertResponse(200);
    $data = Json::decode($response);
    $this->assertEqual($data, $this->getExpectedResources(), 'Resource list correct');

    $url = Url::fromRoute('rest.bundle_type_resource.GET.json');
    $url->setRouteParameters(
      [
        '_format' => $this->defaultFormat,
        'entity_type' => 'node',
        'bundle' => 'page',
      ]
    );
    $response = $this->httpRequest($url, 'GET');
    $this->assertResponse(200);
    $data = Json::decode($response);
    $this->assertEqual($data, $this->getExpectedBundle('node', 'page'), 'Page bundle information correct.');
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
      'guzzle_cookies' => $this->guzzle_cookies,
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
    if (isset($expected[$entity_type_id][$bundle_name])) {
      return $expected[$entity_type_id][$bundle_name];
    }
    throw new \Exception("Unknown resource: $entity_type_id:$bundle_name");
  }

}
