<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateProjectsTable extends Migration {

	public function up()
	{
		Schema::create('projects', function(Blueprint $table) {
			$table->increments('id');
			$table->string('name', 255);
			$table->text('description');
			$table->integer('type_id')->unsigned();
			$table->integer('status_id')->unsigned();
			$table->string('repo_link', 255)->nullable();
			$table->smallInteger('max_member_count')->default('1');
            $table->date('deadline')->nullable();
            $table->text('review')->nullable();
            $table->softDeletes();
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('projects');
	}
}
