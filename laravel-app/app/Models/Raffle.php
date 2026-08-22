<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Raffle extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'total_numbers', 'number_width', 'price_per_package',
        'numbers_per_package', 'max_random_changes', 'reservation_minutes',
        'assignment_mode', 'sale_enabled', 'is_featured', 'draw_date',
        'prize_title', 'prize_description', 'public_sales_text', 'image_path', 'media_paths', 'organizer_name',
        'organizer_whatsapp', 'payment_instructions', 'rules_text',
    ];

    protected $casts = [
        'sale_enabled' => 'boolean',
        'is_featured' => 'boolean',
        'draw_date' => 'date',
        'media_paths' => 'array',
    ];

    public function numbers(): HasMany
    {
        return $this->hasMany(RaffleNumber::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function packageOptions(int $maxPackages = 5): array
    {
        return collect(range(1, $maxPackages))->map(fn (int $count) => [
            'packages' => $count,
            'quantity' => $this->numbers_per_package * $count,
            'amount' => $this->price_per_package * $count,
        ])->all();
    }

    public function numberStart(): int
    {
        return 0;
    }

    public function numberEnd(): int
    {
        return max(0, $this->total_numbers - 1);
    }

    public function effectiveNumberWidth(): int
    {
        return max((int) $this->number_width, strlen((string) $this->numberEnd()));
    }

    public function formatNumber(int $number): string
    {
        return str_pad((string) $number, $this->effectiveNumberWidth(), '0', STR_PAD_LEFT);
    }
}

