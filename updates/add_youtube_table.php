<?php

namespace Cleanse\Streaming\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class AddYoutubeTable extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('cleanse_streaming_streamers', 'stream_url')) {
            return;
        }

        Schema::table('cleanse_streaming_streamers', function($table)
        {
            # Dropping Webhook support for now.
            $table->dropColumn(['expires_at', 'webhook_id']);

            # Update user_id to support strings
            $table->string('user_id')->nullable()->change();

            # Add necessary YouTube Live columns.
            $table->string('stream_url')->nullable();
            $table->string('stream_image')->nullable();
        });
    }

        public function down()
    {
    }
}
