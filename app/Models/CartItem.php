<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = ['cart_id', 'product_id', 'variant_key', 'qty'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
