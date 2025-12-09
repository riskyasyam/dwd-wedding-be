<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'decoration_id',
        'type',
        'quantity',
        'base_price',
        'discount',
        'price',
    ];

    protected $casts = [
        'base_price' => 'integer',
        'discount' => 'integer',
        'price' => 'integer',
    ];

    /**
     * Get the order that owns the item.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the decoration for the order item.
     */
    public function decoration()
    {
        return $this->belongsTo(Decoration::class);
    }
}
