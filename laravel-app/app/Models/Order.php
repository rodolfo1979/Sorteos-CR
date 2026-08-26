<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_uuid', 'raffle_id', 'buyer_name', 'buyer_phone', 'buyer_email',
        'package_count', 'amount_total', 'assignment_mode', 'random_changes_used',
        'status', 'receipt_path', 'receipt_original_name', 'receipt_mime',
        'approved_at', 'rejected_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function raffle(): BelongsTo
    {
        return $this->belongsTo(Raffle::class);
    }

    public function numbers(): BelongsToMany
    {
        return $this->belongsToMany(RaffleNumber::class, 'order_numbers')
            ->withPivot('number')
            ->withTimestamps();
    }

    public function activityEvents(): HasMany
    {
        return $this->hasMany(OrderEvent::class)->latest();
    }
}
