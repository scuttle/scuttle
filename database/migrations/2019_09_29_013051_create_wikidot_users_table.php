<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWikidotUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wikidot_users', function (Blueprint $table) {
            $table->unsignedBigInteger('wd_user_id')->primary();
            $table->string('username');
            $table->string('avatar_path')->nullable(); // S3 path. We get the avatar from a 2stacks job.
            $table->timestamp('wd_user_since')->nullable(); // We get this from a 2stacks job later.
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
        Schema::dropIfExists('wikidot_users');
    }
}
