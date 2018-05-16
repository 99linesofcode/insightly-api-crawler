<?php declare(strict_types=1);

namespace Acme;

use Acme\Logger;
use \GuzzleHttp\Exception\TransferException;

final class Throttle {

  const DAILY_LIMIT = 20000;

  /**
   * @var int
   */
  private $ratePerSecond;

/**
 * @var int
 */
  private $numberOfSeconds;

  /**
   * @var int
   */
  private $hitsThisCycle;

  /**
   * @var string
   */
  private $currentDateString;

  /**
   * @var string
   */
  private $storeFilepath;

  /**
   * @var JSON object
   */
  private $store;

  /**
   * @var int
   */
  private $startTime;

  /**
   * @var int
   */
  private $expirationTime;

  public function __construct(int $ratePerSecond = 5, int $numberOfSeconds = 1) {
    $this->ratePerSecond = $ratePerSecond;
    $this->numberOfSeconds = $numberOfSeconds;

    $this->hitsThisCycle = 0;
    $this->startDateString = '2018-04-20';
    $this->currentDateString = date('Y-m-d');
    $this->storeFilepath = realpath(__DIR__ . '/..') . '/data.json';
    $this->store = json_decode(file_get_contents($this->storeFilepath));
  }

  public function attempt($closure) {
    if($this->isNewDay()) {
      $this->resetStoreData();
    }

    if($this->isDailyLimitSurpassed()) {
      throw new \Exception('[Throttle] Daily limit reached. Go back to sleep..');
    }

    $this->setTimers();

    if($this->isRatePerSecondExceeded()) {
      $this->hitsThisCycle = 0;

      $secondsPassed = microtime(true) - $this->startTime;
      $sleepInSeconds = $this->expirationTime - microtime(true);
      Logger::debug('[Throttle] Rate limit reached. ' . $secondsPassed. ' seconds passed. Sleeping for ' . $sleepInSeconds . ' seconds, so as to wait until ' . ($secondsPassed + $sleepInSeconds) . ' second passed');

      usleep(intval(round($sleepInSeconds * 1000000)));
    }

    $this->hit();

    try {
      return $closure();
    }
    catch (TransferException $e) {
      Logger::debug('[Insightly] Warning, A Guzzle TransferException occurred. Rethrown to be handled by Insighty.php');
      throw $e;
    }
  }

  private function isNewDay(): bool {
    return $this->numberOfDaysPassed($this->currentDateString) > $this->numberOfDaysPAssed($this->store->date);
  }

  private function numberOfDaysPassed(string $date) {
    return (new \DateTime($date))->diff(new \DateTime($this->startDateString))->format('%a');
  }

  private function resetStoreData() {
    Logger::debug('[Throttle] New day, updating $this->store->date to ' . $this->currentDateString . ' and resetting $this->store->requests to 0');
    $this->store->date = $this->currentDateString;
    $this->store->requests = 0;
  }

  private function isDailyLimitSurpassed(): bool {
    return $this->store->requests >= self::DAILY_LIMIT;
  }

  private function setTimers() {
    if($this->expirationTime == null || microtime(true) >= $this->expirationTime) {
      $this->startTime = microtime(true);
      $this->expirationTime = $this->startTime + $this->numberOfSeconds;

      Logger::debug('[Throttle] startTime set to ' . $this->startTime . ' and expirationTime set to ' . $this->expirationTime);
    }
  }

  private function isRatePerSecondExceeded(): bool {
    return $this->hitsThisCycle == $this->ratePerSecond && microtime(true) < $this->expirationTime;
  }

  private function hit() {
    $this->hitsThisCycle++;
    $this->store->requests++;
    file_put_contents($this->storeFilepath, json_encode($this->store));
  }
}
