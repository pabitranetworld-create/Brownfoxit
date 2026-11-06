<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DownloadPermission extends Model
{
    protected $fillable = ['token', 'order_id', 'order_item_id', 'user_id', 'product_id', 'file_key', 'file_name', 'downloads_remaining', 'download_count', 'expires_at', 'revoked_at', 'last_download_at'];

    protected $casts = ['expires_at' => 'datetime', 'revoked_at' => 'datetime', 'last_download_at' => 'datetime'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function problem(): ?string
    {
        return match (true) {
            $this->revoked_at !== null => 'Access to this download has been removed.',
            $this->expires_at && $this->expires_at->isPast() => 'This download link has expired.',
            $this->downloads_remaining !== null && $this->downloads_remaining <= 0 => 'The download limit for this file has been reached.',
            ! $this->order || $this->order->payment_status !== 'paid' => 'This order is not paid.',
            default => null,
        };
    }

    public function link(): string
    {
        return route('shop.download', $this->token);
    }
}
