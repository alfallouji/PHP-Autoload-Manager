--TEST--
autoloadManager - Test case sensitivity
--FILE--
<?php
require __DIR__ . '/../autoloadManager.php';

$autoloadManager = new autoloadManager(null, autoloadManager::SCAN_ONCE);
$autoloadManager->addFolder(__DIR__ . '/src/');
$autoloadManager->register();

$a = new a;
echo 'DONE!';
?>
--EXPECTF--
DONE!
