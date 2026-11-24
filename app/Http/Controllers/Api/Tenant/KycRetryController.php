<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Services\TenantKycService;
use App\Support\Kyc;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class KycRetryController extends Controller
{
    public function store(Request $request, string $uuid, TenantKycService $kyc): JsonResponse
    {
        if (! Kyc::ready()) {
            return response()->json([
                'message' => 'KYC is not enabled or kyc-ai/laravel is not installed.',
            ], 404);
        }

        $request->validate([
            'queue' => ['sometimes', 'boolean'],
        ]);

        /** @var class-string<\Illuminate\Database\Eloquent\Model> $model */
        $model = \KycAi\Laravel\Models\KycVerification::class;
        $verification = $model::query()->where('uuid', $uuid)->first();

        if ($verification === null) {
            return response()->json(['message' => 'Verification not found.'], 404);
        }

        try {
            $outcome = $kyc->retryFromVerification(
                $verification,
                $request->user(),
                $request->boolean('queue', true),
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $payload = [
            'queued' => $outcome['queued'],
            'mode' => $outcome['mode'],
            'source_uuid' => $uuid,
            'message' => $outcome['queued']
                ? 'KYC retry queued (national_id only; original document is not retained).'
                : 'KYC retry completed (national_id only; original document is not retained).',
        ];

        if ($outcome['result'] !== null && is_object($outcome['result']) && method_exists($outcome['result'], 'toArray')) {
            $payload['result'] = $outcome['result']->toArray();
        }

        return response()->json($payload, $outcome['queued'] ? 202 : 200);
    }
}
