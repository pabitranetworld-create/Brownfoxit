<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = ['order_id', 'product_id', 'variant_key', 'name', 'variant_name', 'sku', 'type', 'price', 'qty', 'total'];

    protected $casts = ['price' => 'decimal:2', 'total' => 'decimal:2'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
