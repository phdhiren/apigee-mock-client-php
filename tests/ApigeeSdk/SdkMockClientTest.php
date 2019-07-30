<?php

/*
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Apigee\MockClient\Tests\ApigeeSdk;

use Apigee\Edge\Api\Management\Controller\DeveloperController;
use Apigee\Edge\Api\Management\Entity\Developer;
use Apigee\Edge\Api\Management\Entity\Organization;
use Apigee\Edge\Serializer\EntitySerializer;
use Apigee\Edge\Structure\AttributesProperty;
use Apigee\MockClient\ApigeeSdk\SdkMockClient;
use Apigee\MockClient\Generator\ApigeeSdkEntityGenerator;
use Apigee\MockClient\Generator\ApigeeSdkEntitySource;
use Apigee\MockClient\MockClient;
use Apigee\MockClient\ResponseFactory;
use Apigee\MockClient\SimpleMockStorage;
use Http\Message\RequestMatcher\RequestMatcher;
use PHPUnit\Framework\TestCase;

/**
 * Tests the serializable response.
 */
class SdkMockClientTest extends TestCase {

  /**
   * Used to generate random strings.
   *
   * @var string
   */
  protected $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

  /**
   * Test Apigee Edge entity SDK generator.
   */
  public function testApigeeSdkGenerator() {
    $generator = new ApigeeSdkEntityGenerator(new EntitySerializer());

    // Creates a response factory.
    $factory = new ResponseFactory();
    $factory->addGenerator($generator);

    $developer = new Developer([
      'developerId' => uniqid(),
      'companies' => [substr(str_shuffle($this->permitted_chars), 0, 8)],
      'userName' => substr(str_shuffle($this->permitted_chars), 0, 8),
      'firstName' => substr(str_shuffle($this->permitted_chars), 0, 8),
      'lastName' => substr(str_shuffle($this->permitted_chars), 0, 8),
      'email' => substr(str_shuffle($this->permitted_chars), 0, 8) . '@example.com',
      'organizationName' => 'foo',
      'status' => 'active',
      'attributes' => [
        new AttributesProperty([
          "name" => "MINT_BILLING_TYPE",
          "value" => "PREPAID",
        ]),
      ],
      'createdAt' => new \DateTimeImmutable('2000-01-01 00:00:00.0'),
      'createdBy' => "admin@example.com",
      'lastModifiedAt' => new \DateTimeImmutable('2000-01-01 00:00:00.0'),
      'lastModifiedBy' => "admin@example.com"
    ]);

    // Add a response to a new client.
    $client = new MockClient(new SimpleMockStorage());
    $client->addResponse($factory->generateResponse(new ApigeeSdkEntitySource($developer)));

    $developer_controller = new DeveloperController('foo', new SdkMockClient($client));
    static::assertEquals($developer, $developer_controller->load($developer->id()));
  }

  /**
   * Test getting a list of entities from the Apigee SDK.
   */
  public function testSerializationOfMultipleEntities() {    $generator = new ApigeeSdkEntityGenerator(new EntitySerializer());
    // Creates a response factory.
    $factory = new ResponseFactory();
    $factory->addGenerator($generator);

    $developers = [
      new Developer([
        'developerId' => uniqid(),
        'companies' => [substr(str_shuffle($this->permitted_chars), 0, 8)],
        'userName' => substr(str_shuffle($this->permitted_chars), 0, 8),
        'firstName' => substr(str_shuffle($this->permitted_chars), 0, 8),
        'lastName' => substr(str_shuffle($this->permitted_chars), 0, 8),
        'email' => substr(str_shuffle($this->permitted_chars), 0, 8) . '@example.com',
        'organizationName' => 'foo',
        'status' => 'active',
        'attributes' => [
          new AttributesProperty([
            "name" => "MINT_BILLING_TYPE",
            "value" => "PREPAID",
          ]),
        ],
        'createdAt' => new \DateTimeImmutable('2000-01-01 00:00:00.0'),
        'createdBy' => "admin@example.com",
        'lastModifiedAt' => new \DateTimeImmutable('2000-01-01 00:00:00.0'),
        'lastModifiedBy' => "admin@example.com"
      ]),
      new Developer([
        'developerId' => uniqid(),
        'companies' => [substr(str_shuffle($this->permitted_chars), 0, 8)],
        'userName' => substr(str_shuffle($this->permitted_chars), 0, 8),
        'firstName' => substr(str_shuffle($this->permitted_chars), 0, 8),
        'lastName' => substr(str_shuffle($this->permitted_chars), 0, 8),
        'email' => substr(str_shuffle($this->permitted_chars), 0, 8) . '@example.com',
        'organizationName' => 'foo',
        'status' => 'active',
        'attributes' => [
          new AttributesProperty([
            "name" => "MINT_BILLING_TYPE",
            "value" => "PREPAID",
          ]),
        ],
        'createdAt' => new \DateTimeImmutable('2000-01-01 00:00:00.0'),
        'createdBy' => "admin@example.com",
        'lastModifiedAt' => new \DateTimeImmutable('2000-01-01 00:00:00.0'),
        'lastModifiedBy' => "admin@example.com"
      ]),
    ];

    $organization = new Organization(['name' => 'foo']);

    $client = new MockClient(new SimpleMockStorage());
    // The developer controller will first try to load the organization.
    $client->on(
      new RequestMatcher('/v1/organizations/foo'),
      $factory->generateResponse(new ApigeeSdkEntitySource($organization))
    );
    // Add the developers response.
    $client->addResponse($factory->generateResponse(new ApigeeSdkEntitySource([
      'developer' => $developers,
      'totalRecords' => count($developers),
    ])));

    // Get the developers from the SDK controller.
    $developer_controller = new DeveloperController('foo', new SdkMockClient($client));
    $developers = $developer_controller->getEntities();

    // Test the request.
    $request = $client->getLastRequest();
    static::assertSame('GET', $request->getMethod());
    static::assertSame('https://api.enterprise.apigee.com/v1/organizations/foo/developers?expand=true', (string) $request->getUri());

    // Test the response.
    foreach ($developers as $developer) {
      static::assertEquals($developer, $developers[$developer->getEmail()]);
    }
  }

}
