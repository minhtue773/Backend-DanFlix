<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovieStream extends Model
{

    protected $fillable = [
        'tmdb_id',
        'slug',
        'type',
        'source'
    ];
}