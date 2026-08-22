<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('admin.reports.index', [
            'orders' => Order::with('raffle', 'numbers')->latest()->paginate(30),
            'approvedRevenue' => Order::where('status', 'approved')->sum('amount_total'),
            'pendingCount' => Order::where('status', 'pending')->count(),
            'rejectedCount' => Order::where('status', 'rejected')->count(),
        ]);
    }
}
