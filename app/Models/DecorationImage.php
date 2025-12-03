<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DecorationImage extends Model
{
    protected $fillable = [
        'decoration_id',
        'image',
    ];

    /**
     * Get the decoration that owns the image.
     */
    public function decoration()
    {
        return $this->belongsTo(Decoration::class);
    }
}
