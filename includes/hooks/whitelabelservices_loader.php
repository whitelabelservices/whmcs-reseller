<?php
 











if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}
$hooksFile = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'servers' . DIRECTORY_SEPARATOR . 'WhiteLabelServices' . DIRECTORY_SEPARATOR . 'hooks.php';
if (file_exists($hooksFile)) {
    require_once $hooksFile;
}
