<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEquipmentTable extends Migration{
	/**
	 * Run the migrations.
	 * 
	 * @return void
	 */
	public function up(){
		Schema::create('equipment', function (Blueprint $table){
			$table->integer('id')->unsigned();
			$table->string('name');
			$table->integer('quantity')->unsigned();
			$table->primary('id');
		});
	}
	
	/**
     * Reverse the migrations.
     *
     * @return void
     */
	public function down(){
		Schema::drop('equipment');
	}
}

