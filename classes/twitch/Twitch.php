<?php

namespace Cleanse\Streaming\Classes\Twitch;

use Queue;
use GuzzleHttp;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use Cleanse\Streaming\Classes\Twitch\Filters;

class Twitch
{
    public const BASE_URI = 'https://api.twitch.tv/helix/';
    public const OAUTH_BASE_URI = 'https://id.twitch.tv/oauth2/';
    public const TWITCH_URL = '//www.twitch.tv/';

    protected $clientId;
    protected $clientSecret;
    protected $OAuth;

    public function __construct()
    {
        $this->clientId = env('TWITCH_HELIX_KEY');
        $this->clientSecret = env('TWITCH_HELIX_SECRET');
        $this->OAuth = $this->getOAuth();
    }

    protected function getOAuth()
    {
        try {
            $client = new GuzzleHttp\Client();

            $res = $client->post(self::OAUTH_BASE_URI . 'token?client_id=' . $this->clientId . '&client_secret=' . $this->clientSecret . '&grant_type=client_credentials',
                ['headers' => ['Client-ID' => $this->clientId]]
            );

            $result = json_decode($res->getBody());
        } catch (GuzzleRequestException $exception) {

            if (!$response = $exception->getResponse()) {
                throw $exception;
            }

            $result = [$response, $exception];
        }

        return $result;
    }

    protected function guzzle($method = 'GET', $path = '', $parameters = [])
    {
        try {
            $client = new GuzzleHttp\Client();

            $res = $client->request($method, self::BASE_URI . $path,
                [
                    'headers' => [
                        'Client-ID' => $this->clientId,
                        'Authorization' => sprintf('Bearer %s', $this->OAuth->access_token)],
                    'query' => $parameters
                ]
            );

            $result = json_decode($res->getBody(), true);
        } catch (GuzzleRequestException $exception) {

            if (!$response = $exception->getResponse()) {
                throw $exception;
            }

            $result = [$response, $exception];
        }

        return json_encode($result);
    }

    public function getStreamersByName($name)
    {
        return $this->guzzle('GET', 'search/channels', ['query' => $name]);
    }

    public function getStreamersById($ids)
    {
        return $this->guzzle('GET', 'streams', ['user_id' => $ids]);
    }

    public function updateStreams($streamers, $ids, $strict = false)
    {
        $streamers = json_decode($streamers, true);

        if (!isset($streamers['data'])) {
            return;
        }

        # If there's no data at all, set all streams to 'offline or (0)'.
        if (!count($streamers['data']) > 0) {
            $offline = $ids;

            foreach ($offline as $o) {
                Queue::push('\Cleanse\Streaming\Classes\Jobs\UpdateOffline', ['streamer' => $o]);
            }

            return;
        }

        # Pull out 'online' ids from $offline, which contains the full batch of streamer ids.
        $offline = $ids;
        foreach ($streamers['data'] as $streamer) {
            $offline = array_diff(
                $offline,
                [$streamer['user_id']]
            );
        }

        # Create array with the format needed to update their online status.
        foreach ($streamers['data'] as $streamer) {
            $live = $streamer['type'] == 'live' ? true : false;

            $data = [
                'user_name'    => $streamer['user_name'],
                'user_id'      => $streamer['user_id'],
                'live'         => $live,
                'title'        => $streamer['title'],
                'viewer_count' => $streamer['viewer_count'],
                'stream_url'   => self::TWITCH_URL . $streamer['user_name'],
                'stream_image' => '//static-cdn.jtvnw.net/previews-ttv/live_user_'.strtolower($streamer['user_name']).'-113x64.jpg'
            ];

            if ($strict) {
                if (!(new Filters())->filterStream($streamer['game_id'])) {
                    $offline[] = $streamer['user_id'];
                    continue;
                }
            }

            Queue::push('\Cleanse\Streaming\Classes\Jobs\UpdateStream', ['streamer' => $data]);
        }

        # If remaining $offline has ids, set those streamers to 'offline'
        foreach ($offline as $o) {
            Queue::push('\Cleanse\Streaming\Classes\Jobs\UpdateOffline', ['streamer' => $o]);
        }
    }
}
