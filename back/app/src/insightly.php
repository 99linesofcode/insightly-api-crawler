<?php declare(strict_types=1);

namespace Acme;

require_once '/var/www/html/app/vendor/autoload.php';

use \GuzzleHttp\Client;
use Acme\Throttle;
use \Psr\Http\Message\ResponseInterface;

final class insightly {

  const VERSION_PREFIX = '/v2.2/';

  /**
   * @var \GuzzleHttp\Client
   */
  private $httpClient;

  /**
   * @var Acme\Throttle
   */
  private $throttle;

  public function __construct(\GuzzleHttp\Client $httpClient, Throttle $throttle) {
    $this->httpClient = $httpClient;
    $this->throttle = $throttle;
  }

  public function getEmailsOnInsightlyCount() {
    return $this->throttle->attempt(function() {
      $response = $this->httpClient->get(self::VERSION_PREFIX . 'Emails?count_total=true&brief=true&top=1');
      return intval($response->getHeaderLine('x-total-count'));
    });
  }

  public function getEmails(int $skip) {
    return $this->throttle->attempt(function() use ($skip) {
      $response = $this->httpClient->get(self::VERSION_PREFIX . 'Emails?skip=' . $skip);
      return json_decode($response->getBody()->getContents());
    });
  }

  public function getEmail(int $emailId) {
    return $this->throttle->attempt(function() use ($emailId) {
      $response = $this->httpClient->get(self::VERSION_PREFIX . 'Emails/' . $emailId);
      return json_decode($response->getBody()->getContents());
    });
  }

  public function getAttachments(int $emailId) {
    return $this->throttle->attempt(function() use ($emailId) {
      $response = $this->httpClient->get(self::VERSION_PREFIX . 'Emails/' . $emailId . '/FileAttachments');
      return json_decode($response->getBody()->getContents());
    });
  }
  
  public function getAttachment(int $fileId) {
    return $this->throttle->attempt(function() use ($fileId) {
      $response = $this->httpClient->get(self::VERSION_PREFIX . 'FileAttachments/' . $fileId);
      return json_decode($response->getBody()->getContents());
    });
  }
}
