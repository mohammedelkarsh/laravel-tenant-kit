<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use App\Support\Kyc;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class TenantKycService
{
    /**
     * Apply per-tenant KYC config inside the current (or given) tenant context.
     *
     * @return array{country: string, level: string, extraction_driver: string}
     */
    public function applyTenantConfig(?Tenant $tenant = null): array
    {
        $tenant ??= tenant();

        if (! $tenant instanceof Tenant) {
            throw new RuntimeException('KYC requires an initialized tenant context.');
        }

        $settings = $tenant->kycSettings();

        config([
            'kyc.extraction.default' => $settings['extraction_driver'],
            'kyc.default_level' => $settings['level'],
        ]);

        return $settings;
    }

    /**
     * Verify synchronously (or queue ProcessKycDocument) while tenancy is active.
     * Stancl QueueTenancyBootstrapper keeps queued jobs on the correct tenant.
     */
    public function submit(UploadedFile $file, User $user, bool $queue = false): mixed
    {
        if (! Kyc::ready()) {
            throw new RuntimeException('KYC is not enabled or kyc-ai/laravel is not installed.');
        }

        $settings = $this->applyTenantConfig();

        /** @var \KycAi\Laravel\KycPipeline $pipeline */
        $pipeline = \KycAi\Laravel\Facades\Kyc::document($file)
            ->country($settings['country'])
            ->level($settings['level'])
            ->extractWith($settings['extraction_driver'])
            ->forUser((int) $user->id);

        if ($queue) {
            /** @var PendingDispatch $dispatch */
            $dispatch = $pipeline->dispatch();

            return $dispatch;
        }

        return $pipeline->verify();
    }
}
