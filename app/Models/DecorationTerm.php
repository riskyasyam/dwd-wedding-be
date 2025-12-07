<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DecorationTerm extends Model
{
    protected $fillable = [
        'decoration_id',
        'term',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    /**
     * Get the decoration that owns the term.
     */
    public function decoration()
    {
        return $this->belongsTo(Decoration::class);
    }
}
