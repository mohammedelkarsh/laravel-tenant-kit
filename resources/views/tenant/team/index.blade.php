<x-tenant-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('app.team.title') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="p-4 bg-green-50 text-green-800 rounded-lg text-sm">{{ session('status') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="font-semibold text-gray-900">{{ __('app.team.members') }}</h3>
                    <div class="mt-4 divide-y divide-gray-100">
                        @foreach ($members as $member)
                            <div class="py-3 flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-gray-900">{{ $member->name }}</p>
                                    <p class="text-sm text-gray-500">{{ $member->email }}</p>
                                </div>
                                <span class="text-xs font-semibold uppercase tracking-wide text-indigo-600">
                                    {{ $member->roles->first()?->name ?? 'member' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            @if ($invitations->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="font-semibold text-gray-900">{{ __('app.team.pending_invitations') }}</h3>
                        <ul class="mt-4 space-y-2 text-sm text-gray-600">
                            @foreach ($invitations as $invitation)
                                <li>{{ $invitation->email }} — {{ $invitation->role }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @role('owner|admin')
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="font-semibold text-gray-900">{{ __('app.team.invite_title') }}</h3>
                        <form method="POST" action="{{ route('tenant.team.invite') }}" class="mt-4 space-y-4 max-w-md">
                            @csrf
                            <div>
                                <x-input-label for="email" :value="__('app.team.email')" />
                                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" required />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="role" :value="__('app.team.role')" />
                                <select id="role" name="role" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                    <option value="member">{{ __('app.team.role_member') }}</option>
                                    <option value="admin">{{ __('app.team.role_admin') }}</option>
                                </select>
                            </div>
                            <x-primary-button>{{ __('app.team.send_invitation') }}</x-primary-button>
                        </form>
                    </div>
                </div>
            @endrole
        </div>
    </div>
</x-tenant-layout>
