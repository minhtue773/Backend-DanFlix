<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('movies', function (Blueprint $table) {

            $table->id();

            $table->bigInteger('tmdb_id');
            $table->string('type'); // movie | tv

            $table->string('title');
            $table->string('original_title')->nullable();

            $table->integer('year')->nullable();

            $table->timestamps();

            $table->unique(['tmdb_id', 'type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('movies');
    }
};