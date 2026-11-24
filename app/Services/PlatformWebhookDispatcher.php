<?php

namespace App\Services;

use App\Jobs\DeliverPlatformWebhook;
use Illuminate\Support\Str;

class PlatformWebhookDispatcher
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function dispatch(string $event, array $data): void
    {
        if (! config('webhooks.enabled') || ! filled(config('webhooks.url'))) {
            return;
        }

        $deliveryId = (string) Str::uuid();

        $envelope = [
            'id' => $deliveryId,
            'event' => $event,
            'created_at' => now()->toIso8601String(),
            'data' => $data,
        ];

        if (config('webhooks.queue')) {
            DeliverPlatformWebhook::dispatch($event, $envelope);

            return;
        }

        DeliverPlatformWebhook::dispatchSync($event, $envelope);
    }
}
