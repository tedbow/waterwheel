<?php


namespace Drupal\waterwheel\Tests;

use Drupal\rest\Tests\RESTTestBase;
use Drupal\serialization\Encoder\JsonEncoder;
use GuzzleHttp\Cookie\CookieJar;
use Symfony\Component\Serializer\Serializer;

abstract class WaterwheelTestBase extends RESTTestBase {

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
  }

}
