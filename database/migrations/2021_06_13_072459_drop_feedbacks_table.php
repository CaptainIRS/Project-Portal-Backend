<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropFeedbacksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (DB::getDriverName() !== 'sqlite') { // For the test database
            Schema::table('feedbacks', function (Blueprint $table) {
                $table->dropForeign('feedbacks_project_id_foreign');
                $table->dropForeign('feedbacks_sender_id_foreign');
                $table->dropForeign('feedbacks_receiver_id_foreign');
            });
        }
        Schema::drop('feedbacks');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('project_id')->unsigned();
            $table->integer('sender_id')->unsigned();
            $table->integer('receiver_id')->unsigned()->nullable();
            $table->text('content');
            $table->timestamps();
        });
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->foreign('project_id')->references('id')->on('projects')
                ->onDelete('restrict')
                ->onUpdate('restrict');
        });
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->foreign('sender_id')->references('id')->on('users')
                ->onDelete('restrict')
                ->onUpdate('restrict');
        });
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->foreign('receiver_id')->references('id')->on('users')
                ->onDelete('restrict')
                ->onUpdate('restrict');
        });
    }
}
