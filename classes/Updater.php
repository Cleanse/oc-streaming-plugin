<?php

namespace Cleanse\Streaming\Classes;

use Queue;
use Cleanse\Streaming\Classes\Client;
use Cleanse\Streaming\Models\Streamer;

class Updater
{
    public function updateStreams()
    {
        $streamers = Streamer::isActive()->whereNotNull('user_id')->get();

        $twitchArray = [];
        $youtubeArray = [];
        foreach ($streamers as $streamer) {
            if ($streamer->streaming_platform == 1) {
                $twitchArray[] = $streamer->user_id;
            } else {
                $youtubeArray[] = $streamer->user_id;
            }
        }

        # Twitch
        $this->arrayBatch(array_filter($twitchArray), 99, function ($batch) {
            $this->updateStreamers($batch, 1);
        });

        # YouTube
        $this->arrayBatch(array_filter($youtubeArray), 99, function ($batch) {
            $this->updateStreamers($batch, 2);
        });
    }

    protected function updateStreamers($user_ids, $platform = 1)
    {
        $updater = new Client($platform);
        $streamerData = $updater->getStreamersById($user_ids);

        if (strlen($streamerData) > 0) {
            $updater->updateStreams($streamerData, $user_ids);
        }
    }

    public function updateStreamer($data)
    {
        if (!isset($data['user_id'])) {
            return null;
        }

        $streamer = Streamer::where('user_id', '=', $data['user_id'])->first();

        if (!$streamer) {
            return null;
        }

        foreach ($data as $key => $val) {
            $streamer->$key = $val;
        }

        $streamer->save();

        return $streamer;
    }

    // Twitch specific get streamer id by name.
    public function updateStreamerId($name)
    {
        $query = strtolower($name);
        $client = new Client(1);
        $q = $client->getStreamersByName($query);
        $streamers = json_decode($q, true);

        if (!count($streamers['data']) > 0) {
            return;
        }

        foreach ($streamers['data'] as $streamer) {
            if ($query == strtolower($streamer['display_name'])) {
                $this->updateStreamersId([
                    'user_name' => $streamer['display_name'],
                    'user_id' => $streamer['id'],
                    'live' => $streamer['is_live']
                ]);
                break;
            }
        }
    }

    public function updateStreamersId($data)
    {
        if (!isset($data['user_name'])) {
            return null;
        }

        $streamer = Streamer::where('user_name', '=', $data['user_name'])->first();

        if (!$streamer) {
            return null;
        }

        foreach ($data as $key => $val) {
            $streamer->$key = $val;
        }

        $streamer->save();

        return $streamer;
    }

    final public function offlineStreamer($streamer)
    {
        $update = Streamer::where('user_id', '=', $streamer)->first();

        if (!isset($update)) {
            return;
        }

        $update->live = false;
        $update->viewer_count = 0;
        $update->title = null;
        $update->save();
    }

    //Thanks to: https://stackoverflow.com/a/52489560/592169
    final private function arrayBatch($arr, $batchSize, $closure)
    {
        $batch = [];
        foreach ($arr as $i) {
            $batch[] = $i;

            if (count($batch) === $batchSize) {
                $closure($batch);
                $batch = [];
            }
        }

        if (count($batch)) $closure($batch);
    }
}
