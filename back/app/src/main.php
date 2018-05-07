<?php declare(strict_types=1);

namespace Acme;

use \FileSystemIterator;
use \GuzzleHttp\Client;
use Acme\Throttle;
use Acme\Logger;

class Main {

  const BATCH_SIZE = 100;

  /**
   * @var string
   */
  private $uploadDirectory;

  /**
   * @var array
   */
  private $emailIdsOnDisk;

  /**
   * @var int
   */
  private $numberOfEmailsOnInsightly;

  public function __construct(string $uploadDirectory) {
    $guzzle = new Client([
      'base_uri' => 'https://api.insight.ly',
      'timeout' => 30.0,
      'auth' => ['5dd256cf-b006-49f9-b8f2-4dbc8342c9b0', null, 'basic']
    ]);
    $throttle = new Throttle();
    $this->api = new Insightly($guzzle, $throttle);

    file_put_contents(realpath(__DIR__ . '/..') . '/log.txt', '');
    Logger::debug('[Main] Initialised. Ready to execute commands');

    $this->uploadDirectory = $uploadDirectory;

    $this->localEmailIdStore = json_decode(file_get_contents($this->uploadDirectory . '/ids.json'));
    $this->setnumberOfEmailsOnInsightly();
  }

  /**
   * getRemainingEmailIdsFromInsightly
   */
  public function getRemainingEmailIdsFromInsightly() {
    $listOfEmailIds = [];
    $filepath = $this->uploadDirectory . '/ids.json';

    if($this->isEmailIdsRemaining()) {
      $iterations = $this->getNumberOfIterations();

      for($i = 1; $i <= $iterations; ++$i) {
        $skip = count($this->localEmailIdStore) + count($listOfEmailIds);
        $emails = $this->api->getEmails($skip);
        $emailIds = $this->extractEmailIdsFromResponse($emails);
        $listOfEmailIds = array_merge($listOfEmailIds, $emailIds);
      }

      Logger::debug('[Main] wrote ' . count($listOfEmailIds) . ' emails to $listOfEmailIds. Saving to disk => ' . $filepath);

      $this->localEmailIdStore = array_merge($this->localEmailIdStore, $listOfEmailIds);
      file_put_contents($filepath, json_encode($this->localEmailIdStore));
    }
  }

  /**
   * getIndividualEmailsFromInsightly
   */
  public function getIndividualEmailsFromInsightly() {
    $filepath = $this->uploadDirectory . '/emails';
    $emailsOnFile = array_slice(scandir($filepath), 2);
    $emailsNotOnFile = array_diff($this->localEmailIdStore, $emailsOnFile);

    if( ! empty($emailsNotOnFile)) {
      Logger::debug('[Main] retrieving ' . count($emailsNotOnFile) . ' emails that are not saved to disk');

      foreach($emailsNotOnFile as $emailId) {
        $email = $this->api->getEmail($emailId);
        if(isset($email)) {
          $attachments = $this->api->getAttachments($emailId);
          $email->ATTACHMENTS = $attachments;
          $email->ATTACHMENTS_RETRIEVED = false;

          $outputFile = $filepath . '/' . $emailId . '.json';
          $this->writeToFile($outputFile, $email);
          Logger::debug('[Main] wrote complete email data object with id ' . $emailId . ' to ' . $outputFile);
        }
      }
    }
  }

  public function getAttachmentFilesFromInsightly() {
    $emailDirectory = $this->uploadDirectory . '/emails';
    $emailsOnFile = array_slice(scandir($emailDirectory), 2);
    $emailsWithAttachments = [];

    if( ! empty($emailsOnFile)) {
      foreach($emailsOnFile as $filename) {
        $email = json_decode(file_get_contents($emailDirectory . '/' . $filename));
        if($email->ATTACHMENTS_RETRIEVED == false && ! empty($email->ATTACHMENTS)) {
          $emailsWithAttachments[] = $email;
        }
      }
    }

    if( ! empty($emailsWithAttachments)) {
      $attachmentDirectory = $this->uploadDirectory . '/attachments/';

      foreach($emailsWithAttachments as $email) {
        $emailId = $email->EMAIL_ID;

        foreach($email->ATTACHMENTS as $attachment) {
          $filepath = $attachmentDirectory . $emailId . '/' . $attachment->FILE_NAME;
          $this->makeRecursiveDirectory(dirname($filepath));
          $this->api->getAttachment($attachment->FILE_ID, $filepath);
        }

        $outputFile - $emailDirectory . '/' . $emailId . '.json';
        $email->ATTACHMENTS_RETRIEVED = true;
        $this->writeToFile($outputFile, $email);
        Logger::debug('[Main] ATTACHMENTS_RETRIEVED set to True and wrote to ' . $outputFile);
      }
    }
  }

  private function setnumberOfEmailsOnInsightly() {
    if(isset($this->numberOfEmailsOnInsightly)) {
      return;
    }

    $this->numberOfEmailsOnInsightly = $this->api->getEmailsOnInsightlyCount() - count($this->localEmailIdStore);
  }

  private function isEmailIdsRemaining(): bool {
    $isEmailsRemaining = boolval($this->numberOfEmailsOnInsightly);

    if($isEmailsRemaining) {
      Logger::debug('[Main ] ' . $this->numberOfEmailsOnInsightly . ' emails left to retrieve from Insightly');
    }

    return $isEmailsRemaining;
  }

  private function getNumberOfIterations(): int {
    $numberOfBatches = $this->numberOfEmailsOnInsightly / self::BATCH_SIZE;

    if($numberOfBatches < 1) {
      $numberOfBatches = 1;
    }

    Logger::debug('[Main] retrieving ' . $this->numberOfEmailsOnInsightly . ' emails in ' . floor($numberOfBatches) . ' iterations of ' . self::BATCH_SIZE);

    return intval(ceil($numberOfBatches));
  }

  private function extractEmailIdsFromResponse(array $emails): array {
    return array_map(function($email) { return $email->EMAIL_ID; }, $emails);
  }

  private function writeToFile(string $path, $data) {
    $this->makeRecursiveDirectory(dirname($path));

    $handle = fopen($path, 'w');
    if($handle) {
      fwrite($handle, json_encode($data));
      fclose($handle);
    }
  }

  private function makeRecursiveDirectory(string $directory) {
    if(is_dir($directory)) {
      return;
    }

    mkdir($directory, 0777, true);
  }
}
