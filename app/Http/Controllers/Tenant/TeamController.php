<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(): View
    {
        return view('tenant.team.index', [
            'members' => User::query()->with('roles')->orderBy('name')->get(),
            'invitations' => TeamInvitation::query()
                ->whereNull('accepted_at')
                ->where('expires_at', '>', now())
                ->orderByDesc('created_at')
                ->get(),
        ]);
    }

    public function invite(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'in:admin,member'],
        ]);

        $email = strtolower($request->string('email')->toString());

        if (User::query()->where('email', $email)->exists()) {
            return back()->withErrors(['email' => __('app.team.member_exists')]);
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

        // Logged mail driver will record the invite in storage/logs for local dev.
        \Illuminate\Support\Facades\Mail::raw(
            __('app.team.invitation_body', ['workspace' => tenant('name'), 'url' => $acceptUrl]),
            fn ($message) => $message->to($email)->subject(__('app.team.invitation_subject'))
        );

        return back()->with('status', __('app.team.invitation_sent', ['email' => $email]));
    }
}
