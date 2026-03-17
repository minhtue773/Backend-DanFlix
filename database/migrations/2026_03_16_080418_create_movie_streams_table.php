<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up()
    {
        Schema::create('movie_streams', function (Blueprint $table) {

            $table->id();

            $table->bigInteger('tmdb_id');

            $table->string('type'); // movie | tv

            $table->integer('season')->nullable();

            $table->string('slug');

            $table->string('source')->default('phimapi');

            $table->timestamps();

            $table->unique(['tmdb_id', 'type', 'season']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('movie_streams');
    }
};