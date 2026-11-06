<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const STATUSES = [
        'pending' => 'Pending payment', 'on_hold' => 'On hold (waiting for check)', 'processing' => 'Processing',
        'completed' => 'Completed', 'cancelled' => 'Cancelled', 'refunded' => 'Refunded', 'failed' => 'Failed',
    ];

    public const PAYMENT_STATUSES = ['unpaid' => 'Unpaid', 'pending' => 'Payment pending', 'paid' => 'Paid', 'refunded' => 'Refunded', 'failed' => 'Failed'];

    protected $fillable = [
        'number', 'access_key', 'user_id', 'status', 'payment_status', 'payment_method', 'currency', 'subtotal', 'discount',
        'shipping', 'tax', 'total', 'coupon_code', 'billing', 'shipping_address', 'needs_shipping', 'customer_note', 'admin_note',
        'manual_reference', 'ip', 'paid_at',
    ];

    protected $casts = [
        'billing' => 'array', 'shipping_address' => 'array', 'needs_shipping' => 'boolean', 'paid_at' => 'datetime',
        'subtotal' => 'decimal:2', 'discount' => 'decimal:2', 'shipping' => 'decimal:2', 'tax' => 'decimal:2', 'total' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('id');
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(DownloadPermission::class)->chaperone('order');
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class)->latest('id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function canPay(): bool
    {
        return ! $this->isPaid() && in_array($this->status, ['pending', 'failed'], true);
    }

    /** Public link to the order (works for guests through the access key). */
    public function link(): string
    {
        return route('shop.order', ['number' => $this->number, 'key' => $this->access_key]);
    }

    public function log(string $type, string $message): void
    {
        $this->events()->create(['type' => $type, 'message' => $message, 'user_id' => auth()->id()]);
    }

    public function email(): string
    {
        return (string) ($this->billing['email'] ?? '');
    }

    public function customerName(): string
    {
        return (string) ($this->billing['name'] ?? '');
    }
}
