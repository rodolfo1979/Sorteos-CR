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
        return view('admin.dashboard.index', [
            'raffles' => Raffle::withCount('numbers')->latest()->get(),
            'pendingPayments' => Order::where('status', 'pending')->count(),
            'approvedRevenue' => Order::where('status', 'approved')->sum('amount_total'),
            'recentOrders' => Order::with('raffle', 'numbers')->latest()->limit(8)->get(),
        ]);
    }
}
