<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeThreadsTableMoreNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('threads', function (Blueprint $table) {
            $table->unsignedBigInteger('wd_thread_id')->nullable(); // Ha. We missed this entirely. Awesome. :[
            $table->unsignedBigInteger('wd_page_id')->nullable(); // Associate threads to pages where applicable.

            // We're going to make a couple more things nullable as we're only going to have the wd_thread_id to get started with in many instances.
            $table->unsignedBigInteger('forum_id')->nullable()->change();
            $table->string('title')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('threads', function (Blueprint $table) {
            $table->dropColumn(['wd_thread_id', 'wd_page_id']);
            $table->unsignedBigInteger('forum_id')->change();
            $table->string('title')->change();
        });
    }
}
