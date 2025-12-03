<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DecorationFreeItem extends Model
{
    protected $fillable = [
        'decoration_id',
        'item_name',
        'description',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    /**
     * Get the decoration that owns the free item.
     */
    public function decoration()
    {
        return $this->belongsTo(Decoration::class);
    }
}
