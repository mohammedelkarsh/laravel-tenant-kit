<?php

namespace App\Models;

use App\Support\TenantUrls;
use Laravel\Cashier\Billable;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use Billable, HasDatabase, HasDomains;

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'suspended_at',
            'created_at',
            'updated_at',
        ];
    }

    protected function casts(): array
    {
        return [
            'suspended_at' => 'datetime',
        ];
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    public function url(): string
    {
        $primaryDomain = $this->domains()->first()?->domain ?? $this->id;

        return TenantUrls::forSubdomain($primaryDomain);
    }

    public function urlHost(): string
    {
        $primaryDomain = $this->domains()->first()?->domain ?? $this->id;

        return TenantUrls::hostLabel($primaryDomain);
    }

    public function stripeName(): ?string
    {
        return $this->name;
    }

    public function stripeEmail(): ?string
    {
        return $this->id.'@billing.'.config('app.central_domain');
    }
}
