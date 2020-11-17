<?php

namespace RutubeScrapper;

class RutubeScrapper {
    protected array $endpoints;
    private \GuzzleHttp\Client $client;

    /**
     * RutubeScrapper constructor.
     */
    public function __construct()
    {
        $this->client = new \GuzzleHttp\Client(['cookies' => true]);
        $this->endpoints = [
            'login_api' => 'https://pass.rutube.ru/api/accounts/phone/login/',
            'login_social_api' => 'https://rutube.ru/social/auth/rupass/?callback_path=/social/login/rupass/',
            'video_api' => 'http://rutube.ru/api/video',
            'visitor_api' => 'https://rutube.ru/api/accounts/visitor/',
            'ad_api' => 'https://mtr.rutube.ru/api/v3/interactive',
            'hls_api' => 'https://rutube.ru/api/play/options'
        ];
    }

    /**
     * Login to rutube through social API
     *
     * @param string $phone Phone number of Rutube LiST Account
     * @param string $password Password of Rutube LiST Account
     *
     * @return object Login result
     * @throws RutubeScrapperException
     */
    public function login(string $phone, string $password)
    {
        if (trim($phone) === '' || trim($password) === '') throw new RutubeScrapperException('You must enter Rutube LiST credentials');

        $result = json_decode($this->client->request('POST', "{$this->endpoints['login_api']}", ['json' => compact('phone', 'password')])->getBody());
        if ($result->success !== true) throw new RutubeScrapperException("Invalid credentials");

        $this->client->request('GET', "{$this->endpoints['login_social_api']}"); // This API store some cookies that is necessary for the other operations

        return $result;
    }

    /**
     * Get internal video id
     *
     * @param string $videoId A videoId, either a long id like 26f9dfab263c80f22c16b0ef1c4b77ad or an short one like 6105209
     *
     * @return string Internal video id
     */
    public function video(string $videoId)
    {
        $result = json_decode($this->client->request('GET', "{$this->endpoints['video_api']}/{$videoId}")->getBody());

        return $result->id;
    }

    /**
     * Retrieve award (sort of token to get access to the video balancer without actually answering a quiz or watching an ad)
     *
     * @param string $internalVideoId Internal video id
     *
     * @return string Award token
     */
    public function award(string $internalVideoId)
    {
        // Retrieve club_params_encrypted for actual logged user
        $clubParamsEncrypted = json_decode($this->client->request('GET', "{$this->endpoints['visitor_api']}")->getBody())->club_params_encrypted;

        // Retrieve the award token, ordinarily given after watching the ad and answering the quiz
        return json_decode($this->client->request('GET', "{$this->endpoints['ad_api']}?{$clubParamsEncrypted}&video_id={$internalVideoId}")->getBody())->award;
    }

    /**
     * Retrieve streams for a video id with an award
     *
     * @param string $internalVideoId Internal video id
     * @param string $award Award token
     * @param bool $raw Return a parsed stream list or raw stream list
     *
     * @return array|string List of streams, if parsed return a multidimensional array with resolution[bandwidth] = url of stream, otherwise the raw video balancer
     * @throws RutubeScrapperException
     */
    public function streamList(string $internalVideoId, string $award, bool $raw = false)
    {
        // Retrieve the video balancer (multiple hls streams of multiple resolution and bitrate)
        $videoBalancerResult = json_decode($this->client->request('GET', "{$this->endpoints['hls_api']}/{$internalVideoId}?club_token={$award}")->getBody());
        $videoBalancerUrl = $videoBalancerResult->video_balancer->m3u8 ?? $videoBalancerResult->video_balancer->default ?? null;

        if ($videoBalancerUrl === null) throw new RutubeScrapperException("No video balancer was found for « {$this->endpoints['hls_api']}/{$internalVideoId}?club_token={$award} » !");

        // Parse video balancer link to echo a list of streams
        $videoBalancer = trim($this->client->request('GET', $videoBalancerUrl)->getBody());

        if ($raw === true) return $videoBalancer;

        $videoBalancer = explode("\n", $videoBalancer);
        unset($videoBalancer[0]); // remove #EXTM3U
        $videoBalancer = array_map('trim', $videoBalancer);
        $streamListTmp = array_chunk($videoBalancer, 2);
        $streamList = [];

        foreach ($streamListTmp as $stream) {
            preg_match_all("/([^,= ]+)=([^,= ]+)/", $stream[0], $r);
            $params = array_combine($r[1], $r[2]);
            $url = $stream[1];

            $streamList[$params['RESOLUTION']][$params['BANDWIDTH']] = $url;
        }

        return $streamList;
    }
}