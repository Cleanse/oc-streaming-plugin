<?php

namespace Cleanse\Streaming\Classes\Jobs;

use Cleanse\Streaming\Classes\Updater;

class UpdateStream
{
    public function fire($job, $data)
    {
        $streams = new Updater();
        $streams->updateStreamer($data['streamer']);

        $job->delete();
    }
}
