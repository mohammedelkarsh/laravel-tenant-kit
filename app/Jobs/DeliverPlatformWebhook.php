<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeliverPlatformWebhook implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /**
     * @param  array<string, mixed>  $envelope
     */
    public function __construct(
        public readonly string $event,
        public readonly array $envelope,
    ) {}

    public function handle(): void
    {
        $url = config('webhooks.url');

        if (! filled($url)) {
            return;
        }

        $body = json_encode($this->envelope, JSON_THROW_ON_ERROR);
        $secret = (string) config('webhooks.secret', '');
        $signature = $secret !== ''
            ? 'sha256='.hash_hmac('sha256', $body, $secret)
            : null;

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => 'laravel-tenant-kit-webhooks/1.6',
            'X-Tenant-Kit-Event' => $this->event,
            'X-Tenant-Kit-Delivery' => (string) ($this->envelope['id'] ?? ''),
        ];

        if ($signature !== null) {
            $headers['X-Tenant-Kit-Signature'] = $signature;
        }

        try {
            $response = Http::timeout((int) config('webhooks.timeout', 5))
                ->withBody($body, 'application/json')
                ->withHeaders($headers)
                ->post($url);

            if ($response->failed()) {
                Log::warning('webhooks.delivery_failed', [
                    'event' => $this->event,
                    'delivery' => $this->envelope['id'] ?? null,
                    'status' => $response->status(),
                ]);

                $response->throw();
            }
        } catch (Throwable $exception) {
            Log::warning('webhooks.delivery_error', [
                'event' => $this->event,
                'delivery' => $this->envelope['id'] ?? null,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
