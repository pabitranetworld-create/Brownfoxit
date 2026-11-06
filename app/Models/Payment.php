<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = ['order_id', 'gateway', 'reference', 'gateway_txn_id', 'amount', 'currency', 'status', 'method', 'message', 'meta', 'verified_at'];

    protected $casts = ['meta' => 'array', 'amount' => 'decimal:2', 'verified_at' => 'datetime'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** Keep a short log of gateway answers (never secrets or card data). */
    public function remember(string $key, mixed $value): void
    {
        $meta = $this->meta ?? [];
        $meta[$key] = $value;
        $this->meta = $meta;
    }
}
