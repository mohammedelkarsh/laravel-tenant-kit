<?php

namespace App\Models;

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
            'created_at',
            'updated_at',
        ];
    }

    public function url(): string
    {
        $primaryDomain = $this->domains()->first()?->domain ?? $this->id;

        if (str_contains($primaryDomain, '.')) {
            return 'http://'.$primaryDomain;
        }

        return 'http://'.$primaryDomain.'.'.config('app.central_domain');
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
