<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorImage extends Model
{
    protected $fillable = [
        'vendor_id',
        'image',
    ];

    /**
     * Get the vendor that owns the image.
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
