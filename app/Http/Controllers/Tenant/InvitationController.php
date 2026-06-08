<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class InvitationController extends Controller
{
    public function accept(string $token): RedirectResponse
    {
        $invitation = TeamInvitation::query()
            ->where('token', $token)
            ->whereNull('accepted_at')
            ->firstOrFail();

        if ($invitation->isExpired()) {
            return redirect()->route('tenant.home')->withErrors([
                'email' => __('app.invitations.expired'),
            ]);
        }

        if (! Auth::check()) {
            return redirect()
                ->route('tenant.register', ['email' => $invitation->email])
                ->with('status', __('app.invitations.register_to_accept'));
        }

        if (Auth::user()->email !== $invitation->email) {
            return redirect()->route('tenant.dashboard')->withErrors([
                'email' => __('app.invitations.wrong_email', ['email' => $invitation->email]),
            ]);
        }

        Auth::user()->syncRoles([$invitation->role]);

        $invitation->update(['accepted_at' => now()]);

        return redirect()
            ->route('tenant.team.index')
            ->with('status', __('app.invitations.accepted'));
    }
}
