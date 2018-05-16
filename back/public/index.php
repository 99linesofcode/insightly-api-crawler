<?php

$autoloadPath = '/var/www/html/app/vendor/autoload.php';
$uploadDirectory = '/var/www/html/app/uploads';

use Acme\Main;

require_once $autoloadPath;
$main = new Main($uploadDirectory);

try {
  $main->getRemainingEmailIdsFromInsightly();
  $main->getIndividualEmailsFromInsightly();
  $main->getAttachmentFilesFromInsightly();
  $main->getIncompleteEmails();
}
catch (\Exception $e) {
  echo $e->getMessage();
}

function dump($mixed) {
  echo '<pre>';
  var_dump($mixed);
  echo '</pre>';
}

