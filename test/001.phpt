--TEST--
autoloadManager - Test no-cache REGEN_ONCE
--FILE--
<?php
require __DIR__ . '/../autoloadManager.php';

$autoloadManager = new autoloadManager(null, autoloadManager::REGEN_ONCE);
$autoloadManager->addFolder(__DIR__ . '/src/');
$autoloadManager->register();

$a = new A;
$c = new C;
?>
--EXPECTF--
Fatal error: Class 'C' not found in %s on line %d

