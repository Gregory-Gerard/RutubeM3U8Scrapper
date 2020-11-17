<?php

require_once 'vendor/autoload.php';
require_once 'src/RutubeScrapper.php';
require_once 'config.php';

use RutubeScrapper\RutubeScrapper;

global $credentials;

if (isset($argv[1]) === false) die("[ERROR] You need to pass a video id".PHP_EOL);

try {
	$scrapper = new RutubeScrapper();
	$scrapper->login($credentials['phone'], $credentials['password']);
	$internalVideoId = $scrapper->video($argv[1]);
	$award = $scrapper->award($internalVideoId);

	foreach($scrapper->streamList($internalVideoId, $award) as $resolution => $bandwidthList) {
	    echo PHP_EOL."---------- {$resolution} ----------".PHP_EOL;

	    foreach ($bandwidthList as $bandwidth => $url) {
            echo "{$bandwidth} Kbps : {$url}".PHP_EOL;
        }
    }
} catch (\Exception $e) {
	die("[ERROR] {$e->getMessage()}".PHP_EOL);
}