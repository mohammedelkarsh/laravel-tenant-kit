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

    /**
     * Re-run verification from a stored national_id (documents are not retained after verify).
     *
     * @param  \KycAi\Laravel\Models\KycVerification  $verification
     * @return array{queued: bool, mode: string, result: mixed}
     */
    public function retryFromVerification(object $verification, User $user, bool $queue = true): array
    {
        if (! Kyc::ready()) {
            throw new RuntimeException('KYC is not enabled or kyc-ai/laravel is not installed.');
        }

        $nationalId = is_string($verification->national_id ?? null)
            ? trim((string) $verification->national_id)
            : '';

        if ($nationalId === '') {
            throw new RuntimeException(
                'Cannot retry: no national_id on this verification. Re-upload a document via /kyc.'
            );
        }

        $status = (string) ($verification->status ?? '');
        if ($status === 'passed') {
            throw new RuntimeException('Cannot retry an already passed verification.');
        }

        $this->applyTenantConfig();

        $originalLevel = (string) ($verification->level ?? 'internal');
        $level = \KycAi\Laravel\KycLevel::tryFrom($originalLevel) ?? \KycAi\Laravel\KycLevel::Internal;

        // Documents are deleted after verify; retry without a file is national_id-only.
        $mode = 'national_id_only';
        if ($level->requiresExtraction()) {
            $level = \KycAi\Laravel\KycLevel::Internal;
        }

        /** @var \KycAi\Laravel\KycPipeline $pipeline */
        $pipeline = \KycAi\Laravel\Facades\Kyc::number($nationalId)
            ->country((string) ($verification->country ?? 'sa'))
            ->level($level)
            ->forUser((int) ($verification->user_id ?? $user->id));

        if ($queue) {
            $pipeline->dispatch();

            return [
                'queued' => true,
                'mode' => $mode,
                'result' => null,
            ];
        }

        return [
            'queued' => false,
            'mode' => $mode,
            'result' => $pipeline->verify(),
        ];
    }
}
