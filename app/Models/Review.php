<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'user_id',
        'decoration_id',
        'rating',
        'comment',
        'posted_at',
    ];

    protected $casts = [
        'posted_at' => 'date',
    ];

    /**
     * Get the user that owns the review.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the decoration that owns the review.
     */
    public function decoration()
    {
        return $this->belongsTo(Decoration::class);
    }
}
