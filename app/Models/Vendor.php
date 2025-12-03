<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'category',
        'email',
        'phone',
        'address',
        'description',
        'rating',
    ];

    protected $casts = [
        'rating' => 'decimal:1',
    ];

    /**
     * Get the images for the vendor.
     */
    public function images()
    {
        return $this->hasMany(VendorImage::class);
    }
}
