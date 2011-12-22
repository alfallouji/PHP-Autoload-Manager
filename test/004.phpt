--TEST--
autoloadManager - Test cache SCAN_ONCE
--FILE--
<?php
require __DIR__ . '/../autoloadManager.php';

$autoloadManager = new autoloadManager(__DIR__ . '/cache.php', autoloadManager::SCAN_ONCE);
$autoloadManager->addFolder(__DIR__ . '/src');
$autoloadManager->register();

class_exists('A');

$time1 = filemtime(__DIR__ . '/cache.php');

sleep(2);
class_exists('C');

$time2 = filemtime(__DIR__ . '/cache.php');

var_dump($time1 == $time2);
unlink(__DIR__ . '/cache.php');

?>
--EXPECTF--
bool(true)
