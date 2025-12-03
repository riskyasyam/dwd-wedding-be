<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inspiration extends Model
{
    protected $fillable = [
        'title',
        'image',
        'colors',
        'location',
        'liked_count',
    ];

    protected $casts = [
        'colors' => 'array', // Cast JSON to array
        'liked_count' => 'integer',
    ];

    /**
     * Get the users who saved this inspiration.
     */
    public function savedByUsers()
    {
        return $this->belongsToMany(User::class, 'inspiration_user_saved');
    }
}
