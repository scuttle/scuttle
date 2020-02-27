<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMilestonesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('milestones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('page_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('wd_user_id');
            $table->unsignedInteger('wiki_id');
            $table->string('slug');
            $table->unsignedInteger('milestone');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('slug');
            $table->index('wd_user_id');

            $table->foreign('wiki_id')->references('id')->on('wikis');
            $table->foreign('page_id')->references('id')->on('pages');
            $table->foreign('wd_user_id')->references('wd_user_id')->on('wikidot_users');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('milestones');
    }
}
