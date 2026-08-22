<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Raffle;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $raffles = Raffle::withCount([
            'numbers',
            'numbers as sold_numbers_count' => fn ($query) => $query->where('status', 'sold'),
            'numbers as reserved_numbers_count' => fn ($query) => $query->where('status', 'reserved'),
        ])->latest()->get();

        return view('admin.dashboard.index', [
            'raffles' => $raffles,
            'pendingPayments' => Order::where('status', 'pending')->count(),
            'approvedRevenue' => Order::where('status', 'approved')->sum('amount_total'),
            'reservedOrders' => Order::where('status', 'pending')->count(),
            'recentOrders' => Order::with('raffle', 'numbers')->latest()->limit(8)->get(),
            'salesChart' => [
                'labels' => $raffles->pluck('name')->values(),
                'sold' => $raffles->pluck('sold_numbers_count')->values(),
                'reserved' => $raffles->pluck('reserved_numbers_count')->values(),
            ],
        ]);
    }
}
