<?php

namespace Cleanse\Streaming\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class AddStreamingTable extends Migration
{
    public function up()
    {
        Schema::create('cleanse_streaming_streamers', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->smallInteger('streaming_platform')->default(1);
            $table->integer('user_id')->unsigned()->nullable()->index();
            $table->string('user_name');
            $table->text('title')->nullable();
            $table->integer('viewer_count')->default(0);
            $table->smallInteger('live')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->string('webhook_id')->nullable();
            $table->boolean('activated')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cleanse_streaming_streamers');
    }
}
