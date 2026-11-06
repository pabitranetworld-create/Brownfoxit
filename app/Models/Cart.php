<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    protected $fillable = ['token', 'user_id', 'coupon_code'];

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }
}
