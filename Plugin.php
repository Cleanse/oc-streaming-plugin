<?php

namespace Cleanse\Streaming;

use App;
use Backend;
use Config;
use Controller;
use Event;
use Queue;
use System\Classes\PluginBase;
use Cleanse\Streaming\Models\Streamer;

/**
 * Streaming Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about the Streaming Plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name' => 'Streaming',
            'description' => 'Adds streamer online status data to your site.',
            'author' => 'Paul E Lovato',
            'icon' => 'icon-video-camera'
        ];
    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return [
            'Cleanse\Streaming\Components\Streamers' => 'cleanseStreamingStreamers',
            'Cleanse\Streaming\Components\Online'    => 'cleanseStreamingOnline'
        ];
    }

    public function registerPermissions()
    {
        return [
            'cleanse.streaming.access_streamers' => [
                'tab' => 'Streaming',
                'label' => 'Manage Streamers'
            ]
        ];
    }

    public function registerNavigation()
    {
        return [
            'streaming' => [
                'label' => 'Streaming',
                'url' => Backend::url('cleanse/streaming/streamers'),
                'icon' => 'icon-video-camera',
                'permissions' => ['cleanse.streaming.*'],
                'order' => 26,

                'sideMenu' => [
                    'new_streamer' => [
                        'label' => 'New Streamer',
                        'icon' => 'icon-plus',
                        'url' => Backend::url('cleanse/streaming/streamers/create'),
                        'permissions' => ['cleanse.streaming.access_streamers']
                    ],
                    'streamersmini' => [
                        'label' => 'Streamers',
                        'icon' => 'icon-copy',
                        'url' => Backend::url('cleanse/streaming/streamers'),
                        'permissions' => ['cleanse.streaming.access_streamers']
                    ]
                ]
            ]
        ];
    }

    public function boot()
    {
        /**
         * Detects a new Twitch addition and grabs their platform 'user_id'
         */
        Event::listen('cleanse.streaming.streamer_id', function ($streamer) {
            Queue::push('\Cleanse\Streaming\Classes\Jobs\UpdateStreamId', ['streamer' => $streamer]);
        });

        /**
         * Lists the streamer in the search results.
         */
        Event::listen('offline.sitesearch.query', function ($query) {

            $items = Streamer::isActive()->where('user_name', 'like', "%${query}%")
                ->get();

            $results = $items->map(function ($item) use ($query) {

                $relevance = mb_stripos($item->user_name, $query) !== false ? 2 : 1;

                return [
                    'title' => $item->user_name,
                    'text' => $item->title,
                    'url' => $item->stream_url,
                    'relevance' => $relevance,
                ];
            });

            return [
                'provider' => 'Streaming',
                'results' => $results,
            ];
        });
    }

    /**
     * @param string $schedule
     * Used to update streams polled.
     */
    public function registerSchedule($schedule)
    {
        $schedule->call(function () {
            Queue::push('\Cleanse\Streaming\Classes\Jobs\QueueStreamers');
        })->everyFiveMinutes();
    }
}
