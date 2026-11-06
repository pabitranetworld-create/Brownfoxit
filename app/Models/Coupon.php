<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = ['code', 'type', 'value', 'min_subtotal', 'max_uses', 'used_count', 'starts_at', 'ends_at', 'is_active', 'description'];

    protected $casts = [
        'value' => 'decimal:2', 'min_subtotal' => 'decimal:2', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_active' => 'boolean',
    ];

    /** Why the coupon cannot be used for this subtotal, or null when it is valid. */
    public function problem(float $subtotal): ?string
    {
        return match (true) {
            ! $this->is_active => 'This coupon is not active.',
            $this->starts_at && $this->starts_at->isFuture() => 'This coupon is not valid yet.',
            $this->ends_at && $this->ends_at->isPast() => 'This coupon has expired.',
            $this->max_uses !== null && $this->used_count >= $this->max_uses => 'This coupon has been fully used.',
            $this->min_subtotal !== null && $subtotal < (float) $this->min_subtotal => 'This coupon needs a minimum order of '.money($this->min_subtotal).'.',
            default => null,
        };
    }

    public function discountFor(float $subtotal): float
    {
        $d = $this->type === 'fixed' ? (float) $this->value : $subtotal * (float) $this->value / 100;

        return round(min($d, $subtotal), 2);
    }
}
