<?php

namespace App\Listeners;

use App\Models\Tenant;
use App\Services\UsageMeter;
use Illuminate\Mail\Events\MessageSent;

class RecordOutboundEmailUsage
{
    public function __construct(
        private readonly UsageMeter $usageMeter,
    ) {}

    public function handle(MessageSent $event): void
    {
        if (! tenancy()->initialized) {
            return;
        }

        $tenant = tenant();

        if (! $tenant instanceof Tenant) {
            return;
        }

        $this->usageMeter->record($tenant, 'outbound_emails');
    }
}
