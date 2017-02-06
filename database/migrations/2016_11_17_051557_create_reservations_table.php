<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReservationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('room_name');
            $table->dateTime('timeslot');
            $table->text('description');
			//$table->string('recur_id');
			$table->integer('wait_position')->unsigned();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('room_name')->references('name')->on('rooms');
            $table->unique(['user_id', 'room_name', 'timeslot']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reservations');
    }
}
