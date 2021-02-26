<?php

namespace Cleanse\Streaming\Classes\Jobs;

use Cleanse\Streaming\Classes\Updater;

class QueueStreamers
{
    public function fire($job)
    {
        $streams = new Updater();
        $streams->updateStreams();

        $job->delete();
    }
}
