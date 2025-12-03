<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $fillable = [
        'cart_id',
        'decoration_id',
        'type',
        'quantity',
        'price',
    ];

    protected $casts = [
        'price' => 'integer',
    ];

    /**
     * Get the cart that owns the item.
     */
    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Get the decoration for the cart item.
     */
    public function decoration()
    {
        return $this->belongsTo(Decoration::class);
    }
}
