<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'order_number',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'city',
        'district',
        'sub_district',
        'postal_code',
        'subtotal',
        'voucher_code',
        'voucher_discount',
        'discount',
        'delivery_fee',
        'total',
        'status',
        'payment_method',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'integer',
        'voucher_discount' => 'integer',
        'discount' => 'integer',
        'delivery_fee' => 'integer',
        'total' => 'integer',
    ];

    /**
     * Get the user that owns the order.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order items for the order.
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
