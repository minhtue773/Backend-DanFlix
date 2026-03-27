<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'role',
        'status',
        'email_verified_at',
        'otp_code',
        'otp_expires_at',
        'otp_attempts',
        'otp_last_sent_at',

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function preferences()
    {
        return $this->hasOne(UserPreference::class);
    }

    public function history()
    {
        return $this->hasMany(UserHistory::class);
    }

    public function watchlist()
    {
        return $this->belongsToMany(
            Movie::class,
            'watchlists',
            'user_id',
            'movie_id'
        )->withTimestamps()->withPivot('added_at');
    }

    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }
}
