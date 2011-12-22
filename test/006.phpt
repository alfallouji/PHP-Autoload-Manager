--TEST--
autoloadManager - Test cache REGEN_CACHE_NOT_FOUND
--FILE--
<?php
require __DIR__ . '/../autoloadManager.php';

$autoloadManager = new autoloadManager(__DIR__ . '/cache.php', autoloadManager::REGEN_ALWAYS | autoloadManager::REGEN_CACHE_NOT_FOUND);
$autoloadManager->addFolder(__DIR__ . '/src');
$autoloadManager->register();

class_exists('C');

$time1 = filemtime(__DIR__ . '/cache.php');

sleep(2);
class_exists('C');

$time2 = filemtime(__DIR__ . '/cache.php');

var_dump($time1 == $time2);
unlink(__DIR__ . '/cache.php');

?>
--EXPECTF--
bool(true)
