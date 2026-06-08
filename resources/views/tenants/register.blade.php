<x-guest-layout>
    <div class="mb-4 flex justify-end">
        <x-locale-switcher />
    </div>

    <div class="mb-4 text-center">
        <h1 class="text-xl font-semibold text-gray-900">{{ __('app.workspace.create_title') }}</h1>
        <p class="mt-2 text-sm text-gray-600">
            {{ __('app.workspace.create_subtitle', ['domain' => config('app.central_domain')]) }}
        </p>
    </div>

    <form method="POST" action="{{ route('tenants.store') }}">
        @csrf

        <div>
            <x-input-label for="name" :value="__('app.workspace.name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="subdomain" :value="__('app.workspace.url')" />
            <div class="flex mt-1">
                <x-text-input id="subdomain" class="block w-full rounded-e-none" type="text" name="subdomain" :value="old('subdomain')" required dir="ltr" />
                <span class="inline-flex items-center px-3 text-sm text-gray-600 bg-gray-100 border border-s-0 border-gray-300 rounded-e-md" dir="ltr">
                    .{{ $centralDomain }}
                </span>
            </div>
            <x-input-error :messages="$errors->get('subdomain')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-6">
            <x-primary-button>
                {{ __('app.workspace.create_button') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
