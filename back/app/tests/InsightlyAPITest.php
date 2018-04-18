<?php declare(strict_types=1);

use \PHPUnit\Framework\TestCase;
use \GuzzleHttp\Handler\MockHandler;
use \GuzzleHttp\HandlerStack;
use \GuzzleHttp\Client;
use \GuzzleHttp\Psr7\Response;
use \GuzzleHttp\Exception\ClientException;
use \Acme\Http\InsightlyAPI;

class InsighlyAPITest extends TestCase {

  /**
   * InsightlyAPI->getEmails(); 
   */
  public function test_getEmails_returns_a_paginated_array_containing_json_objects() {
    $body = file_get_contents(__DIR__ . '/mocks/insightly_api/getEmails-response.json');

    $api = $this->getAPI(200, $body);
    $result = $api->getEmails();

    $this->assertEquals(json_decode($body), $result);
    $this->assertCount(100, $result);
  }
  public function test_getEmails_response_can_be_limited_to_x_results() {
    $body = file_get_contents(__DIR__ . '/mocks/insightly_api/getEmails-limited-response.json');

    $api = $this->getAPI(200, $body);
    $result = $api->getEmails('?top=5');

    $this->assertCount(5, $result);
  }
  public function test_getEmails_response_can_be_told_to_skip_x_results() {
    $correctBody = file_get_contents(__DIR__ . '/mocks/insightly_api/getEmails-skipped-response.json');
    $incorrectBody = file_get_contents(__DIR__ . '/mocks/insightly_api/getEmails-limited-response.json');

    $api = $this->getAPI(200, $correctBody);
    $result = $api->getEmails('?skip=5&top=5');

    $this->assertEquals(json_decode($correctBody), $result);
    $this->assertNotEquals(json_decode($incorrectBody), $result);
  }

  /**
   * InsightlyAPI->getEmail();
   */
  public function test_getEmail_returns_json_object_containing_email_data() {
    $body = file_get_contents(__DIR__ . '/mocks/insightly_api/getEmail-response.json');

    $api = $this->getAPI(200, $body);
    $result = $api->getEmail('7459520');

    $this->assertEquals(json_decode($body), $result);
  }

  /**
   * InsightlyAPI->getFileAttachments();
   */
  public function test_getFileAttachments_returns_array_containing_json_objects() {
    $body = file_get_contents(__DIR__ . '/mocks/insightly_api/getEmail-FileAttachments-response.json');

    $api = $this->getAPI(200, $body);
    $result = $api->getFileAttachments('7459520');

    $this->assertEquals(json_decode($body), $result);
  }

  /**
   * Mock external API calls
   *
   * @param int   $status status code to be mocked
   * @param mixed $body   response body to be mocked
   *
   * @return Acme\Http\InsightlyAPI
   */
  private function getAPI(int $status, $body = null) {
    $mock = new MockHandler([new Response($status, [], $body)]);
    $handler = HandlerStack::create($mock);
    $client = new Client(['handler' => $handler]);

    return new InsightlyAPI($client);
  }

}
