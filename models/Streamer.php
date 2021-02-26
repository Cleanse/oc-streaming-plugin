<?php

namespace Cleanse\Streaming\Models;

use Event;
use Model;

/**
 *  Class Streamer
 * This is the model class for table "cleanse_streaming_streamers"
 *
 * @property integer $id
 * @property integer $streaming_platform
 * @property integer $user_id
 * @property string  $user_name
 * @property string  $title
 * @property integer $viewer_count
 * @property integer $live
 * @property string  $stream_url
 * @property string  $stream_image
 * @property boolean $activated
 */
class Streamer extends Model
{
    use \October\Rain\Database\Traits\Validation;

    public $table = 'cleanse_streaming_streamers';

    /*
     * Validation
     */
    public $rules = [
        'user_name' => ['required', 'unique:cleanse_streaming_streamers']
    ];

    public function beforeCreate()
    {
        $this->user_name = strtolower($this->user_name);
    }

    public function afterCreate()
    {
        if ($this->user_id == '') {
            Event::fire('cleanse.streaming.streamer_id', [$this->user_name]);
        }
    }

    //
    // Scope(s)
    //
    public function scopeIsActive($query)
    {
        return $query
            ->whereNotNull('activated')
            ->where('activated', true);
    }
}
