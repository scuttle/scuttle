<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('wd_page_id')->nullable();
            $table->unsignedBigInteger('wd_user_id')->nullable();
            $table->unsignedInteger('milestone');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('wiki_id');
            $table->string('slug', 120); // Twice as long as Wikidot, just because.
            $table->mediumText('latest_revision')->nullable(); // Only nullable for 2stacks migration jobs.
            $table->json('metadata');
            $table->timestamp('jsontimestamp'); // We'll cache all the page data and touch this on update.
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
        Schema::dropIfExists('pages');
    }
}
