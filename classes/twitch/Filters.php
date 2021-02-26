<?php

namespace Cleanse\Streaming\Classes\Twitch;

class Filters
{
    # todo: Redo since Twitch supports the game title now.
    const GAME_ID = '510056';

    public function filterStream($gameId)
    {
        if ($gameId !== self::GAME_ID) {
            return false;
        }

        return true;
    }
}
