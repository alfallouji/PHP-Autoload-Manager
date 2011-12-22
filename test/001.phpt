--TEST--
autoloadManager - Test no-cache SCAN_ONCE
--FILE--
<?php
require __DIR__ . '/../autoloadManager.php';

$autoloadManager = new autoloadManager(null, autoloadManager::SCAN_ONCE);
$autoloadManager->addFolder(__DIR__ . '/src/');
$autoloadManager->register();

$a = new A;
$c = new C;
?>
--EXPECTF--
Fatal error: Class 'C' not found in %s on line %d

