<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DecorationImage extends Model
{
    protected $fillable = [
        'decoration_id',
        'image',
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
        
        // If already full URL, return as is
        if (str_starts_with($this->image, 'http')) {
            return $this->image;
        }
        
        // Prepend APP_URL for relative paths
        return config('app.url') . $this->image;
    }

    /**
     * Get the decoration that owns the image.
     */
    public function decoration()
    {
        return $this->belongsTo(Decoration::class);
    }
}
