<?php

$autoloadPath = '../app/vendor/autoload.php';
$uploadDirectory = realpath('/var/www/html/app/uploads');

use Acme\Main;

require_once $autoloadPath;
$main = new Main($uploadDirectory);

try {
  $main->getRemainingEmailIdsFromInsightly();
  $main->getIndividualEmailsFromInsightly();
  $main->getAttachmentFilesFromInsightly();
}
catch (\Exception $e) {
  echo $e->getMessage();
}

function dump($mixed) {
  echo '<pre>';
  var_dump($mixed);
  echo '</pre>';
}

