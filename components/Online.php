<?php

namespace Cleanse\Streaming\Components;

use Cms\Classes\ComponentBase;
use Cleanse\Streaming\Models\Streamer;

class Online extends ComponentBase
{
    /**
     * @var Streamer A collection of streamers to display
     */
    public $streamers;

    public function componentDetails()
    {
        return [
            'name'            => 'Online Streamers',
            'description'     => 'Grabs the online streamers in your database.'
        ];
    }

    /**
     * For now we'll interact directly, after I learn, make sure this only checks the db.
     */
    public function onRun()
    {
        $this->streamers = $this->page['streamers'] = $this->loadStreamers();

        $this->addCss('assets/css/cleanse-streamers.css');
    }

    public function loadStreamers()
    {
        return Streamer::isActive()->where('live', 1)->orderBy('user_name', 'asc')->get();
    }
}
