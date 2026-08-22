<?php

// Ejemplos de Form Requests. Separar en app/Http/Requests/*.php.

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRaffleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-raffles') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'total_numbers' => ['required', 'integer', 'min:5', 'max:100000'],
            'price' => ['required', 'integer', 'min:1'],
            'numbers_per_order' => ['required', 'integer', 'min:1', 'max:100'],
            'max_random_changes' => ['required', 'integer', 'min:0', 'max:20'],
            'reservation_minutes' => ['required', 'integer', 'min:5', 'max:240'],
            'assignment_mode' => ['required', Rule::in(['manual', 'random'])],
            'draw_date' => ['nullable', 'date'],
            'prize' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'organizer_name' => ['required', 'string', 'max:160'],
            'organizer_whatsapp' => ['nullable', 'string', 'max:30'],
            'payment_info' => ['nullable', 'string', 'max:5000'],
            'rules_text' => ['nullable', 'string', 'max:10000'],
        ];
    }
}

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'buyer_name' => ['required', 'string', 'max:160'],
            'buyer_phone' => ['required', 'string', 'max:30'],
            'buyer_email' => ['nullable', 'email', 'max:180'],
            'numbers' => ['nullable', 'array', 'max:100'],
            'numbers.*' => ['string', 'max:12'],
            'package_count' => ['required', 'integer', 'min:1', 'max:10'],
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ];
    }
}
