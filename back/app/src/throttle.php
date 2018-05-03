<?php declare(strict_types=1);

namespace Acme;

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

  public function __construct(int $ratePerSecond = 5, int $numberOfSeconds = 1) {
    $this->ratePerSecond = $ratePerSecond;
    $this->numberOfSeconds = $numberOfSeconds;

    $this->hitsThisCycle = 0;
    $this->currentDateString = date('Y-m-d');
    $this->storeFilepath = '/var/www/html/app/data.json';
    $this->store = json_decode(file_get_contents($this->storeFilepath));
  }

  public function attempt($closure) {
    if($this->isNewDay()) {
      $this->resetStoreData();
    }

    if($this->isDailyLimitSurpassed()) {
      throw new \Exception('[Throttle] Daily limit reached. Go back to sleep..');
    }

    if( ! isset($this->expirationTime)) {
     $this->setExpirationTime(); 
    }

    if($this->ratePerSecondExceeded()) {
      $sleepInMicroSeconds = round(($this->expirationTime - microtime(true)) * 1000000);
      Logger::debug('[Throttle] Rate limit reached. Sleeping for ' . $sleepInMicroSeconds . ' microseconds..');

      usleep(intval($sleepInMicroSeconds));
      $this->resetratePerSecond();
    }

    $this->hit();

    return $closure();
  }

  private function isNewDay(): bool {
    return $this->numberOfDaysPassed() > $this->store->date;
  }

  private function numberOfDaysPassed() {
    return (new \DateTime())->diff(new \DateTime($this->currentDateString))->format('%a');
  }

  private function resetStoreData() {
    Logger::debug('[Throttle] New day, updating $this->store->date and resetting $this->store->requests');
    $this->store->date = $this->currentDateString;
    $this->store->requests = 0;
  }

  private function isDailyLimitSurpassed(): bool {
    return $this->store->requests >= self::DAILY_LIMIT;
  }

  private function setExpirationTime() {
    $this->expirationTime = microtime(true) + $this->numberOfSeconds;
  }

  private function ratePerSecondExceeded(): bool {
    return $this->hitsThisCycle >= $this->ratePerSecond && microtime(true) < $this->expirationTime;
  }

  private function resetratePerSecond() {
    $this->hitsThisCycle = 0;
    $this->setExpirationTime();
  }

  private function hit() {
    $this->hitsThisCycle++;
    $this->store->requests++;
    file_put_contents($this->storeFilepath, json_encode($this->store));
  }
}
