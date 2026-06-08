<x-tenant-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('app.dashboard.title') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid sm:grid-cols-3 gap-6">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">{{ __('app.tenant.members') }}</p>
                    <p class="mt-1 text-2xl font-bold">{{ $memberCount }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">{{ __('app.tenant.url') }}</p>
                    <p class="mt-1 font-mono text-sm text-indigo-600" dir="ltr">{{ $tenantId }}.{{ config('app.central_domain') }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6 flex flex-col gap-2 text-sm">
                    <a href="{{ route('tenant.team.index') }}" class="text-indigo-600 hover:text-indigo-800 font-medium">{{ __('app.nav.team') }} →</a>
                    <a href="{{ $billingUrl }}" class="text-indigo-600 hover:text-indigo-800 font-medium">{{ __('app.nav.billing') }} →</a>
                </div>
            </div>
        </div>
    </div>
</x-tenant-layout>
