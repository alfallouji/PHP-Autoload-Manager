--TEST--
autoloadManager - Test cache SCAN_ONCE
--FILE--
<?php
require __DIR__ . '/../autoloadManager.php';

$autoloadManager = new autoloadManager(__DIR__ . '/cache.php', autoloadManager::SCAN_ONCE);
$autoloadManager->addFolder(__DIR__ . '/src');
$autoloadManager->register();

$a = new A;
$b = new B;

var_dump(include(__DIR__ . '/cache.php'));
unlink(__DIR__ . '/cache.php');

?>
--EXPECTF--
array(2) {
  ["B"]=>
  string(%d) "%s/src/b.php"
  ["A"]=>
  string(%d) "%s/src/a.php"
}

