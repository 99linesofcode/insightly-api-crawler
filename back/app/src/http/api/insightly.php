<?php declare(strict_types=1);

namespace Acme\Http\API;

require_once '/var/www/html/app/vendor/autoload.php';

use \GuzzleHttp\Client;
use \GuzzleHttp\Exception\RequestException;
use \GuzzleHttp\Exception\ClientException;
use \Psr\Http\Message\ResponseInterface;

final class insightly {

  const BATCH_SIZE = 100;
  const VERSION = 'v2.2';

  /**
   * @var \GuzzleHttp\Client
   */
  private $client;
  
  public function __construct(\GuzzleHttp\Client $client) {
    $this->client = $client;
  }

  public function getTotalNumberOfEmails(): int {
    $response = $this->client->get('/' . self::VERSION . '/Emails?brief=true&top=1&count_total=true');
    return intval($response->getHeaderLine('x-total-count'));
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
   * getEmailFileAttachments
   * 
   * Returns a list of attachments belonging to a single email.
   * We will use this to loop over and download any attachment
   * that we are able to retrieve.
   *
   * @return array
   */
  public function getEmailFileAttachments(string $id, array $options = []): array {
    return $this->get('Emails/' . $id . '/FileAttachments', $options);
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
   * Create and send a HTTP GET request
   *
   * @param string  $uri the URI to hit
   * @param array   $options HTTP client configuration options
   *
   * @return ResponseInterface
   *
   * @throws Exception
   * @throws ClientException
   * @throws RequestException
   */
  private function get(string $uri, array $options = []) {
    $response = $this->client->get('/' . self::VERSION . '/' . $uri, $options);

    return json_decode($response->getBody()->getContents());
  }
}
