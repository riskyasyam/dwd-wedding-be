<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Decoration extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'region',
        'description',
        'base_price',
        'discount_percent',
        'final_price',
        'discount_start_date',
        'discount_end_date',
        'rating',
        'review_count',
        'is_deals',
        'minimum_dp_percentage',
    ];

    protected $casts = [
        'base_price' => 'integer',
        'discount_percent' => 'integer',
        'final_price' => 'integer',
        'rating' => 'decimal:1',
        'review_count' => 'integer',
        'is_deals' => 'boolean',
        'minimum_dp_percentage' => 'integer',
        'discount_start_date' => 'date',
        'discount_end_date' => 'date',
    ];

    // Helper method untuk cek apakah diskon masih aktif
    public function hasActiveDiscount(): bool
    {
        if (!$this->discount_start_date || !$this->discount_end_date) {
            return false;
        }
        
        $now = now();
        return $now->between($this->discount_start_date, $this->discount_end_date);
    }

    // Helper method untuk hitung final price otomatis
    public function calculateFinalPrice(): int
    {
        if ($this->hasActiveDiscount() && $this->discount_percent > 0) {
            return $this->base_price - ($this->base_price * $this->discount_percent / 100);
        }
        
        return $this->base_price;
    }

    /**
     * Get the images for the decoration.
     */
    public function images()
    {
        return $this->hasMany(DecorationImage::class);
    }

    /**
     * Get the free items for the decoration.
     */
    public function freeItems()
    {
        return $this->hasMany(DecorationFreeItem::class);
    }

    /**
     * Get the advantages for the decoration.
     */
    public function advantages()
    {
        return $this->hasMany(DecorationAdvantage::class)->orderBy('order');
    }

    /**
     * Get the terms for the decoration.
     */
    public function terms()
    {
        return $this->hasMany(DecorationTerm::class)->orderBy('order');
    }

    /**
     * Get the FAQs for the decoration.
     */
    public function faqs()
    {
        return $this->hasMany(Faq::class)->orderBy('order');
    }

    /**
     * Get the reviews for the decoration.
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get the cart items for the decoration.
     */
    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Get the order items for the decoration.
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
