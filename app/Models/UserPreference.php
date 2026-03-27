<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'genre_ids',
        'language_ids',
    ];

    protected $casts = [
        'genre_ids' => 'array',
        'language_ids' => 'array',
    ];

    protected $table = 'user_preferences';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
