<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PlatformController extends Controller
{
    public function __invoke(): View
    {
        $stats = [
            'tenants' => Tenant::query()->count(),
            'active_tenants' => Tenant::query()->where('status', 'active')->count(),
            'domains' => DB::table('tenant_domains')->count(),
            'unassigned_records' => $this->unassignedRecordsCount(),
        ];

        $tenants = Tenant::query()
            ->withCount(['domains', 'raffles', 'orders'])
            ->orderByRaw("status = 'active' desc")
            ->orderBy('name')
            ->get()
            ->map(fn (Tenant $tenant) => $this->tenantSummary($tenant));

        return view('superadmin.platform.index', [
            'title' => 'Super admin - Sorteos CR',
            'stats' => $stats,
            'tenants' => $tenants,
        ]);
    }

    private function tenantSummary(Tenant $tenant): array
    {
        $lastOrderAt = DB::table('orders')
            ->where('tenant_id', $tenant->id)
            ->latest('created_at')
            ->value('created_at');

        return [
            'tenant' => $tenant,
            'domains_count' => $tenant->domains_count,
            'raffles_count' => $tenant->raffles_count,
            'orders_count' => $tenant->orders_count,
            'pending_orders' => DB::table('orders')->where('tenant_id', $tenant->id)->where('status', 'pending')->count(),
            'approved_revenue' => (int) DB::table('orders')->where('tenant_id', $tenant->id)->where('status', 'approved')->sum('amount_total'),
            'last_order_at' => $lastOrderAt ? Carbon::parse($lastOrderAt)->timezone($tenant->timezone ?: config('app.timezone'))->format('d/m/Y H:i') : 'Sin ordenes',
        ];
    }

    private function unassignedRecordsCount(): int
    {
        return collect(['raffles', 'orders', 'raffle_numbers', 'order_events'])
            ->sum(fn (string $table) => DB::table($table)->whereNull('tenant_id')->count());
    }
}
