<?php

require_once 'vendor/autoload.php';
require_once 'config.php';

function api($videoId) {
	global $rutubeEndpoints, $credentials;

	$client = new GuzzleHttp\Client(['cookies' => true]);

	// Login to Rutube
	$loginApi = $client->request('POST', "{$rutubeEndpoints['login_api']}", ['json' => $credentials]);
	if (json_decode($loginApi->getBody())->success !== true) throw new Exception("Invalid credentials");

	$loginSocialApi = $client->request('GET', "{$rutubeEndpoints['login_social_api']}"); // si on ajoute pas cette étape quand on login, les cookies sont pas tous reçus


	// Get the internal id of the video
	$videoApi = $client->request('GET', "{$rutubeEndpoints['video_api']}/{$videoId}");

	$videoInternalId = json_decode($videoApi->getBody())->id;


	// Retrieve club_params_encrypted for actual logged user
	// This is used to bypass the weird ad system
	$visitorApi = $client->request('GET', "{$rutubeEndpoints['visitor_api']}");

	$clubParamsEncrypted = json_decode($visitorApi->getBody())->club_params_encrypted;


	// Retrieve the award token, ordinarily given after watching the ad and answering the quiz
	$adApi = $client->request('GET', "{$rutubeEndpoints['ad_api']}?{$clubParamsEncrypted}&video_id={$videoInternalId}");

	$clubToken = json_decode($adApi->getBody())->award;


	// Retrieve the video balancer (multiple hls streams of multiple resolution and bitrate)
	$hlsApi = $client->request('GET', "{$rutubeEndpoints['hls_api']}/{$videoId}?club_token={$clubToken}");

	$hlsApiJson = json_decode($hlsApi->getBody());

	$videoBalancerUrl = $hlsApiJson->video_balancer->m3u8 ?? $hlsApiJson->video_balancer->default ?? null;


	// Parse video balancer link to echo a list of streams
	$videoBalancerApi = $client->request('GET', $videoBalancerUrl);

	$listOfStreams = trim($videoBalancerApi->getBody());

	$listOfStreams = explode("\n", $listOfStreams);
	unset($listOfStreams[0]); // remove #EXTM3U
	$listOfStreams = array_map('trim', $listOfStreams);
	$listOfStreams = array_chunk($listOfStreams, 2);

	foreach ($listOfStreams as $stream) {
		preg_match_all("/([^,= ]+)=([^,= ]+)/", $stream[0], $r);
		$params = array_combine($r[1], $r[2]);

		$url = $stream[1];

		echo "HLS Stream Found ! ({$params['RESOLUTION']}, {$params['BANDWIDTH']} kb/s) : {$url}\n";
	}
}