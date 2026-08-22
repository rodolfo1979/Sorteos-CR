<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\View\View;

class ConfirmationController extends Controller
{
    public function show(string $uuid): View
    {
        $order = Order::with('raffle', 'numbers')->where('public_uuid', $uuid)->firstOrFail();

        return view('raffles.confirmation', ['order' => $order]);
    }
}
