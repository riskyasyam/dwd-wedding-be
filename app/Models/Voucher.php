<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Voucher extends Model
{
    protected $fillable = [
        'code',
        'type',
        'discount_value',
        'min_purchase',
        'max_discount',
        'usage_limit',
        'usage_count',
        'usage_per_user',
        'valid_from',
        'valid_until',
        'is_active',
        'description',
    ];

    protected $casts = [
        'discount_value' => 'integer',
        'min_purchase' => 'integer',
        'max_discount' => 'integer',
        'usage_limit' => 'integer',
        'usage_count' => 'integer',
        'usage_per_user' => 'integer',
        'is_active' => 'boolean',
        'valid_from' => 'date',
        'valid_until' => 'date',
    ];

    /**
     * Check if voucher is currently valid
     */
    public function isValid(): bool
    {
        $now = Carbon::now();
        
        return $this->is_active 
            && $now->between($this->valid_from, $this->valid_until)
            && ($this->usage_limit === null || $this->usage_count < $this->usage_limit);
    }

    /**
     * Check if user can use this voucher
     */
    public function canBeUsedBy(int $userId, int $cartTotal): array
    {
        // Check if active
        if (!$this->is_active) {
            return ['valid' => false, 'message' => 'Voucher is not active'];
        }

        // Check date validity
        $now = Carbon::now();
        if ($now->lt($this->valid_from)) {
            return ['valid' => false, 'message' => 'Voucher is not yet valid'];
        }
        if ($now->gt($this->valid_until)) {
            return ['valid' => false, 'message' => 'Voucher has expired'];
        }

        // Check usage limit
        if ($this->usage_limit !== null && $this->usage_count >= $this->usage_limit) {
            return ['valid' => false, 'message' => 'Voucher usage limit reached'];
        }

        // Check minimum purchase
        if ($cartTotal < $this->min_purchase) {
            return [
                'valid' => false, 
                'message' => 'Minimum purchase of Rp ' . number_format($this->min_purchase, 0, ',', '.') . ' required'
            ];
        }

        // Check user usage count
        $userUsageCount = Order::where('user_id', $userId)
            ->where('voucher_code', $this->code)
            ->count();
        
        if ($userUsageCount >= $this->usage_per_user) {
            return ['valid' => false, 'message' => 'You have already used this voucher'];
        }

        return ['valid' => true, 'message' => 'Voucher is valid'];
    }

    /**
     * Calculate discount amount for given cart total
     */
    public function calculateDiscount(int $cartTotal): int
    {
        if ($this->type === 'percentage') {
            $discount = ($cartTotal * $this->discount_value) / 100;
            
            // Apply max discount if set
            if ($this->max_discount !== null && $discount > $this->max_discount) {
                return $this->max_discount;
            }
            
            return (int) $discount;
        }
        
        // Fixed discount
        return $this->discount_value;
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Get voucher display text
     */
    public function getDisplayText(): string
    {
        if ($this->type === 'percentage') {
            $text = "{$this->discount_value}% OFF";
            if ($this->max_discount) {
                $text .= " (max Rp " . number_format($this->max_discount, 0, ',', '.') . ")";
            }
        } else {
            $text = "Rp " . number_format($this->discount_value, 0, ',', '.') . " OFF";
        }

        if ($this->min_purchase > 0) {
            $text .= " - Min purchase Rp " . number_format($this->min_purchase, 0, ',', '.');
        }

        return $text;
    }
}
