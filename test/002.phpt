--TEST--
autoloadManager - Test cache REGEN_NEVER
--FILE--
<?php
require __DIR__ . '/../autoloadManager.php';

$autoloadManager = new autoloadManager(__DIR__ . '/testcache.php', autoloadManager::REGEN_NEVER);
$autoloadManager->register();

$a = new A;
$b = new B;
?>
--EXPECTF--
Fatal error: Class 'B' not found in %s on line %d

