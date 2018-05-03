<?php declare(strict_types=1);

namespace Acme;

use \FileSystemIterator;
use \GuzzleHttp\Client;
use Acme\Throttle;

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

  public function __construct() {
    $guzzle = new Client([
      'base_uri' => 'https://api.insight.ly',
      'timeout' => 2.0,
      'auth' => ['5dd256cf-b006-49f9-b8f2-4dbc8342c9b0', null, 'basic']
    ]);
    $throttle = new Throttle();

    $this->api = new Insightly($guzzle, $throttle);
    $this->uploadDirectory = realpath('/var/www/html/app/uploads');

    $this->setLocalEmailIdStore();
    $this->setnumberOfEmailsOnInsightly();
  }

  /**
   * getRemainingEmailIdsFromInsightly
   */
  public function getRemainingEmailIdsFromInsightly() {
    $listOfEmailIds = [];

    if($this->isEmailIdsRemaining()) {
      for($i = 0; $i < $this->getNumberOfIterations(); ++$i) {
        $skip = count($this->localEmailIdStore) + count($listOfEmailIds);
        $emails = $this->api->getEmails($skip);
        $emailIds = $this->extractEmailIdsFromResponse($emails);
        $listOfEmailIds = array_merge($listOfEmailIds, $emailIds);
      }

      $this->localEmailIdStore = array_merge($this->localEmailIdStore, $listOfEmailIds);
      file_put_contents($this->uploadDirectory . '/' . 'ids.json', json_encode($this->localEmailIdStore));
    }
  }
  
  /**
   * getIndividualEmailsFromInsightly
   */
  public function getIndividualEmailsFromInsightly() {
    $emailsNotOnFile = array_diff($this->localEmailIdStore, []);

    if( ! empty($emailsNotOnFile)) {
      foreach($emailsNotOnFile as $emailId) {
        $email = $this->api->getEmail($emailId);
        $attachments = $this->api->getAttachments($emailId);
        $email->ATTACHMENTS = $attachments;

        $this->writeToJsonFile('/emails.json', $email);
      }
    }
  }

  private function setLocalEmailIdStore() {
    $this->localEmailIdStore = json_decode(
      file_get_contents($this->uploadDirectory . '/ids.json')
    );
  }

  private function setnumberOfEmailsOnInsightly() {
    if(isset($this->numberOfEmailsOnInsightly)) {
      return;
    }

    $this->numberOfEmailsOnInsightly = $this->api->getEmailsOnInsightlyCount() - count($this->localEmailIdStore);
  } 

  private function isEmailIdsRemaining(): bool {
    return boolval($this->numberOfEmailsOnInsightly);
  }

  private function getNumberOfIterations(): int {
    $numberOfBatches = $this->numberOfEmailsOnInsightly / self::BATCH_SIZE;

    if($numberOfBatches < 1) {
      $numberOfBatches = 1;
    }

    return intval(ceil($numberOfBatches));
  }

  private function extractEmailIdsFromResponse(array $emails): array {
    return array_map(function($email) { return $email->EMAIL_ID; }, $emails);
  }

  private function getAttachmentsOnDisk(): array {
    // slice off dot directories
    $directories = array_slice(scandir($this->uploadDirectory . '/' . 'attachments'), 2);

    return array_map('intval', $directories);
  }

  private function dd($mixed) {
    return die(var_dump($mixed));
  }

  private function writeToJsonFile(string $path, $data) {
    $pathinfo = pathinfo($path);
    $directory = $this->uploadDirectory . '/' . $pathinfo['dirname'];
    $filepath = $directory . '/' . $pathinfo['filename'] . '.' . $pathinfo['extension'];

    $this->makeRecursiveDirectory($directory);

    $handle = fopen($filepath, 'r+');

    if( ! isset($handle)) {
      $handle = fopen($filepath, 'w+');
    }

    if($handle) {
      fseek($handle, 0, SEEK_END);

      if(ftell($handle) > 0) {
        fseek($handle, -1, SEEK_END);
        fwrite($handle, ',', 1);
        fwrite($handle, json_encode($data) . ']');
      }
      else {
        fwrite($handle, json_encode([$data]));
      }

      fclose($handle);
    }
  }

  private function makeRecursiveDirectory(string $directory) {
    if(is_dir($directory)) {
      return;
    }

    mkdir($directory, 0777, true);
  }
>>>>>>> 39051b3... fixup! Get individual emails
}
