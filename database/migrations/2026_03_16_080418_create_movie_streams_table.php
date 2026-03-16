<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up()
    {
        Schema::create('movie_streams', function (Blueprint $table) {

            $table->id();

            $table->bigInteger('tmdb_id')->index();

            $table->string('slug');

            $table->string('type'); // movie | tv

            $table->string('source')->default('phimapi');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('movie_streams');
    }
};