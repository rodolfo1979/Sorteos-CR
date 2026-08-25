<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Raffle;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class RealtimeController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $raffles = Raffle::withCount([
            'numbers',
            'numbers as sold_numbers_count' => fn ($query) => $query->where('status', 'sold'),
            'numbers as reserved_numbers_count' => fn ($query) => $query->where('status', 'reserved'),
        ])->latest()->get();

        $pendingOrders = Order::with('raffle', 'numbers')
            ->where('status', 'pending')
            ->latest()
            ->get();

        $recentOrders = Order::with('raffle', 'numbers')
            ->latest()
            ->limit(20)
            ->get();

        return response()->json([
            'stats' => [
                'approved_revenue' => '₡'.number_format((int) Order::where('status', 'approved')->sum('amount_total'), 0, ',', ' '),
                'pending_payments' => $pendingOrders->count(),
                'raffles_count' => $raffles->count(),
                'reserved_numbers' => number_format($raffles->sum('reserved_numbers_count')),
            ],
            'sales_chart' => [
                'labels' => $raffles->pluck('name')->values(),
                'sold' => $raffles->pluck('sold_numbers_count')->values(),
                'reserved' => $raffles->pluck('reserved_numbers_count')->values(),
            ],
            'recent_orders' => $recentOrders->map(fn (Order $order) => [
                'buyer_name' => $order->buyer_name,
                'raffle_name' => $order->raffle?->name ?? 'Sorteo eliminado',
                'numbers' => $order->numbers->pluck('number')->values(),
                'status' => $order->status,
            ])->values(),
            'pending_orders' => $pendingOrders->map(fn (Order $order) => [
                'id' => $order->id,
                'buyer_name' => $order->buyer_name,
                'buyer_phone' => $order->buyer_phone,
                'buyer_email' => $order->buyer_email ?: 'Sin correo',
                'raffle_name' => $order->raffle?->name ?? 'Sorteo eliminado',
                'order_code' => strtoupper(substr($order->public_uuid, 0, 8)),
                'created_at' => $order->created_at->timezone('America/Costa_Rica')->format('d/m/Y H:i'),
                'amount' => '₡'.number_format($order->amount_total, 0, ',', ' '),
                'numbers' => $order->numbers->pluck('number')->values(),
                'receipt_url' => $order->receipt_path ? Storage::url($order->receipt_path) : null,
                'approve_url' => route('admin.payments.approve', $order),
                'reject_url' => route('admin.payments.reject', $order),
            ])->values(),
            'updated_at' => now('America/Costa_Rica')->format('H:i:s'),
        ]);
    }
}
