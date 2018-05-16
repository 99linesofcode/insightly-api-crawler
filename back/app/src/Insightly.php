<?php declare(strict_types=1);

namespace Acme;

use \GuzzleHttp\Client;
use \GuzzleHttp\Exception\TransferException;
use Acme\Throttle;
use Acme\Logger;
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
      $totalCount = intval($response->getHeaderLine('x-total-count'));
      Logger::debug('[Insightly] There are ' . $totalCount . ' emails stored on this account on Insightly');
      return $totalCount;
    });
  }

  public function getEmails(int $skip) {
    Logger::debug('[Insightly] Hit https://api.insight.ly/Emails?skip=' . $skip);

    return $this->throttle->attempt(function() use ($skip) {
      $response = $this->httpClient->get(self::VERSION_PREFIX . 'Emails?skip=' . $skip);
      return json_decode($response->getBody()->getContents());
    });
  }

  public function getEmail(int $emailId) {
    Logger::debug('[Insightly] Hit https://api.insight.ly/Emails/' . $emailId);

    try {
      return $this->throttle->attempt(function() use ($emailId) {
        $response = $this->httpClient->get(self::VERSION_PREFIX . 'Emails/' . $emailId);

        return json_decode($response->getBody()->getContents());
      });
    }
    catch (TransferException $e) {
      if($e->getCode() == 500) {
        Logger::debug('[Insightly] Warning, HTTP status 500 returned. Gracefully handled it by creating an empty email object for id ' . $emailId);
        $email = new \stdClass();
        $email->EMAIL_ID = $emailId;

        return $email;
      }

      throw $e;
    }
  }

  public function getAttachments(int $emailId) {
    Logger::debug('[Insightly] Hit https://api.insight.ly/Emails/' . $emailId . '/FileAttachments');

    return $this->throttle->attempt(function() use ($emailId) {
      $response = $this->httpClient->get(self::VERSION_PREFIX . 'Emails/' . $emailId . '/FileAttachments');
      return json_decode($response->getBody()->getContents());
    });
  }

  public function getAttachment(int $fileId, string $filepath) {
    Logger::debug('[Insightly] Hit https://api.insight.ly/FileAttachments/' . $fileId);

    return $this->throttle->attempt(function() use ($fileId, $filepath) {
      $response = $this->httpClient->get(self::VERSION_PREFIX . 'FileAttachments/' . $fileId, ['sink' => $filepath]);
      Logger::debug('[Insightly] attachment sinked to ' . $filepath);
    });
  }
}
