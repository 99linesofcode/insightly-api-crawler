<?php

require_once '/var/www/html/app/vendor/autoload.php';

use Acme\Main;

$main = new Main();
#$main->getRemainingEmailIdsFromInsightly();
#$main->getIndividualEmailsFromInsightly();
$main->getAttachmentFilesFromInsightly();
