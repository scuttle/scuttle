<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('thread_id');
            $table->unsignedInteger('user_id');
            $table->unsignedBigInteger('wd_user_id')->nullable();
            $table->unsignedBigInteger('parent_id');
            $table->string('subject');
            $table->mediumText('text');
            $table->json('metadata');
            $table->timestamp('JsonTimestamp'); // We'll cache all the page data and touch this on update.
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('posts');
    }
}
