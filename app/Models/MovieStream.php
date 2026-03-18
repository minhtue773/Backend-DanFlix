<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovieStream extends Model
{

    protected $fillable = [
        'tmdb_id',
        'type',
        'season',
        'slug',
        'source',
        'match_score',
        'matched_by',
        'last_checked_at'
    ];
}
