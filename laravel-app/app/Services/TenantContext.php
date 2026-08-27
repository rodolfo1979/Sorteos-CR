<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Schema;
use Throwable;

class TenantContext
{
    public function current(): ?Tenant
    {
        return $this->principal();
    }

    public function principal(): ?Tenant
    {
        try {
            if (! Schema::hasTable('tenants')) {
                return null;
            }

            return Tenant::query()
                ->where('slug', 'sorteos-cr')
                ->first()
                ?? Tenant::query()->oldest('id')->first();
        } catch (Throwable) {
            return null;
        }
    }
}