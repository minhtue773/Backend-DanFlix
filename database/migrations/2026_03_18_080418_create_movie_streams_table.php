<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up()
    {
        Schema::create('movie_streams', function (Blueprint $table) {

            $table->id();

            $table->bigInteger('tmdb_id')->nullable();
            $table->string('type'); // movie | tv
            $table->integer('season')->nullable();
            $table->string('slug');
            $table->string('source')->default('phimapi');
            $table->float('match_score')->nullable();
            $table->string('matched_by')->nullable(); // imdb | fuzzy
            $table->timestamp('last_checked_at')->nullable();

            $table->timestamps();

            $table->unique(['slug', 'source']);
            $table->index(['tmdb_id', 'type', 'season']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('movie_streams');
    }
};
