<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(): View
    {
        $tenants = Tenant::query()
            ->withCount(['domains', 'raffles', 'orders'])
            ->orderByRaw("status = 'active' desc")
            ->orderBy('name')
            ->get();

        return view('superadmin.tenants.index', [
            'title' => 'Tenants - Super admin',
            'stats' => [
                'total' => Tenant::query()->count(),
                'active' => Tenant::query()->where('status', 'active')->count(),
                'suspended' => Tenant::query()->where('status', 'suspended')->count(),
                'domains' => DB::table('tenant_domains')->count(),
            ],
            'tenants' => $tenants,
        ]);
    }

    public function create(): View
    {
        return view('superadmin.tenants.form', [
            'title' => 'Crear tenant - Super admin',
            'tenant' => new Tenant(['status' => 'active', 'timezone' => 'America/Costa_Rica', 'currency' => 'CRC']),
            'action' => route('superadmin.tenants.store'),
            'method' => 'post',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateTenant($request)->validate();
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);

        DB::transaction(function () use ($data): void {
            $tenant = Tenant::query()->create($data);

            if ($tenant->primary_domain) {
                $tenant->domains()->create([
                    'domain' => Str::lower($tenant->primary_domain),
                    'type' => 'primary',
                    'is_verified' => false,
                ]);
            }

            $tenant->settings()->create([
                'mail_from_address' => config('mail.from.address'),
                'mail_from_name' => $tenant->name,
                'notification_email' => $tenant->notification_email,
                'reservation_minutes_default' => 45,
            ]);
        });

        return redirect()->route('superadmin.tenants.index')->with('status', 'Tenant creado correctamente.');
    }

    public function edit(Tenant $tenant): View
    {
        return view('superadmin.tenants.form', [
            'title' => 'Editar tenant - Super admin',
            'tenant' => $tenant,
            'action' => route('superadmin.tenants.update', $tenant),
            'method' => 'put',
        ]);
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $this->validateTenant($request, $tenant)->validate();
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);

        DB::transaction(function () use ($tenant, $data): void {
            $oldDomain = $tenant->primary_domain;
            $tenant->update($data);

            if ($tenant->primary_domain) {
                $tenant->domains()->updateOrCreate(
                    ['domain' => Str::lower($tenant->primary_domain)],
                    ['type' => 'primary', 'is_verified' => $oldDomain === $tenant->primary_domain]
                );
            }

            $tenant->settings()->updateOrCreate(
                ['tenant_id' => $tenant->id],
                [
                    'mail_from_name' => $tenant->name,
                    'notification_email' => $tenant->notification_email,
                ]
            );
        });

        return redirect()->route('superadmin.tenants.index')->with('status', 'Tenant actualizado correctamente.');
    }

    public function destroy(Tenant $tenant): RedirectResponse
    {
        if ($tenant->raffles()->exists() || $tenant->orders()->exists()) {
            return back()->withErrors('Este tenant ya tiene datos operativos. Puedes suspenderlo, pero no eliminarlo.');
        }

        $tenant->delete();

        return redirect()->route('superadmin.tenants.index')->with('status', 'Tenant eliminado correctamente.');
    }

    private function validateTenant(Request $request, ?Tenant $tenant = null): \Illuminate\Validation\Validator
    {
        return Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('tenants', 'slug')->ignore($tenant)],
            'status' => ['required', Rule::in(['active', 'suspended'])],
            'primary_domain' => ['nullable', 'string', 'max:255', Rule::unique('tenants', 'primary_domain')->ignore($tenant)],
            'admin_email' => ['nullable', 'email', 'max:255'],
            'notification_email' => ['nullable', 'email', 'max:255'],
            'timezone' => ['required', 'string', 'max:64'],
            'currency' => ['required', 'string', 'max:8'],
            'primary_color' => ['nullable', 'string', 'max:24'],
            'accent_color' => ['nullable', 'string', 'max:24'],
        ]);
    }
}
