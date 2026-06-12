<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate(array_merge([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
        ], ApiAbilities::validationRules('tenant')));

        $user = User::query()->where('email', $request->string('email'))->first();

        if (! $user || ! $user->password || ! Hash::check($request->string('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $abilities = ApiAbilities::resolve($request->input('abilities'), 'tenant');
        $token = $user->createToken($request->string('device_name'), $abilities);

        return response()->json([
            'token' => $token->plainTextToken,
            'abilities' => $abilities,
            'tenant' => [
                'id' => tenant('id'),
                'name' => tenant('name'),
            ],
            'user' => $user->only(['id', 'name', 'email']),
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Token revoked.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'tenant' => [
                'id' => tenant('id'),
                'name' => tenant('name'),
            ],
            'user' => $request->user()->only(['id', 'name', 'email']),
        ]);
    }
}
