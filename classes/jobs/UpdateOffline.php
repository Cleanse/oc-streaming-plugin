<?php

namespace Cleanse\Streaming\Classes\Jobs;

use Cleanse\Streaming\Classes\Updater;

class UpdateOffline
{
    public function fire($job, $data)
    {
        $streams = new Updater();
        $streams->offlineStreamer($data['streamer']);

        $job->delete();
    }
}
