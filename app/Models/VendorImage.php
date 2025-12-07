<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorImage extends Model
{
    protected $fillable = [
        'vendor_id',
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
        
        if (str_starts_with($this->image, 'http')) {
            return $this->image;
        }
        
        return config('app.url') . $this->image;
    }

    /**
     * Get the vendor that owns the image.
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
