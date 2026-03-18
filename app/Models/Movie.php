<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    protected $fillable = [

        'tmdb_id',
        'title',
        'original_title',
        'year',
        'type'
    ];
}
