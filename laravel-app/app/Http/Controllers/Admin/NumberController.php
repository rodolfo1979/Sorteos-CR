<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Raffle;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NumberController extends Controller
{
    public function index(Request $request): View
    {
        $raffle = Raffle::firstOrFail();
        $status = $request->string('status')->toString();
        $search = $request->string('search')->toString();

        $numbers = $raffle->numbers()
            ->when(in_array($status, ['available', 'reserved', 'sold'], true), fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where('number', 'like', "%{$search}%"))
            ->orderBy('number')
            ->paginate(100)
            ->withQueryString();

        return view('admin.numbers.index', compact('raffle', 'numbers', 'status', 'search'));
    }
}
