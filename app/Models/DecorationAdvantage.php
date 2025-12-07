<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DecorationAdvantage extends Model
{
    protected $fillable = [
        'decoration_id',
        'title',
        'description',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    /**
     * Get the decoration that owns the advantage.
     */
    public function decoration()
    {
        return $this->belongsTo(Decoration::class);
    }
}
