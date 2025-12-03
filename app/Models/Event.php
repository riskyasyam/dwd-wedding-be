<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'banner_image',
        'start_date',
        'end_date',
        'location',
        'short_description',
        'full_description',
        'organizer',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the images for the event.
     */
    public function images()
    {
        return $this->hasMany(EventImage::class);
    }
}
