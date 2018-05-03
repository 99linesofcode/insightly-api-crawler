<?php declare(strict_types=1);

namespace Acme;

final class Throttle {

  const DAILY_LIMIT = 20000;

  /**
   * @var int
   */
  private $rateLimit;

/**
 * @var int
 */
  private $cycleDurationInSeconds;

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

  public function __construct(int $rateLimit = 5) {
    $this->rateLimit = $rateLimit;
    $this->cycleDurationInSeconds = 3;
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
      throw new \Exception('Daily limit reached. Go back to sleep..');
    }

    if( ! isset($this->expirationTime)) {
     $this->setExpirationTime(); 
    }

    if($this->rateLimitExceeded()) {
      usleep($this->expirationTime - microtime(true));
      $this->resetRateLimit();
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
    $this->store->date = $this->currentDateString;
    $this->store->requests = 0;
  }

  private function isDailyLimitSurpassed(): bool {
    return $this->store->requests >= self::DAILY_LIMIT;
  }

  private function setExpirationTime() {
    return $this->expirationTime = microtime(true) + $this->cycleDurationInSeconds;
  }

  private function rateLimitExceeded(): bool {
    return $this->hitsThisCycle == $this->rateLimit && microtime(true) < $this->expirationTime;
  }

  private function resetRateLimit() {
    $this->hitsThisCycle = 0;
    $this->setExpirationTime();
  }

  private function hit() {
    $this->hitsThisCycle++;
    $this->store->requests++;
    file_put_contents($this->storeFilepath, json_encode($this->store));
  }
}
