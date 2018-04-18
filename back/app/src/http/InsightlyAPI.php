<?php declare(strict_types=1);

namespace Acme\Http;

require_once '/var/www/html/app/vendor/autoload.php';

use \GuzzleHttp\Client;
use \GuzzleHttp\Exception\RequestException;
use \GuzzleHttp\Exception\ClientException;
use \Psr\Http\Message\ResponseInterface;

final class InsightlyAPI {

  /**
   * @var \GuzzleHttp\Client
   */
  private $client;

  private $version = 'v2.2';

  public function __construct(\GuzzleHttp\Client $client) {
    $this->client = $client;
  }

  /**
   * getEmail
   *
   * @return array
   */
  public function getEmail(string $id, array $options = []): \stdClass {
    return $this->get('Emails/' . $id, $options);
  }

  /**
   * getEmails
   *
   * Returns a batched list of emails. We will use this to
   * loop over and get specific emails as getEmails does not
   * contain the full body text.
   *
   * @return array
   */
  public function getEmails(string $queryString = '', array $options = []): array {
    return $this->get('Emails' . $queryString . '&brief=true', $options);
  }

  /**
   * getFileAttachments
   * 
   * Returns a list of attachments belonging to a single email.
   * We will use this to loop over and download any attachment
   * that we are able to retrieve.
   *
   * @return array
   */
  public function getFileAttachments(string $id, array $options = []): array {
    return $this->get('Emails/' . $id . '/FileAttachments', $options);
  }

  /**
   * Create and send a HTTP GET request
   *
   * @param string  $uri the URI to hit
   * @param array   $options HTTP client configuration options
   *
   * @return ResponseInterface
   *
   * @throws ClientException
   * @throws RequestException
   */
  private function get(string $uri, array $options = []) {
    $response = $this->client->get('/' . $this->version . '/' . $uri, $options);

    return json_decode($response->getBody()->getContents());
  }
}
