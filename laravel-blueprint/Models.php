<?php

// Ejemplos de modelos. En Laravel real, separar cada clase en app/Models/*.php.

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Raffle extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'total_numbers',
        'price',
        'numbers_per_order',
        'max_random_changes',
        'assignment_mode',
        'sale_enabled',
        'draw_date',
        'prize',
        'image_path',
        'organizer_name',
        'organizer_whatsapp',
        'payment_info',
        'rules_text',
    ];

    protected $casts = [
        'sale_enabled' => 'boolean',
        'draw_date' => 'date',
    ];

    public function numbers(): HasMany
    {
        return $this->hasMany(RaffleNumber::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}

class RaffleNumber extends Model
{
    protected $fillable = [
        'raffle_id',
        'number',
        'status',
        'order_id',
        'reserved_until',
    ];

    protected $casts = [
        'reserved_until' => 'datetime',
    ];

    public function raffle(): BelongsTo
    {
        return $this->belongsTo(Raffle::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}

class Order extends Model
{
    protected $fillable = [
        'raffle_id',
        'buyer_name',
        'buyer_phone',
        'buyer_email',
        'amount',
        'package_count',
        'assignment_mode',
        'status',
        'receipt_path',
        'approved_at',
        'rejected_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function raffle(): BelongsTo
    {
        return $this->belongsTo(Raffle::class);
    }

    public function numbers(): HasMany
    {
        return $this->hasMany(OrderNumber::class);
    }

    public function raffleNumbers(): HasMany
    {
        return $this->hasMany(RaffleNumber::class);
    }
}

class OrderNumber extends Model
{
    protected $fillable = [
        'order_id',
        'raffle_number_id',
        'number',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function raffleNumber(): BelongsTo
    {
        return $this->belongsTo(RaffleNumber::class);
    }
}
