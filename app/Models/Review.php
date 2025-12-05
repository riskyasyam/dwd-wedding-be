<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'user_id',
        'customer_name',
        'decoration_id',
        'rating',
        'comment',
        'posted_at',
    ];

    protected $casts = [
        'posted_at' => 'date',
    ];

    /**
     * Append accessor attributes to JSON response.
     */
    protected $appends = ['display_name'];

    /**
     * Get the display name (customer_name for fake reviews, or user name for real reviews).
     */
    public function getDisplayNameAttribute()
    {
        return $this->customer_name ?? $this->user?->name ?? 'Anonymous';
    }

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
