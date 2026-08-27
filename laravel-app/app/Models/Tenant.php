<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'status',
        'primary_domain',
        'admin_email',
        'notification_email',
        'timezone',
        'currency',
        'logo_path',
        'primary_color',
        'accent_color',
    ];

    public function domains(): HasMany
    {
        return $this->hasMany(TenantDomain::class);
    }

    public function settings(): HasOne
    {
        return $this->hasOne(TenantSetting::class);
    }

    public function raffles(): HasMany
    {
        return $this->hasMany(Raffle::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}