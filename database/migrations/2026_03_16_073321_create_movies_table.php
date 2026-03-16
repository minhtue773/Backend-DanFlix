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

            $table->bigInteger('tmdb_id')->unique();

            $table->string('title');
            $table->string('original_title')->nullable();

            $table->integer('year')->nullable();

            $table->string('poster_path')->nullable();
            $table->string('backdrop_path')->nullable();

            $table->string('type')->default('movie');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('movies');
    }
};