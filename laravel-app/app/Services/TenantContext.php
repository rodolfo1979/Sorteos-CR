<?php

namespace App\Services;

use App\Models\Tenant;

class TenantContext
{
    public function current(): ?Tenant
    {
        return $this->principal();
    }

    public function principal(): ?Tenant
    {
        return Tenant::query()
            ->where('slug', 'sorteos-cr')
            ->first()
            ?? Tenant::query()->oldest('id')->first();
    }
}