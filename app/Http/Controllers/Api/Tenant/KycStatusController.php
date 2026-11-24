<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Support\Kyc;
use Illuminate\Http\JsonResponse;

class KycStatusController extends Controller
{
    public function show(): JsonResponse
    {
        $payload = [
            'enabled' => Kyc::enabled(),
            'package_installed' => Kyc::packageInstalled(),
            'ready' => Kyc::ready(),
            'latest' => null,
        ];

        if (! Kyc::ready()) {
            return response()->json($payload);
        }

        /** @var class-string<\Illuminate\Database\Eloquent\Model> $model */
        $model = \KycAi\Laravel\Models\KycVerification::class;
        $latest = $model::query()->latest('id')->first();

        if ($latest !== null) {
            $payload['latest'] = [
                'uuid' => $latest->uuid ?? null,
                'status' => $latest->status ?? null,
                'passed' => $latest->passed ?? null,
                'country' => $latest->country ?? null,
                'confidence' => $latest->confidence ?? null,
                'created_at' => optional($latest->created_at)?->toIso8601String(),
            ];
        }

        return response()->json($payload);
    }
}
