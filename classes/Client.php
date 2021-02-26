<?php

namespace Cleanse\Streaming\Classes;

use Cleanse\Streaming\Classes\Twitch\Twitch;
use Cleanse\Streaming\Classes\YouTube\YouTube;

class Client
{
    protected $platform;

    public function __construct($platform = 1)
    {
        $this->platform = $this->getPlatform($platform);
    }

    /**
     * @param int $platform
     * @return Twitch|YouTube
     */
    protected function getPlatform($platform = 1)
    {
        if ($platform === 1) {
            return new Twitch();
        } else {
            return new YouTube();
        }
    }

    /**
     * Twitch only method.
     *
     * @param $name
     * @return string
     */
    public function getStreamersByName($name)
    {
        return $this->platform->getStreamersByName($name);
    }

    public function getStreamersById($ids = [])
    {
        return $this->platform->getStreamersById($ids);
    }

    public function updateStreams($ids, $strict = false)
    {
        return $this->platform->updateStreams($ids, $strict);
    }
}
