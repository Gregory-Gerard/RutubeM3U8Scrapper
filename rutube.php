<?php

require_once 'api.php';

if (isset($argv[1]) === false) die("[ERROR] You need to pass a video id\n".PHP_EOL);

try {
	api($argv[1]);
} catch (Exception $e) {
	die("[ERROR] {$e->getMessage()}".PHP_EOL);
}