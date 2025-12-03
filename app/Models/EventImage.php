<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventImage extends Model
{
    protected $fillable = [
        'event_id',
        'image',
    ];

    /**
     * Get the event that owns the image.
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
