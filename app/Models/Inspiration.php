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

    protected $appends = ['image_url'];

    /**
     * Get full image URL.
     */
    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return null;
        }
        
        if (str_starts_with($this->image, 'http')) {
            return $this->image;
        }
        
        return config('app.url') . $this->image;
    }

    /**
     * Get the users who saved this inspiration.
     */
    public function savedByUsers()
    {
        return $this->belongsToMany(User::class, 'inspiration_user_saved');
    }
}
