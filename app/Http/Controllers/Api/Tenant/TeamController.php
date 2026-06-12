<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class TeamController extends Controller
{
    public function index(): JsonResponse
    {
        $members = User::query()
            ->with('roles')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->first()?->name ?? 'member',
            ]);

        return response()->json(['data' => $members]);
    }

    public function invite(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'in:admin,member'],
        ]);

        $email = strtolower($request->string('email')->toString());

        if (User::query()->where('email', $email)->exists()) {
            return response()->json([
                'message' => __('app.team.member_exists'),
            ], 422);
        }

        TeamInvitation::query()
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->delete();

        $invitation = TeamInvitation::createFor(
            $email,
            $request->string('role')->toString(),
            $request->user()->id,
        );

        $acceptUrl = route('tenant.invitations.accept', $invitation->token);

        Mail::raw(
            __('app.team.invitation_body', ['workspace' => tenant('name'), 'url' => $acceptUrl]),
            fn ($message) => $message->to($email)->subject(__('app.team.invitation_subject'))
        );

        return response()->json([
            'data' => [
                'email' => $invitation->email,
                'role' => $invitation->role,
                'expires_at' => $invitation->expires_at->toIso8601String(),
            ],
            'message' => __('app.team.invitation_sent', ['email' => $email]),
        ], 201);
    }
}
