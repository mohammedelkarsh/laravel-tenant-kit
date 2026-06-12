<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('app.dashboard.title') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid sm:grid-cols-3 gap-6">
                <a href="/admin" class="block bg-white shadow-sm sm:rounded-lg p-6 hover:ring-2 hover:ring-indigo-500 transition">
                    <h3 class="font-semibold text-gray-900">{{ __('app.dashboard.admin_panel') }}</h3>
                </a>
                <a href="{{ route('tenants.create') }}" class="block bg-white shadow-sm sm:rounded-lg p-6 hover:ring-2 hover:ring-indigo-500 transition">
                    <h3 class="font-semibold text-gray-900">{{ __('app.dashboard.create_workspace') }}</h3>
                </a>
                <a href="{{ \App\Support\TenantUrls::demo() }}" target="_blank" class="block bg-white shadow-sm sm:rounded-lg p-6 hover:ring-2 hover:ring-indigo-500 transition">
                    <h3 class="font-semibold text-gray-900">{{ __('app.dashboard.demo_workspace') }}</h3>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
