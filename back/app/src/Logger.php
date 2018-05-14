<?php declare(strict_types=1);

namespace Acme;

class logger {

  /**
   * @var bool
   */
  private $enabled;

  public static function debug(string $message) {
    self::write($message);
  }

  private function write(string $message) {
    $timestamp = (new \DateTime())->format('Y-m-d H:i:s.u');
    $message = $timestamp . ' ' . $message . "\n";

    $handle = fopen(realpath(__DIR__ . '/..') . '/log.txt', 'a');
    fwrite($handle, $message);
    fclose($handle);
  }
}
