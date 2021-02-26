<?php

namespace Cleanse\Streaming\Classes\Jobs;

use Cleanse\Streaming\Classes\YouTube\YouTube;

class CrawlYoutubeChannel
{
    public function fire($job, $data)
    {
        $streams = new YouTube();
        $streams->updateChannel($data['streamer']);

        $job->delete();
    }
}
