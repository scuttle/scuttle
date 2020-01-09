<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRevisionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('revisions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('wd_revision_id')->nullable();
            $table->unsignedBigInteger('wd_user_id')->nullable();
            $table->char('revision_type', 1);
            $table->unsignedBigInteger('page_id');
            $table->unsignedInteger('user_id');
            $table->mediumText('content')->nullable(); // Validate so this doesn't happen on accident. We need nullable for non-source changes.
            $table->json('metadata');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('revisions');
    }
}
