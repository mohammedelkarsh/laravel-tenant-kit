<?php

namespace App\Listeners;

use App\Models\Tenant;
use App\Services\PlatformWebhookDispatcher;
use Illuminate\Database\Eloquent\Model;

class DispatchKycPlatformWebhook
{
    public function __construct(
        private readonly PlatformWebhookDispatcher $webhooks,
    ) {}

    /**
     * Handle kyc-ai pipeline events (KycVerified / KycFailed).
     */
    public function handle(object $event): void
    {
        if (! property_exists($event, 'result') || ! is_object($event->result)) {
            return;
        }

        $result = $event->result;

        if (method_exists($result, 'needsManualReview') && $result->needsManualReview()) {
            $eventName = 'kyc.verification.pending_review';
        } elseif ($event instanceof \KycAi\Laravel\Events\KycVerified) {
            $eventName = 'kyc.verification.passed';
        } else {
            $eventName = 'kyc.verification.failed';
        }

        $this->dispatchWebhook($eventName, [
            'status' => method_exists($result, 'status') ? $result->status() : null,
            'passed' => method_exists($result, 'passed') ? $result->passed() : null,
            'country' => method_exists($result, 'country') ? $result->country() : null,
            'level' => method_exists($result, 'level') ? $result->level()->value : null,
            'confidence' => method_exists($result, 'confidence') ? $result->confidence() : null,
            'failure_reason' => method_exists($result, 'failureReason') ? $result->failureReason() : null,
            'warnings' => method_exists($result, 'warnings') ? $result->warnings() : [],
        ]);
    }

    /**
     * Handle Filament / manual status changes on the verification row.
     */
    public function handleVerificationUpdated(Model $verification): void
    {
        if (! $verification->wasChanged('status')) {
            return;
        }

        $status = (string) ($verification->getAttribute('status') ?? '');

        $eventName = match ($status) {
            'passed' => 'kyc.verification.passed',
            'pending_review' => 'kyc.verification.pending_review',
            default => 'kyc.verification.failed',
        };

        $this->dispatchWebhook($eventName, [
            'uuid' => $verification->getAttribute('uuid'),
            'status' => $status,
            'passed' => (bool) $verification->getAttribute('passed'),
            'country' => $verification->getAttribute('country'),
            'level' => $verification->getAttribute('level'),
            'confidence' => $verification->getAttribute('confidence'),
            'failure_reason' => $verification->getAttribute('failure_reason'),
            'source' => 'verification_updated',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function dispatchWebhook(string $eventName, array $data): void
    {
        $tenant = tenancy()->initialized ? tenant() : null;

        $this->webhooks->dispatch($eventName, array_merge([
            'workspace_id' => $tenant instanceof Tenant ? $tenant->id : null,
            // No national_id / document paths — keep PII out of webhook payloads.
        ], $data));
    }
}
