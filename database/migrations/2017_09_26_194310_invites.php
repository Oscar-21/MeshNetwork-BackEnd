<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Invites extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('invites', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('userID');
            $table->integer('spaceID')->unsigned();
            $table->dateTime('date');
            $table->string('status')->default('sent');
            $table->timestamps();
            $table->foreign('spaceID')->references('id')->on('workspaces')->onDelete('cascade');
        });

    }

      /**
     * Reverse the migrations.
     *
     * @return void
     */
     public function down()
     {
         Schema::dropIfExists('invites');
     }
 }