<?php

namespace Cleanse\Streaming\Classes\Jobs;

use Cleanse\Streaming\Classes\Updater;

class UpdateStreamId
{
    public function fire($job, $data)
    {
        $streams = new Updater();
        $streams->updateStreamerId($data['streamer']);

        $job->delete();
    }
}
