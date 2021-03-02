<?php

namespace Cleanse\Streaming\Classes\YouTube;

use DOMDocument;
use DOMXPath;
use Queue;
use GuzzleHttp;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;

class YouTube
{
    public const BASE_URI = 'https://www.youtube.com/channel/';
    public const DETECT = '{"text":" watching"}';

    public $json_container = 'var ytInitialData';

    public function getStreamersById($ids)
    {
        foreach ($ids as $id) {
            Queue::push('\Cleanse\Streaming\Classes\Jobs\CrawlYoutubeChannel', ['streamer' => $id]);
        }

        return '';
    }

    public function updateChannel($channelId)
    {
        $channelData = $this->guzzle($channelId);

        $data = $this->checkData($channelData, $channelId);

        if (!isset($data)) {
            return;
        }

        Queue::push('\Cleanse\Streaming\Classes\Jobs\UpdateStream', ['streamer' => $data]);
    }

    private function guzzle($channel)
    {
        try {
            $client = new GuzzleHttp\Client();
            $res = $client->request('GET', self::BASE_URI . $channel);

            $result = $res->getBody();
        } catch (GuzzleRequestException $exception) {
            if (!$response = $exception->getResponse()) {
                throw $exception;
            }

            $result = [$response, $exception];
        }

        return $result;
    }

    public function checkData($html, $channelId)
    {
        // If we have data, then create array...
        if (strpos($html, self::DETECT)) {
            return $this->grabJSON($html, $channelId);
        }

        // Else we send an empty array back to turn off stream.
        $arr = [
            'user_id'      => $channelId,
            'live'         => 0,
            'title'        => '',
            'viewer_count' => 0,
            'stream_url'   => self::BASE_URI . $channelId,
            'stream_image' => ''
        ];

        return $arr;
    }

    protected function grabJSON($html, $channelId)
    {
        # Use DOM & XPATH to search
        $dom = new DOMDocument();
        @$dom->loadHTML($html);

        $xpath = new DOMXPath($dom);
        $cells = $xpath->query('//html/body/script[contains(.,"'.$this->json_container.'")]');
        if ($cells->length > 0) {
            $cell = $cells->item(0);
            $data = $cell->nodeValue;
        } else {
            return [];
        }

        # Grab only json data
        $elementData = substr($data, 20, -1);
        $elementData = html_entity_decode($elementData);

        $json = json_decode($elementData, true);

        return $this->parseJson($json, $channelId);
    }

    protected function parseJson($json_full, $channelId)
    {
        $json = $json_full['contents']['twoColumnBrowseResultsRenderer']['tabs'][0]['tabRenderer']['content']['sectionListRenderer']['contents'][0]['itemSectionRenderer']['contents'][0]['channelFeaturedContentRenderer']['items'][0]['videoRenderer'];

        if (!isset($json)) {
            return [];
        }

        # URL
        $url = '//www.youtube.com/watch?v=';
        $videoId = $json['videoId'];

        # Image
        $thumbnails = $json['thumbnail']['thumbnails'];
        $image = '';
        foreach ($thumbnails as $thumbnail) {
            $image = $thumbnail['url'];
        }

        # Stream Title
        $title = $json['title']['runs'][0]['text'];

        # Viewer Count
        $viewerCount = (int) filter_var($json['viewCountText']['runs'][0]['text'], FILTER_SANITIZE_NUMBER_INT);

        # Channel Name
        $userName = $json['shortBylineText']['runs'][0]['text'];

        # Live or nah?
        $live = 0;
        $preLive = $json['thumbnailOverlays'][0]['thumbnailOverlayTimeStatusRenderer']['text']['runs'][0]['text'];
        if (isset($preLive) && $preLive == 'LIVE') {
            $live = 1;
        }

        # DB patch, add: url, image
        $arr = [
            'stream_url'   => $url . $videoId,
            'stream_image' => $image,
            'user_name'    => $userName,
            'user_id'      => $channelId,
            'live'         => $live,
            'title'        => $title,
            'viewer_count' => $viewerCount
        ];

        return $arr;
    }
}
